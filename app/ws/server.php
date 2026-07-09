<?php
/**
 * Sardab WebSocket Signaling Server
 * Pure PHP, zero dependencies, zero-knowledge architecture.
 * Room codes are SHA-256 hashed before storage (server never sees real room codes).
 * Usage: php server.php [port] [host]
 * Default: php server.php 8080 0.0.0.0
 */

declare(strict_types=1);

// ---- Configuration ----
$DEFAULT_PORT = 8080;
$DEFAULT_HOST = '0.0.0.0';
$PING_INTERVAL = 30;       // Send ping every 30s
$CLIENT_TIMEOUT = 120;     // Disconnect after 120s no activity
$MAX_PAYLOAD_SIZE = 1048576; // 1MB max message
$STALE_CLEAN_INTERVAL = 15; // Clean stale connections every 15s
$DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$ROOMS_FILE = $DATA_DIR . DIRECTORY_SEPARATOR . 'rooms.json';

@mkdir($DATA_DIR, 0777, true);

// ---- WebSocket Frame Opcodes ----
const OP_CONT = 0x0;
const OP_TEXT = 0x1;
const OP_BIN  = 0x2;
const OP_CLOSE = 0x8;
const OP_PING = 0x9;
const OP_PONG = 0xA;

const WS_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

// ---- Global State ----
$clients = [];   // resource => ClientInfo
$rooms = [];     // roomHash => [sid => resource, ...]
$users = [];     // roomHash => [sid => ['name'=>..., 'ping'=>...], ...]
$signals = [];   // roomHash => [['i'=>..., 't'=>..., 'f'=>..., 'd'=>...], ...]
$roomSeq = [];   // roomHash => int
$lastPing = [];  // resource => float (microtime)
$readBuf = [];   // resource => string (partial read buffer)

class ClientInfo {
  public string $id;
  public string $sid;
  public bool $handshake = false;
  public array $roomSubs = [];  // roomHash => true

  public function __construct() {
    $this->id = bin2hex(random_bytes(8));
    $this->sid = '';
  }
}

// ---- Utility Functions ----

function hashRoom(string $code): string {
  return hash('sha256', $code . ':sardab:room:v1');
}

function logMsg(string $msg): void {
  $ts = date('Y-m-d H:i:s');
  fwrite(STDERR, "[$ts] $msg\n");
}

function encodeFrame(string $payload, int $opcode = OP_TEXT): string {
  $len = strlen($payload);
  $frame = chr((0x80 | $opcode)); // FIN + opcode
  if ($len < 126) {
    $frame .= chr($len);
  } elseif ($len < 65536) {
    $frame .= chr(126) . pack('n', $len);
  } else {
    $frame .= chr(127) . pack('J', $len);
  }
  return $frame . $payload;
}

function decodeFrame(string $data): ?array {
  if (strlen($data) < 2) return null;
  $first = ord($data[0]);
  $second = ord($data[1]);
  $opcode = $first & 0x0F;
  $masked = ($second & 0x80) !== 0;
  $len = $second & 0x7F;
  $offset = 2;
  if ($len === 126) {
    if (strlen($data) < 4) return null;
    $len = unpack('n', substr($data, 2, 2))[1];
    $offset = 4;
  } elseif ($len === 127) {
    if (strlen($data) < 10) return null;
    $len = unpack('J', substr($data, 2, 8))[1];
    $offset = 10;
  }
  if ($masked) {
    if (strlen($data) < $offset + 4) return null;
    $mask = substr($data, $offset, 4);
    $offset += 4;
  }
  if (strlen($data) < $offset + $len) return null;
  $payload = substr($data, $offset, $len);
  if ($masked) {
    for ($i = 0; $i < $len; $i++) {
      $payload[$i] = chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
    }
  }
  return ['opcode' => $opcode, 'payload' => $payload];
}

function performHandshake(string $request): ?string {
  if (!preg_match('/Sec-WebSocket-Key:\s*(.+?)[\r\n]/i', $request, $m)) return null;
  $key = trim($m[1]);
  $accept = base64_encode(sha1($key . WS_GUID, true));
  $response = "HTTP/1.1 101 Switching Protocols\r\n"
    . "Upgrade: websocket\r\n"
    . "Connection: Upgrade\r\n"
    . "Sec-WebSocket-Accept: $accept\r\n"
    . "Sec-WebSocket-Version: 13\r\n"
    . "\r\n";
  return $response;
}

// ---- Persistence ----

function loadRooms(): void {
  global $rooms, $users, $signals, $roomSeq;
  if (!file_exists($GLOBALS['ROOMS_FILE'])) return;
  $data = @json_decode(file_get_contents($GLOBALS['ROOMS_FILE']), true);
  if (!is_array($data)) return;
  foreach ($data as $hash => $room) {
    if (!isset($rooms[$hash])) $rooms[$hash] = [];
    if (!isset($users[$hash])) $users[$hash] = $room['users'] ?? [];
    if (!isset($signals[$hash])) $signals[$hash] = $room['signals'] ?? [];
    if (!isset($roomSeq[$hash])) $roomSeq[$hash] = $room['seq'] ?? 0;
  }
}

function saveRooms(): void {
  // Merge with existing file to preserve signals from HTTP polling clients
  $existing = [];
  if (file_exists($GLOBALS['ROOMS_FILE'])) {
    $f = fopen($GLOBALS['ROOMS_FILE'], 'r');
    if ($f) {
      flock($f, LOCK_SH);
      $c = stream_get_contents($f);
      flock($f, LOCK_UN);
      fclose($f);
      $existing = json_decode($c, true);
      if (!is_array($existing)) $existing = [];
    }
  }
  foreach ($GLOBALS['rooms'] as $hash => $conns) {
    if (!empty($conns) || !empty($GLOBALS['signals'][$hash])) {
      $wsSignals = $GLOBALS['signals'][$hash] ?? [];
      $httpSignals = $existing[$hash]['signals'] ?? [];
      // Merge, preserving order by 'i', deduplicating by 'i'
      $merged = $wsSignals;
      $seen = [];
      foreach ($merged as $s) $seen[$s['i']] = true;
      foreach ($httpSignals as $s) {
        if (!isset($seen[$s['i']])) {
          $merged[] = $s;
          $seen[$s['i']] = true;
        }
      }
      usort($merged, function($a, $b) { return $a['i'] - $b['i']; });
      // Take the max seq
      $wsSeq = $GLOBALS['roomSeq'][$hash] ?? 0;
      $httpSeq = $existing[$hash]['seq'] ?? 0;
      $data[$hash] = [
        'users' => $GLOBALS['users'][$hash] ?? [],
        'signals' => $merged,
        'seq' => max($wsSeq, $httpSeq),
      ];
    }
  }
  $f = fopen($GLOBALS['ROOMS_FILE'], 'c+');
  if ($f) {
    flock($f, LOCK_EX);
    ftruncate($f, 0);
    fwrite($f, json_encode($data, JSON_PRETTY_PRINT));
    flock($f, LOCK_UN);
    fclose($f);
  }
}

// ---- Signal Handling ----

function sendToClient($conn, string $msg): void {
  try {
    $frame = encodeFrame($msg);
    @fwrite($conn, $frame);
  } catch (\Throwable $e) {
    logMsg("Send error: " . $e->getMessage());
  }
}

function sendJson($conn, array $data): void {
  sendToClient($conn, json_encode($data));
}

function sendToRoom(string $roomHash, string $exceptSid, array $msg): void {
  global $clients, $rooms;
  $conns = $rooms[$roomHash] ?? [];
  foreach ($conns as $sid => $conn) {
    if ($sid !== $exceptSid && isset($clients[(int)$conn])) {
      sendJson($conn, $msg);
    }
  }
}

function handleJoin($conn, string $roomHash, string $sid, array $data): void {
  global $clients, $rooms, $users, $signals, $roomSeq;
  $name = $data['name'] ?? 'User';
  $cid = (int)$conn;

  if (!isset($rooms[$roomHash])) $rooms[$roomHash] = [];
  if (!isset($users[$roomHash])) $users[$roomHash] = [];
  if (!isset($signals[$roomHash])) $signals[$roomHash] = [];
  if (!isset($roomSeq[$roomHash])) $roomSeq[$roomHash] = 0;

  // Disconnect existing session if same SID
  if (isset($rooms[$roomHash][$sid])) {
    $oldConn = $rooms[$roomHash][$sid];
    if ($oldConn !== $conn) {
      @fclose($oldConn);
      $oldCid = (int)$oldConn;
      unset($clients[$oldCid]);
      unset($GLOBALS['readBuf'][$oldCid]);
      unset($GLOBALS['lastPing'][$oldCid]);
    }
  }

  $rooms[$roomHash][$sid] = $conn;
  $users[$roomHash][$sid] = ['name' => $name, 'ping' => time()];
  $clients[$cid]->sid = $sid;
  $clients[$cid]->roomSubs[$roomHash] = true;

  $seq = ++$roomSeq[$roomHash];
  $signals[$roomHash][] = ['i' => $seq, 't' => 'join', 'f' => $sid, 'd' => ['name' => $name]];

  // Notify others in room
  sendToRoom($roomHash, $sid, [
    'type' => 'signal',
    'data' => ['i' => $seq, 't' => 'join', 'f' => $sid, 'd' => ['name' => $name]]
  ]);

  // Send user list to the joiner
  $userList = [];
  foreach ($users[$roomHash] as $uSid => $uData) {
    $userList[] = ['sid' => $uSid, 'name' => $uData['name']];
  }
  $isCreator = (count($users[$roomHash]) === 1);
  sendJson($conn, ['type' => 'joined', 'users' => $userList, 'creator' => $isCreator]);

  saveRooms();
}

function handleLeave($conn, string $roomHash, string $sid): void {
  global $rooms, $users, $signals, $roomSeq, $clients;
  if (!$roomHash || !$sid) return;

  $seq = ++$roomSeq[$roomHash];
  $signals[$roomHash][] = ['i' => $seq, 't' => 'leave', 'f' => $sid];

  sendToRoom($roomHash, $sid, [
    'type' => 'signal',
    'data' => ['i' => $seq, 't' => 'leave', 'f' => $sid]
  ]);

  unset($rooms[$roomHash][$sid]);
  unset($users[$roomHash][$sid]);
  unset($clients[(int)$conn]->roomSubs[$roomHash]);

  // Auto-destroy room if empty
  if (empty($rooms[$roomHash])) {
    unset($rooms[$roomHash]);
    unset($users[$roomHash]);
    unset($signals[$roomHash]);
    unset($roomSeq[$roomHash]);
  }

  saveRooms();
}

function handleSignal($conn, string $roomHash, string $sid, $data): void {
  global $signals, $roomSeq;
  if (!$roomHash || !$sid || !is_array($data)) return;
  if (!isset($roomSeq[$roomHash])) $roomSeq[$roomHash] = 0;

  $seq = ++$roomSeq[$roomHash];
  $signals[$roomHash][] = ['i' => $seq, 't' => 'signal', 'f' => $sid, 'd' => $data];

  // Relay to target or broadcast
  $target = $data['target'] ?? null;
  if ($target && $target !== 'broadcast') {
    $conns = $rooms[$roomHash] ?? [];
    $targetConn = $conns[$target] ?? null;
    if ($targetConn) {
      sendJson($targetConn, [
        'type' => 'signal',
        'data' => ['i' => $seq, 't' => 'signal', 'f' => $sid, 'd' => $data]
      ]);
    }
  } else {
    sendToRoom($roomHash, $sid, [
      'type' => 'signal',
      'data' => ['i' => $seq, 't' => 'signal', 'f' => $sid, 'd' => $data]
    ]);
  }

  // Trim signal history to last 500 per room
  if (count($signals[$roomHash]) > 500) {
    $signals[$roomHash] = array_slice($signals[$roomHash], -500);
  }

  saveRooms();
}

// ---- Client Message Router ----

function handleMessage($conn, string $msg): void {
  global $clients;
  $cid = (int)$conn;
  $data = json_decode($msg, true);
  if (!$data || !isset($data['type'])) return;

  $roomHash = $data['room'] ?? '';
  $sid = $data['sid'] ?? $clients[$cid]->sid ?? '';

  switch ($data['type']) {
    case 'join':
      if ($roomHash) handleJoin($conn, $roomHash, $data['sid'] ?? '', $data['data'] ?? []);
      break;
    case 'leave':
      if ($roomHash && $sid) handleLeave($conn, $roomHash, $sid);
      break;
    case 'signal':
      handleSignal($conn, $roomHash, $sid, $data['data'] ?? []);
      break;
    case 'ping':
      sendJson($conn, ['type' => 'pong']);
      break;
    case 'pong':
      // Update ping time
      break;
  }
}

function disconnectClient($conn): void {
  global $clients, $rooms, $users, $readBuf, $lastPing;
  $cid = (int)$conn;
  if (!isset($clients[$cid])) return;

  $client = $clients[$cid];
  // Leave all subscribed rooms
  foreach ($client->roomSubs as $roomHash => $_) {
    handleLeave($conn, $roomHash, $client->sid);
  }

  @fclose($conn);
  unset($clients[$cid]);
  unset($readBuf[$cid]);
  unset($lastPing[$cid]);
}

// ---- Main Server Loop ----

function runServer(string $host, int $port): void {
  global $clients, $readBuf, $lastPing;

  $address = "tcp://$host:$port";
  $server = stream_socket_server($address, $errno, $errstr);
  if (!$server) {
    logMsg("Failed to bind to $address: $errstr ($errno)");
    exit(1);
  }
  stream_set_blocking($server, false);
  logMsg("Sardab WebSocket server listening on ws://$host:$port");

  loadRooms();
  logMsg("Loaded " . count($GLOBALS['rooms']) . " rooms from disk");

  $lastClean = microtime(true);
  $lastPingAll = microtime(true);
  $running = true;

  global $PING_INTERVAL, $CLIENT_TIMEOUT, $STALE_CLEAN_INTERVAL;

  // Handle SIGINT/SIGTERM gracefully (not available on Windows, but nice for Linux)
  if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function () use (&$running) { $running = false; logMsg("Shutting down..."); });
    pcntl_signal(SIGTERM, function () use (&$running) { $running = false; logMsg("Shutting down..."); });
  }

  while ($running) {
    if (function_exists('pcntl_signal_dispatch')) pcntl_signal_dispatch();

    $read = [$server];
    foreach ($clients as $cid => $client) {
      if (is_resource($client)) {
        $read[] = $client;
      } else {
        unset($clients[$cid]);
      }
    }

    $write = null;
    $except = null;

    if (empty($read)) {
      usleep(100000); // 100ms
      continue;
    }

    $ready = @stream_select($read, $write, $except, 1);

    if ($ready === false) {
      usleep(100000);
      continue;
    }

    // Accept new connections
    if (in_array($server, $read, true)) {
      $conn = @stream_socket_accept($server, 0);
      if ($conn) {
        stream_set_blocking($conn, false);
        $cid = (int)$conn;
        $clients[$cid] = new ClientInfo();
        $readBuf[$cid] = '';
        $lastPing[$cid] = microtime(true);
        logMsg("New connection from " . stream_socket_get_name($conn, true));
      }
      $read = array_filter($read, fn($r) => $r !== $server);
    }

    // Read from clients
    foreach ($read as $conn) {
      $cid = (int)$conn;
      if (!isset($clients[$cid])) continue;

      $data = @fread($conn, 65536);
      if ($data === false || $data === '') {
        disconnectClient($conn);
        continue;
      }

      $lastPing[$cid] = microtime(true);

      if (!$clients[$cid]->handshake) {
        $readBuf[$cid] .= $data;
        // Check for complete HTTP request
        if (strpos($readBuf[$cid], "\r\n\r\n") !== false) {
          $response = performHandshake($readBuf[$cid]);
          if ($response) {
            @fwrite($conn, $response);
            $clients[$cid]->handshake = true;
            logMsg("WebSocket handshake completed for " . stream_socket_get_name($conn, true));
          } else {
            logMsg("Invalid WebSocket handshake from " . stream_socket_get_name($conn, true));
            disconnectClient($conn);
          }
          $readBuf[$cid] = '';
        }
        continue;
      }

      $readBuf[$cid] .= $data;

      // Process complete frames from buffer
      while (true) {
        $frame = decodeFrame($readBuf[$cid]);
        if ($frame === null) break; // Incomplete frame, wait for more data

        $readBuf[$cid] = substr($readBuf[$cid], frameLength($readBuf[$cid]));

        switch ($frame['opcode']) {
          case OP_TEXT:
            handleMessage($conn, $frame['payload']);
            break;
          case OP_BIN:
            // Binary frames not used; ignore
            break;
          case OP_CLOSE:
            disconnectClient($conn);
            break 2;
          case OP_PING:
            sendToClient($conn, encodeFrame($frame['payload'], OP_PONG));
            break;
          case OP_PONG:
            // Received pong response
            break;
          case OP_CONT:
            // Fragmentation not supported
            break;
        }
      }
    }

    // Periodic tasks
    $now = microtime(true);

    // Ping all clients every PING_INTERVAL
    if ($now - $lastPingAll >= $PING_INTERVAL) {
      foreach ($clients as $cid => $client) {
        if (!is_resource($client)) continue;
        $conn = $client;
        try {
          sendToClient($conn, encodeFrame('', OP_PING));
        } catch (\Throwable $e) {
          disconnectClient($conn);
        }
      }
      $lastPingAll = $now;
    }

    // Clean stale connections
    if ($now - $lastClean >= $STALE_CLEAN_INTERVAL) {
      foreach ($clients as $cid => $client) {
        if (!is_resource($client)) continue;
        $conn = $client;
        $elapsed = $now - ($lastPing[$cid] ?? $now);
        if ($elapsed > $CLIENT_TIMEOUT) {
          logMsg("Timeout: disconnecting stale client $cid");
          disconnectClient($conn);
        }
      }
      $lastClean = $now;
    }

    // Log stats periodically
    static $lastStats = 0;
    if ($now - $lastStats >= 60) {
      $roomCount = count(array_filter($GLOBALS['rooms'], fn($r) => !empty($r)));
      logMsg(sprintf("Stats: %d clients, %d active rooms", count($clients), $roomCount));
      $lastStats = $now;
    }
  }

  // Shutdown
  foreach ($clients as $cid => $client) {
    if (is_resource($client)) @fclose($client);
  }
  fclose($server);
  saveRooms();
  logMsg("Server stopped");
}

function frameLength(string $data): int {
  if (strlen($data) < 2) return 0;
  $len = ord($data[1]) & 0x7F;
  $offset = 2;
  if ($len === 126) $offset = 4;
  elseif ($len === 127) $offset = 10;
  $masked = (ord($data[1]) & 0x80) !== 0;
  if ($masked) $offset += 4;
  if ($len === 126) $len = unpack('n', substr($data, 2, 2))[1];
  elseif ($len === 127) $len = unpack('J', substr($data, 2, 8))[1];
  return $offset + $len;
}

// ---- Entry Point ----
$host = $argv[2] ?? $DEFAULT_HOST;
$port = (int)($argv[1] ?? $DEFAULT_PORT);

logMsg("Starting Sardab WebSocket Server...");
runServer($host, $port);
