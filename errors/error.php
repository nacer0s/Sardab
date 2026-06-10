<?php
$code = (int)($_SERVER['REDIRECT_STATUS'] ?? $_GET['code'] ?? 500);
if ($code < 100 || $code > 599) $code = 500;
http_response_code($code);

$messages = [
    400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
    405 => 'Method Not Allowed', 408 => 'Request Timeout', 429 => 'Too Many Requests',
    500 => 'Internal Server Error', 502 => 'Bad Gateway', 503 => 'Service Unavailable',
    504 => 'Gateway Timeout', 418 => "I'm a Teapot"
];
$msg = $messages[$code] ?? 'Error';
$cat = $code >= 500 ? 'Server Error' : ($code >= 400 ? 'Client Error' : ($code >= 300 ? 'Redirect' : ($code >= 200 ? 'Success' : 'Info')));
$c   = (string)$code;
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $code ?> — Sardab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="error-page">
    <div class="error-code">
      <?php for ($i = 0; $i < strlen($c); $i++): ?>
        <span class="<?= $i === 1 ? '' : 'error-char-dim' ?>"><?= htmlspecialchars($c[$i], ENT_QUOTES, 'UTF-8') ?></span>
      <?php endfor; ?>
    </div>
    <p class="error-message"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="/" class="btn btn-ghost"><i class="fa-solid fa-arrow-left btn-icon"></i> Back to Home</a>
  </div>
</body>
</html>