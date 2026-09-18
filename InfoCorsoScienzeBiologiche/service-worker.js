// Cambia questa riga:
const CACHE_NAME = 'insubria-app-v7'; // Era v3
const DATA_CACHE_NAME = 'insubria-data-v7'; // Era v3

// ATTENZIONE: Se non hai ancora creato le immagini icon-192.png e 512, rimuovile da questa lista, 
// altrimenti il Service Worker andrà in errore durante l'installazione!
const ASSETS_TO_CACHE = [
    './',
    './index.html',
    './frontend/css/style.css',
    './frontend/js/app.js',
    './manifest.json',
    './img/logo-light.png',
    './img/logo-dark.png'
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
    // 1. GESTIONE DATI DINAMICI (Lezioni del calendario)
    if (event.request.url.includes('proxy.php')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Se la rete funziona, aggiorniamo la cache in background
                    // così app.js la troverà fresca al prossimo giro
                    if (response.status === 200) {
                        const clonedResponse = response.clone();
                        caches.open(DATA_CACHE_NAME).then(cache => {
                            cache.put(event.request, clonedResponse);
                        });
                    }
                    return response;
                })
                .catch(async (err) => {
                    console.log('Rete assente o errore. Il SW restituisce la cache per proxy.php');
                    const cachedResponse = await caches.match(event.request);
                    
                    if (cachedResponse) {
                        return cachedResponse; 
                    }
                    
                    // Fallback pulito se non c'è rete e non c'è mai stata cache
                    return new Response(JSON.stringify([]), {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({ 'Content-Type': 'application/json' })
                    });
                })
        );
    } 
    // 2. GESTIONE ASSETS STATICI (HTML, CSS, Immagini, JS)
    else {
        event.respondWith(
            caches.match(event.request).then(response => {
                // Strategia Cache-First: carica dal telefono se c'è, altrimenti scarica dalla rete
                return response || fetch(event.request);
            })
        );
    }
});