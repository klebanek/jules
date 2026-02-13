const CACHE_NAME = 'elmar-pwa-v3';
const urlsToCache = [
  './',
  './index.html',
  './manifest.json',
  './logo.png',
  './assets/js/quagga.min.js',
  './assets/js/jspdf.umd.min.js',
  './assets/js/jspdf.plugin.autotable.min.js',
  './assets/js/JsBarcode.all.min.js',
  './assets/js/xlsx.full.min.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});

self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
