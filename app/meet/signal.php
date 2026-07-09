<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);
$file = $dataDir . DIRECTORY_SEPARATOR . 'rooms.json';

function readData() {
    global $file;
    if (!file_exists($file)) return [];
    $f = fopen($file, 'r');
    flock($f, LOCK_SH);
    $c = stream_get_contents($f);
    flock($f, LOCK_UN);
    fclose($f);
    $d = json_decode($c, true);
    return is_array($d) ? $d : [];
}

function writeData($data) {
    global $file;
    $f = fopen($file, 'c+');
    flock($f, LOCK_EX);
    ftruncate($f, 0);
    fwrite($f, json_encode($data));
    flock($f, LOCK_UN);
    fclose($f);
}

function cleanStale($rooms) {
    $now = time();
    foreach ($rooms as $code => $room) {
        if (!empty($room['users'])) {
            foreach ($room['users'] as $sid => $u) {
                $isCreator = ($sid === ($room['creator'] ?? ''));
                $threshold = $isCreator ? 300 : 120;
                if ($now - ($u['ping'] ?? 0) > $threshold) unset($rooms[$code]['users'][$sid]);
            }
        }
        if (empty($rooms[$code]['users'])) {
            unset($rooms[$code]);
        }
    }
    return $rooms;
}

function fmtUsers($users, $now) {
    $out = [];
    foreach ($users as $sid => $u) {
        $out[] = ['sid'=>$sid,'name'=>$u['name'],'online'=>($now - ($u['ping']??0) < 15)];
    }
    return $out;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $room = $_GET['room'] ?? '';
    $sid = $_GET['sid'] ?? '';
    $last = (int)($_GET['last'] ?? 0);
    $noblock = ($_GET['noblock'] ?? '') === '1';
    if (!$room || !$sid) { echo json_encode(['ok'=>false,'error'=>'missing']); exit; }
    $rooms = readData();
    $signals = $rooms[$room]['signals'] ?? [];
    $users = fmtUsers($rooms[$room]['users'] ?? [], time());
    $news = [];
    foreach ($signals as $s) { if ($s['i'] > $last) $news[] = $s; }
    if (!empty($news)) {
        $maxId = $last;
        foreach ($news as $s) { if ($s['i'] > $maxId) $maxId = $s['i']; }
        echo json_encode(['ok'=>true,'signals'=>$news,'users'=>$users,'last'=>$maxId]);
        exit;
    }
    if ($noblock) {
        echo json_encode(['ok'=>true,'signals'=>[],'users'=>$users,'last'=>$last]);
        exit;
    }
    ignore_user_abort(true);
    $maxWait = 25;
    $start = time();
    while (time() - $start < $maxWait) {
        $rooms = readData();
        $signals = $rooms[$room]['signals'] ?? [];
        $users = fmtUsers($rooms[$room]['users'] ?? [], time());
        $news = [];
        foreach ($signals as $s) { if ($s['i'] > $last) $news[] = $s; }
        if (!empty($news)) {
            $maxId = $last;
            foreach ($news as $s) { if ($s['i'] > $maxId) $maxId = $s['i']; }
            echo json_encode(['ok'=>true,'signals'=>$news,'users'=>$users,'last'=>$maxId]);
            exit;
        }
        if (isset($rooms[$room]['users'][$sid])) {
            $f = fopen($file, 'c+');
            flock($f, LOCK_EX);
            $raw = stream_get_contents($f);
            $all = json_decode($raw, true);
            if (isset($all[$room]['users'][$sid])) {
                $all[$room]['users'][$sid]['ping'] = time();
                ftruncate($f, 0);
                rewind($f);
                fwrite($f, json_encode($all));
            }
            flock($f, LOCK_UN);
            fclose($f);
        }
        sleep(1);
    }
    $users = fmtUsers($rooms[$room]['users'] ?? [], time());
    echo json_encode(['ok'=>true,'signals'=>[],'users'=>$users,'last'=>$last]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $room = $input['room'] ?? '';
    $sid = $input['sid'] ?? '';
    $data = $input['data'] ?? [];
    if (!$room || !$sid) { echo json_encode(['ok'=>false,'error'=>'missing']); exit; }

    $f = fopen($file, 'c+');
    flock($f, LOCK_EX);
    $c = stream_get_contents($f);
    $rooms = json_decode($c, true);
    if (!is_array($rooms)) $rooms = [];
    if (!isset($rooms[$room])) $rooms[$room] = ['users'=>[],'signals'=>[],'seq'=>0];

    if ($type === 'join') {
        $name = $data['name'] ?? 'User';
        $isNewRoom = empty($rooms[$room]['users']);
        $rooms[$room]['users'][$sid] = ['name'=>$name, 'ping'=>time()];
        if ($isNewRoom) $rooms[$room]['creator'] = $sid;
        $seq = ++$rooms[$room]['seq'];
        $rooms[$room]['signals'][] = ['i'=>$seq, 't'=>'join', 'f'=>$sid, 'd'=>['name'=>$name]];
        $users = fmtUsers($rooms[$room]['users'] ?? [], time());
        $creator = (count($rooms[$room]['users']) === 1);
        ftruncate($f, 0);
        rewind($f);
        fwrite($f, json_encode($rooms));
        flock($f, LOCK_UN);
        fclose($f);
        echo json_encode(['ok'=>true, 'users'=>$users, 'creator'=>$creator]);
        exit;
    }
    if ($type === 'leave') {
        unset($rooms[$room]['users'][$sid]);
        $seq = ++$rooms[$room]['seq'];
        $rooms[$room]['signals'][] = ['i'=>$seq, 't'=>'leave', 'f'=>$sid];
        if (empty($rooms[$room]['users'])) {
            unset($rooms[$room]);
        }
        ftruncate($f, 0);
        rewind($f);
        fwrite($f, json_encode($rooms));
        flock($f, LOCK_UN);
        fclose($f);
        echo json_encode(['ok'=>true]);
        exit;
    }
    if ($type === 'signal') {
        $seq = ++$rooms[$room]['seq'];
        $rooms[$room]['signals'][] = ['i'=>$seq, 't'=>'signal', 'f'=>$sid, 'd'=>$data];
        ftruncate($f, 0);
        rewind($f);
        fwrite($f, json_encode($rooms));
        flock($f, LOCK_UN);
        fclose($f);
        echo json_encode(['ok'=>true, 'id'=>$seq]);
        exit;
    }
    flock($f, LOCK_UN);
    fclose($f);
    echo json_encode(['ok'=>false,'error'=>'unknown type']);
    exit;
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $room = $input['room'] ?? '';
    $sid = $input['sid'] ?? '';
    if ($room && $sid) {
        $f = fopen($file, 'c+');
        flock($f, LOCK_EX);
        $c = stream_get_contents($f);
        $rooms = json_decode($c, true);
        if (!is_array($rooms)) $rooms = [];
        unset($rooms[$room]['users'][$sid]);
        $seq = ++$rooms[$room]['seq'];
        $rooms[$room]['signals'][] = ['i'=>$seq, 't'=>'leave', 'f'=>$sid];
        if (empty($rooms[$room]['users'])) {
            unset($rooms[$room]);
        }
        ftruncate($f, 0);
        rewind($f);
        fwrite($f, json_encode($rooms));
        flock($f, LOCK_UN);
        fclose($f);
    }
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'invalid method']);
