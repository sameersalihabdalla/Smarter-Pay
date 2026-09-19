const CACHE_NAME = 'smoney-v2';
const assetsToCache = [
  'logo.png',
  'logo2.png'
];

// تثبيت الـ Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(assetsToCache);
    })
  );
  self.skipWaiting();
});

// تفعيل الخدمة وتنظيف الكاش القديم
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clientsClaim();
});

// جلب الطلبات بالطريقة العادية مع محاولة الاعتماد على الكاش عند اللزوم
self.addEventListener('fetch', (event) => {
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});