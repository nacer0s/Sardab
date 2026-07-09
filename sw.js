const CACHE = 'sardab-v15';
const URLS = ['/', '/app/', '/assets/css/style.css', '/assets/js/i18n.js', '/assets/js/crypto.js', '/assets/js/ws.js', '/assets/js/main.js', '/app/meet/assets/js/app.js', '/favicon.svg', '/logo.svg'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(URLS)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(Promise.all([
    clients.claim(),
    caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
  ]));
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  const url = e.request.url;
  if (!url.startsWith('http')) return;
  if (url.includes('signal.php') || url.includes('ws://') || url.includes('wss://')) return;
  if (url.startsWith('https://api.qrserver.com')) return;
  e.respondWith(
    caches.match(e.request).then(r => {
      if (r) return r;
      return fetch(e.request).then(res => {
        if (res.ok && res.type === 'basic') {
          const clone = res.clone();
          caches.open(CACHE).then(c => c.put(e.request, clone));
        }
        return res;
      }).catch(() => {
        return caches.match(e.request).then(r2 => r2 || new Response('', {status: 204}));
      });
    })
  );
});
