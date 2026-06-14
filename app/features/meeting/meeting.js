(function () {
  'use strict';

  var currentMeeting = null;
  var callActive     = false;
  var timerInt       = null;
  var startTime      = 0;
  var peerTiles      = {};  // peerId -> tile element
  var myName         = '';

  var $ = function (id) { return document.getElementById(id); };

  var btnLeave   = $('btn-leave');
  var btnEnd     = $('ctrl-end');
  var btnMute    = $('ctrl-mute');
  var btnCam     = $('ctrl-camera');
  var btnScreen  = $('ctrl-screen');
  var btnFile    = $('ctrl-file');
  var fileInput  = $('file-input');
  var vidGrid    = $('video-grid');
  var tileLocal  = $('tile-local');
  var locVid     = $('local-video');
  var locLabel   = $('local-label');
  var chatMsg    = $('chat-messages');
  var chatInput  = $('chat-input');
  var btnSend    = $('btn-send-chat');
  var btnFileShare = $('btn-file-share');
  var statusEl   = $('meeting-status');
  var indicator  = document.querySelector('.meeting-indicator');
  var timerEl    = $('meeting-timer');
  var pcountEl   = $('pcount-num');
  var placeholder = $('meeting-placeholder');
  var roomLinkBx = $('meeting-room-link');
  var chatToggle = $('chat-toggle');
  var chatPanel  = $('meeting-chat');
  var nameInput  = $('name-input');
  var nameBtn    = $('name-save');
  var nameDisplay = $('name-display');
  var nameEditBtn = $('name-edit');

  /* ─── UI Helpers ───────────────────────────────── */

  function setStatus(text, connected) {
    if (statusEl) statusEl.textContent = text;
    if (indicator) indicator.classList.toggle('is-connected', !!connected);
  }

  function setTimer(val) { if (timerEl) timerEl.textContent = val; }
  function setPcount(n) { if (pcountEl) pcountEl.textContent = n; }

  function showPlaceholder(show) {
    if (placeholder) placeholder.style.display = show ? 'flex' : 'none';
  }

  function resetUI() {
    setStatus('Disconnected', false);
    setTimer('00:00');
    setPcount(0);
    showPlaceholder(true);
    if (locVid) locVid.srcObject = null;
    if (btnMute) btnMute.classList.remove('is-muted', 'is-active');
    if (btnCam) btnCam.classList.remove('is-muted', 'is-active');
    if (btnScreen) btnScreen.classList.remove('is-active');
    Object.keys(peerTiles).forEach(function (pid) { removePeerTile(pid); });
    peerTiles = {};
    if (chatMsg) {
      chatMsg.innerHTML = '<div class="meeting-chat-empty"><p>No messages yet. Say hello!</p></div>';
    }
    callActive = false;
    if (timerInt) { clearInterval(timerInt); timerInt = null; }
  }

  function addSystemMsg(text) {
    if (!chatMsg) return;
    var empty = chatMsg.querySelector('.meeting-chat-empty');
    if (empty) empty.remove();
    var el = document.createElement('div');
    el.className = 'meeting-chat-system';
    el.textContent = text;
    chatMsg.appendChild(el);
    chatMsg.scrollTop = chatMsg.scrollHeight;
  }

  function addChatMsg(sender, text) {
    if (!chatMsg) return;
    var empty = chatMsg.querySelector('.meeting-chat-empty');
    if (empty) empty.remove();
    var el = document.createElement('div');
    el.className = 'meeting-chat-msg';
    el.innerHTML = '<span class="meeting-chat-msg-sender">' + esc(sender) + '</span>' +
                   '<span class="meeting-chat-msg-text">' + esc(text) + '</span>';
    chatMsg.appendChild(el);
    chatMsg.scrollTop = chatMsg.scrollHeight;
  }

  function addFileMsg(sender, blob, name) {
    if (!chatMsg) return;
    var empty = chatMsg.querySelector('.meeting-chat-empty');
    if (empty) empty.remove();
    var el = document.createElement('div');
    el.className = 'meeting-chat-msg';
    var url = URL.createObjectURL(blob);
    el.innerHTML =
      '<span class="meeting-chat-msg-sender">' + esc(sender) + '</span>' +
      '<span class="meeting-chat-msg-text">sent a file:</span>' +
      '<div class="meeting-chat-file">' +
        '<span class="meeting-chat-file-icon"><i class="fa-solid fa-file"></i></span>' +
        '<span class="meeting-chat-file-name">' + esc(name) + '</span>' +
        '<a class="btn btn-sm btn-ghost" href="' + url + '" download="' + esc(name) + '"><i class="fa-solid fa-download"></i></a>' +
      '</div>';
    chatMsg.appendChild(el);
    chatMsg.scrollTop = chatMsg.scrollHeight;
  }

  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  /* ─── Name ──────────────────────────────────────── */

  function showNamePrompt() {
    if (!nameInput || !nameBtn || !nameDisplay || !nameEditBtn) return;
    var saved = localStorage.getItem('sardab_meeting_name') || '';
    if (saved) {
      myName = saved;
      if (nameDisplay) nameDisplay.textContent = myName;
      if (nameInput) nameInput.style.display = 'none';
      if (nameBtn) nameBtn.style.display = 'none';
      if (nameDisplay) nameDisplay.style.display = 'inline';
      if (nameEditBtn) nameEditBtn.style.display = 'inline';
      return;
    }
    if (nameDisplay) nameDisplay.style.display = 'none';
    if (nameEditBtn) nameEditBtn.style.display = 'none';
    if (nameInput) nameInput.style.display = 'inline';
    if (nameBtn) nameBtn.style.display = 'inline';
    if (nameInput) nameInput.focus();
  }

  function saveName(name) {
    myName = name || 'Anonymous';
    localStorage.setItem('sardab_meeting_name', myName);
    if (nameDisplay) nameDisplay.textContent = myName;
    if (nameInput) nameInput.style.display = 'none';
    if (nameBtn) nameBtn.style.display = 'none';
    if (nameDisplay) nameDisplay.style.display = 'inline';
    if (nameEditBtn) nameEditBtn.style.display = 'inline';
    if (locLabel) locLabel.textContent = myName;
    if (currentMeeting && currentMeeting.updateName) {
      currentMeeting.updateName(myName);
    }
  }

  function editName() {
    if (nameInput) nameInput.style.display = 'inline';
    if (nameBtn) nameBtn.style.display = 'inline';
    if (nameDisplay) nameDisplay.style.display = 'none';
    if (nameEditBtn) nameEditBtn.style.display = 'none';
    if (nameInput) { nameInput.value = myName; nameInput.focus(); }
  }

  /* ─── Video tiles ─────────────────────────────── */

  function addPeerTile(peerId, peerName) {
    if (peerTiles[peerId]) return;
    var tile = document.createElement('div');
    tile.className = 'video-tile';
    tile.id = 'tile-' + peerId;

    var avatar = document.createElement('div');
    avatar.className = 'video-tile-avatar';
    avatar.innerHTML = '<i class="fa-solid fa-user"></i>';
    tile.appendChild(avatar);

    var label = document.createElement('span');
    label.className = 'video-tile-label';
    label.textContent = peerName || peerId.substring(0, 10);
    tile.appendChild(label);

    vidGrid.appendChild(tile);
    peerTiles[peerId] = tile;
    showPlaceholder(false);
  }

  function removePeerTile(peerId) {
    var tile = peerTiles[peerId];
    if (tile) {
      var vid = tile.querySelector('video');
      if (vid) { vid.srcObject = null; vid.remove(); }
      tile.remove();
      delete peerTiles[peerId];
    }
    var tileCount = vidGrid.querySelectorAll('.video-tile:not(.video-tile-local)').length;
    if (tileCount === 0) showPlaceholder(true);
  }

  function setPeerVideo(peerId, stream) {
    var tile = peerTiles[peerId];
    if (!tile) return;
    var existing = tile.querySelector('video');
    if (existing) {
      existing.srcObject = stream;
      return;
    }
    var avatar = tile.querySelector('.video-tile-avatar');
    if (avatar) avatar.remove();
    var video = document.createElement('video');
    video.autoplay = true;
    video.playsinline = true;
    video.srcObject = stream;
    tile.insertBefore(video, tile.firstChild);
  }

  function updatePeerLabel(peerId, name) {
    var tile = peerTiles[peerId];
    if (tile) {
      var label = tile.querySelector('.video-tile-label');
      if (label) label.textContent = name;
    }
  }

  /* ─── Timer ───────────────────────────────────── */

  function startMeetingTimer() {
    if (!callActive) {
      callActive = true;
      startTime = Date.now();
      setTimer('00:00');
      timerInt = setInterval(function () {
        var sec = Math.floor((Date.now() - startTime) / 1000);
        setTimer(String(Math.floor(sec / 60)).padStart(2, '0') + ':' + String(sec % 60).padStart(2, '0'));
      }, 1000);
    }
  }

  function stopMeetingTimer() {
    callActive = false;
    if (timerInt) { clearInterval(timerInt); timerInt = null; }
  }

  /* ─── Meeting link box ────────────────────────── */

  function showRoomLink(roomId) {
    if (!roomLinkBx) return;
    var url = window.location.origin + '/m/' + encodeURIComponent(roomId);
    roomLinkBx.innerHTML =
      '<div class="room-link-label">Invite link:</div>' +
      '<div class="room-link-row">' +
        '<input type="text" class="input room-link-input" value="' + esc(url) + '" readonly spellcheck="false" />' +
        '<button class="btn btn-sm btn-ghost" data-copy="' + esc(roomId) + '"><i class="fa-solid fa-copy"></i> Copy</button>' +
      '</div>';
  }

  /* ─── Cleanup ─────────────────────────────────── */

  function cleanup() {
    if (currentMeeting) { currentMeeting.disconnect(); currentMeeting = null; }
  }

  /* ─── Init ────────────────────────────────────── */

 function initMeeting(roomId) {
  cleanup();
  resetUI();
  showNamePrompt();
  setStatus('Initializing…', false);
  showPlaceholder(true);

  if (locLabel) locLabel.textContent = myName || 'You';

  var meeting = new Meeting.Meeting(roomId);
  currentMeeting = meeting;

  // ✅ CORRIGÉ — recordActivity ajouté ici, pas de doublon
  meeting.onParticipantJoin = function (peerId, peerName) {
    addPeerTile(peerId, peerName || peerId.substring(0, 8));
    addSystemMsg((peerName || peerId.substring(0, 8)) + ' joined');
    setPcount(meeting.participants.length);

    if (meeting.participants.length === 1) {
      recordActivity('sardab_activity');
      recordSessionMinutes('meeting');
    }
  };

  meeting.onParticipantLeave = function (peerId, peerName) {
    removePeerTile(peerId);
    addSystemMsg((peerName || peerId.substring(0, 8)) + ' left');
    setPcount(meeting.participants.length);
  };

  meeting.onMessage = function (text, fromPeerId) {
    addChatMsg(meeting.getPeerName(fromPeerId), text);
  };

  meeting.onFileReceived = function (blob, name, fromPeerId) {
    addFileMsg(meeting.getPeerName(fromPeerId), blob, name);
  };

  meeting.onAudioStream = function (stream, peerId) {
    setPeerVideo(peerId, stream);
  };

  meeting.onVideoStream = function (stream, peerId) {
    setPeerVideo(peerId, stream);
  };

  meeting.onScreenStream = function (stream) {
    if (stream && locVid) {
      locVid.srcObject = stream;
    } else if (!stream && locVid && currentMeeting && currentMeeting.localStream) {
      locVid.srcObject = currentMeeting.localStream;
    }
  };

  meeting.onNameChange = function (peerId, newName, oldName) {
    updatePeerLabel(peerId, newName);
    addSystemMsg((oldName || peerId.substring(0, 8)) + ' is now ' + newName);
  };

  meeting.onError = function (msg) {
    if (window.showToast) showToast(msg, 'error');
  };

  (async function () {
    try {
      await meeting.init(myName);
      setStatus('Connected', true);
      startMeetingTimer();
      showRoomLink(roomId);
      setPcount(meeting.participants.length);
      showPlaceholder(meeting.participants.length === 0);
    } catch (e) {
      setStatus('Failed: ' + e.message, false);
      if (window.showToast) showToast(e.message, 'error');
    }
  })();
}

  /* ─── Auto-join ───────────────────────────────── */

  var preset = document.body.dataset.roomId || '';
  var roomId = preset || 'meet-' + Math.random().toString(36).substring(2, 10);
  initMeeting(roomId);

  /* ─── Name controls ───────────────────────────── */

  if (nameInput) {
    nameInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (nameBtn) nameBtn.click(); }
    });
  }

  if (nameBtn) {
    nameBtn.addEventListener('click', function () {
      if (nameInput) saveName(nameInput.value.trim() || 'Anonymous');
    });
  }

  if (nameEditBtn) {
    nameEditBtn.addEventListener('click', editName);
  }

  /* ─── Controls ────────────────────────────────── */

  if (btnMute) {
    btnMute.addEventListener('click', function () {
      if (!currentMeeting) return;
      var muted = currentMeeting.toggleMute();
      if (muted === true) { btnMute.classList.remove('is-muted'); btnMute.classList.add('is-active'); }
      else if (muted === false) { btnMute.classList.add('is-muted'); btnMute.classList.remove('is-active'); }
    });
  }

  if (btnCam) {
    btnCam.addEventListener('click', function () {
      if (!currentMeeting) return;
      if (currentMeeting._videoTrack) {
        var on = currentMeeting.toggleCamera();
        if (on === true) { btnCam.classList.remove('is-muted'); btnCam.classList.add('is-active'); }
        else if (on === false) { btnCam.classList.add('is-muted'); btnCam.classList.remove('is-active'); }
      } else {
        btnCam.classList.add('is-active');
        currentMeeting.startVideo().then(function (stream) {
          if (locVid) locVid.srcObject = stream;
        }).catch(function (e) {
          btnCam.classList.remove('is-active');
          if (window.showToast) showToast(e.message, 'error');
        });
      }
    });
  }

  if (btnScreen) {
    btnScreen.addEventListener('click', function () {
      if (!currentMeeting) return;
      if (currentMeeting.screenStream) {
        currentMeeting.stopScreenShare();
        btnScreen.classList.remove('is-active');
      } else {
        currentMeeting.startScreenShare().then(function () {
          btnScreen.classList.add('is-active');
        }).catch(function (e) {
          if (window.showToast) showToast(e.message, 'error');
        });
      }
    });
  }

  if (btnFile) {
    btnFile.addEventListener('click', function () { if (fileInput) fileInput.click(); });
  }

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      if (!currentMeeting || !fileInput.files.length) return;
      var file = fileInput.files[0];
      currentMeeting.sendFile(file).then(function () {
        addSystemMsg('You sent: ' + file.name);
        if (window.showToast) showToast('File sent', 'success');
      }).catch(function (e) {
        if (window.showToast) showToast(e.message, 'error');
      });
      fileInput.value = '';
    });
  }

  if (btnSend) {
    btnSend.addEventListener('click', function () {
      if (!currentMeeting || !chatInput) return;
      var text = chatInput.value.trim();
      if (!text) return;
      currentMeeting.sendMessage(text);
      addChatMsg(myName || 'You', text);
      chatInput.value = '';
    });
  }

  if (chatInput) {
    chatInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); if (btnSend) btnSend.click(); }
    });
  }

  if (btnFileShare) {
    btnFileShare.addEventListener('click', function () { if (fileInput) fileInput.click(); });
  }

  if (chatToggle) {
    chatToggle.addEventListener('click', function () {
      if (chatPanel) chatPanel.classList.toggle('is-collapsed');
    });
  }

  function endMeeting() {
    stopMeetingTimer();
    cleanup();
    resetUI();
    roomId = 'meet-' + Math.random().toString(36).substring(2, 10);
    initMeeting(roomId);
  }

  if (btnEnd) btnEnd.addEventListener('click', endMeeting);

  if (btnLeave) {
    btnLeave.addEventListener('click', function () {
      stopMeetingTimer();
      cleanup();
      window.location.href = '/app';
    });
  }

  window.addEventListener('beforeunload', function () { cleanup(); });

})();
