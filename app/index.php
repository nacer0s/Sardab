<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sardab — Feature Hub</title>
  <meta name="description" content="P2P encrypted messaging, file transfer, voice &amp; video calls." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>

  <nav class="nav-bar" id="nav-bar">
    <a href="/" class="nav-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" class="nav-logo-img" /> Sardab</a>
    <div class="nav-links">
      <a href="/" class="nav-link">Home</a>
    </div>
    <a href="/" class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-left btn-icon"></i> Home</a>
  </nav>

  <main class="page">

    <div class="page-header">
      <h1>Choose a <span class="text-gradient">feature</span></h1>
      <p>All connections are P2P encrypted. No data touches our servers.</p>
    </div>

    <div class="hub-grid stagger-children">

      <a href="/app/messaging" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-comments"></i></div>
        <h2 class="hub-card-title">Messaging</h2>
        <p class="hub-card-desc">Real-time encrypted chat over WebRTC. Direct P2P with zero server storage.</p>
        <span class="hub-card-badge live">Live</span>
      </a>

      <a href="/app/file-send" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-file-arrow-up"></i></div>
        <h2 class="hub-card-title">File Send</h2>
        <p class="hub-card-desc">Send files directly browser-to-browser. Encrypted chunk-by-chunk with AES-256-GCM.</p>
        <span class="hub-card-badge live">Live</span>
      </a>

      <a href="/app/text-send" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-lock"></i></div>
        <h2 class="hub-card-title">Text Vault</h2>
        <p class="hub-card-desc">Server-hosted encrypted vault with burn-after-reading. Self-destructs on first view.</p>
        <span class="hub-card-badge live">Live</span>
      </a>

      <a href="/app/voice-call" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-phone"></i></div>
        <h2 class="hub-card-title">Voice Call</h2>
        <p class="hub-card-desc">Encrypted P2P audio calls using WebRTC. Your voice never passes through any server.</p>
        <span class="hub-card-badge beta">Beta</span>
      </a>

      <a href="/app/video-call" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-video"></i></div>
        <h2 class="hub-card-title">Video Call</h2>
        <p class="hub-card-desc">End-to-end encrypted video calls with audio + video. Direct P2P with no intermediates.</p>
        <span class="hub-card-badge beta">Beta</span>
      </a>

      <a href="/app/meeting" class="hub-card">
        <div class="hub-card-icon"><i class="fa-solid fa-people-group"></i></div>
        <h2 class="hub-card-title">Meeting</h2>
        <p class="hub-card-desc">Group P2P meetings with video grid, chat, screen share &amp; file transfer. Mesh encrypted.</p>
        <span class="hub-card-badge beta">Beta</span>
      </a>

    </div>

  </main>

  <footer class="page-footer">
    <span>Sardab &mdash; Zero-Knowledge Communication Platform</span>
  </footer>

  <script src="/app/assets/js/app.js"></script>
</body>
</html>