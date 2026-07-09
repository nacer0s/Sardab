<?php if (!isset($basePath)) { $basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'); } ?>
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-logo">
      <img src="<?=$basePath?>/logo.svg" alt="Sardab" width="20" height="20">
      <span data-i18n="brand.name">Sardab</span>
    </div>
    <div class="footer-links">
      <a href="<?=$basePath?>/#features" data-i18n="nav.features">Features</a>
      <a href="<?=$basePath?>/#security" data-i18n="nav.security">Security</a>
      <a href="<?=$basePath?>/#faq" data-i18n="nav.faq">FAQ</a>
      <a href="<?=$basePath?>/app/" data-i18n="nav.app">Launch App</a>
    </div>
    <p class="footer-tag"><span data-i18n="footer.tag">Full Encryption</span> &bull; <span data-i18n="footer.zero">Zero Knowledge</span> &bull; <span data-i18n="footer.direct">Direct Connection</span></p>
    <p class="footer-copy">&copy; <?=date('Y')?> <span data-i18n="brand.name">Sardab</span>. <span data-i18n="footer.rights">All rights reserved.</span> <span data-i18n="footer.by">Built by</span> <span data-i18n="footer.dev1">Nacer Eddine Bouars</span>, <span data-i18n="footer.dev2">Sara Chihab</span> &amp; <span data-i18n="footer.dev3">Douae Manar</span> &mdash; <a href="https://kumocoders.gt.tc" target="_blank" rel="noopener" class="kumo-link">KumoCoders</a>.</p>
    <p class="footer-legal"><a href="#" onclick="event.preventDefault();var m=document.getElementById('legalModal');if(m)m.classList.add('open');" data-i18n="footer.legal">Terms &amp; Privacy</a></p>
  </div>
</footer>
