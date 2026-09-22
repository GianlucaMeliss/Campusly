<?php
// Helper per i percorsi sicuri
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};

// MAPPING COLORI UNIVERSITÀ
// In futuro l'ID dell'università arriverà dal Controller tramite i dati del profilo utente
$uniId = $course['university_id'] ?? 1; 

$uniColors = [
    1 => '#007161', // Insubria (Verde)
    2 => '#003366', // Statale Milano (Blu)
    3 => '#b30000', // Bicocca (Rosso)
    4 => '#e88300'  // PoliMi (Arancione)
];

// Se l'università non è in lista, usiamo il Fucsia di Campusly come fallback
$activeUniColor = $uniColors[$uniId] ?? '#E83E8C'; 
?>

<!-- Passiamo il basePath a JS -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
</script>

<!-- OVERRIDE DINAMICO DEI COLORI -->
<style>
    /* Questa classe avvolge tutto il calendario e forza l'uso del colore dell'università */
    .uni-theme {
        --primary-color: <?= $activeUniColor ?>;
        --primary-hover: <?= $activeUniColor ?>dd; /* Leggermente trasparente per l'hover */
    }
    
    .toolbar-calendario {
        background: var(--surface);
        border-bottom: 1px solid var(--border-color);
        padding: 12px 20px;
        position: sticky;
        top: 76px; /* Si aggancia esattamente sotto l'header globale di Campusly */
        z-index: 99;
        box-shadow: 0 4px 12px -4px rgba(0,0,0,0.05);
    }
    
    .toolbar-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .settimana-nav {
        display: flex;
        align-items: center;
        gap: 16px;
    }
</style>

<div class="uni-theme">
    <!-- TOOLBAR SPECIFICA DEL CALENDARIO -->
    <div class="toolbar-calendario">
        <div class="toolbar-inner">
            
            <!-- Controlli Settimana -->
            <div class="settimana-nav">
                <button id="btn-prec" class="btn btn-secondary" style="padding: 8px 12px;" aria-label="Settimana precedente">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <h2 id="label-settimana" style="font-size: 1rem; font-weight: 600; min-width: 160px; text-align: center; margin: 0;">Caricamento...</h2>
                <button id="btn-succ" class="btn btn-secondary" style="padding: 8px 12px;" aria-label="Settimana successiva">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

            <!-- Azioni Aggiuntive -->
            <div style="display: flex; gap: 12px; align-items: center;">
                <button id="btn-export" class="btn btn-secondary" style="padding: 8px 12px;" title="Esporta Calendario">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
                
                <button id="theme-toggle" class="btn btn-secondary" style="padding: 8px 12px;" title="Cambia Tema">
                    <svg id="icon-moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <svg id="icon-sun" style="display: none;" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                </button>
            </div>
            
        </div>
    </div>

    <!-- CONTENITORE PRINCIPALE CALENDARIO -->
    <div class="container" style="padding-top: 24px; padding-bottom: 80px;">
        <div id="calendario-container">
            <p id="caricamento" style="text-align: center; padding: 2rem;">⏳ Caricamento lezioni in corso...</p>
        </div>
    </div>

    <!-- Modale Dettagli Lezione -->
    <div id="modale-dettagli" class="modale-overlay" style="display: none;">
        <div class="modale-content highlight-box">
            <button id="chiudi-modale" class="modale-close" aria-label="Chiudi">&times;</button>
            <div id="modale-body">
                <!-- Contenuto iniettato via JS -->
            </div>
        </div>
    </div>

    <!-- Tasto Flottante (+) -->
    <button id="btn-add-evento" class="fab-add" style="background-color: var(--primary-color);" aria-label="Aggiungi Evento Personale">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
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
                    <label for="form-data">Data</label>
                    <input type="date" id="form-data" required>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <div class="input-group" style="flex: 1;">
                        <label for="form-inizio">Inizio</label>
                        <input type="time" id="form-inizio" required>
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label for="form-fine">Fine</label>
                        <input type="time" id="form-fine" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="form-luogo">Luogo (opzionale)</label>
                    <input type="text" id="form-luogo" placeholder="Es. Aula Studio, Casa...">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 10px; width: 100%;">Salva Evento</button>
            </form>
        </div>
    </div>
</div>

<!-- Script Principale -->
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>?v=11"></script>