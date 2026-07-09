<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - Video Call</title>
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
<link rel="stylesheet" href="<?=$basePath?>/assets/css/style.css?v=16">
<style>
.video-room .sidebar-nav-btn{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;gap:4px;padding:6px 10px;background:transparent;color:var(--text);border:1px solid var(--border);border-radius:var(--rxs);cursor:pointer;font-size:13px;font-weight:500;transition:var(--tr);flex-shrink:0;font-family:inherit}
.video-room .sidebar-nav-btn:hover{background:var(--card)}
.video-room #roomView{display:none;flex:1;flex-direction:column;position:relative;overflow:hidden;min-height:0}
.video-room #roomView.open{display:flex}
.video-room .video-grid{overflow:hidden;background:transparent;flex:1;min-height:0}
.video-room .video-grid.single{max-width:none;margin:0}
.video-room .video-tile{min-height:160px;background:var(--card);position:relative;border-radius:var(--rxs);overflow:hidden;display:flex;align-items:center;justify-content:center;cursor:pointer}
.video-room .video-el{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
.video-room .video-el.screen-el{object-fit:contain}
.video-room .video-grid.screen-active{display:flex;flex-direction:column}
.video-room .video-grid.has-maximized{position:relative}
.video-room .video-grid.has-maximized .video-tile{display:none}
.video-room .video-grid.has-maximized .video-tile.maximized{display:flex;position:absolute;top:0;left:0;width:100%;height:100%;z-index:5;border-radius:var(--rxs);min-height:0;background:#000}
.video-room .video-grid.screen-active.has-maximized .video-tile.maximized{border-radius:0}
.video-room .video-grid.has-maximized .video-tile.maximized .video-el{object-fit:contain}
.video-room .screen-main-area{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;background:#000;position:relative}
.video-room .screen-main-area .video-tile{width:100%;height:100%;min-height:0;border-radius:0}
.video-room .bottom-bar{display:flex;justify-content:center;align-items:center;gap:8px;padding:8px;flex-shrink:0;flex-wrap:wrap}
.video-room .bottom-bar .video-tile{width:140px;height:100px;min-height:0;flex-shrink:0;border-radius:var(--rxs)}
.video-room .bottom-bar .tile-label{font-size:11px;padding:20px 4px 4px}
.video-room .call-overlay-top{position:absolute;top:12px;left:50%;transform:translateX(-50%);z-index:10;display:flex;align-items:center;gap:12px;pointer-events:none}
.video-room .call-timer{font-size:13px;font-family:var(--mono);color:var(--text3);background:var(--card);border:1px solid var(--border);padding:3px 12px;border-radius:20px;white-space:nowrap}
.video-room .e2ee-badge{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text4);padding:3px 10px;border:1px solid var(--border);border-radius:20px;background:var(--card);opacity:0.8;white-space:nowrap}
.video-room .e2ee-badge.active{color:#4caf50;border-color:#4caf5066;opacity:1}
.video-room .e2ee-badge i{font-size:9px}
.video-room .controls-bar{display:flex;align-items:center;justify-content:center;gap:14px;padding:10px 16px 16px;background:var(--bg2);border-top:1px solid var(--border);flex-shrink:0}
.video-room .ctrl-btn{width:42px;height:42px;border-radius:50%;border:1px solid var(--border);background:var(--card);color:var(--text3);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:background .15s,color .15s,opacity .15s,transform .1s;flex-shrink:0;-webkit-tap-highlight-color:transparent;user-select:none}
.video-room .ctrl-btn:hover{background:var(--hover);border-color:var(--text3);color:var(--text)}
.video-room .ctrl-btn:active{transform:scale(0.9)}
.video-room .ctrl-btn.off{opacity:0.5;color:var(--text4)}
@media(max-width:768px){.video-room .bottom-bar .video-tile{width:100px;height:72px}}
@media(max-width:480px){.video-room .bottom-bar .video-tile{width:80px;height:58px}}
.video-room .ctrl-btn.off i{opacity:0.6}
.video-room .ctrl-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.video-room .ctrl-btn.end-call{background:#ff4444;color:#fff;border-color:#ff4444;width:46px;height:46px;font-size:17px}
.video-room .ctrl-btn.end-call:hover{background:#ff6666}
.video-room .ctrl-btn.end-call:active{transform:scale(0.9)}
.video-room .toast{position:fixed;top:72px;left:50%;transform:translateX(-50%);z-index:999;background:var(--card);color:var(--text);border:1px solid var(--border);border-radius:var(--rxs);padding:10px 20px;font-size:13px;box-shadow:0 4px 20px rgba(0,0,0,0.3);opacity:0;transition:opacity .3s ease;max-width:90vw;text-align:center;pointer-events:none}
.video-room .toast.visible{opacity:1}
.video-room .chat-panel{position:absolute;top:0;inset-inline-end:-360px;width:340px;max-width:85vw;height:100%;background:var(--bg2);border-inline-start:1px solid var(--border);z-index:100;display:flex;flex-direction:column;transition:inset-inline-end .3s ease}
.video-room .chat-panel.open{inset-inline-end:0}
.video-room .empty-msg-placeholder{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--text4);font-size:13px;gap:8px;padding:40px 20px;text-align:center}
.video-room .empty-msg-placeholder i{font-size:32px;opacity:0.3}
.video-room .typing-indicator{font-size:11px;color:var(--text4);padding:2px 16px 6px;min-height:0;opacity:0;transition:opacity .2s ease}
.video-room .typing-indicator.visible{opacity:1}
.video-room .msg-actions{display:none;position:absolute;top:4px;right:4px;gap:2px}
.video-room .chat-msg:hover .msg-actions,.video-room .chat-msg:active .msg-actions{display:flex}
.video-room .msg-action-btn{background:var(--bg);border:1px solid var(--border);border-radius:4px;color:var(--text3);font-size:9px;padding:2px 5px;cursor:pointer;line-height:1.4}
.video-room .msg-action-btn:hover{color:var(--text)}
.video-room .msg-reactions{display:flex;gap:4px;margin-top:4px;flex-wrap:wrap}
.video-room .reaction-badge{font-size:10px;padding:1px 5px;background:var(--bg);border:1px solid var(--border);border-radius:8px;cursor:pointer;user-select:none;line-height:1.4}
.video-room .reaction-badge:hover{border-color:var(--accent)}
.video-room .reaction-picker{position:fixed;bottom:100px;right:24px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius);padding:8px;display:flex;gap:4px;z-index:300;box-shadow:0 8px 24px rgba(0,0,0,0.4)}
.video-room .reaction-btn{background:none;border:none;font-size:22px;cursor:pointer;padding:4px 6px;border-radius:4px;transition:var(--tr)}
.video-room .reaction-btn:hover{background:var(--hover)}
.video-room .double-tap-flash .text{background:var(--hover)!important}
.video-room .msg-reply{padding:4px 8px;margin-bottom:4px;background:rgba(255,255,255,0.06);border-radius:4px;font-size:11px;border-inline-start:2px solid var(--accent)}
.video-room .msg-reply .reply-name{font-weight:600;display:block}
.video-room .msg-reply .reply-text{opacity:0.7}
.video-room .reply-preview{display:flex;align-items:center;gap:8px;padding:6px 12px;background:var(--bg);border-top:1px solid var(--border);font-size:12px;min-height:32px}
.video-room .reply-preview-text{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text3)}
.video-room .reply-preview-close{background:none;border:none;color:var(--text4);cursor:pointer;padding:2px}
.video-room .delete-options-modal,.video-room .confirm-modal{position:fixed;inset:0;z-index:400;display:flex;align-items:center;justify-content:center}
.video-room .delete-options-modal .backdrop,.video-room .confirm-modal .backdrop{position:absolute;inset:0;background:rgba(0,0,0,0.5)}
.video-room .delete-options-panel,.video-room .confirm-panel{background:var(--card);border:1px solid var(--border2);border-radius:var(--radius);padding:28px 24px 20px;max-width:380px;width:90vw;position:relative;z-index:1;pointer-events:auto}
.video-room .confirm-panel h3{font-size:17px;font-weight:600;margin-bottom:4px}
.video-room .confirm-panel p{font-size:13px;color:var(--text3);line-height:1.6;margin-bottom:20px}
.video-room .confirm-actions{display:flex;gap:8px}
.video-room .confirm-btn{flex:1;padding:12px;border:none;border-radius:var(--rxs);font-size:14px;font-weight:500;cursor:pointer;transition:var(--tr);font-family:var(--font)}
.video-room .confirm-btn.cancel{background:var(--bg);color:var(--text)}
.video-room .confirm-btn.cancel:hover{background:var(--hover)}
.video-room .confirm-btn.danger{background:#ff6b6b;color:#fff}
.video-room .delete-options-panel{padding:24px;text-align:center}
.video-room .delete-options-panel h3{margin-bottom:16px;font-size:17px;font-weight:600}
.video-room .del-opt{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:12px;background:none;border:1px solid var(--border);border-radius:var(--rxs);color:var(--text);font-size:14px;cursor:pointer;margin-bottom:6px;font-family:var(--font)}
.video-room .del-opt:hover{background:var(--hover)}
.video-room .del-opt.danger{color:#ff6b6b;border-color:#ff6b6b}
.video-room .del-opt.danger:hover{background:rgba(255,107,107,0.1)}
.video-room #joinView{flex:1;display:flex;align-items:center;justify-content:center;padding:24px}
.video-room .join-card{width:100%;max-width:400px;padding:36px 28px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius);text-align:center}
.video-room .join-icon{width:56px;height:56px;border-radius:50%;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--text3);margin:0 auto 16px}
.video-room .join-title{font-size:20px;font-weight:600;margin-bottom:6px}
.video-room .join-sub{font-size:13px;color:var(--text3);margin-bottom:20px}
.video-room .form-input{width:100%;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:var(--rxs);color:var(--text);font-size:14px;outline:none;font-family:var(--font);box-sizing:border-box}
.video-room .form-input:focus{border-color:var(--accent)}
.video-room .join-btn{width:100%;padding:12px 24px;background:var(--text);color:var(--bg);border:none;border-radius:var(--rxs);font-size:15px;font-weight:600;cursor:pointer;font-family:var(--font);margin-top:12px;transition:var(--tr);display:flex;align-items:center;justify-content:center;gap:8px}
.video-room .join-btn:hover{opacity:0.85}
.video-room .join-btn:active{transform:scale(0.98)}
@media(max-width:768px){
.video-room .chat-panel{width:100%;max-width:100%;inset-inline-end:-100%}
.video-room .controls-bar{gap:10px;padding:8px 12px 14px}
.video-room .ctrl-btn{width:38px;height:38px;font-size:14px}
.video-room .ctrl-btn.end-call{width:42px;height:42px;font-size:16px}
.video-room .video-grid{grid-template-columns:1fr}
}
.video-room .nav-actions{gap:8px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;scrollbar-width:none;flex-shrink:1;min-width:0}
.video-room .nav-actions::-webkit-scrollbar{display:none}
@media(max-width:700px){
.video-room .nav-actions{gap:4px}
.video-room .nav-actions .btn-icon{width:32px;height:32px;font-size:12px;flex-shrink:0}
.video-room .nav-actions .leave-btn{font-size:12px;padding:0 10px;height:32px;flex-shrink:0}
.video-room .nav-actions .nav-stat{font-size:11px}
.video-room .sidebar-nav-btn{width:32px;height:32px;font-size:12px;flex-shrink:0}
}
@media(max-width:480px){
.video-room .nav-actions .btn-icon{width:28px;height:28px;font-size:11px}
.video-room .nav-actions .leave-btn{font-size:10px;padding:0 6px;height:28px}
.video-room .sidebar-nav-btn{width:28px;height:28px;font-size:11px}
.video-room .controls-bar{gap:8px;padding:6px 8px 12px}
.video-room .ctrl-btn{width:34px;height:34px;font-size:13px}
.video-room .ctrl-btn.end-call{width:38px;height:38px;font-size:15px}
.video-room .join-card{padding:28px 20px}
}
</style>
</head>
<body class="room-page video-room">
<div class="room-bg">
  <div class="room-glow g1"></div>
  <div class="room-glow g2"></div>
  <canvas class="room-canvas" id="roomCanvas"></canvas>
</div>
<div id="app">
<?php
$navBack=$basePath.'/app/video/';
$navApp='hub.video.title';
$navRoom=$room;
$navActions='<span class="nav-stat timer" id="timer">00:00</span><button class="btn-icon" id="micToggle" title="Mic"><i class="fa-solid fa-microphone"></i></button><button class="btn-icon" id="camToggle" title="Camera"><i class="fa-solid fa-video"></i></button><button class="btn-icon" id="shareToggle" title="Screen"><i class="fa-solid fa-desktop"></i></button><button class="btn-icon" id="chatToggle" title="Chat"><i class="fa-solid fa-comment"></i></button><button class="sidebar-nav-btn" id="sidebarToggle" title="Users"><i class="fa-solid fa-users"></i></button><button class="leave-btn" id="leaveBtn"><i class="fa-solid fa-phone-slash"></i> <span data-i18n="leave">Leave</span></button>';
include __DIR__.'/../../../includes/nav.php';
?>
<div id="roomView">
  <div class="video-grid single" id="videoGrid">
    <div class="video-tile" id="localTile">
      <video id="localVideo" autoplay playsinline muted class="video-el mirror"></video>
      <div class="avatar-placeholder" id="localPlaceholder" style="display:none">
        <i class="fa-solid fa-video-slash"></i>
        <span id="localAvatar">Y</span>
      </div>
      <div class="tile-label">
        <span class="name" id="localName">You</span>
        <span class="mic-icon" id="localMicIcon"><i class="fa-solid fa-microphone"></i></span>
      </div>
    </div>
    <div class="video-tile" id="remoteTile">
      <video id="remoteVideo" autoplay playsinline class="video-el" style="display:none"></video>
      <div class="avatar-placeholder" id="remotePlaceholder">
        <span id="remoteAvatar">?</span>
      </div>
      <div class="tile-label">
        <span class="name" id="remoteName" data-i18n="call.waiting">Waiting...</span>
        <span class="mic-icon" id="remoteMicIcon"><i class="fa-solid fa-microphone"></i></span>
      </div>
    </div>
    <div class="video-tile" id="localScreenTile" style="display:none" data-screen="true">
      <video id="localScreenVideo" autoplay playsinline class="video-el screen-el"></video>
      <div class="tile-label"><span>Your Screen</span></div>
    </div>
  </div>
  <div class="call-overlay-top">
    <span class="call-timer" id="callTimer">00:00</span>
    <span class="e2ee-badge" id="e2eeBadge"><i class="fa-solid fa-lock"></i> <span data-i18n="e2ee">E2EE</span></span>
  </div>
  <div class="controls-bar">
    <button class="ctrl-btn" id="btnMic" title="Mic"><i class="fa-solid fa-microphone"></i></button>
    <button class="ctrl-btn" id="btnCam" title="Camera"><i class="fa-solid fa-video"></i></button>
    <button class="ctrl-btn" id="btnShare" title="Share Screen"><i class="fa-solid fa-desktop"></i></button>
    <button class="ctrl-btn" id="btnSwitch" title="Switch Camera"><i class="fa-solid fa-rotate"></i></button>
    <button class="ctrl-btn end-call" id="btnEnd" title="End Call"><i class="fa-solid fa-phone-slash"></i></button>
  </div>
  <div class="chat-panel" id="chatPanel">
    <div class="chat-panel-header">
      <h3 data-i18n="chat.title">Chat</h3>
      <button class="btn-icon" id="closeChat" title="Close Chat"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="chat-messages" id="chatMessages">
      <div class="empty-msg-placeholder"><i class="fa-solid fa-message"></i><span data-i18n="chat.empty">No messages yet</span></div>
    </div>
    <div class="typing-indicator" id="typingIndicator"></div>
    <div class="chat-input-row">
      <input type="text" id="chatInput" placeholder="Type a message..." data-i18n-placeholder="chat.placeholder">
      <button class="send-btn" id="chatSendBtn"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
  </div>
</div>
<div class="users-modal" id="usersModal">
  <div class="backdrop"></div>
  <div class="users-modal-panel">
    <div class="users-modal-header">
      <i class="fa-solid fa-users"></i>
      <span data-i18n="users">Users</span>
      <span class="badge" id="modalUserCount">0</span>
      <button class="close-modal" id="closeUsersModal"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="users-modal-body" id="modalBody"></div>
  </div>
</div>
<audio id="peerAudio" autoplay style="display:none"></audio>
<div id="toast" class="toast"></div>
<div id="joinView" style="display:none">
  <div class="join-card">
    <div class="join-icon"><i class="fa-solid fa-video"></i></div>
    <h2 class="join-title" data-i18n="join.title">Join Video Call</h2>
    <p class="join-sub" data-i18n="join.sub">Enter your name to join.</p>
    <input type="text" id="nameInput" class="form-input" placeholder="Your name" data-i18n-placeholder="placeholder.name" autocomplete="off">
    <button id="joinBtn" class="join-btn"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="join.btn">Join</span></button>
    <div id="joinError" class="error-msg"></div>
  </div>
</div>
</div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/crypto.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/ws.js?v=4"></script>
<script>
i18n.setTranslations({
  en:{'page.title':'Sardab - Video Call','hub.video.title':'Video Call','encrypted':'Encrypted','users':'Users','leave':'Leave','call.connecting':'Connecting...','call.connected':'Connected','call.disconnected':'Disconnected','call.waiting':'Waiting...','call.screen':'Screen Share','mic.on':'Microphone on','mic.off':'Microphone off','cam.on':'Camera on','cam.off':'Camera off','e2ee':'E2EE','chat.title':'Chat','chat.empty':'No messages yet','chat.placeholder':'Type a message...','join.title':'Join Video Call','join.sub':'Enter your name to join.','join.btn':'Join','join.error.name':'Please enter your name','placeholder.name':'Your name','reply':'Reply','delete.msg':'Delete Message','delete.me':'Delete for me','delete.all':'Delete for everyone','cancel':'Cancel'},
  ar:{'page.title':'سرداب - مكالمة فيديو','hub.video.title':'مكالمة فيديو','encrypted':'مشفر','users':'المستخدمون','leave':'غادر','call.connecting':'جارٍ الاتصال...','call.connected':'متصل','call.disconnected':'غير متصل','call.waiting':'انتظار...','call.screen':'مشاركة الشاشة','mic.on':'الميكروفون مفعل','mic.off':'الميكروفون معطل','cam.on':'الكاميرا مفعلة','cam.off':'الكاميرا معطلة','e2ee':'تشفير من طرف لطرف','chat.title':'الدردشة','chat.empty':'لا توجد رسائل بعد','chat.placeholder':'اكتب رسالة...','join.title':'انضمام','join.sub':'أدخل اسمك للانضمام.','join.btn':'دخول','join.error.name':'الرجاء إدخال اسمك','placeholder.name':'اسمك','reply':'رد','delete.msg':'حذف الرسالة','delete.me':'حذف لي فقط','delete.all':'حذف للجميع','cancel':'إلغاء'}
});
</script>
<script>
(function(){var c=document.getElementById('roomCanvas');if(!c)return;var x=c.getContext('2d');var w,h,p=[];function r(){w=c.width=window.innerWidth;h=c.height=window.innerHeight;p=[];for(var i=0,n=Math.min(Math.floor((w*h)/15000),40);i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25})}r();function d(){x.clearRect(0,0,w,h);for(var i=0;i<p.length;i++){var a=p[i];a.x+=a.vx;a.y+=a.vy;if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;for(var j=i+1;j<p.length;j++){var b=p[j],dx=a.x-b.x,dy=a.y-b.y;if(dx*dx+dy*dy<25000){x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.strokeStyle='rgba(255,255,255,0.025)';x.lineWidth=0.5;x.stroke()}}x.beginPath();x.arc(a.x,a.y,1.2,0,Math.PI*2);x.fillStyle='rgba(255,255,255,0.12)';x.fill()}requestAnimationFrame(d)}d();window.addEventListener('resize',r)})();
</script>
<script>window.ROOM_CODE='<?=htmlspecialchars($room,ENT_QUOTES,'UTF-8')?>';</script>
<script src="<?=$basePath?>/app/video/assets/js/app.js?v=4"></script>
<script>if('serviceWorker' in navigator)navigator.serviceWorker.register('<?=$basePath?>/sw.js').catch(function(){})</script>
</body>
</html>
