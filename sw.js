// Basic Service Worker for Caching (Cache First Strategy)
const CACHE_NAME = 'civicconnect-bhubaneswar-v1';
const urlsToCache = [
  '/town_issues/',
  '/town_issues/auth/login.php',
  '/assets/css/style.css',
  '/assets/js/map-common.js',
  '/assets/images/icon-192x192.png'
];

// Install the service worker and cache essential resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Serve cached content when offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return the cached version, otherwise fetch from network
        return response || fetch(event.request);
      })
  );
});
