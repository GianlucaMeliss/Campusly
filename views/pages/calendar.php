<?php
// Assicuriamoci che la variabile $url sia disponibile dalla vista
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};
?>

<!-- Passiamo il basePath a JavaScript in modo che sappia sempre dove fare le chiamate API -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
</script>

<!-- Header specifico dell'app calendario -->
<div style="background-color: var(--header-bg); color: white; padding: 15px 20px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.3); transition: background-color 0.3s; margin: -2rem -1rem 1rem -1rem;">
    <div class="header-top" style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto;">
        <div class="brand-container" style="display: flex; align-items: center; gap: 12px;">
            <img src="<?= htmlspecialchars($url('/img/logo-light.png')) ?>" alt="Logo" class="brand-logo logo-light">
            <img src="<?= htmlspecialchars($url('/img/logo-dark.png')) ?>" alt="Logo" class="brand-logo logo-dark">
            <h1 style="margin: 0; font-size: 18px; font-weight: 600;">Il Mio Calendario</h1>
        </div>
        
        <button id="theme-toggle" class="theme-btn" aria-label="Cambia tema">
            <svg id="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            <svg id="icon-sun" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        </button>
    </div>
    
    <div class="settimana-nav">
        <button id="btn-prec" aria-label="Settimana precedente">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <h2 id="label-settimana" style="font-size: 15px; font-weight: 500; margin: 0; min-width: 160px; text-align: center;">Caricamento...</h2>
        <button id="btn-succ" aria-label="Settimana successiva">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
        <button id="btn-export" title="Esporta lezioni su Calendario" aria-label="Esporta in Calendario">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        </button>
    </div>
</div>

<div style="max-width: 1200px; margin: 0 auto; width: 100%;">
    <div id="calendario-container">
        <p id="caricamento" style="text-align: center; padding: 2rem;">⏳ Caricamento lezioni in corso...</p>
    </div>
</div>

<!-- ==========================================
     MODALI E COMPONENTI FLOTTANTI
     ========================================== -->

<!-- Modale Dettagli Lezione -->
<div id="modale-dettagli" class="modale-overlay" style="display: none;">
    <div class="modale-content highlight-box">
        <button id="chiudi-modale" class="modale-close" aria-label="Chiudi">&times;</button>
        <div id="modale-body">
            <!-- Il contenuto verrà iniettato da app.js -->
        </div>
    </div>
</div>

<!-- Tasto Flottante (+) per aggiungere Eventi Personali -->
<button id="btn-add-evento" class="fab-add" aria-label="Aggiungi Evento Personale">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
</button>

<!-- Modale Form Nuovo Evento -->
<div id="modale-form-evento" class="modale-overlay" style="display: none;">
    <div class="modale-content highlight-box">
        <button id="chiudi-modale-form" class="modale-close" aria-label="Chiudi">&times;</button>
        <h2 class="hl-titolo" style="margin-top:0;">Nuovo Evento</h2>
        
        <form id="form-nuovo-evento" class="form-personale">
            <div class="input-group">
                <label for="form-titolo">Titolo Evento</label>
                <input type="text" id="form-titolo" placeholder="Es. Studio in Biblioteca" required>
            </div>
            
            <div class="input-group">
                <label for="form-data">Data della lezione / evento</label>
                <input type="date" id="form-data" required>
            </div>
            
            <div class="form-orari-row">
                <div class="input-group" style="flex: 1;">
                    <label for="form-inizio">Orario Inizio</label>
                    <input type="time" id="form-inizio" required>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label for="form-fine">Orario Fine</label>
                    <input type="time" id="form-fine" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="form-luogo">Luogo (opzionale)</label>
                <input type="text" id="form-luogo" placeholder="Es. Aula Studio, Casa, ecc...">
            </div>
            
            <button type="submit" class="btn-salva">Salva Evento</button>
        </form>
    </div>
</div>

<!-- Caricamento dello Script Principale -->
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>?v=9"></script>