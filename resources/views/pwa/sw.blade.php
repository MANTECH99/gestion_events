@php
    /** @var string $version */
@endphp
/* Billeterie Express — service worker */
const VERSION = 'bex-{{ $version }}';
const STATIC_CACHE = `${VERSION}-static`;
const PAGES_CACHE = `${VERSION}-pages`;
const ASSETS_CACHE = `${VERSION}-assets`;

const PRECACHE = [
    '/',
    '/manifest.webmanifest',
    '/hors-ligne',
    '/images/icon.svg',
];

const NEVER_CACHE = [
    '/webhooks',
    '/paiement/',
    '/sw.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
            .catch(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => !key.startsWith(VERSION))
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'skip-waiting') self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) return;
    if (NEVER_CACHE.some((path) => url.pathname.startsWith(path))) return;

    // Assets compilés par Vite : cache long.
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/assets/')) {
        event.respondWith(cacheFirst(request, ASSETS_CACHE));
        return;
    }

    // Images uploadées / statiques.
    if (url.pathname.startsWith('/storage/') || url.pathname.startsWith('/images/')) {
        event.respondWith(cacheFirst(request, ASSETS_CACHE));
        return;
    }

    // Pages : réseau d'abord, cache en secours (navigation hors-ligne).
    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(networkFirstPage(request));
        return;
    }

    event.respondWith(networkFirst(request, PAGES_CACHE));
});

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch (e) {
        return cached ?? Response.error();
    }
}

async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const response = await fetch(request);
        if (response.ok) cache.put(request, response.clone());
        return response;
    } catch (e) {
        const cached = await cache.match(request);
        return cached ?? Response.error();
    }
}

async function networkFirstPage(request) {
    const cache = await caches.open(PAGES_CACHE);

    try {
        const response = await fetch(request);

        if (response.ok) {
            // Ne pas mettre en cache les pages dépendantes d'une session / du paiement.
            const url = new URL(request.url);
            const sensitive =
                url.pathname.startsWith('/organisateur') ||
                url.pathname.startsWith('/admin') ||
                url.pathname.startsWith('/point-de-vente') ||
                url.pathname.startsWith('/mes-reservations') ||
                url.pathname.startsWith('/agent');

            if (!sensitive) cache.put(request, response.clone());
        }

        return response;
    } catch (e) {
        const cached = await cache.match(request);

        if (cached) return cached;

        const offline = await cache.match('/hors-ligne');

        return (
            offline ??
            new Response(
                '<!doctype html><meta charset="utf-8"><title>Hors ligne</title>' +
                    '<body style="font-family:system-ui;padding:3rem;text-align:center">' +
                    '<h1>Hors ligne</h1><p>Vous êtes hors connexion.</p></body>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } },
            )
        );
    }
}