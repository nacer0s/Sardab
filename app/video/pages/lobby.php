<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - Video Call</title>
<meta name="description" content="Waiting room for encrypted video call.">
<link rel="icon" type="image/svg+xml" href="<?=$basePath?>/favicon.svg">
<link rel="manifest" href="<?=$basePath?>/manifest.json">
<meta name="theme-color" content="#050508">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/835a2ccfff.js" crossorigin="anonymous"></script>
<link rel="stylesheet" href="<?=$basePath?>/assets/css/style.css?v=7">
</head>
<body>
<div id="app">
<?php $navBack=$basePath.'/app/video/'; $navApp='hub.video.title'; include __DIR__.'/../../../includes/nav.php'; ?>
<div class="lobby-hero">
  <div class="lobby-bg">
    <div class="lobby-glow g1"></div>
    <div class="lobby-glow g2"></div>
  </div>
  <canvas class="lobby-canvas" id="lobbyCanvas"></canvas>
  <div class="lobby" id="lobby">
    <div class="lobby-card">
      <div class="lobby-label" data-i18n="lobby.room">Room Code</div>
      <div class="lobby-room" id="roomCode"><?=htmlspecialchars($room)?></div>
      <div class="lobby-qr"><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?=urlencode((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$basePath.'/app/video/lobby/'.urlencode($room))?>" alt="QR" onerror="this.parentElement.style.display='none'"></div>
      <div class="lobby-link" id="inviteLinkRow">
        <span id="inviteLinkText" style="word-break:break-all;font-family:var(--mono);font-size:11px"><?=htmlspecialchars((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$basePath.'/app/video/lobby/'.urlencode($room))?></span>
      </div>
      <button class="lobby-copy-btn" id="copyBtn"><i class="fa-regular fa-copy"></i> <span data-i18n="lobby.copy">Copy Link</span></button>
      <div class="lobby-status" id="lobbyStatus"><div class="lobby-spinner" id="lobbySpinner"></div> <span data-i18n="lobby.waiting">Waiting for participants...</span></div>
      <div class="lobby-users" id="lobbyUsers"></div>
    </div>
  </div>
</div>
<div class="lobby-overlay" id="joinOverlay">
  <div class="lobby-card" style="max-width:360px">
    <div class="lobby-icon"><i class="fa-solid fa-video"></i></div>
    <div class="lobby-title" data-i18n="lobby.join_title">Join Call</div>
    <div class="lobby-sub" data-i18n="lobby.join_sub">Enter your name to join this call.</div>
    <input type="text" id="nameInput" class="form-input" style="width:100%;margin-bottom:16px;text-align:center" placeholder="Your name" data-i18n-placeholder="placeholder.name" autocomplete="off" maxlength="30">
    <button class="join-btn" id="joinBtn"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="lobby.join_btn">Join</span></button>
    <div class="error-msg" id="joinError"></div>
  </div>
</div>
<?php include __DIR__.'/../../../includes/footer.php'; ?>
</div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/crypto.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/ws.js?v=1"></script>
<script>
i18n.setTranslations({
  en: { 'page.title': 'Sardab - Video Call', 'hub.video.title': 'Video Call', 'lobby.room': 'Room Code', 'lobby.waiting': 'Waiting for participants...', 'lobby.redirecting': 'Connected! Entering room...', 'lobby.copy': 'Copy Link', 'lobby.join_title': 'Join Call', 'lobby.join_sub': 'Enter your name to join this call.', 'lobby.join_btn': 'Join', 'copy': 'Copy', 'copied': 'Copied!' },
  ar: { 'page.title': 'سرداب - مكالمة فيديو', 'hub.video.title': 'مكالمة فيديو', 'lobby.room': 'رمز الغرفة', 'lobby.waiting': 'في انتظار المشاركين...', 'lobby.redirecting': 'تم الاتصال! جارٍ دخول الغرفة...', 'lobby.copy': 'نسخ الرابط', 'lobby.join_title': 'انضمام للمكالمة', 'lobby.join_sub': 'أدخل اسمك للانضمام إلى هذه المكالمة.', 'lobby.join_btn': 'دخول', 'copy': 'نسخ', 'copied': 'تم النسخ!' }
});
</script>
<script>
(function(){const c=document.getElementById('lobbyCanvas');if(!c)return;const x=c.getContext('2d');let w,h,p=[];function r(){w=c.width=window.innerWidth;h=c.height=window.innerHeight;p=[];for(let i=0,n=Math.min(Math.floor((w*h)/15000),40);i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25})}r();function d(){x.clearRect(0,0,w,h);for(let i=0;i<p.length;i++){const a=p[i];a.x+=a.vx;a.y+=a.vy;if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;for(let j=i+1;j<p.length;j++){const b=p[j],dx=a.x-b.x,dy=a.y-b.y;if(dx*dx+dy*dy<25000){x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.strokeStyle='rgba(255,255,255,0.025)';x.lineWidth=0.5;x.stroke()}}x.beginPath();x.arc(a.x,a.y,1.2,0,Math.PI*2);x.fillStyle='rgba(255,255,255,0.12)';x.fill()}requestAnimationFrame(d)}d();window.addEventListener('resize',r)})();
</script>
<script>window.ROOM_CODE = '<?=htmlspecialchars($room)?>';</script>
<script src="<?=$basePath?>/app/video/assets/js/lobby.js"></script>
</body>
</html>
