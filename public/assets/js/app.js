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