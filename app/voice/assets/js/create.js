const BASE_PATH = window.location.pathname.replace(/\/app\/.*$/, '');
const sid = Math.random().toString(36).substring(2,10);

function genCode() {
  const c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let r = '';
  const buf = new Uint8Array(20);
  crypto.getRandomValues(buf);
  for (let i = 0; i < 20; i++) r += c[buf[i] % c.length];
  return r;
}

async function api(method, body) {
  const url = BASE_PATH + '/app/voice/signal.php';
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

async function createRoom(name) {
  if (!name.trim()) { document.getElementById('createError').textContent = i18n.t('create.error.name'); return; }
  const code = genCode();
  const res = await api('POST', {type:'join', room:code, sid, data:{name:name.trim()}});
  if (!res.ok) { document.getElementById('createError').textContent = i18n.t('create.error.failed'); return; }
  sessionStorage.setItem('sardab-voice-sid', sid);
  sessionStorage.setItem('sardab-voice-name', name.trim());
  sessionStorage.setItem('sardab-voice-room', code);
  sessionStorage.setItem('sardab-voice-creator', res.creator ? '1' : '0');
  window.location.href = BASE_PATH + '/app/voice/lobby/' + code;
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('createBtn').addEventListener('click', () => createRoom(document.getElementById('nameInput')?.value));
  document.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      const input = document.getElementById('nameInput');
      if (input && document.activeElement === input) { e.preventDefault(); createRoom(input?.value); }
    }
  });
});