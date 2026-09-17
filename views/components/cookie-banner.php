
<div id="cookie-banner" class="cookie-overlay">
    <div class="cookie-text">
        <h4><i data-lucide="cookie" style="width:20px; height:20px;"></i> La tua privacy è importante</h4>
        <p>Utilizziamo cookie tecnici essenziali per il sito e, previo tuo consenso, cookie analitici di terze parti per migliorare i nostri servizi. Puoi accettarli tutti o rifiutare quelli non essenziali. Maggiori info nella <a href="/cookie">Cookie Policy</a>.</p>
    </div>
    <div class="cookie-actions">
        <button id="btn-reject-cookies" class="btn-cookie-reject">Rifiuta non essenziali</button>
        <button id="btn-accept-cookies" class="btn-cookie-accept">Accetta Tutti</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const banner = document.getElementById('cookie-banner');
    const btnAccept = document.getElementById('btn-accept-cookies');
    const btnReject = document.getElementById('btn-reject-cookies');
    
    const storageKey = 'broggini_cookie_consent';
    const uuidKey = 'broggini_cookie_uuid';

    // Funzione per generare un ID anonimo per il registro consensi
    // 1. INIETTA IL TOKEN CSRF DAL PHP AL JAVASCRIPT
    const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    let userUUID = localStorage.getItem(uuidKey);
    if (!userUUID) {
        userUUID = generateUUID();
        localStorage.setItem(uuidKey, userUUID);
    }

    // Funzione per inviare il log al server in background
    function logConsentToServer(status) {
        fetch('/api/log-cookie', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                uuid: userUUID,
                consent_status: status,
                csrf_token: csrfToken // <-- TOKEN AGGIUNTO AL PAYLOAD
            })
        }).catch(err => console.error('Errore log cookie:', err));
    }

    // 1. Controlla se l'utente ha già fatto una scelta
    const userConsent = localStorage.getItem(storageKey);

    if (!userConsent) {
        // Mostra il banner con un leggero ritardo
        setTimeout(() => { banner.classList.add('show'); }, 800);
    } else if (userConsent === 'accepted') {
        loadThirdPartyScripts();
    }

    // 2. Azione: ACCETTA
    btnAccept.addEventListener('click', () => {
        localStorage.setItem(storageKey, 'accepted');
        banner.classList.remove('show');
        logConsentToServer('accepted');
        loadThirdPartyScripts(); 
    });

    // 3. Azione: RIFIUTA
    btnReject.addEventListener('click', () => {
        localStorage.setItem(storageKey, 'rejected');
        banner.classList.remove('show');
        logConsentToServer('rejected');
    });

    // 4. Funzione globale per riaprire il banner
    window.openCookieBanner = function(e) {
        if(e) e.preventDefault();
        banner.classList.add('show');
    };
});

function loadThirdPartyScripts() {
    if(window.scriptsLoaded) return;
    window.scriptsLoaded = true;
    console.log("Cookie accettati: Script di terze parti caricati.");
    // Incolla qui gli script Google/Meta...
}
</script>