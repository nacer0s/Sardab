<?php
$presetRoom = isset($_GET['room']) ? htmlspecialchars(trim($_GET['room']), ENT_QUOTES, 'UTF-8') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Video Call — Sardab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body<?php if ($presetRoom): ?> data-room-id="<?= $presetRoom ?>"<?php endif; ?>>

  <nav class="nav-bar" id="nav-bar">
    <a href="/app" class="nav-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" class="nav-logo-img" /> Sardab</a>
    <div class="nav-links">
      <a href="/app" class="nav-link">Features</a>
    </div>
    <a href="/app" class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-left btn-icon"></i> Back</a>
  </nav>

  <main class="page">

    <div class="page-header">
      <h1>P2P <span class="text-gradient">Video Call</span></h1>
      <p>End-to-end encrypted video calls. Audio + Video, direct P2P. <span class="badge badge-amber">Beta</span></p>
    </div>

    <div class="card card-premium">

      <div class="view-lobby" id="view-lobby">

      <div class="p2p-mode-selector">
        <button class="p2p-mode-btn active" data-p2p-mode="send"><i class="fa-solid fa-video"></i> Call</button>
        <button class="p2p-mode-btn" data-p2p-mode="recv"><i class="fa-solid fa-video-slash"></i> Receive</button>
      </div>

      <div id="p2p-panel-send">
        <div id="conn-widget" class="connection-widget status-idle">
          <span class="connection-indicator"></span>
          <span class="connection-icon"><i class="fa-solid fa-plug"></i></span>
          <span class="connection-label">Disconnected</span>
        </div>
        <button id="btn-create" class="btn btn-ghost btn-full"><i class="fa-solid fa-plus btn-icon"></i> Create Room</button>
        <div id="room-link-box" class="room-link-box"></div>
      </div>

      <div id="p2p-panel-recv" class="p2p-panel-hidden">
        <div id="conn-widget-recv" class="connection-widget status-idle">
          <span class="connection-indicator"></span>
          <span class="connection-icon"><i class="fa-solid fa-plug"></i></span>
          <span class="connection-label">Disconnected</span>
        </div>
        <form onsubmit="return false;">
          <label class="field">
            <span class="field-label">Room ID</span>
            <input type="text" id="room-id-input" class="input" placeholder="srdb-xxxxxxxx" autocomplete="off" spellcheck="false" />
          </label>
          <button type="button" id="btn-join" class="btn btn-primary btn-full"><i class="fa-solid fa-right-to-bracket btn-icon"></i> Join Room</button>
        </form>
      </div>

      </div>

      <div class="view-feature" id="view-feature">

      <div class="video-container" id="video-container">
        <video class="video-remote" id="remote-video" autoplay playsinline></video>
        <div class="video-local">
          <video id="local-video" autoplay muted playsinline></video>
          <span class="video-label">You</span>
        </div>
        <span class="video-label" style="bottom:var(--space-2);left:var(--space-2);">Peer</span>
        <div class="video-controls-overlay">
          <button class="call-btn call-btn-mute" id="btn-mute"><i class="fa-solid fa-microphone"></i></button>
          <span class="call-timer" id="call-timer">00:00</span>
          <button class="call-btn" id="btn-camera"><i class="fa-solid fa-video"></i></button>
          <button class="call-btn call-btn-danger" id="btn-end"><i class="fa-solid fa-phone-slash"></i></button>
        </div>
      </div>

      <div class="call-screen" id="call-screen">
        <div class="call-avatar">
          <i class="fa-solid fa-video"></i>
        </div>
        <div class="call-info">
          <span class="call-status-text" id="call-status">Call Ended</span>
          <span class="call-ended-text" id="call-ended-text">Connection closed</span>
        </div>
        <div class="call-actions">
          <button class="call-btn call-btn-danger" id="btn-end-again"><i class="fa-solid fa-phone-slash"></i></button>
        </div>
      </div>

      <div class="card-footer">
        <p>Video is encrypted end-to-end. Direct P2P — no intermediates.</p>
      </div>

      <button id="btn-leave" class="btn btn-ghost btn-sm" style="width:100%;margin-top:var(--space-4);"><i class="fa-solid fa-right-from-bracket btn-icon"></i> Leave Room</button>

      </div>

    </div>

  </main>

  <footer class="page-footer">
    <span>Sardab &mdash; Zero-Knowledge Communication Platform</span>
  </footer>

  <script src="/app/assets/js/crypto.js"></script>
  <script src="/app/assets/js/p2p.js"></script>
  <script src="/app/assets/js/app.js"></script>
  <script src="/app/features/video-call/video-call.js"></script>
</body>
</html>