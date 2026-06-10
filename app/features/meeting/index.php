<?php
$presetRoom = isset($_GET['room']) ? htmlspecialchars(trim($_GET['room']), ENT_QUOTES, 'UTF-8') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Meeting — Sardab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="stylesheet" href="/app/assets/css/meeting.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body<?php if ($presetRoom): ?> data-room-id="<?= $presetRoom ?>"<?php endif; ?> class="meeting-body">

  <div class="meeting-container">

    <!-- Top Bar -->
    <header class="meeting-header">
      <div class="meeting-header-left">
        <a href="/app" class="meeting-logo">Sardab</a>
        <span class="meeting-badge badge badge-amber">Beta</span>
        <span class="meeting-name">P2P Meeting</span>
        <span class="meeting-name-sep">|</span>
        <input type="text" id="name-input" class="meeting-name-input" placeholder="Your name" maxlength="24" />
        <button id="name-save" class="btn btn-ghost btn-sm" style="font-size:0.55rem;padding:2px 6px;">Set</button>
        <span id="name-display" class="meeting-name-display"></span>
        <button id="name-edit" class="btn btn-ghost btn-sm" style="font-size:0.55rem;padding:2px 6px;display:none;"><i class="fa-solid fa-pen"></i></button>
      </div>
      <div class="meeting-header-center">
        <span class="meeting-indicator"></span>
        <span class="meeting-label" id="meeting-status">Disconnected</span>
      </div>
      <div class="meeting-header-right">
        <span class="meeting-timer" id="meeting-timer">00:00</span>
        <span class="meeting-pcount" id="participant-count"><i class="fa-solid fa-user"></i> <span id="pcount-num">0</span></span>
        <button id="btn-leave" class="btn btn-danger btn-sm"><i class="fa-solid fa-phone-slash btn-icon"></i> Leave</button>
      </div>
    </header>

    <!-- Main Content -->
    <div class="meeting-main">

      <!-- Video Grid Area -->
      <div class="meeting-video-area" id="video-area">
        <div class="video-grid" id="video-grid">
          <div class="video-tile video-tile-local" id="tile-local">
            <video id="local-video" autoplay muted playsinline></video>
            <span class="video-tile-label" id="local-label">You</span>
          </div>
          <!-- Remote tiles added by JS -->
        </div>
        <div class="meeting-placeholder" id="meeting-placeholder">
          <div class="meeting-placeholder-icon"><i class="fa-solid fa-people-group"></i></div>
          <p>Waiting for people to join…</p>
          <span class="meeting-placeholder-sub">Share the meeting link to invite others</span>
          <div class="meeting-room-link-box" id="meeting-room-link"></div>
        </div>
      </div>

      <!-- Chat Sidebar -->
      <aside class="meeting-chat" id="meeting-chat">
        <div class="meeting-chat-header">
          <i class="fa-solid fa-hashtag"></i> General
          <button class="meeting-chat-toggle" id="chat-toggle"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
        <div class="meeting-chat-messages" id="chat-messages">
          <div class="meeting-chat-empty">
            <p>No messages yet. Say hello!</p>
          </div>
        </div>
        <div class="meeting-chat-input-row">
          <input type="text" id="chat-input" class="input" placeholder="Type a message…" autocomplete="off" />
          <button id="btn-send-chat" class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i></button>
          <button id="btn-file-share" class="btn btn-ghost btn-sm" title="Send file"><i class="fa-solid fa-paperclip"></i></button>
          <input type="file" id="file-input" style="display:none;" />
        </div>
      </aside>

    </div>

    <!-- Controls Bar -->
    <footer class="meeting-controls">
      <button class="meeting-ctrl-btn" id="ctrl-mute" title="Toggle microphone">
        <i class="fa-solid fa-microphone"></i>
        <span class="meeting-ctrl-label">Mic</span>
      </button>
      <button class="meeting-ctrl-btn" id="ctrl-camera" title="Toggle camera">
        <i class="fa-solid fa-video"></i>
        <span class="meeting-ctrl-label">Camera</span>
      </button>
      <button class="meeting-ctrl-btn" id="ctrl-screen" title="Share screen">
        <i class="fa-solid fa-desktop"></i>
        <span class="meeting-ctrl-label">Screen</span>
      </button>
      <button class="meeting-ctrl-btn" id="ctrl-file" title="Send file">
        <i class="fa-solid fa-folder-open"></i>
        <span class="meeting-ctrl-label">File</span>
      </button>
      <button class="meeting-ctrl-btn meeting-ctrl-end" id="ctrl-end">
        <i class="fa-solid fa-phone-slash"></i>
        <span class="meeting-ctrl-label">End</span>
      </button>
    </footer>

  </div>

  <!-- Toast container -->
  <div class="toast-container" id="toast-container"></div>

  <script src="/app/assets/js/crypto.js"></script>
  <script src="/app/assets/js/p2p.js"></script>
  <script src="/app/assets/js/meeting.js"></script>
  <script src="/app/assets/js/app.js"></script>
  <script src="/app/features/meeting/meeting.js"></script>
</body>
</html>
