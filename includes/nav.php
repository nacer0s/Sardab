<?php if (!isset($basePath)) { $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'); $dir = str_replace('\\', '/', dirname(__DIR__)); $basePath = $docRoot !== '' && strpos($dir, $docRoot) === 0 ? rtrim(substr($dir, strlen($docRoot)), '/') : ''; } ?>
<nav class="nav" id="navbar">
  <div class="nav-inner">
    <div class="nav-start">
      <?php if(isset($navBack)): ?>
        <a href="<?=htmlspecialchars($navBack)?>" class="nav-back"><i class="fa-solid fa-arrow-left"></i></a>
      <?php endif; ?>
      <a href="<?=$basePath?>/" class="logo">
        <img src="<?=$basePath?>/logo.svg" alt="Sardab" width="24" height="24">
        <span data-i18n="brand.name">Sardab</span>
      </a>
      <?php if(isset($navApp)): ?>
        <span class="nav-app" data-i18n="<?=htmlspecialchars($navApp)?>"></span>
      <?php endif; ?>
      <?php if(isset($navRoom)): ?>
        <span class="room-badge visible" id="roomBadge"><?=htmlspecialchars($navRoom)?></span>
        <span class="enc-badge visible"><i class="fa-solid fa-lock"></i></span>
      <?php endif; ?>
    </div>
    <div class="nav-end">
      <?php if(isset($navActions)): ?>
        <div class="nav-actions" id="navActions"><?=$navActions?></div>
      <?php endif; ?>
      <button class="lang-toggle" id="langToggle">AR</button>
      <?php if(!isset($navBack)&&!isset($navApp)): ?>
        <div class="nav-links">
          <a href="<?=$basePath?>/#features" data-i18n="nav.features">Features</a>
          <a href="<?=$basePath?>/#how" data-i18n="nav.how">How It Works</a>
          <a href="<?=$basePath?>/#why" data-i18n="nav.why">Why Sardab</a>
          <a href="<?=$basePath?>/#security" data-i18n="nav.security">Security</a>
          <a href="<?=$basePath?>/#team" data-i18n="nav.team">Team</a>
          <a href="<?=$basePath?>/#roadmap" data-i18n="nav.roadmap">Roadmap</a>
          <a href="<?=$basePath?>/#faq" data-i18n="nav.faq">FAQ</a>
          <a href="<?=$basePath?>/app/" class="nav-cta" data-i18n="nav.app">Launch App</a>
        </div>
      <?php endif; ?>
      <div class="nav-dropdown" id="navDropdown">
        <div class="nav-dropdown-inner" id="navDropdownInner"></div>
      </div>
      <button class="nav-mobile-toggle" id="navMobileToggle"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</nav>
<script>
(function(){
  var t=document.getElementById('navMobileToggle'),n=document.getElementById('navbar'),d=document.getElementById('navDropdownInner');
  if(!t||!n||!d)return;
  var links=document.querySelector('.nav-links');
  if(links){links.querySelectorAll('a').forEach(function(a){d.appendChild(a.cloneNode(true))})}
  t.addEventListener('click',function(e){e.stopPropagation();n.classList.toggle('nav-open')});
  document.addEventListener('click',function(e){if(n.classList.contains('nav-open')&&!n.contains(e.target))n.classList.remove('nav-open')});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&n.classList.contains('nav-open'))n.classList.remove('nav-open')});
})();
</script>
