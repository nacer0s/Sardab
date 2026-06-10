<?php

header('Content-Type: application/json');

const MEETING_DIR = __DIR__ . '/../storage/meeting';

function jsonRes(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function meetingPath(string $room): string {
    return MEETING_DIR . '/' . hash('sha256', $room) . '.json';
}

function ensureDir(): void {
    if (!is_dir(MEETING_DIR) && !@mkdir(MEETING_DIR, 0750, true)) {
        jsonRes(['ok' => false, 'error' => 'Init failed'], 500);
    }
}

function readMeeting(string $room): array {
    $path = meetingPath($room);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function writeMeeting(string $room, array $data): bool {
    return @file_put_contents(meetingPath($room), json_encode($data)) !== false;
}

function isExpired(array $p): bool {
    return (time() - ($p['joined'] ?? 0)) > 7200;
}

function sanitizeName(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Anonymous';
    if (mb_strlen($name) > 24) $name = mb_substr($name, 0, 24);
    return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
}

$action = trim($_GET['action'] ?? '');
$room   = trim($_GET['room'] ?? '');
$peer   = trim($_GET['peer'] ?? '');
$name   = isset($_GET['name']) ? sanitizeName($_GET['name']) : '';

if (!$action) jsonRes(['ok' => false, 'error' => 'Action required'], 400);
if (!$room && $action !== 'cleanup_all') jsonRes(['ok' => false, 'error' => 'Room required'], 400);

ensureDir();

if ($action === 'join') {
    $participants = readMeeting($room);
    $participants = array_values(array_filter($participants, function ($p) { return !isExpired($p); }));

    if (!$peer) {
        $peer = 'peer-' . bin2hex(random_bytes(4));
    }
    if (!$name) {
        $name = 'User-' . substr($peer, -4);
    }

    $exists = false;
    foreach ($participants as &$p) {
        if ($p['id'] === $peer) { $exists = true; $p['joined'] = time(); $p['name'] = $name; break; }
    }
    unset($p);

    if (!$exists) {
        $participants[] = ['id' => $peer, 'name' => $name, 'joined' => time()];
    }

    writeMeeting($room, $participants);

    $result = array_map(function ($p) {
        return ['id' => $p['id'], 'name' => $p['name'] ?? 'Anonymous'];
    }, $participants);

    jsonRes(['ok' => true, 'peerId' => $peer, 'participants' => $result]);
}

if ($action === 'update_name') {
    if (!$peer) jsonRes(['ok' => false, 'error' => 'Peer required'], 400);
    if (!$name) $name = 'Anonymous';
    $participants = readMeeting($room);
    foreach ($participants as &$p) {
        if ($p['id'] === $peer) { $p['name'] = $name; break; }
    }
    unset($p);
    writeMeeting($room, $participants);
    jsonRes(['ok' => true]);
}

if ($action === 'leave') {
    if (!$peer) jsonRes(['ok' => false, 'error' => 'Peer required'], 400);
    $participants = readMeeting($room);
    $participants = array_values(array_filter($participants, function ($p) use ($peer) {
        return $p['id'] !== $peer;
    }));
    if (empty($participants)) {
        $path = meetingPath($room);
        if (file_exists($path)) @unlink($path);
    } else {
        writeMeeting($room, $participants);
    }
    jsonRes(['ok' => true]);
}

if ($action === 'participants') {
    $participants = readMeeting($room);
    $participants = array_values(array_filter($participants, function ($p) { return !isExpired($p); }));
    writeMeeting($room, $participants);

    $result = array_map(function ($p) {
        return ['id' => $p['id'], 'name' => $p['name'] ?? 'Anonymous'];
    }, $participants);

    jsonRes(['ok' => true, 'participants' => $result]);
}

if ($action === 'cleanup_all') {
    $participants = readMeeting($room);
    $participants = array_values(array_filter($participants, function ($p) { return !isExpired($p); }));
    writeMeeting($room, $participants);
    jsonRes(['ok' => true, 'active' => count($participants)]);
}

jsonRes(['ok' => false, 'error' => 'Unknown action'], 400);
