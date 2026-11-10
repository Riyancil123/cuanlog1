/**
 * CuanLog POS - Progressive Web App Service Worker
 */
const CACHE_NAME = 'cuanlog-v1.0.0';
const ASSETS_TO_CACHE = [
  'manifest.json',
  'assets/css/custom.css',
  'assets/js/app.js'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  // Let network handle PHP requests; cache static CDN/CSS if offline
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});

