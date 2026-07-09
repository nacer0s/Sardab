<?php
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'); $dir = str_replace('\\', '/', dirname(dirname(__DIR__))); $basePath = $docRoot !== '' && strpos($dir, $docRoot) === 0 ? rtrim(substr($dir, strlen($docRoot)), '/') : '';
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
if (preg_match('#\.(css|js|svg|png|jpg|jpeg|webp|ico|json|woff2?|ttf|otf|wasm|mp4|webm|ogg|mp3)(\?.*)?$#', $uri)) {
  $relUri = $uri;
  if ($basePath !== '' && strpos($relUri, $basePath) === 0) {
    $relUri = substr($relUri, strlen($basePath));
  }
  $file = dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $relUri);
  if (file_exists($file) && is_file($file)) {
    $mime = ['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml',
             'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp',
             'ico'=>'image/x-icon','json'=>'application/json','woff2'=>'font/woff2',
             'woff'=>'font/woff','ttf'=>'font/ttf','otf'=>'font/otf','txt'=>'text/plain',
             'mp4'=>'video/mp4','webm'=>'video/webm','ogg'=>'audio/ogg','mp3'=>'audio/mpeg',
             'wasm'=>'application/wasm'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mime[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
  }
}

$uri = rtrim($uri, '/');
$base = $basePath . '/app/chat';

if (preg_match('#^' . preg_quote($base, '#') . '/join/?$#', $uri)) {
  $view = 'join';
} elseif (preg_match('#^' . preg_quote($base, '#') . '/create/?$#', $uri)) {
  $view = 'create';
} elseif (preg_match('#^' . preg_quote($base, '#') . '/lobby(/([A-Z0-9]{20}))?/?$#', $uri, $m)) {
  $view = 'lobby';
  $room = $m[2] ?? ($_GET['room'] ?? '');
} elseif (preg_match('#^' . preg_quote($base, '#') . '/([A-Za-z0-9]{20})/?$#', $uri, $m)) {
  $view = 'room';
  $room = strtoupper($m[1]);
} else {
  $view = 'hub';
}
$room = $room ?? '';
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/pages/' . $view . '.php';
