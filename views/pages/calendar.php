<?php
// Assicuriamoci che la variabile $url sia disponibile dalla vista
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
};
?>

<!-- Passiamo il basePath a JavaScript in modo che sappia sempre dove fare le chiamate API -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
</script>

<div class="header-top" style="padding: 15px; background: var(--header-bg); color: white;">
    <div class="brand-container" style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <!-- Usa l'helper $url() per i loghi -->
            <img src="<?= htmlspecialchars($url('/img/logo-light.png')) ?>" alt="Logo" class="brand-logo logo-light">
            <img src="<?= htmlspecialchars($url('/img/logo-dark.png')) ?>" alt="Logo" class="brand-logo logo-dark">
            <h1 style="margin: 0; font-size: 18px;">Il Mio Calendario</h1>
        </div>
        <button id="theme-toggle" class="theme-btn" aria-label="Cambia tema">
            <svg id="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            <svg id="icon-sun" style="display: none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        </button>
    </div>
    
    <div class="settimana-nav">
        <button id="btn-prec"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
        <h2 id="label-settimana">Caricamento...</h2>
        <button id="btn-succ"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
    </div>
</div>

<main>
    <div id="calendario-container">
        <p id="caricamento">⏳ Caricamento lezioni in corso...</p>
    </div>

<!-- Qui incolleremo il resto dei modali (Dettagli e Aggiunta Evento) presenti nel vecchio index.html -->
<!-- ... -->

<!-- Usa l'helper per caricare il file JS -->
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>?v=9"></script>
