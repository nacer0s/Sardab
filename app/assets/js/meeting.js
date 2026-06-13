(function () {
  'use strict';

  var API_URL    = window.location.origin + '/app/api/meeting-api.php';
  var POLL_MS    = 2000;
  var MAX_POLLS  = 90;

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
          json: function () { return Promise.resolve(JSON.parse(x.responseText)); }
        });
      };
      x.onerror = function () { reject(new Error('XHR failed')); };
      x.send(body || null);
    });
  }

  function _api(action, room, peer, name) {
    var url = API_URL + '?action=' + encodeURIComponent(action) + '&room=' + encodeURIComponent(room);
    if (peer) url += '&peer=' + encodeURIComponent(peer);
    if (name) url += '&name=' + encodeURIComponent(name);
    return _xhr('GET', url);
  }

  function _postApi(action, room, peer, name) {
    var url = API_URL + '?action=' + encodeURIComponent(action) + '&room=' + encodeURIComponent(room);
    if (peer) url += '&peer=' + encodeURIComponent(peer);
    if (name) url += '&name=' + encodeURIComponent(name);
    return _xhr('POST', url);
  }

  function subRoomId(mainId, a, b) {
    var parts = [a, b].sort();
    return mainId + '_' + parts[0] + '_' + parts[1];
  }

  function Meeting(roomId) {
    if (!(this instanceof Meeting)) return new Meeting(roomId);
    this.roomId       = roomId;
    this.peerId       = '';
    this.displayName  = '';
    this.participants = [];    // [{id, name}]
    this.rooms        = {};    // peerId -> P2P.Room instance
    this.conns        = {};    // peerId -> { connected, ... }
    this.localStream  = null;
    this.screenStream = null;
    this._screenSenders = {};
    this._audioTrack  = null;
    this._videoTrack  = null;
    this._pollTimer   = null;
    this._destroyed   = false;
    this.passphrase   = '';

    this.onParticipantJoin = null;
    this.onParticipantLeave = null;
    this.onMessage = null;
    this.onFileReceived = null;
    this.onAudioStream = null;
    this.onVideoStream = null;
    this.onScreenStream = null;
    this.onStatus = null;
    this.onError = null;
    this.onNameChange = null;
  }

  Meeting.prototype.getPeerName = function (peerId) {
    for (var i = 0; i < this.participants.length; i++) {
      if (this.participants[i].id === peerId) return this.participants[i].name;
    }
    return peerId.substring(0, 8);
  };

  /* ─── Init & name update ──────────────────────── */

  Meeting.prototype.init = async function (displayName) {
    var self = this;
    if (self.onStatus) self.onStatus('Connecting…');
    self.passphrase = 'srdb-meet-' + self.roomId + '-key';
    self.displayName = displayName || '';

    var resp = await _postApi('join', self.roomId, '', self.displayName);
    var data = await resp.json();
    if (!data.ok) throw new Error('Failed to connect');
    self.peerId = data.peerId;
    self.participants = data.participants.filter(function (p) { return p.id !== self.peerId; });

    self._connectToAll();

    /* Fire onParticipantJoin for all existing participants */
    self.participants.forEach(function (p) {
      if (self.onParticipantJoin) self.onParticipantJoin(p.id, p.name);
    });

    self._startPoll();

    return self.peerId;
  };

  Meeting.prototype.updateName = async function (newName) {
    var self = this;
    self.displayName = newName;
    await _postApi('update_name', self.roomId, self.peerId, newName);
    if (self.onNameChange) self.onNameChange(newName);
  };

  /* ─── Participant polling ─────────────────────── */

  Meeting.prototype._startPoll = function () {
    var self = this;
    var attempts = 0;

    var check = async function () {
      if (self._destroyed) return;
      attempts++;

      try {
        var resp = await _api('participants', self.roomId);
        var data = await resp.json();
        if (!data.ok) return;

        var current = data.participants.filter(function (p) { return p.id !== self.peerId; });
        var oldIds  = self.participants.map(function (p) { return p.id; });
        var curIds  = current.map(function (p) { return p.id; });

        var joined = current.filter(function (p) { return oldIds.indexOf(p.id) === -1; });
        var left   = self.participants.filter(function (p) { return curIds.indexOf(p.id) === -1; });

        joined.forEach(function (p) {
          self.participants.push(p);
          if (self.onParticipantJoin) self.onParticipantJoin(p.id, p.name);
          self._connectToPeer(p.id);
        });

        left.forEach(function (p) {
          var idx = self.participants.indexOf(p);
          if (idx !== -1) self.participants.splice(idx, 1);
          self._disconnectPeer(p.id);
          if (self.onParticipantLeave) self.onParticipantLeave(p.id, p.name);
        });

        /* Check for name changes */
        for (var i = 0; i < current.length; i++) {
          var cp = current[i];
          for (var j = 0; j < self.participants.length; j++) {
            if (self.participants[j].id === cp.id && self.participants[j].name !== cp.name) {
              var oldName = self.participants[j].name;
              self.participants[j].name = cp.name;
              if (self.onNameChange) self.onNameChange(cp.id, cp.name, oldName);
            }
          }
        }
      } catch (e) {}
    };

    check();
    self._pollTimer = setInterval(check, POLL_MS);
  };

  Meeting.prototype._stopPoll = function () {
    if (this._pollTimer) {
      clearInterval(this._pollTimer);
      this._pollTimer = null;
    }
  };

  /* ─── Per-peer connection management ──────────── */

  Meeting.prototype._connectToAll = function () {
    var self = this;
    self.participants.forEach(function (p) {
      self._connectToPeer(p.id);
    });
  };

  Meeting.prototype._connectToPeer = function (peerId) {
    var self = this;
    if (self.conns[peerId]) return;
    if (peerId === self.peerId) return;
    if (self._destroyed) return;

    var subId = subRoomId(self.roomId, self.peerId, peerId);
    var isHigher = self.peerId > peerId;
    var entry = { peerId: peerId, connected: false };

    var room = new P2P.Room(subId);
    room._meetingPeerId = peerId;
    self.rooms[peerId] = room;
    self.conns[peerId] = entry;

    room.onMessage = function (text) {
      if (self.onMessage) self.onMessage(text, peerId);
    };

    room.onFileReceived = function (blob, name) {
      if (self.onFileReceived) self.onFileReceived(blob, name, peerId);
    };

    room.onAudioStream = function (stream) {
      if (self.onAudioStream) self.onAudioStream(stream, peerId);
    };

    room.onVideoStream = function (stream) {
      if (self.onVideoStream) self.onVideoStream(stream, peerId);
    };

    room.onStatus = function (s) {
      if (s === 'connected') {
        entry.connected = true;
        room.passphrase = self.passphrase;
        /* Add local tracks to newly connected room (fixes late-joiner bug) */
        if (self.localStream && room.pc) {
          self.localStream.getTracks().forEach(function (t) {
            try { room.pc.addTrack(t, self.localStream); } catch (e) {}
          });
        }
        /* Add screen share track to newly connected room, if active */
        if (self.screenStream && room.pc) {
          self._screenSenders[peerId] = self._screenSenders[peerId] || [];
          self.screenStream.getTracks().forEach(function (t) {
            try {
              var sender = room.pc.addTrack(t, self.screenStream);
              self._screenSenders[peerId].push(sender);
            } catch (e) {}
          });
        }
      }
    };

    room.onError = function (msg) {
      if (self.onError) self.onError('Peer ' + peerId + ': ' + msg);
    };

    if (isHigher) {
      (async function () {
        try {
          await room.create();
        } catch (e) {
          entry.error = e.message;
        }
      })();
    } else {
      (async function () {
        try {
          await room.join();
        } catch (e) {
          entry.error = e.message;
        }
      })();
    }
  };

  Meeting.prototype._disconnectPeer = function (peerId) {
    if (this.rooms[peerId]) {
      this.rooms[peerId].disconnect();
      delete this.rooms[peerId];
    }
    delete this.conns[peerId];
    delete this._screenSenders[peerId];
  };

  /* ─── Messaging ───────────────────────────────── */

  Meeting.prototype.sendMessage = function (text) {
    var self = this;
    var sent = false;
    Object.keys(self.rooms).forEach(function (peerId) {
      var r = self.rooms[peerId];
      if (r && r.connected) {
        try { r.sendMessage(text); sent = true; } catch (e) {}
      }
    });
    return sent;
  };

  Meeting.prototype.sendFile = async function (file) {
    var self = this;
    var ok = false;
    for (var peerId in self.rooms) {
      var r = self.rooms[peerId];
      if (r && r.connected) {
        try { await r.sendFile(file); ok = true; } catch (e) {}
      }
    }
    return ok;
  };

  /* ─── Media ───────────────────────────────────── */

  Meeting.prototype.startAudio = async function () {
    var self = this;
    if (self._audioTrack) return;
    var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    self.localStream = stream;
    self._audioTrack = stream.getAudioTracks()[0];
    Object.keys(self.rooms).forEach(function (peerId) {
      var r = self.rooms[peerId];
      if (r && r.pc && r.connected) {
        try { r.pc.addTrack(self._audioTrack, stream); } catch (e) {}
      }
    });
    return stream;
  };

  Meeting.prototype.stopAudio = function () {
    if (this._audioTrack) {
      this._audioTrack.stop();
      this._audioTrack = null;
    }
    if (!this._videoTrack) this.localStream = null;
  };

  Meeting.prototype.startVideo = async function () {
    var self = this;
    if (self._videoTrack) return;
    try {
      var stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
      self.localStream = stream;
      stream.getAudioTracks().forEach(function (t) { self._audioTrack = t; self._audioStream = stream; });
      self._videoTrack = stream.getVideoTracks()[0];

      Object.keys(self.rooms).forEach(function (peerId) {
        var r = self.rooms[peerId];
        if (r && r.pc && r.connected) {
          stream.getTracks().forEach(function (t) {
            try { r.pc.addTrack(t, stream); } catch (e) {}
          });
        }
      });
      return stream;
    } catch (e) {
      throw new Error('Camera/Mic unavailable');
    }
  };

  Meeting.prototype.stopVideo = function () {
    if (this._videoTrack) { this._videoTrack.stop(); this._videoTrack = null; }
    this.stopAudio();
    this.localStream = null;
  };

  Meeting.prototype.toggleMute = function () {
    if (this._audioTrack) {
      this._audioTrack.enabled = !this._audioTrack.enabled;
      return this._audioTrack.enabled;
    }
    return null;
  };

  Meeting.prototype.toggleCamera = function () {
    if (this._videoTrack) {
      this._videoTrack.enabled = !this._videoTrack.enabled;
      return this._videoTrack.enabled;
    }
    return null;
  };

  /* ─── Screen share ────────────────────────────── */

  Meeting.prototype.startScreenShare = async function () {
    var self = this;
    if (self.screenStream) return self.screenStream;
    try {
      var stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
      self.screenStream = stream;
      self._screenSenders = self._screenSenders || {};

      stream.getVideoTracks()[0].onended = function () {
        self.stopScreenShare();
      };

      Object.keys(self.rooms).forEach(function (peerId) {
        var r = self.rooms[peerId];
        if (r && r.pc && r.connected) {
          self._screenSenders[peerId] = self._screenSenders[peerId] || [];
          stream.getTracks().forEach(function (t) {
            try {
              var sender = r.pc.addTrack(t, stream);
              self._screenSenders[peerId].push(sender);
            } catch (e) {}
          });
        }
      });

      if (self.onScreenStream) self.onScreenStream(stream);
      return stream;
    } catch (e) {
      throw new Error('Screen share cancelled or unavailable');
    }
  };

  Meeting.prototype.stopScreenShare = function () {
    var self = this;
    if (self.screenStream) {
      self.screenStream.getTracks().forEach(function (t) { t.stop(); });
      self.screenStream = null;
    }
    if (self._screenSenders) {
      Object.keys(self._screenSenders).forEach(function (peerId) {
        var r = self.rooms[peerId];
        (self._screenSenders[peerId] || []).forEach(function (sender) {
          if (r && r.pc) {
            try { r.pc.removeTrack(sender); } catch (e) {}
          }
        });
      });
      self._screenSenders = {};
    }
    if (self.onScreenStream) self.onScreenStream(null);
  };

  /* ─── Disconnect ──────────────────────────────── */

  Meeting.prototype.disconnect = function () {
    if (this._destroyed) return;
    this._destroyed = true;
    this._stopPoll();
    this.stopVideo();
    this.stopScreenShare();

    var self = this;
    var signalUrl = window.location.origin + '/app/api/signal.php';
    Object.keys(self.rooms).forEach(function (peerId) {
      var sr = self.rooms[peerId];
      if (sr) {
        /* sendBeacon fallback for signal cleanup on page unload */
        try { navigator.sendBeacon(signalUrl + '?action=cleanup&id=' + encodeURIComponent(sr.roomId)); } catch (e) {}
        sr.disconnect();
      }
      delete self.conns[peerId];
    });
    self.rooms = {};

    _postApi('leave', self.roomId, self.peerId).catch(function () {});
    /* Fire-and-forget sendBeacon fallback for page unload */
    try {
      var beaconUrl = API_URL + '?action=leave&room=' + encodeURIComponent(self.roomId) + '&peer=' + encodeURIComponent(self.peerId);
      navigator.sendBeacon(beaconUrl);
    } catch (e) {}
  };

  window.Meeting = { Meeting: Meeting };

})();