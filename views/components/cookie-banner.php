<div id="cookie-banner" class="cookie-overlay">
    <div class="cookie-text">
        <h4><i data-lucide="cookie" style="width:20px; height:20px;"></i> La tua privacy è importante</h4>
        <p>Utilizziamo cookie tecnici essenziali per il sito e, previo tuo consenso, cookie analitici di terze parti per migliorare i nostri servizi. Puoi accettarli tutti o rifiutare quelli non essenziali. Maggiori info nella <a href="<?= BASE_PATH ?>/cookie">Cookie Policy</a>.</p>
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
    
    const storageKey = 'campusly_cookie_consent';
    const uuidKey = 'campusly_cookie_uuid';

    const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>";

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

    function logConsentToServer(status) {
        fetch('<?= BASE_PATH ?>/api/log-cookie', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                uuid: userUUID,
                consent_status: status,
                csrf_token: csrfToken
            })
        }).catch(err => console.error('Errore log cookie:', err));
    }

    const userConsent = localStorage.getItem(storageKey);

    if (!userConsent) {
        setTimeout(() => { banner.classList.add('show'); }, 800);
    } else if (userConsent === 'accepted') {
        loadThirdPartyScripts();
    }

    btnAccept.addEventListener('click', () => {
        localStorage.setItem(storageKey, 'accepted');
        banner.classList.remove('show');
        logConsentToServer('accepted');
        loadThirdPartyScripts(); 
    });

    btnReject.addEventListener('click', () => {
        localStorage.setItem(storageKey, 'rejected');
        banner.classList.remove('show');
        logConsentToServer('rejected');
    });

    window.openCookieBanner = function(e) {
        if (e) e.preventDefault();
        banner.classList.add('show');
    };
});

function loadThirdPartyScripts() {
    if (window.scriptsLoaded) return;
    window.scriptsLoaded = true;
}
</script>
