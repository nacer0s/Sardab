<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sardab — Zero-Knowledge Communication Platform</title>
  <meta name="description" content="Encrypt, share, and communicate with complete privacy. P2P messaging, encrypted file transfer, voice &amp; video calls. Zero knowledge. No accounts." />
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
      <a href="#features" class="nav-link">Features</a>
      <a href="#security" class="nav-link">Security</a>
      <a href="#use-cases" class="nav-link">Use Cases</a>
      <a href="#faq" class="nav-link">FAQ</a>
    </div>
    <a href="/app" class="btn btn-ghost btn-sm">Launch App <i class="fa-solid fa-arrow-right btn-icon btn-icon-right"></i></a>
  </nav>

  <header class="hero-section">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>

    <div class="section-label" style="justify-content:center;margin-bottom:var(--space-4);">
      <span class="label-dot"></span> End-to-End Encrypted
    </div>

    <h1>
      <span class="text-gradient">Private communication</span><br />
      for the <span style="opacity:0.7;">paranoid</span>.
    </h1>

      <p class="hero-subtitle">Group meetings, messaging, file transfer, voice &amp; video calls — all P2P encrypted in your browser. No servers, no logs, no accounts.</p>

    <div class="hero-cta-row">
      <a href="/app" class="btn btn-primary btn-lg">Open Sardab <i class="fa-solid fa-arrow-right btn-icon btn-icon-right"></i></a>
      <a href="#features" class="btn btn-ghost btn-lg">See Features <i class="fa-solid fa-chevron-down btn-icon btn-icon-right" style="font-size:0.7em;"></i></a>
    </div>

    <div class="hero-stats">
      <div>
        <span class="hero-stat-value">P2P Encrypted</span>
        <span class="hero-stat-label">WebRTC DataChannel</span>
      </div>
      <div class="hero-stat-divider"></div>
      <div>
        <span class="hero-stat-value">AES-256-GCM</span>
        <span class="hero-stat-label">Client-Side Crypto</span>
      </div>
      <div class="hero-stat-divider"></div>
      <div>
        <span class="hero-stat-value">Zero Accounts</span>
        <span class="hero-stat-label">No Sign-Up Required</span>
      </div>
    </div>
  </header>

  <main>

    <section id="features" class="section-block reveal reveal-up">
      <div class="section-header centered">
        <div class="section-label"><span class="label-dot"></span> All Features</div>
        <h2 class="section-title">Everything you need for <span class="text-gradient">private communication</span></h2>
        <p class="section-desc centered">Five powerful tools, one unified interface. All encrypted end-to-end before they leave your device.</p>
      </div>

      <div class="grid-3 stagger-children" style="max-width:900px;margin:0 auto;">
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-comments"></i></div>
          <h3 class="feat-card-title">P2P Messaging</h3>
          <p class="feat-card-desc">Real-time encrypted chat over WebRTC DataChannel. Messages are delivered directly between browsers with zero server storage.</p>
          <div class="feat-tag">Direct Connect</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-file-arrow-up"></i></div>
          <h3 class="feat-card-title">P2P File Transfer</h3>
          <p class="feat-card-desc">Send files directly between browsers. Each chunk encrypted with AES-256-GCM. No file ever touches any server.</p>
          <div class="feat-tag">Encrypted</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-lock"></i></div>
          <h3 class="feat-card-title">Encrypted Vault</h3>
          <p class="feat-card-desc">Store secrets with burn-after-reading. Encrypted in your browser before upload. Self-destructs on first view.</p>
          <div class="feat-tag">Auto-Destroy</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-phone"></i></div>
          <h3 class="feat-card-title">Voice Calls</h3>
          <p class="feat-card-desc">Encrypted P2P audio calls using WebRTC. Your voice never passes through any server — direct browser-to-browser.</p>
          <div class="feat-tag">Beta</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-video"></i></div>
          <h3 class="feat-card-title">Video Calls</h3>
          <p class="feat-card-desc">End-to-end encrypted video calls with Audio + Video. Direct P2P connection with no intermediate servers.</p>
          <div class="feat-tag">Beta</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-people-group"></i></div>
          <h3 class="feat-card-title">Group Meetings</h3>
          <p class="feat-card-desc">Full mesh P2P group meetings with video grid, real-time chat, screen share &amp; file transfer. Like Zoom, but encrypted.</p>
          <div class="feat-tag">Beta</div>
        </article>
        <article class="card card-hover" style="padding:var(--space-6);">
          <div class="feat-icon-wrap"><i class="fa-solid fa-shield-halved"></i></div>
          <h3 class="feat-card-title">Zero Knowledge</h3>
          <p class="feat-card-desc">No accounts, no servers, no logs. Even we cannot access your data. Your encryption keys never leave your browser.</p>
          <div class="feat-tag">Private by Design</div>
        </article>
      </div>
    </section>

    <div class="trust-bar reveal reveal-up">
      <span class="trust-pill">AES-256-GCM</span>
      <span class="trust-pill">WebRTC DataChannel</span>
      <span class="trust-pill">Client-Side Only</span>
      <span class="trust-pill">Burn After Reading</span>
      <span class="trust-pill">P2P Encrypted</span>
      <span class="trust-pill">No Accounts</span>
      <span class="trust-pill">Mesh P2P</span>
      <span class="trust-pill">Open Source</span>
    </div>

    <section id="security" class="section-block alt reveal reveal-up">
      <div class="section-header centered">
        <div class="section-label"><span class="label-dot"></span> Security Stack</div>
        <h2 class="section-title">Cryptographic <span class="text-gradient">foundation</span></h2>
        <p class="section-desc centered">Industry-standard primitives powering every message, file, and call.</p>
      </div>

      <div class="crypto-grid">
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-microchip"></i></div>
          <div>
            <h3 class="crypto-name">PBKDF2</h3>
            <p class="crypto-desc">600,000 iterations of SHA-256 key derivation transforms passphrases into 256-bit encryption keys.</p>
          </div>
          <div class="crypto-stat">600K</div>
        </div>
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-fingerprint"></i></div>
          <div>
            <h3 class="crypto-name">SHA-256</h3>
            <p class="crypto-desc">Vault IDs are hashed before storage. The server never knows the original vault name.</p>
          </div>
          <div class="crypto-stat">256-BIT</div>
        </div>
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-key"></i></div>
          <div>
            <h3 class="crypto-name">AES-256-GCM</h3>
            <p class="crypto-desc">Authenticated encryption ensures both confidentiality and integrity for all stored and transferred data.</p>
          </div>
          <div class="crypto-stat">GCM</div>
        </div>
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-laptop-code"></i></div>
          <div>
            <h3 class="crypto-name">Web Crypto API</h3>
            <p class="crypto-desc">All cryptographic operations use native browser APIs. No external crypto libraries or dependencies.</p>
          </div>
          <div class="crypto-stat">NATIVE</div>
        </div>
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-bolt"></i></div>
          <div>
            <h3 class="crypto-name">WebRTC</h3>
            <p class="crypto-desc">Direct P2P connections for real-time messaging, file transfer, and audio/video calls. No intermediate servers.</p>
          </div>
          <div class="crypto-stat">P2P</div>
        </div>
        <div class="crypto-card">
          <div class="crypto-icon"><i class="fa-solid fa-server"></i></div>
          <div>
            <h3 class="crypto-name">Zero-Knowledge</h3>
            <p class="crypto-desc">Servers only facilitate signaling. Encrypted payloads, passphrases, and media never touch any server.</p>
          </div>
          <div class="crypto-stat">ZERO-K</div>
        </div>
      </div>
    </section>

    <section id="use-cases" class="section-block reveal reveal-up">
      <div class="section-header centered">
        <div class="section-label"><span class="label-dot"></span> Use Cases</div>
        <h2 class="section-title">Who needs <span class="text-gradient">Sardab</span>?</h2>
      </div>

      <div class="use-case-list stagger-children">
        <div class="use-case-item">
          <div class="use-case-icon"><i class="fa-solid fa-newspaper"></i></div>
          <div class="use-case-text">
            <h3>Journalists &amp; Whistleblowers</h3>
            <p>Communicate securely with sources using P2P encrypted messaging and file transfer. No trace, no servers, no compromise.</p>
          </div>
        </div>
        <div class="use-case-item">
          <div class="use-case-icon"><i class="fa-solid fa-code"></i></div>
          <div class="use-case-text">
            <h3>Developers &amp; Teams</h3>
            <p>Share API keys, credentials, and secrets via burn-after-reading vaults. Direct P2P file transfer for sensitive documents.</p>
          </div>
        </div>
        <div class="use-case-item">
          <div class="use-case-icon"><i class="fa-solid fa-lock"></i></div>
          <div class="use-case-text">
            <h3>Privacy-Conscious Individuals</h3>
            <p>Make encrypted voice and video calls without any service provider listening. Your conversations stay between you and your contact.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section-block alt reveal reveal-up">
      <div class="section-header centered">
        <div class="section-label"><span class="label-dot"></span> Simple Flow</div>
        <h2 class="section-title">How it <span class="text-gradient">works</span></h2>
      </div>

      <div class="steps-grid">
        <div class="step-item">
          <div class="step-circle"><span class="step-num">1</span></div>
          <h3 class="step-title">Create a Room</h3>
          <p class="step-desc">Generate a unique room ID. Share it with the person you want to connect with via any channel.</p>
        </div>
        <div class="step-item">
          <div class="step-circle"><span class="step-num">2</span></div>
          <h3 class="step-title">Establish P2P Connection</h3>
          <p class="step-desc">WebRTC creates a direct encrypted tunnel between browsers. No data passes through any server.</p>
        </div>
        <div class="step-item">
          <div class="step-circle"><span class="step-num">3</span></div>
          <h3 class="step-title">Communicate Freely</h3>
          <p class="step-desc">Chat, send files, start a voice/video call, or host a group meeting — all end-to-end encrypted. Disconnect when done.</p>
        </div>
      </div>
    </section>

    <section id="faq" class="section-block reveal reveal-up">
      <div class="section-header centered">
        <div class="section-label"><span class="label-dot"></span> FAQ</div>
        <h2 class="section-title">Frequently Asked <span class="text-gradient">Questions</span></h2>
      </div>

      <div class="faq-list">
        <div class="faq-item">
          <button class="faq-question">Is Sardab really private? <span class="faq-question-icon"><i class="fa-solid fa-plus"></i></span></button>
          <div class="faq-answer">Yes. All connections are direct P2P (peer-to-peer) via WebRTC. Messages, files, and media are encrypted with AES-256-GCM before they leave your browser. The server only helps establish the connection — it never sees your data.</div>
        </div>
        <div class="faq-item">
          <button class="faq-question">Do I need to create an account? <span class="faq-question-icon"><i class="fa-solid fa-plus"></i></span></button>
          <div class="faq-answer">No. There are no accounts, no emails, no phone numbers. Just generate a room link and share it with who you want to talk to. When you leave, everything is gone.</div>
        </div>
        <div class="faq-item">
          <button class="faq-question">What happens if the person I'm talking to leaves? <span class="faq-question-icon"><i class="fa-solid fa-plus"></i></span></button>
          <div class="faq-answer">The connection is closed. Messages and files are not stored anywhere. To reconnect, you create a new room and share the link again.</div>
        </div>
        <div class="faq-item">
          <button class="faq-question">Can I use Sardab on my phone? <span class="faq-question-icon"><i class="fa-solid fa-plus"></i></span></button>
          <div class="faq-answer">Yes. Sardab works in any modern browser (Chrome, Firefox, Safari, Edge) on desktop and mobile. No app installation needed.</div>
        </div>
        <div class="faq-item">
          <button class="faq-question">Is Sardab open source? <span class="faq-question-icon"><i class="fa-solid fa-plus"></i></span></button>
          <div class="faq-answer">Yes. The entire source code is available on GitHub. Anyone can audit it, fork it, or self-host it.</div>
        </div>
      </div>
    </section>

    <section class="final-cta reveal reveal-up">
      <h2>Ready for <span class="text-gradient">private communication</span>?</h2>
      <p>No sign-up. No servers. Just encryption.</p>
      <a href="/app" class="btn btn-primary btn-lg">Open Sardab <i class="fa-solid fa-arrow-right btn-icon btn-icon-right"></i></a>
    </section>

  </main>

  <footer class="page-footer">
    <div>
      <span>Sardab &mdash; Zero-Knowledge Communication Platform &middot; 2026</span>
      <span style="display:block;margin-top:var(--space-2);">Built with <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Crypto_API" target="_blank" rel="noopener">Web Crypto API</a> &middot; <a href="https://webrtc.org" target="_blank" rel="noopener">WebRTC</a> &middot; <a href="https://fonts.google.com/" target="_blank" rel="noopener">Inter</a></span>
    </div>

    <div style="margin-top:var(--space-5);padding-top:var(--space-5);border-top:1px solid var(--clr-border);">
      <div style="font-size:var(--text-2xs);color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:var(--space-3);">Project Links</div>
      <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);justify-content:center;">
        <a href="https://github.com/nacer0s/Sardab" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;"><i class="fa-brands fa-github"></i> GitHub</a>
        <a href="https://qabilah.com/hackathon/255665101472799432/projects/256995479982706688" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;">Qabilah</a>
        <a href="https://www.mortakaz.com/projects/6a28e09dc100ecc87a9da743" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;">Mortakaz</a>
      </div>
    </div>

    <div style="margin-top:var(--space-4);padding-top:var(--space-4);border-top:1px solid var(--clr-border);">
      <div style="font-size:var(--text-2xs);color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:var(--space-3);">Developer</div>
      <div style="display:flex;flex-wrap:wrap;gap:var(--space-3);justify-content:center;">
        <a href="https://github.com/nacer0s" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;"><i class="fa-brands fa-github"></i> nacer0s</a>
        <a href="https://linkedin.com/in/nacer0s" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;"><i class="fa-brands fa-linkedin"></i> LinkedIn</a>
        <a href="https://mortakaz.com/@nacer0s" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;">Mortakaz</a>
        <a href="https://qabilah.com/profile/nacer0s" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;">Qabilah</a>
        <a href="https://www.instagram.com/nacer0s" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;"><i class="fa-brands fa-instagram"></i> Instagram</a>
        <a href="https://kc.gt.tc/Nacer" target="_blank" rel="noopener" class="trust-pill" style="font-size:0.5rem;">Portfolio</a>
      </div>
    </div>
  </footer>

  <script src="/app/assets/js/app.js"></script>
</body>
</html>