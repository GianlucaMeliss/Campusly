<?php
// MAPPING COLORI UNIVERSITÀ (In futuro gestito dinamicamente dal DB)
$uniId = $course['university_id'] ?? 1;
$uniColors = [
    1 => '#007161', // Insubria (Verde)
    2 => '#003366', // Statale Milano (Blu)
    3 => '#b30000', // Bicocca (Rosso)
    4 => '#e88300'  // PoliMi (Arancione)
];
// Fallback al Fucsia Campusly se l'ID non è mappato
$activeUniColor = $uniColors[$uniId] ?? '#E83E8C'; 
?>

<!-- Passiamo il path a JavaScript -->
<?php $groupId = $_GET['group'] ?? 'null'; ?>
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
    window.ACTIVE_GROUP_ID = <?= htmlspecialchars($groupId) ?>;
</script>

<!-- WRAPPER TEMA UNIVERSITÀ: Passa il colore dinamicamente al CSS -->
<div class="uni-theme" style="--uni-primary: <?= $activeUniColor ?>; --uni-primary-hover: <?= $activeUniColor ?>E6;">

<!-- Passiamo il path a JavaScript -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
</script>

<!-- WRAPPER TEMA UNIVERSITÀ: Passa il colore dinamicamente al CSS -->
<div class="uni-theme" style="--uni-primary: <?= $activeUniColor ?>; --uni-primary-hover: <?= $activeUniColor ?>E6;">
    
    <!-- 1. TOOLBAR CALENDARIO -->
    <div class="toolbar-calendario">
        <?php if ($groupId !== 'null'): ?>
            <div style="background: var(--brand-purple); color: white; text-align: center; padding: 8px; font-weight: 600; font-size: 0.9rem; display: flex; justify-content: center; align-items: center; gap: 10px;">
                <span>👥 Stai visualizzando il calendario di gruppo</span>
                <a href="<?= htmlspecialchars($route('/dashboard')) ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white; border: none; padding: 4px 10px;">Torna al mio</a>
            </div>
        <?php endif; ?>
        <div class="container toolbar-inner">
            
            <!-- Controlli Settimana -->
            <div class="settimana-nav">
                <button id="btn-prec" class="btn-icon" aria-label="Settimana precedente">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <h2 id="label-settimana">Caricamento...</h2>
                <button id="btn-succ" class="btn-icon" aria-label="Settimana successiva">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

            <!-- Azioni -->
            <div class="toolbar-actions">
                <div id="sync-status" class="sync-status" title="Stato sincronizzazione"></div>
                <button id="btn-export" class="btn-icon" title="Esporta Calendario">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
            </div>
            
        </div>
    </div>

    <!-- 2. GRIGLIA CALENDARIO PRINCIPALE -->
    <div class="container calendario-wrapper">
        <div id="calendario-container">
            <div class="loading-state">
                <div class="spinner"></div>
                <p id="caricamento">Caricamento lezioni in corso...</p>
            </div>
        </div>
    </div>

    <!-- 3. MODALE DETTAGLI LEZIONE -->
    <div id="modale-dettagli" class="modale-overlay hidden">
        <div class="modale-content highlight-box">
            <button id="chiudi-modale" class="modale-close" aria-label="Chiudi">&times;</button>
            <div id="modale-body">
                <!-- Il contenuto viene iniettato da app.js -->
            </div>
        </div>
    </div>

    <!-- 4. TASTO FLOTTANTE (FAB) -->
    <button id="btn-add-evento" class="fab-add" aria-label="Aggiungi Evento">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
    </button>

    <!-- 5. MODALE NUOVO EVENTO -->
    <div id="modale-form-evento" class="modale-overlay hidden">
        <div class="modale-content highlight-box">
            <button id="chiudi-modale-form" class="modale-close" aria-label="Chiudi">&times;</button>
            <h2 class="form-title">Nuovo Evento</h2>
            
            <form id="form-nuovo-evento" class="form-personale">
                <div class="input-group">
                    <label for="form-titolo">Titolo Evento</label>
                    <input type="text" id="form-titolo" placeholder="Es. Studio in Biblioteca" required>
                </div>
                
                <div class="input-group">
                    <label for="form-data">Data</label>
                    <input type="date" id="form-data" required>
                </div>
                
                <div class="form-row">
                    <div class="input-group">
                        <label for="form-inizio">Inizio</label>
                        <input type="time" id="form-inizio" required>
                    </div>
                    <div class="input-group">
                        <label for="form-fine">Fine</label>
                        <input type="time" id="form-fine" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="form-luogo">Luogo (opzionale)</label>
                    <input type="text" id="form-luogo" placeholder="Es. Aula Studio, Casa...">
                </div>
                
                <button type="submit" class="btn btn-uni form-submit">Salva Evento</button>
            </form>
        </div>
    </div>
    
</div>
<!-- MODALE INSTALLAZIONE PWA (Dinamico) -->
    <div id="pwa-install-modal" class="modale-overlay hidden" style="display: none; z-index: 3000;">
        <div class="modale-content highlight-box" style="text-align: center; padding: 30px;">
            <button class="modale-close" id="pwa-close-btn">&times;</button>
            <div id="pwa-icon" style="font-size: 48px; margin-bottom: 16px;">📱</div>
            <h2 id="pwa-title" class="section-title">Installa Campusly</h2>
            <p id="pwa-desc" class="section-subtitle" style="margin-bottom: 24px;">Aggiungi l'app per un accesso immediato e offline.</p>
            
            <!-- Questo contenitore verrà popolato dal JS con le istruzioni o il bottone -->
            <div id="pwa-action-container"></div>
        </div>
    </div>

<!-- Script base (Dark mode, Modali, PWA) -->
<script src="<?= $url('/assets/js/app.js') ?>"></script>

<!-- Script specifico del Calendario (Chiamate API e render griglia) -->
<script src="<?= $url('/assets/js/calendar.js') ?>"></script>