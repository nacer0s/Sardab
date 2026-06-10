(function () {
  'use strict';

  var currentRoom = null;
  var $ = function (id) { return document.getElementById(id); };

  var connW   = $('conn-widget-send');
  var connWR  = $('conn-widget-recv');
  var connL   = connW.querySelector('.connection-label');
  var connLR  = connWR.querySelector('.connection-label');
  var btnCr   = $('btn-create');
  var btnJn   = $('btn-join');
  var roomBx  = $('room-link-box');
  var rInput  = $('room-id-input');
  var chatSec = $('view-feature');
  var chatMsg = $('chat-messages');
  var chatIn  = $('chat-input');
  var btnSend = $('btn-send-msg');
  var btnLv   = $('btn-leave');
  var lobby   = $('view-lobby');
  var feature = $('view-feature');

  function showLobby() {
    if (feature) feature.classList.remove('is-visible');
    if (lobby) setTimeout(function () { lobby.classList.remove('is-hidden'); }, 50);
  }

  function showFeature() {
    if (lobby) lobby.classList.add('is-hidden');
    if (feature) setTimeout(function () { feature.classList.add('is-visible'); }, 300);
  }

  function setConn(el, label, state, text) {
    el.className = 'connection-widget status-' + state;
    label.textContent = text;
  }

  function setBusy(buttons, busy) {
    for (var i = 0; i < buttons.length; i++) {
      if (buttons[i]) buttons[i].disabled = busy;
    }
  }

  function addChat(text, type) {
    var empty = chatMsg.querySelector('.chat-empty');
    if (empty) empty.remove();
    var b = document.createElement('div');
    b.className = 'chat-bubble ' + type;
    b.textContent = text;
    var t = document.createElement('span');
    t.className = 'chat-bubble-meta';
    t.textContent = new Date().toLocaleTimeString();
    b.appendChild(t);
    chatMsg.appendChild(b);
    chatMsg.scrollTop = chatMsg.scrollHeight;
  }

  function doSend() {
    if (!currentRoom || !currentRoom.connected) return;
    var text = chatIn ? chatIn.value.trim() : '';
    if (!text) return;
    try {
      currentRoom.sendMessage(text);
      addChat(text, 'sent');
      chatIn.value = '';
      chatIn.focus();
    } catch (e) {}
  }

  function showChat() {
    showFeature();
    if (chatIn) setTimeout(function () { chatIn.focus(); }, 100);
  }

  function hideChat() {
    showLobby();
  }

  function resetUI() {
    if (roomBx) { roomBx.classList.remove('visible'); roomBx.innerHTML = ''; }
    hideChat();
    setConn(connW, connL, 'idle', 'Disconnected');
    setConn(connWR, connLR, 'idle', 'Disconnected');
    setBusy([btnCr, btnJn], false);
  }

  function cleanup() {
    if (currentRoom) { currentRoom.disconnect(); currentRoom = null; }
  }

  if (btnCr) {
    btnCr.addEventListener('click', async function () {
      cleanup();
      if (roomBx) { roomBx.classList.remove('visible'); roomBx.innerHTML = ''; }
      hideChat();
      chatMsg.innerHTML = '<div class="chat-empty"><div class="chat-empty-icon"><i class="fa-solid fa-comment-dots"></i></div><p>Connected. Start typing to chat.</p></div>';

      var roomId = 'srdb-' + Math.random().toString(36).substring(2, 10);
      setConn(connW, connL, 'searching', 'Creating…');
      setBusy([btnCr, btnJn], true);

      if (roomBx) {
        var url = window.location.origin + '/r/' + encodeURIComponent(roomId);
        roomBx.innerHTML =
          '<div class="room-link-label">Share this room ID with your contact:</div>' +
          '<div class="room-link-row">' +
            '<input type="text" class="input room-link-input" value="' + url.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '" readonly spellcheck="false" />' +
            '<button class="btn btn-sm btn-ghost" data-copy="' + roomId.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '"><i class="fa-solid fa-copy"></i> Copy</button>' +
          '</div>' +
          '<div class="room-link-hint">Or share the code: <code class="room-code">' + roomId + '</code></div>';
        roomBx.classList.add('visible');
      }

      var room = new P2P.Room(roomId);
      currentRoom = room;

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connW, connL, 'connected', 'Connected');
          showChat();
          setBusy([btnCr, btnJn], false);
          return;
        }
        if (s === 'disconnected' || s === 'connection error') {
          setConn(connW, connL, 'error', 'Disconnected');
          hideChat();
          cleanup();
          setBusy([btnCr, btnJn], false);
          return;
        }
        setConn(connW, connL, 'searching', s);
      };

      room.onMessage = function (text) {
        addChat(text, 'received');
      };

      room.onError = function (msg) {
        setConn(connW, connL, 'error', msg);
        cleanup();
        setBusy([btnCr, btnJn], false);
      };

      try { await room.create(); } catch (e) {
        setConn(connW, connL, 'error', e.message);
        cleanup();
        setBusy([btnCr, btnJn], false);
      }
    });
  }

  if (btnJn) {
    btnJn.addEventListener('click', async function () {
      cleanup();
      hideChat();
      chatMsg.innerHTML = '<div class="chat-empty"><div class="chat-empty-icon"><i class="fa-solid fa-comment-dots"></i></div><p>Connected. Start typing to chat.</p></div>';

      var roomId = rInput ? rInput.value.trim() : '';
      if (!roomId) { setConn(connWR, connLR, 'error', 'Enter Room ID'); return; }
      var m = roomId.match(/\/r\/([^\/?#]+)/);
      if (m) roomId = m[1];

      setConn(connWR, connLR, 'searching', 'Joining…');
      setBusy([btnCr, btnJn], true);

      var room = new P2P.Room(roomId);
      currentRoom = room;

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connWR, connLR, 'connected', 'Connected');
          showChat();
          setBusy([btnCr, btnJn], false);
          return;
        }
        if (s === 'disconnected' || s === 'connection error') {
          setConn(connWR, connLR, 'error', 'Disconnected');
          hideChat();
          cleanup();
          setBusy([btnCr, btnJn], false);
          return;
        }
        setConn(connWR, connLR, 'searching', s);
      };

      room.onMessage = function (text) {
        addChat(text, 'received');
      };

      room.onError = function (msg) {
        setConn(connWR, connLR, 'error', msg);
        cleanup();
        setBusy([btnCr, btnJn], false);
      };

      try { await room.join(); } catch (e) {
        setConn(connWR, connLR, 'error', e.message);
        cleanup();
        setBusy([btnCr, btnJn], false);
      }
    });
  }

  if (rInput) {
    rInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnJn) btnJn.click(); }
    });
  }

  if (btnSend) {
    btnSend.addEventListener('click', doSend);
  }

  if (chatIn) {
    chatIn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); doSend(); }
    });
  }

  if (btnLv) {
    btnLv.addEventListener('click', function () {
      cleanup();
      hideChat();
      resetUI();
    });
  }

  var preset = document.body.dataset.roomId || '';
  if (preset) {
    if (rInput) rInput.value = preset;
    var recvBtn = document.querySelector('.p2p-mode-btn[data-p2p-mode="recv"]');
    if (recvBtn) recvBtn.click();
    if (btnJn) setTimeout(function () { btnJn.click(); }, 300);
  }

  window.addEventListener('beforeunload', function () { cleanup(); });

})();