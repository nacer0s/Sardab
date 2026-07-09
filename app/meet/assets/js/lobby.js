(function() {
  'use strict';
  const APP = 'meet';
  const BASE_PATH = window.location.pathname.replace(/\/app\/.*$/, '');
  const roomCode = document.getElementById('roomCode').textContent.trim();
  let sid = sessionStorage.getItem('sardab-' + APP + '-sid');
  let name = sessionStorage.getItem('sardab-' + APP + '-name');
  let redirecting = false;
  let transport = null;

  async function joinRoom(n) {
    if (!n.trim()) { document.getElementById('joinError').textContent = 'Please enter your name'; return; }
    const s = document.getElementById('joinBtn');
    s.disabled = true; s.innerHTML = '<div class="lobby-spinner" style="width:14px;height:14px;margin:0 auto"></div>';
    sid = Math.random().toString(36).substring(2,10);
    name = n.trim();
    sessionStorage.setItem('sardab-' + APP + '-sid', sid);
    sessionStorage.setItem('sardab-' + APP + '-name', name);
    sessionStorage.setItem('sardab-' + APP + '-room', roomCode);
    transport = createSignalingTransport(APP);
    transport.onSignal(function() {});
    const res = await transport.connect(roomCode, sid, name, false);
    if (!res || !res.ok) {
      document.getElementById('joinError').textContent = 'Failed to join room';
      s.disabled = false; s.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> <span>Join</span>';
      return;
    }
    sessionStorage.setItem('sardab-' + APP + '-creator', res.creator ? '1' : '0');
    document.getElementById('joinOverlay').classList.remove('open');
    transport.onUsers(handleUsers);
    transport.onSignal(handleSignal);
  }

  function handleUsers(users) {
    renderUsers(users || []);
    if (users && users.length > 1) redirectToRoom();
  }

  function handleSignal(sig) {
    if (sig.t === 'join' && sig.f !== sid) redirectToRoom();
  }

  function renderUsers(users) {
    const container = document.getElementById('lobbyUsers');
    container.innerHTML = '';
    users.forEach(u => {
      if (u.sid === sid) return;
      const div = document.createElement('div');
      div.className = 'lobby-user';
      div.innerHTML = '<div class="dot online"></div><span class="name">' + u.name + '</span>';
      container.appendChild(div);
    });
  }

  function redirectToRoom() {
    if (redirecting) return;
    redirecting = true;
    const status = document.getElementById('lobbyStatus');
    if (status) {
      status.className = 'lobby-status connected';
      status.innerHTML = '<i class="fa-solid fa-check-circle"></i> <span>' + i18n.t('lobby.redirecting') + '</span>';
    }
    const sp = document.getElementById('lobbySpinner');
    if (sp) sp.style.display = 'none';
    setTimeout(() => { window.location.href = BASE_PATH + '/app/' + APP + '/' + roomCode; }, 1500);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const copyBtn = document.getElementById('copyBtn');
    if (copyBtn) {
      copyBtn.addEventListener('click', () => {
        const t = document.getElementById('inviteLinkText').textContent;
        navigator.clipboard.writeText(t).then(() => {
          copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> <span>' + i18n.t('copied') + '</span>';
          setTimeout(() => { copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> <span>' + i18n.t('copy') + '</span>'; }, 2000);
        }).catch(() => {});
      });
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        const ni = document.getElementById('nameInput');
        if (ni && document.activeElement === ni) { e.preventDefault(); joinRoom(ni.value); }
      }
    });
    document.getElementById('joinBtn')?.addEventListener('click', () => joinRoom(document.getElementById('nameInput')?.value || ''));
    if (sid && name) {
      document.getElementById('joinOverlay').classList.remove('open');
      transport = createSignalingTransport(APP);
      transport.onUsers(handleUsers);
      transport.onSignal(handleSignal);
      transport.connect(roomCode, sid, name, false).then(res => {
        if (res && res.ok) sessionStorage.setItem('sardab-' + APP + '-creator', res.creator ? '1' : '0');
      });
    } else {
      document.getElementById('joinOverlay').classList.add('open');
    }
  });
})();
