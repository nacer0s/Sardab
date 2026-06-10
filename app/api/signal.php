<?php

header('Content-Type: application/json');

const STORAGE_DIR = __DIR__ . '/../storage';
const SIGNAL_DIR  = STORAGE_DIR . '/signal';
const HASH_ALGO   = 'sha256';

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function signalPath(string $id, string $type): string {
    return SIGNAL_DIR . '/' . hash(HASH_ALGO, $id) . '.' . $type;
}

function ensureSignalDir(): void {
    if (!is_dir(SIGNAL_DIR) && !@mkdir(SIGNAL_DIR, 0750, true)) {
        jsonResponse(['ok' => false, 'error' => 'Signal init failed'], 500);
    }
}

$action = $_GET['action'] ?? '';
$id     = trim($_GET['id'] ?? '');

if ($id === '' && $action !== '') {
    jsonResponse(['ok' => false, 'error' => 'ID required'], 400);
}

/* ─── WebRTC: Offer ─────────────────────────────── */

if ($action === 'offer') {
    ensureSignalDir();
    $path = signalPath($id, 'offer');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['sdp'])) {
            jsonResponse(['ok' => false, 'error' => 'Invalid SDP'], 400);
        }
        $nonce = bin2hex(random_bytes(6));
        $store = ['sdp' => $input['sdp'], 'nonce' => $nonce];
        if (@file_put_contents($path, json_encode($store)) === false) {
            jsonResponse(['ok' => false, 'error' => 'Write failed'], 500);
        }
        jsonResponse(['ok' => true, 'nonce' => $nonce]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!file_exists($path)) {
            jsonResponse(['ok' => false, 'error' => 'No offer'], 404);
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            jsonResponse(['ok' => false, 'error' => 'Read failed'], 500);
        }
        $parsed = json_decode($raw, true);
        jsonResponse(['ok' => true, 'sdp' => $parsed['sdp'] ?? null, 'nonce' => $parsed['nonce'] ?? null]);
    }

    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

/* ─── WebRTC: Answer ────────────────────────────── */

if ($action === 'answer') {
    ensureSignalDir();
    $path = signalPath($id, 'answer');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['sdp'])) {
            jsonResponse(['ok' => false, 'error' => 'Invalid SDP'], 400);
        }
        $nonce = bin2hex(random_bytes(6));
        $store = ['sdp' => $input['sdp'], 'nonce' => $nonce];
        if (@file_put_contents($path, json_encode($store)) === false) {
            jsonResponse(['ok' => false, 'error' => 'Write failed'], 500);
        }
        jsonResponse(['ok' => true, 'nonce' => $nonce]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!file_exists($path)) {
            jsonResponse(['ok' => false, 'error' => 'No answer'], 404);
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            jsonResponse(['ok' => false, 'error' => 'Read failed'], 500);
        }
        $parsed = json_decode($raw, true);
        jsonResponse(['ok' => true, 'sdp' => $parsed['sdp'] ?? null, 'nonce' => $parsed['nonce'] ?? null]);
    }

    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

/* ─── WebRTC: ICE Candidates ───────────────────── */

if ($action === 'candidate') {
    ensureSignalDir();
    $path = signalPath($id, 'candidates');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['candidate']) || !is_string($input['candidate'])) {
            jsonResponse(['ok' => false, 'error' => 'Invalid candidate'], 400);
        }
        $line = $input['candidate'] . "\n";
        if (@file_put_contents($path, $line, FILE_APPEND) === false) {
            jsonResponse(['ok' => false, 'error' => 'Write failed'], 500);
        }
        jsonResponse(['ok' => true]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!file_exists($path)) {
            jsonResponse(['ok' => true, 'candidates' => []]);
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            jsonResponse(['ok' => true, 'candidates' => []]);
        }
        $lines = explode("\n", trim($raw));
        $candidates = array_values(array_filter($lines, function ($l) { return $l !== ''; }));
        jsonResponse(['ok' => true, 'candidates' => $candidates]);
    }

    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

/* ─── Cleanup ──────────────────────────────────── */

if ($action === 'cleanup') {
    $offerPath = signalPath($id, 'offer');
    $ansPath   = signalPath($id, 'answer');
    $candPath  = signalPath($id, 'candidates');
    if (file_exists($offerPath)) @unlink($offerPath);
    if (file_exists($ansPath))   @unlink($ansPath);
    if (file_exists($candPath))  @unlink($candPath);
    jsonResponse(['ok' => true]);
}

/* ─── Default: vault existence check ───────────── */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$path = STORAGE_DIR . '/vault/' . hash(HASH_ALGO, $id) . '.enc';

if (!file_exists($path)) {
    jsonResponse(['exists' => false, 'expired' => false]);
}

$raw = file_get_contents($path);
if ($raw === false) {
    jsonResponse(['exists' => false, 'expired' => false]);
}

$wrapper = json_decode($raw, true);
$expired = false;

if (is_array($wrapper) && isset($wrapper['v']) && $wrapper['v'] === 2) {
    if ($wrapper['e'] !== null && $wrapper['e'] < time()) {
        $expired = true;
        @unlink($path);
    }
}

jsonResponse(['exists' => !$expired, 'expired' => $expired]);
