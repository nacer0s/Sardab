<?php
$presetRoom = isset($_GET['room']) ? htmlspecialchars(trim($_GET['room']), ENT_QUOTES, 'UTF-8') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>P2P Messaging — Sardab</title>
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
      <h1>P2P <span class="text-gradient">Messaging</span></h1>
      <p>Real-time encrypted chat. Messages delivered directly between browsers.</p>
    </div>

    <div class="card card-premium">

      <div class="view-lobby" id="view-lobby">

      <div class="p2p-mode-selector">
        <button class="p2p-mode-btn active" data-p2p-mode="send"><i class="fa-solid fa-right-to-bracket"></i> Create Room</button>
        <button class="p2p-mode-btn" data-p2p-mode="recv"><i class="fa-solid fa-left-right"></i> Join Room</button>
      </div>

      <div id="p2p-panel-send">
        <div id="conn-widget-send" class="connection-widget status-idle">
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
        <div class="chat-container">
          <div id="chat-messages" class="chat-messages">
            <div class="chat-empty">
              <div class="chat-empty-icon"><i class="fa-solid fa-comment-dots"></i></div>
              <p>Connected. Start typing to chat.</p>
            </div>
          </div>
          <div class="chat-input-row">
            <input type="text" id="chat-input" class="input" placeholder="Type a message…" autocomplete="off" />
            <button type="button" id="btn-send-msg" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i></button>
          </div>
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
  <script src="/app/features/messaging/messaging.js"></script>
</body>
</html>