(function () {
  'use strict';

  var currentRoom = null;
  var $ = function (id) { return document.getElementById(id); };

  var connW   = $('conn-widget');
  var connWR  = $('conn-widget-recv');
  var connL   = connW.querySelector('.connection-label');
  var connLR  = connWR.querySelector('.connection-label');
  var btnCr   = $('btn-create');
  var btnJn   = $('btn-join');
  var roomBx  = $('room-link-box');
  var rInput  = $('room-id-input');
  var sendF   = $('send-form');
  var fileIn  = $('file-input');
  var filePass = $('file-pass');
  var btnSend = $('btn-send');
  var recvPass = $('recv-pass');
  var recvW   = $('recv-waiting');
  var btnCan  = $('btn-cancel');
  var btnLv   = $('btn-leave');
  var progS   = $('progress-section');
  var progF   = $('progress-fill');
  var progT   = $('progress-text');
  var dCard   = $('download-card');
  var dFn     = $('dl-filename');
  var dSz     = $('dl-filesize');
  var dBtn    = $('btn-download');
  var dropZ   = document.querySelector('.drop-zone');
  var fileNm  = document.querySelector('.file-name');
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

  function showProgress(pct, mode) {
    if (progS) progS.classList.add('visible');
    if (progF) { progF.style.width = pct + '%'; progF.className = 'progress-fill' + (mode === 'receive' ? ' is-receive' : ''); }
    if (progT) progT.textContent = pct + '%';
  }

  function hideProgress() {
    if (progS) progS.classList.remove('visible');
    if (progF) progF.style.width = '0%';
    if (progT) progT.textContent = '';
  }

  function showDownloadCard(blob, name, size) {
    if (dCard) dCard.classList.add('visible');
    if (dFn) dFn.textContent = name;
    if (dSz) dSz.textContent = size;
    if (dBtn) {
      dBtn.onclick = function () {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 10000);
      };
    }
  }

  function hideDownloadCard() {
    if (dCard) dCard.classList.remove('visible');
  }

  function resetUI() {
    if (roomBx) { roomBx.classList.remove('visible'); roomBx.innerHTML = ''; }
    if (sendF) sendF.classList.remove('visible');
    hideProgress();
    hideDownloadCard();
    if (recvW) recvW.classList.remove('visible');
    setConn(connW, connL, 'idle', 'Disconnected');
    setConn(connWR, connLR, 'idle', 'Disconnected');
    setBusy([btnCr, btnJn, btnSend], false);
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
        var url = window.location.origin + '/f/' + encodeURIComponent(roomId);
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

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connW, connL, 'connected', 'Connected');
          showFeature();
          if (sendF) sendF.classList.add('visible');
          setBusy([btnCr, btnJn], false);
          return;
        }
        if (s === 'disconnected' || s === 'connection error') {
          setConn(connW, connL, 'error', 'Disconnected');
          showLobby();
          if (sendF) sendF.classList.remove('visible');
          cleanup();
          setBusy([btnCr, btnJn], false);
          return;
        }
        setConn(connW, connL, 'searching', s);
      };

      room.onProgress = function (pct) { showProgress(pct, 'send'); };

      room.onError = function (msg) {
        setConn(connW, connL, 'error', msg);
        showLobby();
        cleanup();
        setBusy([btnCr, btnJn, btnSend], false);
      };

      try { await room.create(); } catch (e) {
        setConn(connW, connL, 'error', e.message);
        showLobby();
        cleanup();
        setBusy([btnCr, btnJn], false);
      }
    });
  }

  if (btnSend) {
    btnSend.addEventListener('click', async function () {
      if (!currentRoom || !currentRoom.connected) {
        setConn(connW, connL, 'error', 'Not Connected'); return; }
      var pass = filePass ? filePass.value.trim() : '';
      if (!pass) { setConn(connW, connL, 'error', 'Enter Passphrase'); return; }
      var file = fileIn ? fileIn.files[0] : null;
      if (!file) { setConn(connW, connL, 'error', 'Select a File'); return; }

      if (file.size > 104857600) {
        var ok = confirm('File is ' + (file.size / 1048576).toFixed(1) + ' MB. Large files may take time. Continue?');
        if (!ok) return;
      }

      currentRoom.passphrase = pass;
      setConn(connW, connL, 'searching', 'Sending…');
      setBusy([btnSend], true);

      try {
        await currentRoom.sendFile(file);
        setConn(connW, connL, 'connected', 'Sent!');
        if (progT) progT.textContent = 'Complete';
      } catch (e) {
        setConn(connW, connL, 'error', 'Send Failed');
      }
      setBusy([btnSend], false);
    });
  }

  if (btnJn) {
    btnJn.addEventListener('click', async function () {
      cleanup();
      hideProgress();
      hideDownloadCard();

      var roomId = rInput ? rInput.value.trim() : '';
      if (!roomId) { setConn(connWR, connLR, 'error', 'Enter Room ID'); return; }
      var m = roomId.match(/\/f\/([^\/?#]+)/);
      if (m) roomId = m[1];
      var passphrase = recvPass ? recvPass.value.trim() : '';
      if (!passphrase) { setConn(connWR, connLR, 'error', 'Enter Passphrase'); return; }

      setConn(connWR, connLR, 'searching', 'Joining…');
      setBusy([btnCr, btnJn], true);

      var room = new P2P.Room(roomId);
      currentRoom = room;
      room.passphrase = passphrase;

      room.onStatus = function (s) {
        if (room !== currentRoom) return;
        if (s === 'connected') {
          setConn(connWR, connLR, 'connected', 'Connected');
          showFeature();
          if (recvW) recvW.classList.add('visible');
          setBusy([btnCr, btnJn], false);
          return;
        }
        if (s === 'disconnected' || s === 'connection error') {
          setConn(connWR, connLR, 'error', 'Disconnected');
          showLobby();
          if (recvW) recvW.classList.remove('visible');
          hideProgress();
          cleanup();
          setBusy([btnCr, btnJn], false);
          return;
        }
        if (s.indexOf('Receiving:') === 0) {
          setConn(connWR, connLR, 'receiving', 'Receiving…');
          return;
        }
        if (s === 'decrypting') {
          setConn(connWR, connLR, 'decrypting', 'Decrypting…');
          return;
        }
        setConn(connWR, connLR, 'searching', s);
      };

      room.onProgress = function (pct) { showProgress(pct, 'receive'); };

      room.onError = function (msg) {
        setConn(connWR, connLR, 'error', msg);
        showLobby();
        if (recvW) recvW.classList.remove('visible');
        hideProgress();
        cleanup();
        setBusy([btnCr, btnJn], false);
      };

      room.onFileReceived = function (blob, name) {
        if (recvW) recvW.classList.remove('visible');
        hideProgress();
        setConn(connWR, connLR, 'connected', 'Received!');
        var sz = (blob.size / 1024).toFixed(1) + ' KB';
        if (blob.size >= 1048576) sz = (blob.size / 1048576).toFixed(2) + ' MB';
        showDownloadCard(blob, name, sz);
      };

      try { await room.join(); } catch (e) {
        setConn(connWR, connLR, 'error', e.message);
        showLobby();
        cleanup();
        setBusy([btnCr, btnJn], false);
      }
    });
  }

  if (btnCan) {
    btnCan.addEventListener('click', function () {
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

  if (dropZ && fileIn) {
    dropZ.addEventListener('click', function () { fileIn.click(); });

    fileIn.addEventListener('change', function () {
      if (fileIn.files && fileIn.files[0]) {
        var f = fileIn.files[0];
        dropZ.querySelector('p').textContent = 'Selected:';
        var sz = (f.size / 1024).toFixed(1) + ' KB';
        if (f.size >= 1048576) sz = (f.size / 1048576).toFixed(2) + ' MB';
        if (fileNm) fileNm.textContent = f.name + ' (' + sz + ')';
      }
    });

    ['dragenter', 'dragover'].forEach(function (ev) {
      dropZ.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        dropZ.classList.add('is-dragover');
      }, false);
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      dropZ.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        dropZ.classList.remove('is-dragover');
      }, false);
    });
    dropZ.addEventListener('drop', function (e) {
      if (e.dataTransfer.files.length) {
        if (fileIn) {
          try { fileIn.files = e.dataTransfer.files; } catch (ex) {}
          fileIn.dispatchEvent(new Event('change'));
        }
      }
    }, false);
  }

  if (rInput) {
    rInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnJn) btnJn.click(); }
    });
  }
  if (recvPass) {
    recvPass.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnJn) btnJn.click(); }
    });
  }
  if (filePass) {
    filePass.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnSend) btnSend.click(); }
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