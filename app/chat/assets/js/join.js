const BASE_PATH = window.location.pathname.replace(/\/app\/.*$/, '');
const sid = Math.random().toString(36).substring(2,10);

async function api(method, body) {
  const url = BASE_PATH + '/app/chat/signal.php';
  if (method === 'GET') {
    const params = new URLSearchParams(body);
    try { const r = await fetch(url + '?' + params.toString()); return await r.json(); }
    catch(e) { return {ok:false,error:e.message}; }
  }
  try {
    const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
    return await r.json();
  } catch(e) { return {ok:false,error:e.message}; }
}

async function joinRoom(code, name) {
  if (!name.trim()) { document.getElementById('joinError').textContent = i18n.t('join.error.name'); return; }
  if (!code.trim()) { document.getElementById('joinError').textContent = i18n.t('join.error.code'); return; }
  code = code.trim().substring(0,20).toUpperCase();
  const res = await api('POST', {type:'join', room:code, sid, data:{name:name.trim()}});
  if (!res.ok) { document.getElementById('joinError').textContent = i18n.t('join.error.notfound'); return; }
  sessionStorage.setItem('sardab-chat-sid', sid);
  sessionStorage.setItem('sardab-chat-name', name.trim());
  sessionStorage.setItem('sardab-chat-room', code);
  sessionStorage.setItem('sardab-chat-creator', res.creator ? '1' : '0');
  window.location.href = BASE_PATH + '/app/chat/' + code;
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('joinBtn').addEventListener('click', () => joinRoom(document.getElementById('roomInput')?.value, document.getElementById('nameInput')?.value));
  document.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      const ri = document.getElementById('roomInput');
      const ni = document.getElementById('nameInput');
      const active = document.activeElement;
      if (active === ri || active === ni) { e.preventDefault(); joinRoom(ri?.value, ni?.value); }
    }
  });
});
