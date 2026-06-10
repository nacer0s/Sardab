<?php
$presetId = isset($_GET['id']) ? htmlspecialchars(trim($_GET['id']), ENT_QUOTES, 'UTF-8') : '';
$mode     = $presetId !== '' ? 'retrieve' : 'store';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Encrypted Vault — Sardab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body data-mode="<?= $mode ?>" data-vault-id="<?= $presetId ?>">

  <nav class="nav-bar" id="nav-bar">
    <a href="/app" class="nav-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" class="nav-logo-img" /> Sardab</a>
    <div class="nav-links">
      <a href="/app" class="nav-link">Features</a>
    </div>
    <a href="/app" class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-left btn-icon"></i> Back</a>
  </nav>

  <main class="page">

    <div class="page-header">
      <h1>Encrypted <span class="text-gradient">Vault</span></h1>
      <p>Store secrets with burn-after-reading. Client-side encrypted. Auto-destroys on first view.</p>
    </div>

    <div class="card card-premium">

      <div class="card-premium-header">
        <div class="card-premium-top">
          <span class="card-premium-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" /></span>
          <span class="card-premium-name">SARDAB</span>
          <span class="card-premium-badge">SECURE</span>
        </div>
        <p class="card-premium-sub">Zero-Knowledge Digital Vault</p>
      </div>

      <div class="tab-group">
        <button id="tab-store" class="tab-btn" role="tab" aria-selected="true" data-section="section-store">Store</button>
        <button id="tab-retrieve" class="tab-btn" role="tab" aria-selected="false" data-section="section-retrieve">Retrieve</button>
      </div>

      <section id="section-store" class="section" aria-hidden="false">
        <form onsubmit="return false;">
          <label class="field">
            <span class="field-label">Secret Data</span>
            <textarea id="store-data" class="textarea" rows="5" placeholder="Paste your secrets, notes, or anything sensitive…"></textarea>
          </label>
          <label class="field">
            <span class="field-label">Vault Name</span>
            <input type="text" id="vault-id" class="input" placeholder="my-secret-vault" autocomplete="off" spellcheck="false" />
            <span style="font-size:var(--text-2xs);color:var(--clr-text-muted);margin-top:var(--space-1);display:block;">Choose a memorable name for the vault link</span>
          </label>
          <label class="field">
            <span class="field-label">Passphrase</span>
            <input type="password" id="store-pass" class="input" placeholder="Your master passphrase" autocomplete="off" />
          </label>
          <div id="strength-meter" class="strength-meter">
            <div class="progress-track"><div id="strength-fill" class="progress-fill" style="width:0%;"></div></div>
            <div id="strength-label" class="progress-text"></div>
          </div>
          <label class="field">
            <span class="field-label">Auto-Delete After</span>
            <select id="store-ttl" class="select">
              <option value="3600">1 Hour</option>
              <option value="86400" selected>24 Hours</option>
              <option value="604800">7 Days</option>
              <option value="2592000">30 Days</option>
              <option value="">Never</option>
            </select>
          </label>
          <button type="button" id="btn-store" class="btn btn-primary btn-full"><i class="fa-solid fa-lock btn-icon"></i> Encrypt &amp; Store</button>
        </form>
      </section>

      <section id="section-retrieve" class="section" aria-hidden="true">
        <form onsubmit="return false;">
          <label class="field">
            <span class="field-label">Vault ID</span>
            <input type="text" id="vault-id-retrieve" class="input" placeholder="my-secret-vault" autocomplete="off" spellcheck="false" />
            <small id="signal-indicator"></small>
          </label>
          <label class="field">
            <span class="field-label">Passphrase</span>
            <input type="password" id="retrieve-pass" class="input" placeholder="Your master passphrase" autocomplete="off" />
          </label>
          <button type="button" id="btn-retrieve" class="btn btn-primary btn-full"><i class="fa-solid fa-eye btn-icon"></i> Decrypt &amp; View Once</button>
          <label class="field label-margin">
            <span class="field-label">Decrypted Data</span>
            <textarea id="retrieve-data" class="textarea" rows="5" placeholder="Decrypted content will appear here…" readonly></textarea>
          </label>
        </form>
      </section>

      <div id="vault-status" class="status-alert"></div>

      <div class="card-footer">
        <p>Sardab &mdash; Zero-Knowledge Digital Vault</p>
      </div>

    </div>

  </main>

  <footer class="page-footer">
    <span>Sardab &mdash; Zero-Knowledge Communication Platform</span>
  </footer>

  <script src="/app/assets/js/crypto.js"></script>
  <script src="/app/assets/js/app.js"></script>
  <script src="/app/features/text-send/text-send.js"></script>
</body>
</html>