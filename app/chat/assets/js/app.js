(function() {
  'use strict';

  const BASE_PATH = window.location.pathname.replace(/\/app\/.*$/, '');
  const APP = 'chat';

  const S = {
    sid: sessionStorage.getItem('sardab-chat-sid') || Math.random().toString(36).substring(2,10),
    room: window.ROOM_CODE || '',
    name: sessionStorage.getItem('sardab-chat-name') || '',
    users: [], lastId: 0,
    peers: {}, encKey: null, msgs: [],
    peerConnected: false, isCreator: false,
    makingOffer: false, msgId: 0, replyTo: null,
    transport: null, pendingInit: {}
  };

  const STUN = {iceServers:[{urls:'stun:stun.l.google.com:19302'},{urls:'stun:stun1.l.google.com:19302'},
    {urls:'turn:openrelay.metered.ca:80',username:'openrelayproject',credential:'openrelayproject'}]};
  const fileBufs = {};

  function esc(s) {
    if (typeof s !== 'string') return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  async function joinRoom() {
    S.isCreator = sessionStorage.getItem('sardab-chat-creator') === '1';
    S.encKey = await Crypto.deriveKey(S.room);
    S.users = [{sid: S.sid, name: S.name}];
    renderChat();
    S.transport = createSignalingTransport(APP);
    S.transport.onSignal(handleSignal);
    S.transport.onUsers(handleUsers);
    S.transport.onDisconnect(() => { S.peerConnected = false; updateEncStatus(); });
    const res = await S.transport.connect(S.room, S.sid, S.name, S.isCreator);
    if (!res || !res.ok) { console.error('Failed to join room'); return; }
    S.isCreator = !!res.creator;
  }

  function handleUsers(users) {
    const now = Date.now();
    S.users = (users || []).map(u => ({ sid: u.sid, name: u.name, online: u.online !== false }));
    if (!S.users.some(u => u.sid === S.sid)) S.users.push({sid:S.sid, name:S.name, online:true});
    renderUsers();
    updateUserCount();
    S.users.forEach(u => {
      if (u.sid !== S.sid && !S.peers[u.sid] && !S.pendingInit[u.sid]) {
        if (S.sid < u.sid) {
          S.pendingInit[u.sid] = setTimeout(function() { delete S.pendingInit[u.sid]; initPC(u.sid, true); }, 100);
        } else {
          initPC(u.sid, false);
        }
      }
    });
  }

  function initPC(targetSid, initiator) {
    if (S.peers[targetSid]) return;
    try {
      const pc = new RTCPeerConnection(STUN);
      let dc = null;
      S.makingOffer = false;

      pc.onicecandidate = e => {
        if (e.candidate) {
          S.transport.sendSignal({type:'candidate', candidate:e.candidate.toJSON(), target:targetSid});
        }
      };

      pc.onsignalingstatechange = () => {
        if (pc.signalingState === 'closed' || pc.signalingState === 'stable') S.makingOffer = false;
      };

      pc.oniceconnectionstatechange = () => {
        if (pc.iceConnectionState === 'disconnected' || pc.iceConnectionState === 'failed' || pc.iceConnectionState === 'closed') cleanupPC(targetSid);
      };

      pc.ondatachannel = e => {
        dc = e.channel;
        setupDC(dc, targetSid);
        if (S.peers[targetSid]) S.peers[targetSid].dc = dc;
      };

      if (initiator) {
        dc = pc.createDataChannel('chat');
        setupDC(dc, targetSid);
        makeOffer(pc, targetSid);
      }

      S.peers[targetSid] = {pc, dc, connected: false, pendingCandidates: [], _retries: 0};
      if (initiator) setTimeout(function() { reconnectCheck(targetSid); }, 5000);
    } catch(e) { console.error('initPC err', e); }
  }

  function reconnectCheck(targetSid) {
    var p = S.peers[targetSid];
    if (!p || p.connected) return;
    if (p._retries >= 3) return;
    p._retries++;
    cleanupPC(targetSid);
    initPC(targetSid, true);
  }

  async function makeOffer(pc, targetSid) {
    try {
      S.makingOffer = true;
      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      await waitForIceGathering(pc);
      const finalOffer = pc.localDescription;
      S.transport.sendSignal({type:'offer', sdp:finalOffer.sdp, target:targetSid});
    } catch(e) { console.error('makeOffer err', e); }
    finally { S.makingOffer = false; }
  }

  function waitForIceGathering(pc) {
    return new Promise(resolve => {
      if (pc.iceGatheringState === 'complete') { resolve(); return; }
      const check = () => {
        if (pc.iceGatheringState === 'complete') {
          pc.removeEventListener('icegatheringstatechange', check);
          resolve();
        }
      };
      pc.addEventListener('icegatheringstatechange', check);
      setTimeout(() => { pc.removeEventListener('icegatheringstatechange', check); resolve(); }, 3000);
    });
  }

  function setupDC(dc, targetSid) {
    dc.onopen = () => {
      if (S.peers[targetSid]) S.peers[targetSid].connected = true;
      S.peerConnected = Object.values(S.peers).some(p => p.connected);
      updateEncStatus();
      try { dc.bufferedAmountLowThreshold = 16384; } catch(e) {}
    };
    dc.onclose = () => {
      if (S.peers[targetSid]) S.peers[targetSid].connected = false;
      S.peerConnected = Object.values(S.peers).some(p => p.connected);
      updateEncStatus();
    };
    dc.onmessage = async e => {
      if (!S.encKey) return;
      try {
        handleMsg(JSON.parse(await Crypto.decrypt(S.encKey, e.data)));
      } catch(err) { console.error('decrypt err', err); }
    };
  }

  function handleMsg(msg) {
    if (msg.type === 'file') handleFileChunk(msg);
    else if (msg.type === 'text') { S.msgs.push(msg); renderMsg(msg, false); }
    else if (msg.type === 'delete') handleDeleteMsg(msg);
    else if (msg.type === 'typing') handleTyping(msg);
    else if (msg.type === 'reaction') handleReaction(msg);
  }

  function handleDeleteMsg(msg) {
    if (!msg.targetId) return;
    const existing = document.querySelector('.message[data-msg-id="' + msg.targetId + '"]');
    if (existing) existing.remove();
  }

  function handleTyping(msg) {
    const el = document.getElementById('typingIndicator');
    if (!el) return;
    if (msg.isTyping) {
      el.textContent = i18n.t('chat.typing', {name: msg.name});
      el.classList.add('visible');
      clearTimeout(el._typingTimer);
      el._typingTimer = setTimeout(() => el.classList.remove('visible'), 2000);
    } else {
      el.classList.remove('visible');
    }
  }

  function handleReaction(msg) {
    if (!msg.msgId || !msg.reaction || !msg.action) return;
    const target = S.msgs.find(m => m.id === msg.msgId);
    if (!target) return;
    if (!target.reactions) target.reactions = {};
    if (msg.action === 'remove') {
      if (target.reactions[msg.reaction]) {
        delete target.reactions[msg.reaction][msg.sender];
        if (Object.keys(target.reactions[msg.reaction]).length === 0) delete target.reactions[msg.reaction];
      }
    } else if (msg.action === 'add') {
      for (const emoji in target.reactions) {
        if (target.reactions[emoji][msg.sender]) { delete target.reactions[emoji][msg.sender]; if (Object.keys(target.reactions[emoji]).length === 0) delete target.reactions[emoji]; }
      }
      if (!target.reactions[msg.reaction]) target.reactions[msg.reaction] = {};
      target.reactions[msg.reaction][msg.sender] = true;
    }
    renderReactions(msg.msgId);
  }

  function handleSendReaction(msgId, reaction) {
    const msg = S.msgs.find(m => m.id === msgId);
    if (!msg) return;
    if (!msg.reactions) msg.reactions = {};
    if (msg.reactions[reaction] && msg.reactions[reaction][S.sid]) {
      const sig = {type:'reaction', action:'remove', msgId, reaction, sender:S.sid, name:S.name};
      broadcast(sig); handleReaction(sig); return;
    }
    let old = null;
    for (const emoji in msg.reactions) { if (msg.reactions[emoji][S.sid]) { old = emoji; break; } }
    if (old) { const rm = {type:'reaction', action:'remove', msgId, reaction:old, sender:S.sid, name:S.name}; broadcast(rm); handleReaction(rm); }
    const sig = {type:'reaction', action:'add', msgId, reaction, sender:S.sid, name:S.name};
    broadcast(sig); handleReaction(sig);
  }

  function renderReactions(msgId) {
    const msgEl = document.querySelector('.message[data-msg-id="' + msgId + '"]');
    if (!msgEl) return;
    const target = S.msgs.find(m => m.id === msgId);
    if (!target || !target.reactions) { const e = msgEl.querySelector('.msg-reactions'); if (e) e.innerHTML = ''; return; }
    let reactEl = msgEl.querySelector('.msg-reactions');
    if (!reactEl) { reactEl = document.createElement('div'); reactEl.className = 'msg-reactions'; msgEl.appendChild(reactEl); }
    reactEl.innerHTML = '';
    for (const emoji of Object.keys(target.reactions).sort()) {
      const count = Object.keys(target.reactions[emoji]).length;
      if (count === 0) continue;
      const r = document.createElement('span');
      r.className = 'reaction-badge'; r.dataset.reaction = emoji;
      r.textContent = emoji + ' ' + count;
      reactEl.appendChild(r);
    }
  }

  function handleFileChunk(msg) {
    if (!msg.id || msg.idx === undefined || !msg.chunk || typeof msg.chunk !== 'string') return;
    if (!fileBufs[msg.id]) {
      fileBufs[msg.id] = {chunks:[], total:msg.total, name:msg.name, fileType:msg.fileType||'', sender:msg.sender, time:msg.time, authorName:msg.authorName};
    }
    fileBufs[msg.id].chunks[msg.idx] = msg.chunk;
    const pending = fileBufs[msg.id];
    let received = 0;
    for (let k = 0; k < pending.total; k++) { if (pending.chunks[k] !== undefined) received++; }
    if (received === pending.total) {
      try {
        var allBytes = [];
        for (var k = 0; k < pending.total; k++) {
          var chunkB64 = pending.chunks[k].replace(/[^A-Za-z0-9+/=]/g, '');
          var binaryStr = atob(chunkB64);
          for (var j = 0; j < binaryStr.length; j++) allBytes.push(binaryStr.charCodeAt(j));
        }
        var bytes = new Uint8Array(allBytes);
        var blob = new Blob([bytes], {type:pending.fileType||'application/octet-stream'});
        var url = URL.createObjectURL(blob);
        S.msgs.push({type:'file_ready', name:pending.name, url, fileType:pending.fileType, time:pending.time, sender:pending.sender, authorName:pending.authorName});
        renderFileMsg({name:pending.name, url, fileType:pending.fileType, time:pending.time, sender:pending.sender, authorName:pending.authorName}, false);
      } catch(e) { console.error('file assemble err', e); }
      delete fileBufs[msg.id];
    }
  }

  function cleanupPC(targetSid) {
    const peer = S.peers[targetSid];
    if (!peer) return;
    if (peer.pc) { try { peer.pc.close(); } catch(e) {} }
    delete S.peers[targetSid];
    if (S.pendingInit[targetSid]) { clearTimeout(S.pendingInit[targetSid]); delete S.pendingInit[targetSid]; }
    S.peerConnected = Object.values(S.peers).some(p => p.connected);
    updateEncStatus();
  }

  function updateEncStatus() {
    const badge = document.getElementById('encBadge');
    if (badge) badge.style.display = (S.peerConnected && S.encKey) ? 'inline-flex' : 'none';
    const sendBtn = document.getElementById('sendBtn');
    if (sendBtn) sendBtn.disabled = !S.encKey;
    const voiceBtn = document.getElementById('voiceBtn');
    if (voiceBtn && !mediaRec) voiceBtn.disabled = !(S.peerConnected && S.encKey);
    flushMsgQueue();
  }

  var msgQueue = [];

  function flushMsgQueue() {
    if (!S.peerConnected || !S.encKey || !msgQueue.length) return;
    for (const msg of msgQueue) broadcast(msg);
    msgQueue = [];
  }

  async function handleSignal(sig) {
    if (sig.t === 'join' && sig.f !== S.sid) {
      const exists = S.users.find(u => u.sid === sig.f);
      if (exists) {
        exists.online = true;
      } else {
        S.users.push({sid:sig.f, name:(sig.d||{}).name||'User', online:true});
      }
      renderUsers();
      updateUserCount();
      if (!S.peers[sig.f]) {
        if (S.sid < sig.f) {
          S.pendingInit[sig.f] = setTimeout(function() { delete S.pendingInit[sig.f]; initPC(sig.f, true); }, 100);
        } else {
          initPC(sig.f, false);
        }
      }
    }
    if (sig.t === 'leave' && sig.f !== S.sid) {
      S.users = S.users.filter(u => u.sid !== sig.f);
      renderUsers();
      updateUserCount();
      cleanupPC(sig.f);
    }
    if (sig.t === 'signal' && sig.f !== S.sid) {
      const d = sig.d;
      if (!d) return;
      if (d.target && d.target !== S.sid) return;

      if (d.type === 'offer') {
        if (!S.peers[sig.f]) initPC(sig.f, false);
        const peer = S.peers[sig.f];
        if (!peer || !peer.pc) return;
        const pc = peer.pc;
        if (pc.signalingState !== 'stable') {
          try { await pc.setLocalDescription({type: 'rollback'}); } catch (e) {}
        }
        try {
          await pc.setRemoteDescription(new RTCSessionDescription({type:'offer', sdp:d.sdp}));
          const answer = await pc.createAnswer();
          await pc.setLocalDescription(answer);
          await waitForIceGathering(pc);
          const finalAnswer = pc.localDescription;
          S.transport.sendSignal({type:'answer', sdp:finalAnswer.sdp, target:sig.f});
        } catch(e) { console.error('offer handle err', e); }
      }
      if (d.type === 'answer') {
        const peerA = S.peers[sig.f];
        if (peerA && peerA.pc && peerA.pc.signalingState === 'have-local-offer') {
          try { await peerA.pc.setRemoteDescription(new RTCSessionDescription({type:'answer', sdp:d.sdp})); } catch(e) {}
        }
      }
      if (d.type === 'candidate') {
        const peerC = S.peers[sig.f];
        if (peerC && peerC.pc && peerC.pc.remoteDescription && peerC.pc.remoteDescription.type) {
          try { await peerC.pc.addIceCandidate(new RTCIceCandidate(d.candidate)); } catch(e) {}
        } else if (peerC && peerC.pc) {
          if (!peerC.pendingCandidates) peerC.pendingCandidates = [];
          peerC.pendingCandidates.push(d.candidate);
        }
      }
    }
  }

  async function sendText(text) {
    if (!text.trim()) return;
    const msg = {type:'text', text:text.trim(), name:S.name, time:Date.now(), sender:S.sid, id: 'm' + (++S.msgId)};
    if (S.replyTo) {
      const orig = S.msgs.find(m => m.id === S.replyTo.id);
      if (orig) msg.replyTo = { id: orig.id, name: orig.name, text: orig.text };
    }
    S.msgs.push(msg);
    renderMsg(msg, true);
    if (S.peerConnected && S.encKey) broadcast(msg);
    else msgQueue.push(msg);
  }

  async function broadcast(msg) {
    if (!S.encKey) return;
    try {
      const k = S.encKey || await Crypto.deriveKey(S.room);
      const enc = await Crypto.encrypt(k, msg);
      for (const sid in S.peers) {
        const p = S.peers[sid];
        if (p.connected && p.dc && p.dc.readyState === 'open') {
          try { p.dc.send(enc); } catch(e) { console.error('dc send err', e); }
        }
      }
    } catch(e) { console.error('broadcast err', e); }
  }

  async   function sendFile(file, localUrl) {
    if (!S.peerConnected || !S.encKey) { alert('No connection'); return; }
    if (file.size > 10 * 1024 * 1024) { alert('File must be under 10MB'); return; }
    const fileId = S.sid + '-' + Math.random().toString(36).substring(2,10);
    const arrayBuffer = await file.arrayBuffer();
    const chunkSize = 4 * 1024;
    const total = Math.ceil(arrayBuffer.byteLength / chunkSize);
    const t = Date.now();
    const fileMsg = {name:file.name, id:fileId, fileType:file.type, time:t, sender:S.sid, url:localUrl || '', authorName:S.name};
    S.msgs.push({type:'file_meta', name:file.name, total, id:fileId, fileType:file.type, time:t, sender:S.sid, url:localUrl || '', authorName:S.name});
    renderFileMsg(fileMsg, true);
    for (let i = 0; i < total; i++) {
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, arrayBuffer.byteLength);
      const chunk = new Uint8Array(arrayBuffer.slice(start, end));
      let bin = '';
      for (let j = 0; j < chunk.length; j++) bin += String.fromCharCode(chunk[j]);
      const b64 = btoa(bin);
      await broadcast({type:'file', name:file.name, chunk:b64, idx:i, total, id:fileId, time:t, sender:S.sid, fileType:file.type, authorName:S.name});
      await flushDC();
    }
  }

  async function flushDC() {
    var peers = [];
    for (const sid in S.peers) {
      const p = S.peers[sid];
      if (p.connected && p.dc && p.dc.readyState === 'open' && p.dc.bufferedAmount > 64000) peers.push(p);
    }
    if (!peers.length) return;
    // re-check: buffer may have drained between first check and now
    var filtered = [];
    for (var i = 0; i < peers.length; i++) if (peers[i].dc.bufferedAmount > 64000) filtered.push(peers[i]);
    if (!filtered.length) return;
    await new Promise(function(resolve){
      var n = filtered.length, done = false;
      var timer = setTimeout(function(){ done = true; resolve(); }, 3000);
      for (var i = 0; i < n; i++) {
        filtered[i].dc.addEventListener('bufferedamountlow', function h(){
          if (done) return;
          if (!--n || n <= 0) { done = true; clearTimeout(timer); resolve(); }
        });
      }
    });
  }

  var mediaRec = null; var recChunks = []; var recStream = null;

  function toggleVoiceRecord() {
    const btn = document.getElementById('voiceBtn');
    if (!btn) return;
    if (mediaRec && mediaRec.state === 'recording') {
      mediaRec.stop();
      return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { alert('Voice recording not supported'); return; }
    navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
      recStream = stream; recChunks = [];
      mediaRec = new MediaRecorder(stream, {mimeType: MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm'});
      mediaRec.ondataavailable = function(e){if(e.data.size>0)recChunks.push(e.data)};
      mediaRec.onstop = function(){
        btn.classList.remove('recording');
        if (recChunks.length) {
          const blob = new Blob(recChunks, {type:'audio/webm'});
          const localUrl = URL.createObjectURL(blob);
          if (!S.peerConnected || !S.encKey) { alert('No connection'); cleanupRec(); return; }
          sendFile(new File([blob], 'voice-note-' + Date.now() + '.webm', {type:'audio/webm'}), localUrl);
        }
        cleanupRec();
      };
      mediaRec.onerror = function(){cleanupRec(); btn.classList.remove('recording')};
      mediaRec.start();
      btn.classList.add('recording');
    }).catch(function(){alert('Microphone access denied')});
  }

  function cleanupRec() {
    if (mediaRec) { try { if (mediaRec.state !== 'inactive') mediaRec.stop(); } catch(e) {} mediaRec = null; }
    if (recStream) { recStream.getTracks().forEach(function(t){t.stop()}); recStream = null; }
    recChunks = [];
  }

  var vpAudio = null; var vpPlayer = null; var vpUrl = null;

  function toggleVoicePlayer(player) {
    var url = player.getAttribute('data-url');
    if (!url) return;
    if (vpPlayer === player && vpAudio) {
      if (!vpAudio.paused) {
        vpAudio.pause();
        player.querySelector('.vp-play i').className = 'fa-solid fa-play';
        return;
      }
      vpAudio.play().then(function(){
        player.querySelector('.vp-play i').className = 'fa-solid fa-pause';
      });
      return;
    }
    if (vpAudio) { vpAudio.pause(); vpAudio.src = ''; vpAudio = null; }
    if (vpPlayer) {
      vpPlayer.querySelector('.vp-play i').className = 'fa-solid fa-play';
      vpPlayer.querySelector('.vp-fill').style.width = '0%';
      vpPlayer.querySelector('.vp-time').textContent = '0:00';
    }
    vpAudio = new Audio(url);
    vpPlayer = player;
    vpUrl = url;
    vpAudio.onloadedmetadata = function(){};
    vpAudio.ontimeupdate = function(){
      if (!vpAudio || !vpPlayer) return;
      var pct = vpAudio.currentTime / vpAudio.duration * 100;
      vpPlayer.querySelector('.vp-fill').style.width = (isNaN(pct)?0:pct) + '%';
      var m = Math.floor(vpAudio.currentTime / 60);
      var s = Math.floor(vpAudio.currentTime % 60);
      vpPlayer.querySelector('.vp-time').textContent = m + ':' + (s<10?'0':'') + s;
    };
    vpAudio.onended = function(){
      if (vpPlayer) vpPlayer.querySelector('.vp-play i').className = 'fa-solid fa-play';
      vpAudio = null; vpPlayer = null; vpUrl = null;
    };
    vpAudio.onerror = function(){ if (vpPlayer) vpPlayer.querySelector('.vp-play i').className = 'fa-solid fa-play'; vpAudio = null; vpPlayer = null; vpUrl = null; };
    vpAudio.play().then(function(){
      player.querySelector('.vp-play i').className = 'fa-solid fa-pause';
    }).catch(function(){
      vpAudio = null; vpPlayer = null; vpUrl = null;
    });
  }

  function seekVoicePlayer(player, seekEl, e) {
    if (!vpAudio || vpPlayer !== player) return;
    var rect = seekEl.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var pct = Math.max(0, Math.min(1, x / rect.width));
    vpAudio.currentTime = pct * vpAudio.duration;
  }

  function renderMsg(msg, isMine) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    const empty = container.parentNode.querySelector('.empty-state');
    if (empty) empty.remove();
    const div = document.createElement('div');
    div.className = 'message ' + (isMine ? 'outgoing' : 'incoming') + ' has-actions';
    div.dataset.msgId = msg.id || '';
    const t = new Date(msg.time || msg.t);
    const ts = String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');
    let replyHtml = '';
    if (msg.replyTo) {
      replyHtml = '<div class="msg-reply"><span class="reply-name">' + esc(msg.replyTo.name||'') + '</span><span class="reply-text">' + esc(msg.replyTo.text||'') + '</span></div>';
    }
    let actionsHtml = '<div class="msg-actions"><button class="msg-action-btn" data-action="reply" title="Reply"><i class="fa-solid fa-reply"></i></button><button class="msg-action-btn" data-action="react" title="React"><i class="fa-solid fa-face-smile"></i></button><button class="msg-action-btn" data-action="delete" title="Delete"><i class="fa-solid fa-trash"></i></button></div>';
    div.innerHTML = '<div class="msg-info"><span class="msg-sender">' + esc(msg.name||msg.sender||'') + '</span><span class="msg-time">' + ts + '</span></div>' + replyHtml + '<div class="bubble"><p class="msg-text">' + esc(msg.text||'') + '</p></div><div class="msg-reactions"></div>' + actionsHtml;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (msg.reactions && Object.keys(msg.reactions).length) renderReactions(msg.id);
  }

  function renderFileMsg(file, isMine) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    const empty = container.parentNode.querySelector('.empty-state');
    if (empty) empty.remove();
    const div = document.createElement('div');
    div.className = 'message ' + (isMine ? 'outgoing' : 'incoming');
    const t = new Date(file.time || '');
    const ts = String(t.getHours()).padStart(2,'0')+':'+String(t.getMinutes()).padStart(2,'0');
    const isVoice = file.name && file.name.indexOf('voice-note-') === 0;
    const isImage = file.fileType && file.fileType.indexOf('image/') === 0;
    const isVideo = file.fileType && file.fileType.indexOf('video/') === 0;
    // Use authorName (display name) if available, fallback to sender ID
    const displayName = file.authorName || file.sender || '';
    let html = '<div class="msg-info"><span class="msg-sender">' + esc(displayName) + '</span><span class="msg-time">' + ts + '</span></div><div class="bubble">';
    if (isVoice && file.url) {
      html += '<div class="msg-voice"><div class="voice-player" data-url="' + esc(file.url) + '"><button class="vp-play"><i class="fa-solid fa-play"></i></button><div class="vp-seek"><div class="vp-track"><div class="vp-fill"></div></div></div><span class="vp-time">0:00</span></div></div>';
    } else if (isImage && file.url) {
      html += '<div class="msg-image"><img src="' + esc(file.url) + '" alt="' + esc(file.name) + '" loading="lazy"></div>';
    } else if (isVideo && file.url) {
      html += '<div class="msg-video"><video src="' + esc(file.url) + '" controls preload="metadata"></video></div>';
    } else {
      html += '<div class="msg-file"><i class="fas fa-paperclip"></i> ';
      if (file.url) html += '<a href="' + file.url + '" download="' + esc(file.name) + '" target="_blank">' + esc(file.name) + '</a>';
      else html += '<span>' + esc(file.name) + '</span>';
      html += '</div>';
    }
    html += '</div><div class="msg-reactions"></div>';
    div.innerHTML = html;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    if (file.id && S.msgs.find(m => m.id === file.id && m.reactions && Object.keys(m.reactions).length)) renderReactions(file.id);
  }

  function renderUsers() {
    const body = document.getElementById('sidebarBody');
    if (!body) return;
    body.innerHTML = '';
    if (!S.users || S.users.length === 0) {
      const html = '<div style="padding:20px;text-align:center;color:#666;font-size:13px;">No users connected</div>';
      body.innerHTML = html;
      syncModalUsers(html);
      return;
    }
    var html = '';
    for (const u of S.users) {
      const isMe = u.sid === S.sid;
      const initial = (u.name||'?').charAt(0);
      const online = u.online !== false;
      html += '<div class="user-item"><div class="user-avatar">' + esc(initial) + '</div><span class="user-name">' + esc(u.name) + (isMe ? ' <span style="font-size:11px;color:#666;">(You)</span>' : '') + '</span><span class="user-status' + (online ? ' online' : '') + '"></span></div>';
    }
    body.innerHTML = html;
    syncModalUsers(html);
  }

  function syncModalUsers(html) {
    const modalBody = document.getElementById('modalBody');
    if (modalBody) modalBody.innerHTML = html;
  }

  function updateUserCount() {
    const sc = document.getElementById('sidebarUserCount');
    if (sc) sc.textContent = S.users.length;
    const mc = document.getElementById('modalUserCount');
    if (mc) mc.textContent = S.users.length;
  }

  function renderChat() {
    const roomBadge = document.getElementById('roomBadge');
    const headerActions = document.getElementById('headerActions');
    if (roomBadge) { roomBadge.textContent = S.room; roomBadge.classList.add('visible'); }
    if (headerActions) headerActions.classList.add('visible');
    updateEncStatus();
    updateUserCount();
    renderUsers();
  }

  async function destroyRoomData() {
    if (S.transport) S.transport.disconnect();
    for (const sid in S.peers) cleanupPC(sid);
    for (const sid in S.pendingInit) { clearTimeout(S.pendingInit[sid]); }
    S.peers = {}; S.peerConnected = false; S.msgs = []; S.encKey = null; S.isCreator = false; S.users = []; S.pendingInit = {};
    sessionStorage.removeItem('sardab-chat-sid');
    sessionStorage.removeItem('sardab-chat-name');
    sessionStorage.removeItem('sardab-chat-room');
    sessionStorage.removeItem('sardab-chat-creator');
  }

  async function leaveRoom() {
    await destroyRoomData();
    window.location.href = BASE_PATH + '/app/chat/';
  }

  let typingTimer = null;

  function setupEvents() {
    document.addEventListener('click', function(e) {
      const t = e.target;
      if (t.id === 'leaveBtn' || t.closest('#leaveBtn')) { if (confirm('Leave room?')) leaveRoom(); return; }
      if (t.id === 'sendBtn' || t.closest('#sendBtn')) { handleSend(); return; }
      if (t.id === 'attachBtn' || t.closest('#attachBtn')) { document.getElementById('fileInput').click(); return; }
      if (t.id === 'voiceBtn' || t.closest('#voiceBtn')) { toggleVoiceRecord(); return; }
      const actionBtn = t.closest('.msg-action-btn');
      if (actionBtn) {
        const msgEl = actionBtn.closest('.message');
        const msgId = msgEl?.dataset?.msgId;
        const action = actionBtn.dataset.action;
        if (!msgId) return;
        const msg = S.msgs.find(m => m.id === msgId);
        if (!msg) return;
        if (action === 'reply') {
          S.replyTo = { id: msgId, name: msg.name, text: msg.text || (msg.fileType ? '📎 ' + (msg.name||'File') : '') };
          showReplyPreview();
        } else if (action === 'delete') {
          showDeleteOptions(msgId, msg.sender === S.sid);
        } else if (action === 'react') {
          showReactionPicker(msgId);
        }
        return;
      }
      const vpBtn = t.closest('.vp-play');
      if (vpBtn) {
        const player = vpBtn.closest('.voice-player');
        if (player) toggleVoicePlayer(player);
        return;
      }
      const vpSeek = t.closest('.vp-seek');
      if (vpSeek) {
        const player = vpSeek.closest('.voice-player');
        if (player) seekVoicePlayer(player, vpSeek, e);
        return;
      }
      if (!t.closest('.msg-action-btn') && !t.closest('.reaction-picker') && !t.closest('.reaction-badge') && !t.closest('.voice-player')) {
        const dMsgEl = t.closest('.message');
        if (dMsgEl && dMsgEl.dataset.msgId) {
          const now = Date.now();
          if (dMsgEl._lastTap && now - dMsgEl._lastTap < 300) { dMsgEl._lastTap = 0; handleSendReaction(dMsgEl.dataset.msgId, '👍'); dMsgEl.classList.add('double-tap-flash'); setTimeout(function(){dMsgEl.classList.remove('double-tap-flash')},300); return; }
          dMsgEl._lastTap = now;
        }
      }
      const reactionBtn = t.closest('.reaction-btn');
      if (reactionBtn) {
        const msgId = reactionBtn.dataset.msgId;
        const reaction = reactionBtn.dataset.reaction;
        if (msgId && reaction) { handleSendReaction(msgId, reaction); document.querySelectorAll('.reaction-picker').forEach(el => el.remove()); }
        return;
      }
      const reactBadge = t.closest('.reaction-badge');
      if (reactBadge) {
        const msgEl = reactBadge.closest('.message');
        const msgId = msgEl?.dataset?.msgId;
        const reaction = reactBadge.dataset.reaction;
        if (msgId && reaction) { handleSendReaction(msgId, reaction); }
        return;
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        const input = document.getElementById('msgInput');
        if (input && document.activeElement === input) { e.preventDefault(); handleSend(); }
      }
    });
    document.getElementById('fileInput').addEventListener('change', async function(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      if (file.size > 10 * 1024 * 1024) { alert('File too large'); this.value = ''; return; }
      try { await sendFile(file); } catch(err) { alert('Failed to send file'); }
      this.value = '';
    });
    const msgInput = document.getElementById('msgInput');
    if (msgInput) {
      msgInput.addEventListener('input', function() {
        if (!S.peerConnected) return;
        if (typingTimer) clearTimeout(typingTimer);
        broadcast({type:'typing', isTyping:true, name:S.name, sender:S.sid});
        typingTimer = setTimeout(() => {
          broadcast({type:'typing', isTyping:false, name:S.name, sender:S.sid});
        }, 2000);
      });
    }
    window.addEventListener('beforeunload', function() { cleanupRec(); destroyRoomData(); });
    window.addEventListener('pagehide', function() { cleanupRec(); destroyRoomData(); });

    document.addEventListener('touchstart', function(e) {
      var el = e.target.closest('.message');
      if (!el || e.target.closest('.msg-action-btn') || e.target.closest('.reaction-picker') || e.target.closest('.reaction-badge')) return;
      el._lpTimer = setTimeout(function() { showReactionPicker(el.dataset.msgId); el._lpFired = true; }, 500);
    }, {passive:true});
    document.addEventListener('touchend', function(e) {
      var el = e.target.closest('.message');
      if (el && el._lpTimer) { clearTimeout(el._lpTimer); delete el._lpTimer; }
    });
    document.addEventListener('touchmove', function(e) {
      var el = e.target.closest('.message');
      if (el && el._lpTimer) { clearTimeout(el._lpTimer); delete el._lpTimer; }
    }, {passive:true});
  }

  var dismissPicker = null;

  function showReactionPicker(msgId) {
    document.querySelectorAll('.reaction-picker').forEach(el => el.remove());
    if (dismissPicker) { document.removeEventListener('click', dismissPicker); dismissPicker = null; }
    const picker = document.createElement('div');
    picker.className = 'reaction-picker';
    const reactions = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
    picker.innerHTML = reactions.map(r => '<button class="reaction-btn" data-msg-id="' + msgId + '" data-reaction="' + r + '">' + r + '</button>').join('');
    document.body.appendChild(picker);
    dismissPicker = function(e) {
      if (!picker.contains(e.target)) { picker.remove(); document.removeEventListener('click', dismissPicker); dismissPicker = null; }
    };
    setTimeout(function() { document.addEventListener('click', dismissPicker); }, 10);
  }

  function showReplyPreview() {
    if (!S.replyTo) return;
    const bar = document.getElementById('chatInputBar');
    if (!bar) return;
    let preview = document.getElementById('replyPreview');
    if (!preview) {
      preview = document.createElement('div');
      preview.id = 'replyPreview';
      preview.className = 'reply-preview';
      bar.parentNode.insertBefore(preview, bar);
    }
    preview.innerHTML = '<span class="reply-preview-text">' + esc(S.replyTo.text) + '</span><button class="reply-preview-close" title="Cancel"><i class="fa-solid fa-xmark"></i></button>';
    preview.querySelector('.reply-preview-close').onclick = cancelReply;
  }

  function cancelReply() {
    S.replyTo = null;
    const preview = document.getElementById('replyPreview');
    if (preview) preview.remove();
  }

  function showDeleteOptions(msgId, isOwnMsg) {
    const existing = document.getElementById('deleteOptions');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = 'deleteOptions';
    modal.className = 'delete-options-modal';
    let html = '<button class="del-opt" data-action="me"><i class="fa-solid fa-user"></i><span data-i18n="delete.me">Delete for me</span></button>';
    if (isOwnMsg) html += '<button class="del-opt del-opt-danger" data-action="all"><i class="fa-solid fa-users"></i><span data-i18n="delete.all">Delete for everyone</span></button>';
    modal.innerHTML = '<div class="backdrop"></div><div class="delete-options-panel"><h3 style="font-size:17px;font-weight:600;margin-bottom:12px" data-i18n="delete.msg">Delete Message</h3>' + html + '</div>';
    document.body.appendChild(modal);
    modal.querySelector('.backdrop').onclick = () => modal.remove();
    modal.querySelectorAll('.del-opt').forEach(btn => {
      btn.onclick = () => {
        const action = btn.dataset.action;
        modal.remove();
        if (action === 'me') confirmDelete(msgId, false);
        else if (action === 'all') confirmDelete(msgId, true);
      };
    });
  }

  function confirmDelete(msgId, forAll) {
    const existing = document.getElementById('confirmModal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'confirm-modal';
    const title = forAll ? i18n.t('delete.all?') : i18n.t('delete.me?');
    const text = forAll ? i18n.t('delete.all.desc') : i18n.t('delete.me.desc');
    modal.innerHTML = '<div class="backdrop"></div><div class="confirm-panel"><h3>' + title + '</h3><p>' + text + '</p><div class="confirm-actions"><button class="confirm-btn cancel" data-i18n="cancel">' + i18n.t('cancel') + '</button><button class="confirm-btn danger" data-i18n="' + (forAll ? 'delete.all' : 'delete.me') + '">' + i18n.t(forAll ? 'delete.all' : 'delete.me') + '</button></div></div>';
    document.body.appendChild(modal);
    modal.querySelector('.backdrop').onclick = () => modal.remove();
    modal.querySelector('.confirm-btn.cancel').onclick = () => modal.remove();
    modal.querySelector('.confirm-btn.danger').onclick = () => { modal.remove(); deleteMsg(msgId, forAll); };
  }

  async function deleteMsg(msgId, forAll) {
    const msg = S.msgs.find(m => m.id === msgId);
    if (!msg) return;
    if (forAll) broadcast({ type: 'delete', targetId: msgId, deleteForAll: true, sender: S.sid });
    const existing = document.querySelector('.message[data-msg-id="' + msgId + '"]');
    if (existing) existing.remove();
  }

  function handleSend() {
    const input = document.getElementById('msgInput');
    if (!input || !input.value.trim()) return;
    sendText(input.value);
    input.value = '';
    cancelReply();
  }

  function toggleSidebar() {
    if (window.innerWidth <= 768) {
      const modal = document.getElementById('usersModal');
      const body = document.getElementById('sidebarBody');
      const modalBody = document.getElementById('modalBody');
      if (!modal) return;
      if (modal.classList.contains('open')) { modal.classList.remove('open'); return; }
      if (body && modalBody) modalBody.innerHTML = body.innerHTML;
      modal.classList.add('open');
      return;
    }
    const sidebar = document.getElementById('usersSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    const isOpen = sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('open', isOpen);
    if (isOpen) sidebar.querySelector('.sidebar-body')?.focus();
  }

  function closeUsersModal() {
    const modal = document.getElementById('usersModal');
    if (modal) modal.classList.remove('open');
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (S.room && S.name) {
      setupEvents();
      joinRoom();
    }
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) backdrop.addEventListener('click', toggleSidebar);
    const usersModal = document.getElementById('usersModal');
    if (usersModal) {
      usersModal.querySelector('.backdrop').addEventListener('click', closeUsersModal);
      usersModal.querySelector('.close-modal').addEventListener('click', closeUsersModal);
    }
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        const sidebar = document.getElementById('usersSidebar');
        const bd = document.getElementById('sidebarBackdrop');
        if (sidebar) sidebar.classList.remove('open');
        if (bd) bd.classList.remove('open');
        const modal = document.getElementById('usersModal');
        if (modal) modal.classList.remove('open');
      }
    });
  });
})();
