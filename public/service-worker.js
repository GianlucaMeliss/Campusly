// Cambia questa riga:
const CACHE_NAME = 'campusly-app-v11'; 
const DATA_CACHE_NAME = 'campusly-data-v11';
const PATH_PREFIX = '/Campusly/';

const ASSETS_TO_CACHE = [
    PATH_PREFIX,
    PATH_PREFIX + '/assets/css/calendar.css',
    PATH_PREFIX + '/assets/css/style.css',
    PATH_PREFIX + '/assets/js/app.js',
    PATH_PREFIX + '/manifest.json',
    PATH_PREFIX + '/img/logo-light.png',
    PATH_PREFIX + '/img/logo-dark.png',
    PATH_PREFIX + '/img/icon-192.png',
    PATH_PREFIX + '/img/icon-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('Cache V3 aperta: salvataggio assets statici aggiornati');
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting(); // Forza l'installazione immediata senza aspettare che l'utente chiuda la tab
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keyList => {
            return Promise.all(keyList.map(key => {
                // Elimina le vecchie versioni (v2 o v1) per evitare conflitti
                if (key !== CACHE_NAME && key !== DATA_CACHE_NAME) {
                    console.log('Rimozione vecchia cache obsoleta:', key);
                    return caches.delete(key);
                }
            }));
        })
    );
    self.clients.claim(); // Prende subito il controllo della pagina
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // 1. GESTIONE DATI DINAMICI (API Calendario) -> Network First con Cache Fallback
    if (url.pathname.includes('/api/calendario')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.status === 200) {
                        const clonedResponse = response.clone();
                        caches.open(DATA_CACHE_NAME).then(cache => cache.put(event.request, clonedResponse));
                    }
                    return response;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(event.request);
                    return cachedResponse || new Response(JSON.stringify([]), {
                        status: 503,
                        headers: new Headers({ 'Content-Type': 'application/json' })
                    });
                })
        );
        return; // Interrompe qui
    } 

    // 2. NAVIGAZIONE PAGINE HTML (PHP) -> Network First
    // Evita di pescare vecchie pagine in cache rompendo i token CSRF o le sessioni
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                // Se sei offline, prova a mostrare la dashboard cachata se esiste
                return caches.match(event.request);
            })
        );
        return;
    }

    // 3. ASSETS STATICI (CSS, JS, Img) -> Cache First
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});