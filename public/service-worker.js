// Cambia questa riga:
const CACHE_NAME = 'campusly-app-v8'; 
const DATA_CACHE_NAME = 'campusly-data-v8';

const ASSETS_TO_CACHE = [
    '/', // La rotta principale del router
    '/assets/css/calendar.css',
    '/assets/css/style.css', // Il CSS base del boilerplate
    '/assets/js/app.js',
    '/manifest.json',
    '/img/logo-light.png',
    '/img/logo-dark.png',
    '/img/icon-192.png',
    '/img/icon-512.png'
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
    // 1. GESTIONE DATI DINAMICI (Lezioni del calendario sulle nuove API)
    // Cambiamo 'proxy.php' con '/api/calendario'
    if (event.request.url.includes('/api/calendario')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.status === 200) {
                        const clonedResponse = response.clone();
                        caches.open(DATA_CACHE_NAME).then(cache => {
                            cache.put(event.request, clonedResponse);
                        });
                    }
                    return response;
                })
                .catch(async (err) => {
                    const cachedResponse = await caches.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse; 
                    }
                    return new Response(JSON.stringify([]), {
                        status: 503,
                        headers: new Headers({ 'Content-Type': 'application/json' })
                    });
                })
        );
    } 
    // 2. GESTIONE ASSETS STATICI
    else {
        event.respondWith(
            caches.match(event.request).then(response => {
                return response || fetch(event.request);
            })
        );
    }
});