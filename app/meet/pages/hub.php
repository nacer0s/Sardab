<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - Meet</title>
<meta name="description" content="End-to-end encrypted group meetings. Join or start a secure meeting.">
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
<?php $navBack=$basePath.'/app/'; $navApp='hub.meet.title'; include __DIR__.'/../../../includes/nav.php'; ?>
<div class="hub-hero">
  <div class="hub-bg">
    <div class="hub-glow g1"></div>
    <div class="hub-glow g2"></div>
  </div>
  <canvas class="hub-canvas" id="hubCanvas"></canvas>
  <div class="hub-intro">
    <span class="hub-badge"><i class="fa-solid fa-people-group"></i> <span data-i18n="hub.title">Meet</span></span>
    <h1 data-i18n="hub.title">Meet</h1>
    <p data-i18n="hub.sub">End-to-end encrypted group meetings.</p>
  </div>
  <div class="hub-actions">
    <a href="<?=$basePath?>/app/meet/join/" class="hub-card">
      <div class="hub-card-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
      <h2 data-i18n="hub.join">Join Meeting</h2>
      <p data-i18n="hub.join.desc">Enter a room code and join the meeting.</p>
    </a>
    <a href="<?=$basePath?>/app/meet/create/" class="hub-card">
      <div class="hub-card-icon"><i class="fa-solid fa-plus"></i></div>
      <h2 data-i18n="hub.create">Start Meeting</h2>
      <p data-i18n="hub.create.desc">Create a room and share the invite link.</p>
    </a>
  </div>
</div>
<?php include __DIR__.'/../../../includes/footer.php'; ?>
</div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script>
i18n.setTranslations({
  en: { 'page.title': 'Sardab - Meet', 'hub.meet.title': 'Meet', 'hub.title': 'Meet', 'hub.sub': 'End-to-end encrypted group meetings.', 'hub.join': 'Join Meeting', 'hub.join.desc': 'Enter a room code and join the meeting.', 'hub.create': 'Start Meeting', 'hub.create.desc': 'Create a room and share the invite link.' },
  ar: { 'page.title': 'سرداب - اجتماع', 'hub.meet.title': 'اجتماع', 'hub.title': 'اجتماع', 'hub.sub': 'اجتماعات جماعية مشفرة من طرف لطرف.', 'hub.join': 'انضمام', 'hub.join.desc': 'أدخل رمز الغرفة وانضم للاجتماع.', 'hub.create': 'بدء اجتماع', 'hub.create.desc': 'أنشئ غرفة وشارك رابط الدعوة.' }
});
</script>
<script>
(function(){const c=document.getElementById('hubCanvas');if(!c)return;const x=c.getContext('2d');let w,h,p=[];function r(){w=c.width=window.innerWidth;h=c.height=window.innerHeight;p=[];for(let i=0,n=Math.min(Math.floor((w*h)/15000),40);i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25})}r();function d(){x.clearRect(0,0,w,h);for(let i=0;i<p.length;i++){const a=p[i];a.x+=a.vx;a.y+=a.vy;if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;for(let j=i+1;j<p.length;j++){const b=p[j],dx=a.x-b.x,dy=a.y-b.y;if(dx*dx+dy*dy<25000){x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.strokeStyle='rgba(255,255,255,0.025)';x.lineWidth=0.5;x.stroke()}}x.beginPath();x.arc(a.x,a.y,1.2,0,Math.PI*2);x.fillStyle='rgba(255,255,255,0.12)';x.fill()}requestAnimationFrame(d)}d();window.addEventListener('resize',r)})();
</script>
<script src="<?=$basePath?>/assets/js/main.js?v=3"></script>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?=$basePath?>/sw.js').catch(()=>{});</script>
</body>
</html>