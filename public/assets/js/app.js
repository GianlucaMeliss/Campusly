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
// 4. INSTALLAZIONE PWA (Pop-up Dinamico)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    let deferredPrompt;
    const pwaModal = document.getElementById('pwa-install-modal');
    const pwaCloseBtn = document.getElementById('pwa-close-btn');
    const pwaActionContainer = document.getElementById('pwa-action-container');
    const pwaTitle = document.getElementById('pwa-title');
    const pwaDesc = document.getElementById('pwa-desc');
    const pwaIcon = document.getElementById('pwa-icon');

    // Verifica se è già installata o se l'utente l'ha chiusa in precedenza
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    const pwaDismissed = localStorage.getItem('pwa_prompt_dismissed');

    if (!isStandalone && !pwaDismissed && pwaModal) {
        
        const ua = navigator.userAgent;
        const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
        const isMac = /Mac OS X/.test(ua);
        const isMacSafari = isMac && /^((?!chrome|android).)*safari/i.test(ua);
        const isAndroid = /Android/.test(ua);

        // Funzione per mostrare il modale con ritardo
        const showModal = () => {
            setTimeout(() => { pwaModal.style.display = 'flex'; }, 2000);
        };

        if (isIOS) {
            // Personalizzazione per iPhone/iPad (Safari)
            pwaIcon.innerHTML = '🍎';
            pwaTitle.textContent = "Installa su iPhone/iPad";
            pwaDesc.textContent = "Aggiungi Campusly alla tua Home per usarla come un'app nativa a schermo intero.";
            pwaActionContainer.innerHTML = `
                <div style="background: var(--bg-main); padding: 16px; border-radius: var(--radius-md); text-align: left; font-size: 0.9rem;">
                    <p style="margin: 0;">1. Tocca l'icona <strong>Condividi</strong> in basso <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-bottom: 2px;"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg><br><br>2. Scorri e seleziona <strong>"Aggiungi alla schermata Home"</strong> ➕</p>
                </div>`;
            showModal();
            
        } else if (isMacSafari) {
            // Personalizzazione per Mac (Safari)
            pwaIcon.innerHTML = '💻';
            pwaTitle.textContent = "Installa su Mac";
            pwaDesc.textContent = "Aggiungi Campusly al tuo Dock per un accesso fulmineo.";
            pwaActionContainer.innerHTML = `
                <div style="background: var(--bg-main); padding: 16px; border-radius: var(--radius-md); text-align: left; font-size: 0.9rem;">
                    <p style="margin: 0;">1. Clicca sull'icona <strong>Condividi</strong> in alto a destra <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-bottom: 2px;"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg><br><br>2. Seleziona <strong>"Aggiungi al Dock"</strong>.</p>
                </div>`;
            showModal();
            
        } else {
            // Personalizzazione per Android, Windows e Chrome su Mac (Supportano l'evento nativo)
            pwaIcon.innerHTML = isAndroid ? '🤖' : '💻';
            pwaTitle.textContent = isAndroid ? "Installa su Android" : "Installa l'App Desktop";
            pwaDesc.textContent = "Scarica l'app di Campusly per un'esperienza ultra-veloce e accesso offline.";
            pwaActionContainer.innerHTML = `<button id="pwa-install-btn" class="btn btn-primary btn-full-width">Installa App</button>`;
            
            // Per questi dispositivi attendiamo il "via libera" del sistema prima di mostrare il pop-up
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault(); 
                deferredPrompt = e; 
                
                // Agganciamo l'evento al nuovo bottone appena iniettato
                const pwaInstallBtn = document.getElementById('pwa-install-btn');
                if (pwaInstallBtn) {
                    pwaInstallBtn.addEventListener('click', async () => {
                        pwaModal.style.display = 'none';
                        if (deferredPrompt) {
                            deferredPrompt.prompt();
                            const { outcome } = await deferredPrompt.userChoice;
                            if (outcome === 'accepted') console.log('App installata!');
                            deferredPrompt = null;
                        }
                    });
                }
                showModal();
            });
        }
    }

    // Gestione chiusura e salvataggio nel LocalStorage
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
});