/*
 * Service worker SIMAGTI (PWA).
 * - Halaman (HTML) selalu diambil dari jaringan dan TIDAK disimpan, karena berisi data pribadi pengguna yang login.
 *   Bila jaringan putus, tampilkan /offline.html.
 * - Aset statis (CSS, JS, gambar, ikon, font) disajikan dari cache lalu diperbarui di latar (stale-while-revalidate).
 * Naikkan VERSION setiap kali daftar PRECACHE berubah.
 */
const VERSION = 'simagti-v3';
const PRECACHE = [
    '/offline.html',
    '/css/kp.css',
    '/css/theme-landing.css',
    '/js/app.js',
    '/images/logo-simagti.svg',
    '/images/logo-simagti-mark.svg',
    '/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

const isStatic = (url) => /^\/(css|js|images|icons|fonts)\//.test(url.pathname) || url.hostname === 'fonts.gstatic.com';

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    // Navigasi halaman: jaringan dulu, halaman offline bila gagal. Tidak pernah menyimpan HTML.
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
        return;
    }

    if (!isStatic(url)) return;

    event.respondWith(
        caches.open(VERSION).then(async (cache) => {
            const cached = await cache.match(req);
            const network = fetch(req)
                .then((res) => {
                    if (res.ok || res.type === 'opaque') cache.put(req, res.clone());
                    return res;
                })
                .catch(() => cached);
            return cached || network;
        }),
    );
});
