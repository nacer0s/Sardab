(function () {
  'use strict';

  var BASE_URL    = window.location.origin + '/app/api/signal.php';
  var CHUNK_SIZE  = 65536;
  var POLL_MS     = 800;
  var MAX_POLLS   = 75;
  var ICE_TIMEOUT = 8000;
  var MAX_FILE_SIZE = 268435456;
  var NONCE_RE = /^[0-9a-f]{12}$/;
  var STUN = [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
    { urls: 'stun:stun2.l.google.com:19302' },
    { urls: 'stun:stun3.l.google.com:19302' },
    { urls: 'stun:stun4.l.google.com:19302' }
  ];

  function Room(roomId) {
    if (!(this instanceof Room)) return new Room(roomId);
    this.roomId         = roomId;
    this.pc             = null;
    this.channel        = null;
    this.isInitiator    = false;
    this.connected      = false;
    this.passphrase     = '';
    this.pollTimer      = null;
    this.candidateTimer = null;
    this.pollAttempts   = 0;
    this.onStatus       = null;
    this.onError        = null;
    this.onProgress     = null;
    this.onFileReceived = null;
    this.onMessage      = null;
    this.onInit         = null;
    this.onAudioStream  = null;
    this.onVideoStream  = null;
    this._receiving     = false;
    this._rxMeta        = null;
    this._rxChunks      = null;
    this._rxCount       = 0;
    this._audioTrack    = null;
    this._audioStream   = null;
    this._videoTrack    = null;
    this._videoStream   = null;
    this._iceTimer      = null;
    this._destroyed     = false;
    this._iceRestarts   = 0;
  }

  /* ─── Private helpers (XHR to bypass fetch interceptors) ── */

  function _xhr(method, url, body) {
    return new Promise(function (resolve, reject) {
      var x = new XMLHttpRequest();
      x.open(method, url);
      x.withCredentials = false;
      x.setRequestHeader('Accept', 'application/json');
      if (body) x.setRequestHeader('Content-Type', 'application/json');
      x.onreadystatechange = function () {
        if (x.readyState !== 4) return;
        resolve({
          ok: x.status >= 200 && x.status < 300,
          status: x.status,
          text: function () { return Promise.resolve(x.responseText); },
          json: function () { return Promise.resolve(JSON.parse(x.responseText)); }
        });
      };
      x.onerror = function () { reject(new Error('XHR failed')); };
      x.send(body || null);
    });
  }

  function _postSignal(action, id, body) {
    return _xhr('POST', BASE_URL + '?action=' + action + '&id=' + encodeURIComponent(id), JSON.stringify(body));
  }

  function _getSignal(action, id) {
    return _xhr('GET', BASE_URL + '?action=' + action + '&id=' + encodeURIComponent(id));
  }

  /* ─── ICE Candidate exchange ─────────────────── */

  Room.prototype._sendCandidates = function () {
    var self = this;
    if (!self.pc || self._destroyed) return;

    self.pc.onicecandidate = function (evt) {
      if (!evt.candidate || self._destroyed) return;
      var s = JSON.stringify(evt.candidate.toJSON());
      _postSignal('candidate', self.roomId, { candidate: s }).catch(function () {});
    };
  };

  Room.prototype._pollForCandidates = function () {
    var self = this;
    if (self.candidateTimer) { clearInterval(self.candidateTimer); self.candidateTimer = null; }

    var check = function () {
      if (self._destroyed) { self._stopCandidatePoll(); return; }
      if (self.connected) { self._stopCandidatePoll(); return; }

      _getSignal('candidate', self.roomId).then(function (resp) {
        if (!resp.ok) return;
        return resp.json();
      }).then(function (data) {
        if (!data || !data.ok || !data.candidates || self._destroyed) return;
        if (self.connected) { self._stopCandidatePoll(); return; }

        data.candidates.forEach(function (c) {
          if (!self.pc || self._destroyed) return;
          try {
            var parsed = JSON.parse(c);
            if (parsed && parsed.candidate && self.pc.remoteDescription) {
              self.pc.addIceCandidate(new RTCIceCandidate(parsed));
            }
          } catch (e) {}
        });
      }).catch(function () {});
    };

    self.candidateTimer = setInterval(check, 600);
    check();
  };

  Room.prototype._stopCandidatePoll = function () {
    if (this.candidateTimer) {
      clearInterval(this.candidateTimer);
      this.candidateTimer = null;
    }
  };

  /* ─── PeerConnection setup ───────────────────── */

  Room.prototype._initPC = function () {
    var self = this;
    self._destroyed = false;
    self.pc = new RTCPeerConnection({
      iceServers: STUN,
      iceCandidatePoolSize: 0
    });

    self.pc.oniceconnectionstatechange = function () {
      if (self._destroyed) return;
      var s = self.pc.iceConnectionState;
      console.log('[P2P] ICE state:', s);
      if (s === 'connected' || s === 'completed') {
        self._stopCandidatePoll();
        if (self.onStatus) self.onStatus('Establishing secure channel…');
      }
      if (s === 'failed') {
        self.connected = false;
        if (self.onStatus) self.onStatus('disconnected');
      }
    };

    self.pc.ontrack = function (evt) {
      if (self._destroyed) return;
      if (evt.track.kind === 'audio' && self.onAudioStream) {
        self.onAudioStream(evt.streams[0]);
      }
      if (evt.track.kind === 'video' && self.onVideoStream) {
        self.onVideoStream(evt.streams[0]);
      }
    };

    self.pc.onicecandidateerror = function (e) {
      console.log('[P2P] STUN error for ' + e.url + ': ' + (e.errorText || e.errorCode || 'unknown'));
    };
  };

  /* ─── ICE gathering with timeout ─────────────── */

  Room.prototype._waitIce = function () {
    var self = this;
    if (self._destroyed) return Promise.resolve();
    if (self.pc.iceGatheringState === 'complete') return Promise.resolve();

    /* Set the handler BEFORE returning the promise so we don't miss the event */
    self.pc.onicegatheringstatechange = function () {
      if (self._destroyed) return;
      if (self.pc.iceGatheringState === 'complete' && !self._iceDone) {
        self._iceDone = true;
        if (self._iceTimer) { clearTimeout(self._iceTimer); self._iceTimer = null; }
        self._iceResolve();
      }
    };

    return new Promise(function (resolve) {
      self._iceResolve = resolve;
      self._iceDone = false;
      self._iceTimer = setTimeout(function () {
        if (!self._iceDone) { self._iceDone = true; resolve(); }
      }, ICE_TIMEOUT);
    });
  };

  /* ─── Create (initiator) ─────────────────────── */

  Room.prototype.create = async function () {
    var self = this;
    self.isInitiator = true;
    if (self.onStatus) self.onStatus('Initializing…');
    self._initPC();

    try {
      await self.startAudio();
    } catch(err) { console.log("No mic on create", err); }

    if (self.onInit) await self.onInit(self);

    self.channel = self.pc.createDataChannel('sardab');
    self._setupChannel();
    self._sendCandidates();

    try {
      if (self.onStatus) self.onStatus('Creating offer…');
      var offer = await self.pc.createOffer();
      await self.pc.setLocalDescription(offer);
    } catch (e) {
      throw new Error('Offer creation failed: ' + e.message);
    }

    if (self.onStatus) self.onStatus('Gathering ICE candidates…');
    await self._waitIce();

    if (self.onStatus) self.onStatus('Posting offer…');
    var offerSdp = { type: self.pc.localDescription.type, sdp: self.pc.localDescription.sdp };
    var resp = await _postSignal('offer', self.roomId, { sdp: offerSdp });
    if (!resp.ok) throw new Error('Offer POST failed (HTTP ' + resp.status + ')');
    var postData = await resp.json();
    if (!postData.ok) throw new Error('Offer POST rejected by server');
    if (!NONCE_RE.test(postData.nonce)) throw new Error('Offer POST intercepted — bad nonce from server');

    var verifyResp = await _getSignal('offer', self.roomId);
    if (!verifyResp.ok) throw new Error('Offer POST verified false — file not found (HTTP ' + verifyResp.status + ')');

    if (self.onStatus) self.onStatus('Offer posted — waiting for peer…');
    self.pollAttempts = 0;
    self._startPollForAnswer();
    self._pollForCandidates();
  };

  /* ─── Join (receiver) ────────────────────────── */

  Room.prototype._pollForOffer = function () {
    var self = this;
    var attempts = 0;

    return new Promise(function (resolve, reject) {
      var check = async function () {
        if (self._destroyed) { reject(new Error('Cancelled')); return; }

        attempts++;
        if (attempts > MAX_POLLS) {
          reject(new Error('Room not found — offer never posted (HTTP 404)'));
          return;
        }

        try {
          var resp = await _getSignal('offer', self.roomId);
          if (!resp.ok) { setTimeout(check, POLL_MS); return; }
          var data = await resp.json();
          if (!data.ok || !data.sdp) { setTimeout(check, POLL_MS); return; }
          resolve(data.sdp);
        } catch (e) {
          setTimeout(check, POLL_MS);
        }
      };

      check();
    });
  };

  Room.prototype.join = async function () {
    var self = this;
    self.isInitiator = false;
    if (self.onStatus) self.onStatus('Preparing connection…');
    self._initPC();

    try {
      await self.startAudio();
    } catch(err) { console.log("No mic on join", err); }

    if (self.onInit) await self.onInit(self);
    self._sendCandidates();

    self.pc.ondatachannel = function (evt) {
      self.channel = evt.channel;
      self._setupChannel();
    };

    if (self.onStatus) self.onStatus('Waiting for room…');
    var sdp = await self._pollForOffer();

    if (self.onStatus) self.onStatus('Setting remote description…');
    await self.pc.setRemoteDescription(new RTCSessionDescription(sdp));

    if (self.onStatus) self.onStatus('Creating answer…');
    var answer = await self.pc.createAnswer();
    await self.pc.setLocalDescription(answer);
    if (self.onStatus) self.onStatus('Gathering ICE candidates…');
    await self._waitIce();

    if (self.onStatus) self.onStatus('Posting answer…');
    var ansSdp = { type: self.pc.localDescription.type, sdp: self.pc.localDescription.sdp };
    var ansResp = await _postSignal('answer', self.roomId, { sdp: ansSdp });
    if (!ansResp.ok) throw new Error('Answer failed (HTTP ' + ansResp.status + ')');
    var ansData = await ansResp.json();
    if (!ansData.ok) throw new Error('Answer rejected by server');
    if (!NONCE_RE.test(ansData.nonce)) throw new Error('Answer intercepted — bad nonce from server');

    if (self.onStatus) self.onStatus('Waiting for connection…');
    self._pollForCandidates();

    var candResp = await _getSignal('candidate', self.roomId);
    if (candResp.ok) {
      var candData = await candResp.json();
      if (candData.ok && candData.candidates) {
        candData.candidates.forEach(function (c) {
          if (!self.pc || self._destroyed) return;
          try {
            var parsed = JSON.parse(c);
            if (parsed && parsed.candidate && self.pc.remoteDescription) {
              self.pc.addIceCandidate(new RTCIceCandidate(parsed));
            }
          } catch (e) {}
        });
      }
    }
  };

  /* ─── Data channel messaging ─────────────────── */

  Room.prototype.sendMessage = function (text) {
    if (!this.connected || !this.channel) throw new Error('Not connected');
    this.channel.send(JSON.stringify({ t: 'msg', d: text }));
  };

  Room.prototype.sendFile = async function (file) {
    var self = this;
    if (!self.connected || !self.channel) throw new Error('Not connected');
    if (!self.passphrase) throw new Error('Set a passphrase first');
    if (file.size > MAX_FILE_SIZE) throw new Error('File exceeds 256 MB limit');

    var buffer = await file.arrayBuffer();
    var total  = Math.ceil(buffer.byteLength / CHUNK_SIZE);

    self.channel.send(JSON.stringify({ t: 'meta', n: file.name, s: file.size, m: file.type || 'application/octet-stream', c: total }));

    for (var i = 0; i < total; i++) {
      var start = i * CHUNK_SIZE;
      var end   = Math.min(start + CHUNK_SIZE, buffer.byteLength);
      var chunk = new Uint8Array(buffer.slice(start, end));
      var enc = await Crypto._encryptChunk(chunk, self.passphrase);
      self.channel.send(JSON.stringify({ t: 'chunk', i: i, c: total, d: enc }));
      if (self.onProgress) self.onProgress(Math.round(((i + 1) / total) * 100));
    }

    self.channel.send(JSON.stringify({ t: 'done' }));
  };

  /* ─── Audio / Video ──────────────────────────── */

  Room.prototype.startAudio = async function () {
    var self = this;
    if (!self.pc) throw new Error('No PeerConnection');
    if (self._audioStream) return self._audioStream;

    var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    self._audioStream = stream;
    self._audioTrack = stream.getAudioTracks()[0];
    self.pc.addTrack(self._audioTrack, stream);
    if (self.onStatus) self.onStatus('audio:started');
    return stream;
  };

  Room.prototype.stopAudio = function () {
    if (this._audioStream) {
      this._audioStream.getTracks().forEach(function (t) { t.stop(); });
      this._audioStream = null;
      this._audioTrack = null;
    }
    if (this.onStatus) this.onStatus('audio:stopped');
  };

  Room.prototype.startVideo = async function () {
    var self = this;
    if (!self.pc) throw new Error('No PeerConnection');
    if (self._videoStream) return self._videoStream;

    var stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
    self._videoStream = stream;
    self._audioStream = stream;
    stream.getAudioTracks().forEach(function (t) {
      self._audioTrack = t;
      self.pc.addTrack(t, stream);
    });
    self._videoTrack = stream.getVideoTracks()[0];
    self.pc.addTrack(self._videoTrack, stream);
    if (self.onStatus) self.onStatus('video:started');
    return stream;
  };

  Room.prototype.stopVideo = function () {
    if (this._videoStream) {
      this._videoStream.getTracks().forEach(function (t) { t.stop(); });
      this._videoStream = null;
      this._videoTrack = null;
      this._audioTrack = null;
    }
    this.stopAudio();
    if (this.onStatus) this.onStatus('video:stopped');
  };

  Room.prototype.toggleMute = function () {
    if (this._audioTrack) {
      this._audioTrack.enabled = !this._audioTrack.enabled;
      return this._audioTrack.enabled;
    }
    return null;
  };

  Room.prototype.toggleCamera = function () {
    if (this._videoTrack) {
      this._videoTrack.enabled = !this._videoTrack.enabled;
      return this._videoTrack.enabled;
    }
    return null;
  };

  /* ─── Disconnect / cleanup ───────────────────── */

  Room.prototype.disconnect = function () {
    if (this._destroyed) return;
    console.log('[P2P] disconnect() called on room=' + this.roomId + ', isInitiator=' + this.isInitiator);
    this._destroyed = true;
    this.connected = false;

    this.stopVideo();
    this._stopCandidatePoll();

    if (this._iceTimer) { clearTimeout(this._iceTimer); this._iceTimer = null; }
    if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }

    if (this.channel) {
      try {
        this.channel.onopen = null;
        this.channel.onclose = null;
        this.channel.onerror = null;
        this.channel.onmessage = null;
        this.channel.close();
      } catch (e) {}
      this.channel = null;
    }

    if (this.pc) {
      try {
        this.pc.oniceconnectionstatechange = null;
        this.pc.ontrack = null;
        this.pc.onicecandidate = null;
        this.pc.onicegatheringstatechange = null;
        this.pc.onicecandidateerror = null;
        this.pc.ondatachannel = null;
        this.pc.close();
      } catch (e) {}
      this.pc = null;
    }

    if (this.onStatus) this.onStatus('disconnected');
    _getSignal('cleanup', this.roomId).catch(function () {});
  };

  /* ─── Internal: data channel setup ───────────── */

  Room.prototype._setupChannel = function () {
    var self = this;

    self.channel.onopen = function () {
      console.log('[P2P] channel.onopen fired');
      if (self._destroyed) return;
      self.connected = true;
      self._stopCandidatePoll();
      if (self.onStatus) self.onStatus('connected');
    };

    self.channel.onclose = function () {
      console.log('[P2P] channel.onclose fired, connected=' + self.connected);
      if (self._destroyed) return;
      self.connected = false;
      if (self.onStatus) self.onStatus('disconnected');
    };

    self.channel.onerror = function () {
      console.log('[P2P] channel.onerror fired');
      if (self._destroyed) return;
      if (self.onStatus) self.onStatus('connection error');
    };

    self.channel.onmessage = async function (evt) {
      if (self._destroyed) return;
      var msg;
      try { msg = JSON.parse(evt.data); } catch (e) { return; }

      if (msg.t === 'msg') {
        if (self.onMessage) self.onMessage(msg.d);
        return;
      }

      if (msg.t === 'meta') {
        self._receiving = true;
        self._rxMeta = msg;
        self._rxChunks = new Array(msg.c);
        self._rxCount = 0;
        if (self.onStatus) self.onStatus('Receiving: ' + msg.n);
        return;
      }

      if (msg.t === 'chunk') {
        if (msg.i < self._rxChunks.length) {
          self._rxChunks[msg.i] = msg.d;
          self._rxCount++;
          if (self.onProgress) self.onProgress(Math.round((self._rxCount / msg.c) * 100));
        }
        return;
      }

      if (msg.t === 'done') {
        self._receiving = false;
        if (self.onStatus) self.onStatus('decrypting');
        try {
          var decrypted = [];
          for (var k = 0; k < self._rxChunks.length; k++) {
            if (self._rxChunks[k]) {
              decrypted.push(await Crypto._decryptChunk(self._rxChunks[k], self.passphrase));
            }
          }
          var totalLen = decrypted.reduce(function (a, b) { return a + b.byteLength; }, 0);
          var result = new Uint8Array(totalLen);
          var offset = 0;
          decrypted.forEach(function (d) { result.set(d, offset); offset += d.byteLength; });
          var blob = new Blob([result], { type: self._rxMeta.m });
          if (self.onFileReceived) self.onFileReceived(blob, self._rxMeta.n);
        } catch (e) {
          if (self.onError) self.onError('Decryption failed — wrong passphrase?');
          if (self.onStatus) self.onStatus('Decryption failed');
        }
      }
    };
  };

  /* ─── Poll for answer ────────────────────────── */

  Room.prototype._startPollForAnswer = function () {
    var self = this;

    var check = async function () {
      if (self._destroyed) return 'cancelled';

      self.pollAttempts++;
      if (self.pollAttempts > MAX_POLLS) {
        if (self.onError) self.onError('Connection timed out — peer did not join');
        if (self.onStatus) self.onStatus('connection error');
        self.disconnect();
        return 'timeout';
      }

      try {
        var resp = await _getSignal('answer', self.roomId);
        if (!resp.ok) return false;
        var data = await resp.json();
        if (!data.ok || !data.sdp) return false;

        await self.pc.setRemoteDescription(new RTCSessionDescription(data.sdp));

        var candResp = await _getSignal('candidate', self.roomId);
        if (candResp.ok) {
          var candData = await candResp.json();
          if (candData.ok && candData.candidates) {
            candData.candidates.forEach(function (c) {
              if (!self.pc || self._destroyed) return;
              try {
                var parsed = JSON.parse(c);
                if (parsed && parsed.candidate) {
                  self.pc.addIceCandidate(new RTCIceCandidate(parsed));
                }
              } catch (e) {}
            });
          }
        }

        if (self.onStatus) self.onStatus('connecting');
        return true;
      } catch (e) {
        return false;
      }
    };

    check().then(function (result) {
      if (self._destroyed) return;
      if (result === 'timeout' || result === 'cancelled' || result === true) return;
      self.pollTimer = setInterval(function () {
        if (self._destroyed) { clearInterval(self.pollTimer); self.pollTimer = null; return; }
        check().then(function (r) {
          if (self.pollTimer && (r === true || r === 'timeout' || r === 'cancelled')) {
            clearInterval(self.pollTimer);
            self.pollTimer = null;
          }
        });
      }, POLL_MS);
    });
  };

  /* ─── Crypto helpers (per-chunk AES-GCM) ─────── */

  Crypto._encryptChunk = async function (data, passphrase) {
    var salt = crypto.getRandomValues(new Uint8Array(16));
    var iv   = crypto.getRandomValues(new Uint8Array(12));
    var km = await crypto.subtle.importKey('raw', new TextEncoder().encode(passphrase), 'PBKDF2', false, ['deriveKey']);
    var key = await crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt: salt, iterations: 600000, hash: 'SHA-256' },
      km, { name: 'AES-GCM', length: 256 }, false, ['encrypt']
    );
    var ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, key, data);
    var combined = new Uint8Array(salt.length + iv.length + ct.byteLength);
    combined.set(salt, 0);
    combined.set(iv, salt.length);
    combined.set(new Uint8Array(ct), salt.length + iv.length);
    return Crypto.toBase64(combined);
  };

  Crypto._decryptChunk = async function (ciphertext, passphrase) {
    var combined = Crypto.fromBase64(ciphertext);
    var salt = combined.slice(0, 16);
    var iv   = combined.slice(16, 28);
    var ct   = combined.slice(28);
    var km = await crypto.subtle.importKey('raw', new TextEncoder().encode(passphrase), 'PBKDF2', false, ['deriveKey']);
    var key = await crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt: salt, iterations: 600000, hash: 'SHA-256' },
      km, { name: 'AES-GCM', length: 256 }, false, ['decrypt']
    );
    var plain = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, key, ct);
    return new Uint8Array(plain);
  };

  window.P2P = { Room: Room };

})();
