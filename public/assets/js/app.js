// Variabile globale per il percorso
const basePath = window.APP_BASE_PATH || '';

document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. GESTIONE TEMA (Light / Dark Mode)
    // ==========================================
    const btnTheme = document.getElementById('theme-toggle');
    const iconMoon = document.getElementById('icon-moon');
    const iconSun = document.getElementById('icon-sun');
    
    function impostaTema(scuro) {
        if (scuro) {
            document.body.classList.add('dark-mode');
            if (iconMoon) iconMoon.style.display = 'none';
            if (iconSun) iconSun.style.display = 'block';
        } else {
            document.body.classList.remove('dark-mode');
            if (iconMoon) iconMoon.style.display = 'block';
            if (iconSun) iconSun.style.display = 'none';
        }
    }

    // Controlla il tema salvato o le preferenze di sistema
    const temaSalvato = localStorage.getItem('theme');
    if (temaSalvato === 'dark') {
        impostaTema(true);
    } else if (temaSalvato === 'light') {
        impostaTema(false);
    } else {
        const sistemaScuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
        impostaTema(sistemaScuro);
    }

    // Toggle Tema
    if (btnTheme) {
        btnTheme.addEventListener('click', async () => {
            const diventaScuro = !document.body.classList.contains('dark-mode');
            impostaTema(diventaScuro);
            
            const stringaTema = diventaScuro ? 'dark' : 'light';
            localStorage.setItem('theme', stringaTema);
            
            // Sincronizzazione in Cloud (silenziosa)
            try {
                await fetch(`${basePath}/api/preferenze/tema`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: stringaTema })
                });
            } catch (e) {
                console.warn("Impossibile sincronizzare il tema in cloud.");
            }
        });
    }

    // ==========================================
    // 2. GESTIONE GLOBALE MODALI (Chiusura generica)
    // ==========================================
    // Cerca tutti i bottoni di chiusura modali sulla pagina
    const bottoniChiusura = document.querySelectorAll('.modale-close');
    const modali = document.querySelectorAll('.modale-overlay');

    bottoniChiusura.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modaleOverlay = e.target.closest('.modale-overlay');
            if (modaleOverlay) modaleOverlay.style.display = 'none';
        });
    });

    // Chiudi modale cliccando sullo sfondo scuro
    modali.forEach(modale => {
        modale.addEventListener('click', (e) => {
            if (e.target === modale) {
                modale.style.display = 'none';
            }
        });
    });
});

// ==========================================
// 3. SERVICE WORKER (PWA)
// ==========================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register(`${basePath}/service-worker.js`)
            .then(registration => console.log('Service Worker registrato con successo!'))
            .catch(error => console.error('Errore SW:', error));
    });
}

// ==========================================
// 4. INSTALLAZIONE PWA (Pop-up post-login)
// ==========================================
let deferredPrompt;
const pwaModal = document.getElementById('pwa-install-modal');
const pwaInstallBtn = document.getElementById('pwa-install-btn');
const pwaCloseBtn = document.getElementById('pwa-close-btn');
const pwaIosInstructions = document.getElementById('pwa-ios-instructions');

// Verifica se è già installata o se l'utente ha già ignorato il banner
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
const pwaDismissed = localStorage.getItem('pwa_prompt_dismissed');

if (!isStandalone && !pwaDismissed) {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    if (isIOS) {
        // Su iOS mostriamo il modale con le istruzioni dopo 2 secondi (se il modale esiste nella pagina corrente)
        if (pwaModal) {
            setTimeout(() => {
                pwaInstallBtn.style.display = 'none';
                pwaIosInstructions.style.display = 'block';
                pwaModal.style.display = 'flex';
            }, 2000);
        }
    } else {
        // Su Android/Desktop intercettiamo la richiesta di sistema
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); // Blocca la mini-barra automatica di Chrome
            deferredPrompt = e; // Salva l'evento per attivarlo al click
            
            if (pwaModal) {
                setTimeout(() => {
                    pwaModal.style.display = 'flex';
                }, 2000); // Ritardo di 2 secondi per non aggredire l'utente appena apre la pagina
            }
        });
    }
}

// Azione al click sul pulsante "Installa" (solo Android/Desktop)
if (pwaInstallBtn) {
    pwaInstallBtn.addEventListener('click', async () => {
        pwaModal.style.display = 'none';
        if (deferredPrompt) {
            deferredPrompt.prompt(); // Mostra il prompt nativo del sistema operativo
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                console.log('App installata!');
            }
            deferredPrompt = null;
        }
    });
}

// Chiusura del modale (salva nel LocalStorage per non riproporlo)
const dismissPwaModal = () => {
    if (pwaModal) {
        pwaModal.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
    }
};

if (pwaCloseBtn) pwaCloseBtn.addEventListener('click', dismissPwaModal);
if (pwaModal) {
    pwaModal.addEventListener('click', (e) => {
        if (e.target === pwaModal) dismissPwaModal();
    });
}