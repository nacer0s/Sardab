(function() {
  'use strict';

  if (window.SardabWS) return;
  window.SardabWS = true;

  const BASE_PATH = window.location.pathname.replace(/\/app\/.*$/, '');

  function hashRoom(code) {
    const str = code + ':sardab:room:v1';
    if (crypto.subtle) {
      return crypto.subtle.digest('SHA-256', new TextEncoder().encode(str))
        .then(buf => Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join(''));
    }
    var h = 0;
    for (var i = 0; i < str.length; i++) { h = ((h << 5) - h) + str.charCodeAt(i); h |= 0; }
    return Promise.resolve((h >>> 0).toString(36));
  }

  window.createSignalingTransport = function(appName) {
    const S = {
      room: '', sid: '', roomHash: '',
      pollActive: false, lastId: 0,
      callbacks: { onSignal: null, onUsers: null, onDisconnect: null },
      joinResolve: null,
    };

    function api(method, body) {
      const url = BASE_PATH + '/app/' + appName + '/signal.php';
      if (method === 'GET') {
        return fetch(url + '?' + new URLSearchParams(body)).then(r => r.json());
      }
      return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) }).then(r => r.json());
    }

    async function pollLoop() {
      while (S.pollActive && S.room && S.sid) {
        try {
          const res = await api('GET', { room: S.roomHash, sid: S.sid, last: S.lastId });
          if (res.ok) {
            if (res.signals) {
              S.lastId = res.last || S.lastId;
              for (const sig of res.signals) {
                if (S.callbacks.onSignal) S.callbacks.onSignal(sig);
              }
            }
            if (res.users && S.callbacks.onUsers) S.callbacks.onUsers(res.users);
          }
        } catch(e) {}
        await new Promise(r => setTimeout(r, 500));
      }
      S.pollActive = false;
    }

    async function connect(room, sid, name) {
      S.room = room;
      S.sid = sid;
      S.lastId = 0;
      S.roomHash = await hashRoom(room);

      const res = await api('POST', { type: 'join', room: S.roomHash, sid, data: { name } });
      if (res.ok) {
        S.pollActive = true;
        pollLoop();
        if (S.callbacks.onUsers) S.callbacks.onUsers(res.users || []);
      }
      return res;
    }

    function disconnect() {
      S.pollActive = false;
      if (S.roomHash && S.sid) {
        api('POST', { type: 'leave', room: S.roomHash, sid: S.sid, data: {} }).catch(() => {});
      }
    }

    function sendSignal(data) {
      var p = api('POST', { type: 'signal', room: S.roomHash, sid: S.sid, data });
      p.then(function(r){if(!r||!r.ok)console.error('[SardabWS] sendSignal fail',r);else console.log('[SardabWS] sendSignal ok id='+r.id+' type='+(data.type||'?'))});
      p['catch'](function(e){console.error('[SardabWS] sendSignal err',e)});
    }

    function sendLeave() {
      api('POST', { type: 'leave', room: S.roomHash, sid: S.sid, data: {} }).catch(function(e){console.error('[SardabWS] sendLeave err',e)});
    }

    function onSignal(cb) { S.callbacks.onSignal = cb; }
    function onUsers(cb) { S.callbacks.onUsers = cb; }
    function onDisconnect(cb) { S.callbacks.onDisconnect = cb; }

    return {
      connect, disconnect, sendSignal, sendLeave,
      onSignal, onUsers, onDisconnect,
      get connected() { return S.pollActive; },
    };
  };
})();
