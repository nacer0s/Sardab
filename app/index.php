<?php $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'); $dir = str_replace('\\', '/', dirname(__DIR__)); $basePath = $docRoot !== '' && strpos($dir, $docRoot) === 0 ? rtrim(substr($dir, strlen($docRoot)), '/') : ''; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - Apps</title>
<meta name="description" content="Launch encrypted P2P apps: Chat, Voice, Video, and Meetings.">
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
<?php $navBack=$basePath.'/'; $navApp='nav.apps'; include __DIR__.'/../includes/nav.php'; ?>
<div class="apps-hero">
  <div class="apps-bg">
    <div class="apps-glow g1"></div>
    <div class="apps-glow g2"></div>
  </div>
  <canvas class="apps-canvas" id="appsCanvas"></canvas>
  <div class="apps-intro">
    <span class="apps-badge"><i class="fa-solid fa-shield-halved"></i> <span data-i18n="apps.tag">Pick Your App</span></span>
    <h1 data-i18n="apps.title">Choose How You Communicate</h1>
    <p data-i18n="apps.sub">Every app is fully encrypted. No account. No setup. Just pure privacy.</p>
  </div>
  <div class="apps-cards">
    <a href="<?=$basePath?>/app/chat/" class="app-card">
      <div class="app-card-icon"><i class="fa-solid fa-comments"></i></div>
      <div class="app-card-body">
        <h2 data-i18n="card.chat">Chat</h2>
        <p data-i18n="card.chat.desc">Fully encrypted P2P messages. The server sees nothing.</p>
        <span class="app-card-tag" data-i18n="badge">Encrypted</span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/voice/" class="app-card">
      <div class="app-card-icon"><i class="fa-solid fa-phone"></i></div>
      <div class="app-card-body">
        <h2 data-i18n="card.voice">Voice</h2>
        <p data-i18n="card.voice.desc">End-to-end encrypted voice calls.</p>
        <span class="app-card-tag" data-i18n="badge">Encrypted</span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/video/" class="app-card">
      <div class="app-card-icon"><i class="fa-solid fa-video"></i></div>
      <div class="app-card-body">
        <h2 data-i18n="card.video">Video</h2>
        <p data-i18n="card.video.desc">Fully encrypted live video calls.</p>
        <span class="app-card-tag" data-i18n="badge">Encrypted</span>
      </div>
    </a>
    <a href="<?=$basePath?>/app/meet/" class="app-card">
      <div class="app-card-icon"><i class="fa-solid fa-people-group"></i></div>
      <div class="app-card-body">
        <h2 data-i18n="card.meet">Meetings</h2>
        <p data-i18n="card.meet.desc">Multi-participant mesh P2P rooms.</p>
        <span class="app-card-tag" data-i18n="badge">Encrypted</span>
      </div>
    </a>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
</div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script>
i18n.setTranslations({
  en: {
    'page.title': 'Sardab - Apps',
    'nav.apps': 'Apps',
    'apps.tag': 'Pick Your App',
    'apps.title': 'Choose How You Communicate',
    'apps.sub': 'Every app is fully encrypted. No account. No setup. Just pure privacy.',
    'card.chat': 'Chat',
    'card.chat.desc': 'Fully encrypted P2P messages. The server sees nothing.',
    'card.voice': 'Voice',
    'card.voice.desc': 'End-to-end encrypted voice calls.',
    'card.video': 'Video',
    'card.video.desc': 'Fully encrypted live video calls.',
    'card.meet': 'Meetings',
    'card.meet.desc': 'Multi-participant mesh P2P rooms.',
    'badge': 'Encrypted',
    'encrypted': 'Encrypted'
  },
  ar: {
    'page.title': 'سرداب - التطبيق',
    'nav.apps': 'التطبيقات',
    'apps.tag': 'اختر تطبيقك',
    'apps.title': 'اختر كيف تتواصل',
    'apps.sub': 'كل تطبيق مشفر بالكامل. لا حساب. لا إعداد. خصوصية نقية.',
    'card.chat': 'دردشة',
    'card.chat.desc': 'رسائل P2P مشفرة بالكامل. الخادم لا يرى شيئاً.',
    'card.voice': 'صوت',
    'card.voice.desc': 'مكالمات صوتية مشفرة من طرف لطرف.',
    'card.video': 'فيديو',
    'card.video.desc': 'فيديو مباشر مشفر بالكامل.',
    'card.meet': 'اجتماعات',
    'card.meet.desc': 'غرف اجتماعات Mesh P2P متعددة المشاركين.',
    'badge': 'مشفر',
    'encrypted': 'مشفر'
  }
});
</script>
<script>
(function initAppsCanvas(){
  const canvas=document.getElementById('appsCanvas');
  if(!canvas)return;
  const ctx=canvas.getContext('2d');
  let w,h,pts=[];
  function resize(){
    w=canvas.width=window.innerWidth;
    h=canvas.height=window.innerHeight;
    pts=[];
    const n=Math.min(Math.floor((w*h)/15000),40);
    for(let i=0;i<n;i++)pts.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25});
  }
  resize();
  function draw(){
    ctx.clearRect(0,0,w,h);
    for(let i=0;i<pts.length;i++){
      const p=pts[i];
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0||p.x>w)p.vx*=-1;
      if(p.y<0||p.y>h)p.vy*=-1;
      for(let j=i+1;j<pts.length;j++){
        const q=pts[j],dx=p.x-q.x,dy=p.y-q.y;
        if(dx*dx+dy*dy<25000){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);ctx.strokeStyle=`rgba(255,255,255,${0.02+Math.random()*0.02})`;ctx.lineWidth=0.5;ctx.stroke()}
      }
      ctx.beginPath();ctx.arc(p.x,p.y,1.2,0,Math.PI*2);ctx.fillStyle='rgba(255,255,255,0.15)';ctx.fill();
    }
    requestAnimationFrame(draw);
  }
  draw();
  window.addEventListener('resize',resize);
})();
</script>
<script src="<?=$basePath?>/assets/js/main.js?v=3"></script>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?=$basePath?>/sw.js').catch(()=>{});</script>
</body>
</html>