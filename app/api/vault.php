<?php

header('Content-Type: application/json');

const STORAGE_DIR = __DIR__ . '/../storage/vault';
const RATE_DIR    = __DIR__ . '/../storage/rate';
const HASH_ALGO   = 'sha256';
const MAX_SIZE    = 1048576; // 1 MB

/* ─── Rate limiting ──────────────────────────────── */

function checkRate(string $ip, int $max, int $window): bool {
    if (!is_dir(RATE_DIR) && !@mkdir(RATE_DIR, 0750, true)) {
        return false; // fail-closed
    }
    $file = RATE_DIR . '/' . hash('sha256', $ip) . '.attempts';
    $now  = time();
    $data = [];

    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true) ?? [];
        }
    }

    $data = array_filter($data, function ($ts) use ($now, $window) {
        return $ts > ($now - $window);
    });

    if (count($data) >= $max) {
        return false;
    }

    $data[] = $now;
    @file_put_contents($file, json_encode($data));
    return true;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

/* ─── Store ─────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkRate($ip, 10, 60)) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'Rate limit exceeded']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['data']) || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing data or id']);
        exit;
    }

    $data = trim($input['data']);
    $id   = trim($input['id']);
    $ttl  = isset($input['ttl']) ? (int)$input['ttl'] : null;

    if ($data === '' || $id === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Data and id required']);
        exit;
    }

    if (strlen($data) > MAX_SIZE) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'Data too large (max 1 MB)']);
        exit;
    }

    if (!is_dir(STORAGE_DIR) && !@mkdir(STORAGE_DIR, 0750, true)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Storage init failed']);
        exit;
    }

    $hash   = hash(HASH_ALGO, $id);
    $path   = STORAGE_DIR . '/' . $hash . '.enc';
    $expiry = ($ttl && $ttl > 0) ? time() + $ttl : null;

    $wrapper = json_encode([
        'v' => 2,
        'd' => $data,
        'e' => $expiry
    ]);

    if (@file_put_contents($path, $wrapper) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Write failed']);
        exit;
    }

    echo json_encode(['ok' => true, 'id' => $hash]);
    exit;
}

/* ─── Retrieve ──────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = trim($_GET['id'] ?? '');
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID required']);
        exit;
    }

    $hash = hash(HASH_ALGO, $id);
    $path = STORAGE_DIR . '/' . $hash . '.enc';

    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Not found']);
        exit;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Read failed']);
        exit;
    }

    $wrapper = json_decode($raw, true);
    if (!is_array($wrapper) || !isset($wrapper['d'])) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Corrupt data']);
        exit;
    }

    // Check expiry
    if (isset($wrapper['e']) && $wrapper['e'] !== null && $wrapper['e'] < time()) {
        @unlink($path);
        http_response_code(410);
        echo json_encode(['ok' => false, 'error' => 'Expired']);
        exit;
    }

    // Burn after reading (only when burn=1 is passed)
    $burn = ($_GET['burn'] ?? '') === '1';
    if ($burn) {
        @unlink($path);
    }

    echo json_encode(['ok' => true, 'data' => $wrapper['d'], 'burned' => $burn]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
