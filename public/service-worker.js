/* Campusly - Service Worker */

const SCOPE_PATH = new URL(self.registration.scope).pathname;
const PAGES_CACHE = 'campusly-pages';
const STATIC_CACHE = 'campusly-static';

const NAV_TIMEOUT_MS = 4000;
const MAX_PAGES = 20;
const MAX_STATIC = 150;

// Pagine da NON salvare (token CSRF / flussi di autenticazione)
const NO_CACHE_PAGES = ['login', 'register', 'logout', 'password-dimenticata', 'reset-password/', 'invito/'];

const relPath = (url) => url.pathname.slice(SCOPE_PATH.length);

function isOurs(rawUrl) {
    try {
        const u = new URL(rawUrl, self.location.origin);
        return u.origin === self.location.origin
            && u.pathname.startsWith(SCOPE_PATH)
            && !relPath(u).startsWith('api/');
    } catch (e) {
        return false;
    }
}

function isCacheablePage(url) {
    const p = relPath(url);
    return !NO_CACHE_PAGES.some((x) => p === x || p.startsWith(x));
}

const isGoodResponse = (res) => res && res.status === 200 && res.type === 'basic' && !res.redirected;

async function trim(cacheName, max) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    await Promise.all(keys.slice(0, Math.max(0, keys.length - max)).map((k) => cache.delete(k)));
}

async function dropOldVersions(cache, url) {
    const keys = await cache.keys();
    await Promise.all(
        keys
            .filter((k) => {
                const u = new URL(k.url);
                return u.pathname === url.pathname && u.search !== url.search;
            })
            .map((k) => cache.delete(k))
    );
}

async function clearUserCaches() {
    await Promise.all([caches.delete(PAGES_CACHE), caches.delete(STATIC_CACHE)]);
}

function offlineResponse() {
    return new Response(
        '<!doctype html><html lang="it"><head><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<title>Offline - Campusly</title></head>' +
        '<body style="font-family:system-ui,sans-serif;text-align:center;padding:15vh 24px">' +
        '<h1>Sei offline</h1>' +
        '<p>Questa pagina non è ancora salvata sul dispositivo. Riprova quando torni online.</p>' +
        '<button onclick="location.reload()" style="padding:10px 20px;font-size:1rem">Riprova</button>' +
        '</body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = [PAGES_CACHE, STATIC_CACHE];
        const names = await caches.keys();
        await Promise.all(names.filter((n) => !keep.includes(n)).map((n) => caches.delete(n)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (isOurs(url.href) && relPath(url) === 'logout') {
        event.waitUntil(clearUserCaches());
        return;
    }

    if (request.method !== 'GET') return;
    if (!isOurs(url.href)) return;

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigation(event));
    } else {
        event.respondWith(handleStatic(event));
    }
});

async function handleNavigation(event) {
    const { request } = event;
    const url = new URL(request.url);
    const cacheable = isCacheablePage(url);

    const network = fetch(request).then(async (res) => {
        if (cacheable && isGoodResponse(res)) {
            const cache = await caches.open(PAGES_CACHE);
            await cache.put(request, res.clone());
            await trim(PAGES_CACHE, MAX_PAGES);
        }
        return res;
    });
    network.catch(() => {});
    event.waitUntil(network.catch(() => {}));

    const lookup = async () => {
        const cache = await caches.open(PAGES_CACHE);
        return (await cache.match(request))
            || (await cache.match(request, { ignoreSearch: true }))
            || (await cache.match(SCOPE_PATH + 'dashboard'));
    };

    try {
        const timeout = new Promise((resolve) => setTimeout(() => resolve(null), NAV_TIMEOUT_MS));
        const res = await Promise.race([network, timeout]);
        if (res) return res;

        const cached = await lookup();
        if (cached) return cached;
        return await network.catch(() => offlineResponse());
    } catch (e) {
        return (await lookup()) || offlineResponse();
    }
}

async function handleStatic(event) {
    const { request } = event;
    const url = new URL(request.url);
    const cache = await caches.open(STATIC_CACHE);
    const hit = await cache.match(request);

    if (hit && url.searchParams.has('v')) return hit;

    const refresh = fetch(request).then(async (res) => {
        if (isGoodResponse(res)) {
            await cache.put(request, res.clone());
            await dropOldVersions(cache, url);
            await trim(STATIC_CACHE, MAX_STATIC);
        }
        return res;
    });
    refresh.catch(() => {});

    if (hit) {
        event.waitUntil(refresh.catch(() => {}));
        return hit;
    }

    try {
        return await refresh;
    } catch (e) {
        const old = await cache.match(request, { ignoreSearch: true });
        return old || Response.error();
    }
}

self.addEventListener('message', (event) => {
    const data = event.data || {};

    if (data.type === 'CACHE_URLS') event.waitUntil(warmUp(data));
    if (data.type === 'CLEAR_USER_DATA') event.waitUntil(clearUserCaches());
    if (data.type === 'SKIP_WAITING') self.skipWaiting();
});

async function warmUp({ page, assets = [] }) {
    const jobs = [];

    if (page && isOurs(page) && isCacheablePage(new URL(page))) {
        jobs.push((async () => {
            const res = await fetch(page);
            if (isGoodResponse(res)) {
                const cache = await caches.open(PAGES_CACHE);
                await cache.put(page, res);
            }
        })());
    }

    const statics = await caches.open(STATIC_CACHE);
    assets.filter(isOurs).forEach((u) => {
        jobs.push((async () => {
            if (await statics.match(u)) return;
            const res = await fetch(u);
            if (isGoodResponse(res)) await statics.put(u, res);
        })());
    });

    await Promise.allSettled(jobs);
}
