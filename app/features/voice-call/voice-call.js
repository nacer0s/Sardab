(function () {
  'use strict';

  var currentRoom = null;
  var callActive  = false;
  var callEnded   = false;
  var timerInt    = null;
  var startTime   = 0;

  var $ = function (id) { return document.getElementById(id); };

  var connW  = $('conn-widget');
  var connWR = $('conn-widget-recv');
  var connL  = connW.querySelector('.connection-label');
  var connLR = connWR.querySelector('.connection-label');
  var btnCr  = $('btn-create');
  var btnJn  = $('btn-join');
  var roomBx = $('room-link-box');
  var rInput = $('room-id-input');
  var remAud = $('remote-audio');
  var scrn   = $('call-screen');
  var waves  = $('call-waves');
  var cStat  = $('call-status');
  var cEnded = $('call-ended-text');
  var cTimer = $('call-timer');
  var btnMute = $('btn-mute');
  var btnEnd  = $('btn-end');
  var btnLv   = $('btn-leave');
  var lobby   = $('view-lobby');
  var feature = $('view-feature');

  function showLobby() {
    if (feature) feature.classList.remove('is-visible');
    if (lobby) setTimeout(function () { lobby.classList.remove('is-hidden'); }, 50);
    document.body.classList.remove('is-in-call');
  }

  function showFeature() {
    if (lobby) lobby.classList.add('is-hidden');
    if (feature) setTimeout(function () { feature.classList.add('is-visible'); }, 300);
    document.body.classList.add('is-in-call');
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

  function startCallTimer() {
    showFeature();
    if (!callActive) {
      callActive = true;
      callEnded = false;
      startTime = Date.now();
      if (scrn) { scrn.classList.add('visible'); scrn.classList.remove('is-ended'); }
      if (cStat) cStat.textContent = 'Call Active';
      if (cTimer) cTimer.textContent = '00:00';
      timerInt = setInterval(function () {
        var sec = Math.floor((Date.now() - startTime) / 1000);
        var m = String(Math.floor(sec / 60)).padStart(2, '0');
        var s = String(sec % 60).padStart(2, '0');
        if (cTimer) cTimer.textContent = m + ':' + s;
      }, 1000);
    }
  }

  function stopCallTimer() {
    callActive = false;
    if (startTime > 0) {
      var seconds = Math.floor((Date.now() - startTime) / 1000);
      var month = new Date().toISOString().slice(0, 7); // "2026-06"
      var key = 'sardab_stats';
      var stats = JSON.parse(localStorage.getItem(key) || '{}');
      if (!stats[month]) stats[month] = { voice: 0, video: 0, meeting: 0 };
      stats[month].voice += seconds;
      localStorage.setItem(key, JSON.stringify(stats));
      startTime = 0;
    }
    if (timerInt) { clearInterval(timerInt); timerInt = null; }
    if (cTimer) cTimer.textContent = '';
    if (cStat) cStat.textContent = '';
  }

  function showEnded() {
    callEnded = true;
    if (waves) waves.style.display = 'none';
    if (scrn) {
      scrn.classList.add('is-ended');
      var avatar = scrn.querySelector('.call-avatar');
      if (avatar) { avatar.innerHTML = '<i class="fa-solid fa-phone-slash"></i>'; avatar.classList.add('is-muted'); }
    }
  }

  function resetUI() {
    document.body.classList.remove('is-in-call');
    if (roomBx) { roomBx.classList.remove('visible'); roomBx.innerHTML = ''; }
    stopCallTimer();
    setConn(connW, connL, 'idle', 'Disconnected');
    setConn(connWR, connLR, 'idle', 'Disconnected');
    setBusy([btnCr, btnJn], false);
    if (remAud) remAud.srcObject = null;
    if (scrn) { scrn.classList.remove('visible', 'is-ended'); }
    if (waves) waves.style.display = '';
    var avatar = scrn ? scrn.querySelector('.call-avatar') : null;
    if (avatar) { avatar.innerHTML = '<i class="fa-solid fa-phone"></i>'; avatar.classList.remove('is-muted'); }
    callActive = false;
    callEnded = false;
  }

  function cleanup() {
    if (currentRoom) { currentRoom.disconnect(); currentRoom = null; }
  }

  if (btnCr) {
    btnCr.addEventListener('click', async function () {
      cleanup();
      resetUI();

      var roomId = 'srdb-' + Math.random().toString(36).substring(2, 10);
      setConn(connW, connL, 'searching', 'Creating…');
      setBusy([btnCr, btnJn], true);

      if (roomBx) {
        var url = window.location.origin + '/call/' + encodeURIComponent(roomId);
        roomBx.innerHTML =
          '<div class="room-link-label">Send this link to your contact:</div>' +
          '<div class="room-link-row">' +
            '<input type="text" class="input room-link-input" value="' + url.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '" readonly spellcheck="false" />' +
            '<button class="btn btn-sm btn-ghost" data-copy="' + roomId.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '"><i class="fa-solid fa-copy"></i> Copy</button>' +
          '</div>' +
          '<div class="room-link-hint">Or share the code: <code class="room-code">' + roomId + '</code></div>';
        roomBx.classList.add('visible');
      }

      var room = new P2P.Room(roomId);
      currentRoom = room;

      room.onAudioStream = function (stream) {
        if (remAud) remAud.srcObject = stream;
      };

      room.onInit = async function (r) {
        try { await r.startAudio(); } catch (e) {
          if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError')
            throw new Error('Mic Permission Denied');
          throw new Error('Mic Unavailable');
        }
      };

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connW, connL, 'connected', 'Connected');
          startCallTimer();
          return;
        }
        if (s === 'disconnected' || s === 'connection error') {
           stopCallTimer();
           showEnded();
           cleanup();
           setBusy([btnCr, btnJn], false);
           if (remAud) remAud.srcObject = null;
           return;
        }
        setConn(connW, connL, 'searching', s);
      };

      room.onError = function (msg) {
        showToast(msg, 'error');
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
      resetUI();

      var roomId = rInput ? rInput.value.trim() : '';
      if (!roomId) { setConn(connWR, connLR, 'error', 'Enter Room ID'); return; }
      var m = roomId.match(/\/call\/([^\/?#]+)/);
      if (m) roomId = m[1];

      setConn(connWR, connLR, 'searching', 'Joining…');
      setBusy([btnCr, btnJn], true);

      var room = new P2P.Room(roomId);
      currentRoom = room;

      room.onAudioStream = function (stream) {
        if (remAud) remAud.srcObject = stream;
      };

      room.onInit = async function (r) {
        try { await r.startAudio(); } catch (e) {
          if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError')
            throw new Error('Mic Permission Denied');
          throw new Error('Mic Unavailable');
        }
      };

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connWR, connLR, 'connected', 'Connected');
          startCallTimer();
          return;
        }
       if (s === 'disconnected' || s === 'connection error') {
           stopCallTimer();
           showEnded();
           cleanup();
           setBusy([btnCr, btnJn], false);
           if (remAud) remAud.srcObject = null;
           return;
        }
        setConn(connWR, connLR, 'searching', s);
      };

      room.onError = function (msg) {
        showToast(msg, 'error');
        setConn(connWR, connLR, 'error', msg);
        cleanup();
        setBusy([btnCr, btnJn], false);
      };

      try { await room.join(); } catch (e) {
        setConn(connWR, connLR, 'error', e.message);
        showToast(e.message, 'error');
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

  if (btnMute) {
    btnMute.addEventListener('click', function () {
      if (!currentRoom) return;
      var muted = currentRoom.toggleMute();
      if (muted === true) {
        btnMute.classList.add('is-muted');
        var avatar = scrn ? scrn.querySelector('.call-avatar') : null;
        if (avatar) avatar.classList.add('is-muted');
      } else if (muted === false) {
        btnMute.classList.remove('is-muted');
        var avatar = scrn ? scrn.querySelector('.call-avatar') : null;
        if (avatar) avatar.classList.remove('is-muted');
      }
    });
  }

  if (btnEnd) {
    btnEnd.addEventListener('click', function () {
      stopCallTimer();
      cleanup();
      showLobby();
      resetUI();
    });
  }

  if (btnLv) {
    btnLv.addEventListener('click', function () {
      cleanup();
      showLobby();
      resetUI();
    });
  }

  var preset = document.body.dataset.roomId || '';
  if (preset && rInput) {
    rInput.value = preset;
    var recvBtn = document.querySelector('.p2p-mode-btn[data-p2p-mode="recv"]');
    if (recvBtn) recvBtn.click();
    if (btnJn) setTimeout(function () { btnJn.click(); }, 300);
  }

  window.addEventListener('beforeunload', function () { cleanup(); });

})();