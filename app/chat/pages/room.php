<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - P2P Chat</title>
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
<link rel="stylesheet" href="<?=$basePath?>/assets/css/style.css?v=15">
</head>
<body class="room-page chat-room">
<div class="room-bg">
  <div class="room-glow g1"></div>
  <div class="room-glow g2"></div>
  <canvas class="room-canvas" id="roomCanvas"></canvas>
</div>
<div id="app">
<?php
$navBack=$basePath.'/app/chat/';
$navApp='hub.chat.title';
$navRoom=$room;
$navActions='<button class="sidebar-nav-btn" id="sidebarToggle" title="Users"><i class="fa-solid fa-users"></i></button><button class="leave-btn" id="leaveBtn"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="leave">Leave</span></button>';
include __DIR__.'/../../../includes/nav.php';
?>
<div id="chatLayout" class="chat-layout">
  <div id="roomView" style="flex:1;display:flex;flex-direction:column;overflow:hidden">
    <div class="chat-view visible" id="chatView">
      <div class="messages-container" id="messagesContainer">
        <div class="empty-state" id="emptyState"><i class="fa-solid fa-lock"></i><p data-i18n="encrypted">Encrypted</p></div>
      </div>
      <div class="typing-indicator" id="typingIndicator"></div>
      <div class="chat-input-bar visible" id="chatInputBar">
        <button class="btn-icon" id="attachBtn"><i class="fa-solid fa-paperclip"></i></button>
        <input type="text" id="msgInput" placeholder="Type a message..." data-i18n-placeholder="msg.placeholder" autocomplete="off">
        <button class="voice-btn" id="voiceBtn"><i class="fa-solid fa-microphone"></i></button>
        <button class="send-btn" id="sendBtn" disabled><i class="fa-solid fa-paper-plane"></i></button>
      </div>
    </div>
  </div>
  <aside class="users-sidebar" id="usersSidebar">
    <div class="sidebar-header"><i class="fa-solid fa-users"></i><h3 data-i18n="users">Users</h3><span class="badge" id="sidebarUserCount">0</span></div>
    <div class="sidebar-body" id="sidebarBody"></div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <div class="users-modal" id="usersModal">
    <div class="backdrop"></div>
    <div class="users-modal-panel">
      <div class="users-modal-header">
        <i class="fa-solid fa-users"></i>
        <span data-i18n="users">Users</span>
        <span class="badge" id="modalUserCount">0</span>
        <button class="close-modal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="users-modal-body" id="modalBody"></div>
    </div>
  </div>
</div>
<input type="file" id="fileInput" hidden>
</div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/crypto.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/ws.js?v=2"></script>
<script>
i18n.setTranslations({
  en: { 'page.title': 'Sardab - P2P Chat', 'hub.chat.title': 'P2P Chat', 'encrypted': 'Encrypted', 'users': 'Users', 'leave': 'Leave', 'msg.placeholder': 'Type a message...', 'badge': 'Encrypted', 'reply': 'Reply', 'delete.msg': 'Delete Message', 'delete.me': 'Delete for me', 'delete.all': 'Delete for everyone', 'delete.all?': 'Delete for everyone?', 'delete.all.desc': 'This will delete the message for all participants. This action cannot be undone.', 'delete.me?': 'Delete for you?', 'delete.me.desc': 'The message will be deleted from your view only. Other participants will still see it.', 'cancel': 'Cancel', 'copy': 'Copy', 'copied': 'Copied!' },
  ar: { 'page.title': 'سرداب - دردشة P2P', 'hub.chat.title': 'دردشة P2P', 'encrypted': 'مشفر', 'users': 'المستخدمون', 'leave': 'غادر', 'msg.placeholder': 'اكتب رسالة...', 'badge': 'مشفر', 'reply': 'رد', 'delete.msg': 'حذف الرسالة', 'delete.me': 'حذف لي فقط', 'delete.all': 'حذف للجميع', 'delete.all?': 'حذف للجميع؟', 'delete.all.desc': 'سيتم حذف الرسالة لجميع المشاركين. لا يمكن التراجع عن هذا الإجراء.', 'delete.me?': 'حذف لك فقط؟', 'delete.me.desc': 'ستُحذف الرسالة من عرضك فقط. سيظل المشاركون الآخرون يرونها.', 'cancel': 'إلغاء', 'copy': 'نسخ', 'copied': 'تم النسخ!' }
});
</script>
<script>
(function(){var c=document.getElementById('roomCanvas');if(!c)return;var x=c.getContext('2d');var w,h,p=[];function r(){w=c.width=window.innerWidth;h=c.height=window.innerHeight;p=[];for(var i=0,n=Math.min(Math.floor((w*h)/15000),40);i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25})}r();function d(){x.clearRect(0,0,w,h);for(var i=0;i<p.length;i++){var a=p[i];a.x+=a.vx;a.y+=a.vy;if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;for(var j=i+1;j<p.length;j++){var b=p[j],dx=a.x-b.x,dy=a.y-b.y;if(dx*dx+dy*dy<25000){x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.strokeStyle='rgba(255,255,255,0.025)';x.lineWidth=0.5;x.stroke()}}x.beginPath();x.arc(a.x,a.y,1.2,0,Math.PI*2);x.fillStyle='rgba(255,255,255,0.12)';x.fill()}requestAnimationFrame(d)}d();window.addEventListener('resize',r)})();
</script>
<script>window.ROOM_CODE = '<?=htmlspecialchars($room)?>';</script>
<script>
(function(){
  var appEl = document.getElementById('app');
  function fixHeight(){if(appEl){appEl.style.minHeight=window.innerHeight+'px'}}
  fixHeight();window.addEventListener('resize',fixHeight);

  var inner = document.getElementById('navDropdownInner');
  if (!inner) return;
  var lt = document.getElementById('langToggle');
  if (lt) {
    var c = lt.cloneNode(true);
    c.className = 'lang-toggle in-dropdown';
    inner.appendChild(c);
  }
})();
</script>
<script src="<?=$basePath?>/app/chat/assets/js/app.js?v=7"></script>
<script>if ('serviceWorker' in navigator) navigator.serviceWorker.register('<?=$basePath?>/sw.js').catch(()=>{});</script>
</body>
</html>
