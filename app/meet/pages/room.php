<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=5.0">
<title data-i18n="page.title">Sardab - Meet</title>
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
.meet-room #app{height:100dvh;display:flex;flex-direction:column;position:relative;overflow:hidden}
.meet-room #roomView{display:none;flex:1;flex-direction:column;overflow:hidden;position:relative;min-height:0}
.meet-room #roomView.open{display:flex}
.meet-room .video-grid{overflow:hidden;background:transparent;flex:1;min-height:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:4px;padding:4px;position:relative}
.meet-room .video-grid.single{grid-template-columns:1fr;max-width:600px;margin:0 auto}
.meet-room .video-tile{min-height:200px;background:var(--card);position:relative;border-radius:var(--radius);overflow:hidden;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:border-radius .2s,box-shadow .2s}
.meet-room .video-tile:hover{box-shadow:0 0 0 2px var(--accent)}
.meet-room .video-tile.speaking{box-shadow:0 0 0 2px #4ade80,0 0 20px rgba(74,222,128,.3)}
.meet-room .video-tile.speaking .tile-label::after{content:'🔊';position:absolute;right:8px;bottom:8px;font-size:14px;opacity:.8}
.meet-room .participant-row.speaking .p-avatar{box-shadow:0 0 0 2px #4ade80}
.meet-room .video-grid.single .video-tile{max-width:720px;min-height:320px}
.meet-room .video-el{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;background:#000}
.meet-room .video-el.mirror{transform:scale(-1,1)}
.meet-room .video-grid.screen-active{display:flex;flex-direction:column;gap:0;padding:0;grid-template-columns:none}
.meet-room .screen-main-area{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;background:#000;position:relative;width:100%;padding:4px;box-sizing:border-box}
.meet-room .screen-main-area .video-tile{width:100%;height:100%;min-height:0;border-radius:var(--radius);flex:none;max-width:100%;max-height:100%}
.meet-room .screen-main-area .video-el.screen-el{object-fit:contain}
.meet-room .bottom-bar{display:flex;justify-content:center;align-items:center;gap:8px;padding:8px;flex-shrink:0;flex-wrap:wrap;width:100%;box-sizing:border-box;background:var(--bg2)}
.meet-room .bottom-bar .video-tile{width:160px;height:120px;min-height:0;flex:none;border-radius:var(--rxs);max-width:none;transition:width .2s,height .2s}
.meet-room .bottom-bar .tile-label{font-size:11px;padding:20px 4px 4px}
.meet-room .avatar-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;position:relative;z-index:1;padding:16px}
.meet-room .avatar-placeholder i{font-size:36px;opacity:0.35;color:var(--text4);margin-bottom:2px}
.meet-room .avatar-placeholder .avatar-letter{width:72px;height:72px;border-radius:50%;background:var(--bg);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:600;color:var(--text3)}
.meet-room .avatar-placeholder .avatar-label{font-size:14px;color:var(--text4);font-weight:500}
.meet-room .tile-label{position:absolute;bottom:0;left:0;right:0;padding:32px 8px 8px;background:linear-gradient(transparent,rgba(0,0,0,0.6));display:flex;align-items:center;justify-content:center;gap:6px;font-size:14px;font-weight:500;color:#fff;pointer-events:none;z-index:2;text-align:center}
.meet-room .tile-label .name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:70%}
.meet-room .tile-label .mic-icon{font-size:11px;opacity:0.7;flex-shrink:0}
.meet-room .tile-label .mic-icon.muted{color:#ff6b6b;opacity:1}
.meet-room .conn-status{display:inline-block;width:7px;height:7px;border-radius:50%;margin:0 4px;flex-shrink:0;vertical-align:middle}
.meet-room .conn-status.connected{background:#4ade80;box-shadow:0 0 4px rgba(74,222,128,.5)}
.meet-room .conn-status.connecting{background:#fbbf24;box-shadow:0 0 4px rgba(251,191,36,.5);animation:pulse 1s infinite}
.meet-room .conn-status.failed,.meet-room .conn-status.disconnected{background:#ef4444;box-shadow:0 0 4px rgba(239,68,68,.5)}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.meet-room .chat-panel.drag-over{border-color:var(--accent);box-shadow:inset 0 0 0 2px var(--accent)}
.meet-room .unread-badge{position:absolute;top:-4px;right:-4px;min-width:16px;height:16px;border-radius:8px;background:#e53935;color:#fff;font-size:10px;font-weight:700;display:none;align-items:center;justify-content:center;padding:0 4px;line-height:16px;pointer-events:none;z-index:10}
.meet-room .scroll-bottom{position:absolute;bottom:60px;left:50%;transform:translateX(-50%);z-index:10;width:32px;height:32px;border-radius:50%;background:var(--card);border:1px solid var(--border);color:var(--text3);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:12px;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:var(--tr)}
.meet-room .scroll-bottom:hover{background:var(--bg);color:var(--text)}
.meet-room .tile-badge{position:absolute;top:8px;right:8px;z-index:5;display:flex;gap:4px}
.meet-room .tile-badge .badge-icon{background:rgba(0,0,0,0.5);border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:12px;color:#fff;backdrop-filter:blur(4px)}
.meet-room .tile-badge .badge-icon.hand{background:rgba(255,193,7,0.6);color:#fff}
.meet-room .tile-badge .badge-icon.screen{background:rgba(33,150,243,0.6);color:#fff}
.meet-room .video-grid.has-maximized{position:relative}
.meet-room .video-grid.has-maximized .video-tile{display:none}
.meet-room .video-grid.has-maximized .video-tile.maximized{display:flex;position:absolute;top:0;left:0;width:100%;height:100%;z-index:5;border-radius:0;min-height:0;background:#000;flex:none;max-width:none}
.meet-room .video-grid.screen-active.has-maximized .video-tile.maximized{border-radius:0}
.meet-room .video-grid.has-maximized .video-tile.maximized .video-el{object-fit:contain}
.meet-room .video-grid.has-maximized .video-tile.maximized .tile-label{font-size:16px;padding:48px 16px 16px}
.meet-room .screen-main-area .video-tile .tile-label{font-size:15px;padding:40px 12px 12px}
.meet-room .e2ee-badge{display:inline-flex;align-items:center;gap:4px;font-size:10px;color:var(--text4);padding:2px 8px;border:1px solid var(--border);border-radius:12px;background:var(--card);opacity:0.7;white-space:nowrap;vertical-align:middle;margin-left:6px}
.meet-room .e2ee-badge.active{color:#4caf50;border-color:#4caf5066;opacity:1}
.meet-room .e2ee-badge i{font-size:8px}
.meet-room .controls-bar{display:flex;align-items:center;justify-content:center;gap:12px;padding:10px 16px 16px;background:var(--bg2);border-top:1px solid var(--border);flex-shrink:0}
.meet-room .ctrl-group{display:flex;align-items:center;gap:8px}
.meet-room .ctrl-btn{width:44px;height:44px;border-radius:50%;border:1px solid var(--border);background:var(--card);color:var(--text);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;transition:var(--tr);flex-shrink:0}
.meet-room .ctrl-btn:hover{background:var(--bg);border-color:var(--accent)}
.meet-room .ctrl-btn.off{color:#ff6b6b;border-color:#ff6b6b66;background:rgba(255,107,107,0.1)}
.meet-room .ctrl-btn.off:hover{background:rgba(255,107,107,0.2)}
.meet-room .ctrl-btn.active{color:#4caf50;border-color:#4caf5066;background:rgba(76,175,80,0.1)}
.meet-room .ctrl-btn.active:hover{background:rgba(76,175,80,0.2)}
.meet-room .ctrl-btn.end-call{width:48px;height:48px;background:#e53935;border-color:#e53935;color:#fff}
.meet-room .ctrl-btn.end-call:hover{background:#c62828}
.meet-room .ctrl-btn.share-active{color:#2196f3;border-color:#2196f366;background:rgba(33,150,243,0.1)}
.meet-room .ctrl-separator{width:1px;height:32px;background:var(--border);flex-shrink:0}
.meet-room .chat-panel{position:absolute;top:0;right:0;bottom:0;width:340px;max-width:100%;background:var(--bg2);border-left:1px solid var(--border);display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease;z-index:20;box-shadow:-4px 0 20px rgba(0,0,0,0.3)}
.meet-room .chat-panel.open{transform:translateX(0)}
.meet-room .chat-panel-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0}
.meet-room .chat-panel-header h3{font-size:15px;font-weight:600;margin:0}
.meet-room .chat-panel-header .close-btn{background:none;border:none;color:var(--text3);cursor:pointer;font-size:18px;padding:4px}
.meet-room .chat-messages{flex:1;overflow-y:auto;padding:12px 16px;display:flex;flex-direction:column;gap:8px;min-height:0}
.meet-room .chat-msg{max-width:85%;padding:8px 12px;border-radius:var(--rxs);font-size:13px;line-height:1.4}
.meet-room .chat-msg.self{align-self:flex-end;background:var(--accent);color:#fff}
.meet-room .chat-msg.other{align-self:flex-start;background:var(--card);color:var(--text)}
.meet-room .chat-msg .sender{font-size:11px;font-weight:600;margin-bottom:2px;opacity:0.8}
.meet-room .chat-msg .text{word-break:break-word}
.meet-room .chat-msg .time{font-size:10px;opacity:0.6;text-align:right;margin-top:4px}
.meet-room .chat-msg .file-link{color:inherit;text-decoration:underline;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.meet-room .chat-input-row{display:flex;align-items:center;gap:8px;padding:10px 12px;border-top:1px solid var(--border);flex-shrink:0}
.meet-room .chat-input-row input,.meet-room .chat-input-row textarea{flex:1;padding:10px 12px;border:1px solid var(--border);border-radius:var(--rxs);background:var(--bg);color:var(--text);font-size:13px;outline:none;font-family:inherit;min-width:0;box-sizing:border-box}
.meet-room .chat-input-row input:focus,.meet-room .chat-input-row textarea:focus{border-color:var(--accent)}
.meet-room .chat-input-row .send-btn{width:36px;height:36px;border-radius:50%;border:none;background:var(--accent);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.meet-room .chat-input-row .send-btn:disabled{opacity:0.4;cursor:default}
.meet-room .chat-input-row .attach-btn{background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px;padding:6px;flex-shrink:0}
.meet-room .reactions-overlay{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:15;pointer-events:none}
.meet-room .reactions-overlay .reaction-pop{font-size:40px;animation:reactionFloat 1.5s ease-out forwards;position:absolute}
@keyframes reactionFloat{0%{opacity:1;transform:translateY(0) scale(0.5)}50%{opacity:1;transform:translateY(-60px) scale(1.2)}100%{opacity:0;transform:translateY(-120px) scale(1)}}
.meet-room .reaction-picker{position:absolute;bottom:70px;left:50%;transform:translateX(-50%);z-index:15;display:flex;gap:6px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:8px 12px;box-shadow:0 4px 20px rgba(0,0,0,0.3)}
.meet-room .reaction-picker .reaction-btn{background:none;border:none;font-size:24px;cursor:pointer;padding:4px;transition:transform .15s;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center}
.meet-room .reaction-picker .reaction-btn:hover{transform:scale(1.3);background:var(--bg)}
.meet-room .toast{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:999;background:var(--card);color:var(--text);border:1px solid var(--border);border-radius:var(--radius);padding:10px 20px;font-size:13px;box-shadow:0 4px 20px rgba(0,0,0,0.3);opacity:0;transition:opacity .3s ease;max-width:90vw;text-align:center;pointer-events:none}
.meet-room .toast.visible{opacity:1}
.meet-room .participants-panel{position:absolute;top:0;right:0;bottom:0;width:300px;max-width:100%;background:var(--bg2);border-left:1px solid var(--border);display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease;z-index:20;box-shadow:-4px 0 20px rgba(0,0,0,0.3)}
.meet-room .participants-panel.open{transform:translateX(0)}
.meet-room .participants-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0}
.meet-room .participants-header h3{font-size:15px;font-weight:600;margin:0}
.meet-room .participants-header .close-btn{background:none;border:none;color:var(--text3);cursor:pointer;font-size:18px;padding:4px}
.meet-room .participants-list{flex:1;overflow-y:auto;padding:8px 0;min-height:0}
.meet-room .participant-row{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px}
.meet-room .participant-row .p-avatar{width:36px;height:36px;border-radius:50%;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:600;color:var(--text3);flex-shrink:0}
.meet-room .participant-row .p-name{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.meet-room .participant-row .p-badge{font-size:11px;color:var(--text4);display:flex;align-items:center;gap:4px}
.meet-room .participant-row .p-badge i{font-size:12px}
.meet-room .participant-row .p-badge.hand{color:#ffc107}
.meet-room .participant-row .p-badge.speaking{color:#4caf50}
.meet-room .join-screen{position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg);z-index:50;padding:24px;box-sizing:border-box}
.meet-room .join-card{width:100%;max-width:400px;padding:36px 28px;background:var(--card);border:1px solid var(--border2);border-radius:var(--radius);text-align:center}
.meet-room .join-icon{width:56px;height:56px;border-radius:50%;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--text3);margin:0 auto 16px}
.meet-room .join-title{font-size:20px;font-weight:600;margin-bottom:6px}
.meet-room .join-sub{font-size:13px;color:var(--text3);margin-bottom:20px}
.meet-room .form-input{width:100%;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:var(--rxs);color:var(--text);font-size:14px;outline:none;font-family:var(--font);box-sizing:border-box}
.meet-room .form-input:focus{border-color:var(--accent)}
.meet-room .join-btn{width:100%;padding:12px 24px;background:var(--text);color:var(--bg);border:none;border-radius:var(--rxs);font-size:15px;font-weight:600;cursor:pointer;font-family:var(--font);margin-top:12px;transition:var(--tr);display:flex;align-items:center;justify-content:center;gap:8px}
.meet-room .join-btn:hover{opacity:0.85}
.meet-room .join-btn:active{transform:scale(0.98)}
.meet-room .error-msg{color:#ff6b6b;font-size:12px;margin-top:8px;min-height:18px}
.meet-room #btnHand.hand-raised{color:#ffc107;border-color:#ffc10766;background:rgba(255,193,7,0.1)}
@media(max-width:768px){
.meet-room .video-grid{grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
.meet-room .video-grid.single .video-tile{min-height:240px}
.meet-room .bottom-bar .video-tile{width:120px;height:90px}
.meet-room .controls-bar{gap:8px;padding:8px 10px 14px}
.meet-room .ctrl-btn{width:38px;height:38px;font-size:14px}
.meet-room .ctrl-btn.end-call{width:42px;height:42px}
.meet-room .chat-panel,.meet-room .participants-panel{width:100%}
.meet-room .avatar-placeholder i{font-size:28px}
.meet-room .avatar-placeholder .avatar-letter{font-size:22px}
}
@media(max-width:480px){
.meet-room .video-grid{grid-template-columns:1fr}
.meet-room .video-grid.single .video-tile{min-height:200px}
.meet-room .bottom-bar .video-tile{width:90px;height:68px}
.meet-room .controls-bar{gap:6px;padding:6px 8px 12px}
.meet-room .ctrl-btn{width:34px;height:34px;font-size:13px}
.meet-room .ctrl-btn.end-call{width:38px;height:38px;font-size:15px}
}
</style>
</head>
<body class="room-page meet-room">
<div class="room-bg">
  <div class="room-glow g1"></div>
  <div class="room-glow g2"></div>
  <canvas class="room-canvas" id="roomCanvas"></canvas>
</div>
<div id="app">
<?php
$navBack=$basePath.'/app/meet/';
$navApp='hub.meet.title';
$navRoom=$room;
$navExtra='<span id="timer">00:00</span><span class="e2ee-badge" id="e2eeBadge"><i class="fa-solid fa-lock"></i> <span data-i18n="encrypted">Encrypted</span></span>';
$navActions='<button class="btn-icon" id="micToggle" title="Mic"><i class="fa-solid fa-microphone"></i></button><button class="btn-icon" id="camToggle" title="Camera"><i class="fa-solid fa-video"></i></button><button class="btn-icon" id="screenToggle" title="Screen Share"><i class="fa-solid fa-desktop"></i></button><button class="btn-icon" id="participantsToggle" title="Participants"><i class="fa-solid fa-users"></i></button><button class="btn-icon" id="chatToggle" title="Chat" style="position:relative"><i class="fa-solid fa-comment"></i><span class="unread-badge" id="unreadBadge2">0</span></button><button class="leave-btn" id="leaveBtn"><i class="fa-solid fa-phone-slash"></i> <span data-i18n="leave">Leave</span></button>';
include __DIR__.'/../../../includes/nav.php';
?>
<div id="joinScreen" class="join-screen">
  <div class="join-card">
    <div class="join-icon"><i class="fa-solid fa-people-group"></i></div>
    <h2 class="join-title" data-i18n="join.title">Join Meeting</h2>
    <p class="join-sub" data-i18n="join.sub">Enter your name to join.</p>
    <input type="text" id="nameInput" class="form-input" placeholder="Your name" autocomplete="off">
    <button id="joinBtn" class="join-btn"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="join.btn">Join</span></button>
    <div id="joinError" class="error-msg"></div>
  </div>
</div>
<div id="roomView">
  <div class="video-grid single" id="meetGrid">
    <div class="video-tile" id="selfTile">
      <video id="selfVideo" autoplay playsinline muted class="video-el mirror"></video>
      <div class="avatar-placeholder" id="selfPlaceholder">
        <i class="fa-solid fa-video-slash"></i>
        <span class="avatar-letter" id="selfAvatar">Y</span>
        <span class="avatar-label" id="selfName">You</span>
      </div>
      <div class="tile-label">
        <span class="name" id="selfNameLabel">You</span>
        <span class="mic-icon" id="selfMicIcon"><i class="fa-solid fa-microphone"></i></span>
      </div>
    </div>
  </div>
  <div class="reactions-overlay" id="reactionsOverlay"></div>
  <div class="reaction-picker" id="reactionPicker" style="display:none">
    <button class="reaction-btn" data-emoji="👍">👍</button>
    <button class="reaction-btn" data-emoji="❤️">❤️</button>
    <button class="reaction-btn" data-emoji="😮">😮</button>
    <button class="reaction-btn" data-emoji="😂">😂</button>
    <button class="reaction-btn" data-emoji="🎉">🎉</button>
    <button class="reaction-btn" data-emoji="👏">👏</button>
  </div>
  <div class="controls-bar">
    <div class="ctrl-group">
      <button class="ctrl-btn" id="btnMic" title="Mic"><i class="fa-solid fa-microphone"></i></button>
      <button class="ctrl-btn" id="btnCam" title="Camera"><i class="fa-solid fa-video"></i></button>
      <button class="ctrl-btn" id="btnShare" title="Share Screen"><i class="fa-solid fa-desktop"></i></button>
    </div>
    <div class="ctrl-separator"></div>
    <div class="ctrl-group">
      <button class="ctrl-btn hand-btn" id="btnHand" title="Raise Hand"><i class="fa-solid fa-hand"></i></button>
      <button class="ctrl-btn" id="btnReact" title="Reactions"><i class="fa-solid fa-face-smile"></i></button>
    </div>
    <div class="ctrl-separator"></div>
    <div class="ctrl-group">
      <button class="ctrl-btn" id="btnParticipants" title="Participants"><i class="fa-solid fa-users"></i></button>
      <button class="ctrl-btn" id="btnChat" title="Chat" style="position:relative"><i class="fa-solid fa-comment"></i><span class="unread-badge" id="unreadBadge">0</span></button>
      <button class="ctrl-btn" id="btnFullscreen" title="Fullscreen"><i class="fa-solid fa-expand"></i></button>
      <button class="ctrl-btn end-call" id="btnEnd" title="End Call"><i class="fa-solid fa-phone-slash"></i></button>
    </div>
  </div>
  <div class="chat-panel" id="chatPanel">
    <div class="chat-panel-header">
      <h3 data-i18n="chat.title">Chat</h3>
      <button class="close-btn" id="closeChat"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="chat-messages" id="chatMessages" style="position:relative">
      <div style="text-align:center;color:var(--text4);font-size:13px;padding:40px 16px" data-i18n="chat.empty">No messages yet</div>
      <button class="scroll-bottom" id="scrollBottom"><i class="fa-solid fa-chevron-down"></i></button>
    </div>
    <div class="chat-input-row">
      <button class="attach-btn" id="chatAttach"><i class="fa-solid fa-paperclip"></i></button>
      <textarea id="chatInput" placeholder="Type a message..." rows="1" style="resize:none;overflow-y:auto;min-height:36px;max-height:120px;padding:8px 12px;line-height:1.4;font-family:inherit;font-size:13px;background:var(--bg);border:1px solid var(--border);border-radius:var(--rxs);color:var(--text);outline:none;flex:1;min-width:0;box-sizing:border-box"></textarea>
      <button class="send-btn" id="chatSend"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
  </div>
  <div class="participants-panel" id="participantsPanel">
    <div class="participants-header">
      <h3><span data-i18n="participants">Participants</span> (<span id="participantCount">0</span>)</h3>
      <button class="close-btn" id="closeParticipants"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="participants-list" id="participantsList"></div>
  </div>
</div>
</div>
<input type="file" id="fileInput" hidden>
<audio id="peerAudio" autoplay style="display:none"></audio>
<div class="toast" id="toast"></div>
<script src="<?=$basePath?>/assets/js/i18n.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/crypto.js?v=1"></script>
<script src="<?=$basePath?>/assets/js/ws.js?v=4"></script>
<script>
i18n.setTranslations({
  en:{'page.title':'Sardab - Meet','hub.meet.title':'Meet','encrypted':'Encrypted','leave':'Leave','call.connecting':'Connecting...','call.connected':'Connected','call.disconnected':'Disconnected','participants':'Participants','mic.on':'Microphone on','mic.off':'Microphone off','cam.on':'Camera on','cam.off':'Camera off','chat.title':'Chat','chat.empty':'No messages yet','chat.placeholder':'Type a message...','join.title':'Join Meeting','join.sub':'Enter your name to join.','join.btn':'Join','join.error.name':'Please enter your name','placeholder.name':'Your name','reaction.sent':'Sent {emoji}','hand.raised':'Raised hand','hand.lowered':'Lowered hand','screen.started':'{name} started sharing','screen.stopped':'{name} stopped sharing','user.joined':'{name} joined','user.left':'{name} left'},
  ar:{'page.title':'سرداب - اجتماع','hub.meet.title':'اجتماع','encrypted':'مشفر','leave':'غادر','call.connecting':'جارٍ الاتصال...','call.connected':'متصل','call.disconnected':'غير متصل','participants':'مشاركون','mic.on':'الميكروفون مفعل','mic.off':'الميكروفون معطل','cam.on':'الكاميرا مفعلة','cam.off':'الكاميرا معطلة','chat.title':'الدردشة','chat.empty':'لا توجد رسائل بعد','chat.placeholder':'اكتب رسالة...','join.title':'انضمام','join.sub':'أدخل اسمك للانضمام.','join.btn':'دخول','join.error.name':'الرجاء إدخال اسمك','placeholder.name':'اسمك','reaction.sent':'أرسل {emoji}','hand.raised':'رفع اليد','hand.lowered':'خفض اليد','screen.started':'بدأ {name} مشاركة الشاشة','screen.stopped':'أوقف {name} مشاركة الشاشة','user.joined':'انضم {name}','user.left':'غادر {name}'}
});
</script>
<script>
(function(){var c=document.getElementById('roomCanvas');if(!c)return;var x=c.getContext('2d');var w,h,p=[];function r(){w=c.width=window.innerWidth;h=c.height=window.innerHeight;p=[];for(var i=0,n=Math.min(Math.floor((w*h)/15000),40);i<n;i++)p.push({x:Math.random()*w,y:Math.random()*h,vx:(Math.random()-0.5)*0.25,vy:(Math.random()-0.5)*0.25})}r();function d(){x.clearRect(0,0,w,h);for(var i=0;i<p.length;i++){var a=p[i];a.x+=a.vx;a.y+=a.vy;if(a.x<0||a.x>w)a.vx*=-1;if(a.y<0||a.y>h)a.vy*=-1;for(var j=i+1;j<p.length;j++){var b=p[j],dx=a.x-b.x,dy=a.y-b.y;if(dx*dx+dy*dy<25000){x.beginPath();x.moveTo(a.x,a.y);x.lineTo(b.x,b.y);x.strokeStyle='rgba(255,255,255,0.025)';x.lineWidth=0.5;x.stroke()}}x.beginPath();x.arc(a.x,a.y,1.2,0,Math.PI*2);x.fillStyle='rgba(255,255,255,0.12)';x.fill()}requestAnimationFrame(d)}d();window.addEventListener('resize',r)})();
</script>
<script>window.ROOM_CODE='<?=htmlspecialchars($room,ENT_QUOTES,'UTF-8')?>';</script>
<script src="<?=$basePath?>/app/meet/assets/js/app.js?v=24"></script>
<script>if('serviceWorker' in navigator)navigator.serviceWorker.register('<?=$basePath?>/sw.js')['catch'](function(){})</script>
</body>
</html>
