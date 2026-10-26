<?php
// MAPPING COLORI UNIVERSITÀ
$uniId = $course['university_id'] ?? 1;
$uniColors = [
    1 => '#007161', // Insubria (Verde)
    2 => '#003366', // Statale Milano (Blu)
    3 => '#b30000', // Bicocca (Rosso)
    4 => '#e88300'  // PoliMi (Arancione)
];
$activeUniColor = $uniColors[$uniId] ?? '#E83E8C'; 
$groupId = max(0, (int)($_GET['group'] ?? 0));
$groups = $groups ?? [];

// Cerchiamo i dettagli del gruppo attivo se selezionato
$activeGroup = null;
foreach ($groups as $g) {
    if ((int)$g['id'] === $groupId) {
        $activeGroup = $g;
        break;
    }
}
?>

<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
    window.ACTIVE_GROUP_ID = <?= $groupId > 0 ? $groupId : 'null' ?>;
    window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
</script>

<div class="uni-theme" style="--uni-primary: <?= $activeUniColor ?>; --uni-primary-hover: <?= $activeUniColor ?>E6;">
    
    <!-- 1. TOOLBAR CALENDARIO -->
    <div class="toolbar-calendario">
        <div class="container">
            
            <!-- BARRA SWITCHER: MIO ORARIO / GRUPPI DI STUDIO -->
            <div class="group-switcher-bar">
                <div class="group-pills">
                    <a href="<?= htmlspecialchars($route('/dashboard')) ?>" class="group-pill <?= $groupId === 0 ? 'active' : '' ?>">
                        📅 Il Mio Orario
                    </a>
                    <?php foreach ($groups as $g): ?>
                        <a href="<?= htmlspecialchars($route('/dashboard?group=' . $g['id'])) ?>" class="group-pill <?= (int)$g['id'] === $groupId ? 'active' : '' ?>">
                            👥 <?= htmlspecialchars($g['name']) ?>
                            <span class="pill-count"><?= (int)$g['members_count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                    <button type="button" class="group-pill pill-add" onclick="document.getElementById('modale-gestione-gruppi').style.display='flex'" title="Crea o unisciti a un gruppo">
                        + Gruppo
                    </button>
                </div>

                <?php if ($activeGroup): ?>
                    <div class="active-group-info">
                        <button type="button" class="invite-badge" onclick="condividiLinkGruppo('<?= htmlspecialchars($activeGroup['invite_code']) ?>', '<?= htmlspecialchars(addslashes($activeGroup['name'])) ?>', this)" title="Condividi link di invito">
                            🔗 Invita Amici (<strong><?= htmlspecialchars($activeGroup['invite_code']) ?></strong>)
                        </button>
                        <select class="privacy-quick-select" onchange="cambiaPrivacyGruppo(<?= (int)$activeGroup['id'] ?>, this.value)" title="Il tuo livello di privacy in questo gruppo">
                            <option value="transparent" <?= $activeGroup['privacy_level'] === 'transparent' ? 'selected' : '' ?>>👁️ Mostra materie e aule</option>
                            <option value="logistical" <?= $activeGroup['privacy_level'] === 'logistical' ? 'selected' : '' ?>>📍 Mostra solo sede/aula</option>
                            <option value="opaque" <?= $activeGroup['privacy_level'] === 'opaque' ? 'selected' : '' ?>>🔒 Mostra solo "Occupato"</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="toolbar-inner">
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
                    <?php if ($groupId === 0): ?>
                        <button id="btn-export" class="btn-icon" title="Esporta Calendario (.ics)">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </button>
                    <?php endif; ?>
                </div>
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
            <div id="modale-body"></div>
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
                <button type="submit" class="btn btn-primary form-submit">Salva Evento</button>
            </form>
        </div>
    </div>

    <!-- 6. MODALE RAPIDO GESTIONE GRUPPI (Dalla Dashboard) -->
    <div id="modale-gestione-gruppi" class="modale-overlay hidden">
        <div class="modale-content highlight-box">
            <button class="modale-close" onclick="this.closest('.modale-overlay').style.display='none'">&times;</button>
            <h2 class="section-title" style="margin-bottom: 6px;">Gruppi di Studio</h2>
            <p class="section-subtitle" style="margin-bottom: 20px;">Confronta gli orari con i tuoi amici per trovare pause comuni o organizzare il viaggio.</p>

            <div class="group-modal-tabs">
                <button type="button" class="g-tab active" onclick="switchGroupModalTab('join', this)">Unisciti con Codice</button>
                <button type="button" class="g-tab" onclick="switchGroupModalTab('create', this)">Crea Nuovo</button>
            </div>

            <!-- Form Unisciti -->
            <form id="dash-form-join-group" class="campusly-form" onsubmit="dashJoinGroup(event)">
                <div class="form-group">
                    <label>Codice di Invito (8 caratteri)</label>
                    <input type="text" id="dash-join-code" required placeholder="Es. A1B2C3D4" style="text-transform: uppercase; font-family: monospace; letter-spacing: 2px; text-align: center; font-size: 1.1rem;">
                </div>
                <div class="form-group">
                    <label>Cosa vuoi far vedere agli altri?</label>
                    <select id="dash-join-privacy">
                        <option value="transparent" selected>👁️ Trasparente (Materie e aule esatte)</option>
                        <option value="logistical">📍 Logistico (Solo sede/aula in cui sei)</option>
                        <option value="opaque">🔒 Opaco (Solo "Occupato")</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entra nel Gruppo</button>
            </form>

            <!-- Form Crea -->
            <form id="dash-form-create-group" class="campusly-form" style="display: none;" onsubmit="dashCreateGroup(event)">
                <div class="form-group">
                    <label>Nome del Gruppo</label>
                    <input type="text" id="dash-create-name" required placeholder="Es. Pendolari Como, Team Progetto...">
                </div>
                <div class="form-group">
                    <label>Cosa vuoi far vedere agli altri?</label>
                    <select id="dash-create-privacy">
                        <option value="transparent" selected>👁️ Trasparente (Materie e aule esatte)</option>
                        <option value="logistical">📍 Logistico (Solo sede/aula in cui sei)</option>
                        <option value="opaque">🔒 Opaco (Solo "Occupato")</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Crea e Genera Codice</button>
            </form>
        </div>
    </div>
    
</div>

<!-- 7. MODALE NOVITÀ AGGIORNAMENTO (Compare 1 sola volta) -->
<div id="whats-new-modal" class="modale-overlay hidden" style="display: none; z-index: 2500;">
    <div class="modale-content highlight-box" style="padding: 28px;">
        <button class="modale-close" onclick="chiudiWhatsNew()">&times;</button>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <span style="background: var(--bg-main); border: 1px solid var(--border-color); color: var(--text-primary); font-size: 0.78rem; font-weight: 800; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                🚀 Nuovo Aggiornamento
            </span>
            <h2 class="section-title" style="margin-top: 10px; margin-bottom: 4px;">Novità su Campusly!</h2>
            <p class="section-subtitle" style="margin-bottom: 0;">Abbiamo reso i Gruppi di Studio molto più potenti e immediati.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; align-items: flex-start; background: var(--bg-main); padding: 12px 14px; border-radius: var(--radius-md);">
                <span style="font-size: 1.4rem;">⚡</span>
                <div>
                    <strong style="font-size: 0.92rem; display: block; color: var(--text-primary);">Gruppi sempre a portata di tap</strong>
                    <span style="font-size: 0.82rem; color: var(--text-secondary);">Passa dal tuo orario personale a quello dei tuoi gruppi direttamente dalla barra in alto nel calendario.</span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: flex-start; background: var(--bg-main); padding: 12px 14px; border-radius: var(--radius-md);">
                <span style="font-size: 1.4rem;">✨</span>
                <div>
                    <strong style="font-size: 0.92rem; display: block; color: var(--text-primary);">Calcolo automatico "Buchi Liberi"</strong>
                    <span style="font-size: 0.82rem; color: var(--text-secondary);">Campusly trova in automatico le fasce orarie in cui tu e i tuoi amici siete tutti liberi contemporaneamente.</span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: flex-start; background: var(--bg-main); padding: 12px 14px; border-radius: var(--radius-md);">
                <span style="font-size: 1.4rem;">🔗</span>
                <div>
                    <strong style="font-size: 0.92rem; display: block; color: var(--text-primary);">Invito rapido con Magic Link</strong>
                    <span style="font-size: 0.82rem; color: var(--text-secondary);">Invia il link del gruppo su WhatsApp o Telegram: i tuoi compagni entreranno con un solo tap.</span>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-primary" style="width: 100%;" onclick="chiudiWhatsNew()">
            Fantastico, inizia!
        </button>
    </div>
</div>

<!-- MODALE INSTALLAZIONE PWA (Dinamico) -->
<div id="pwa-install-modal" class="modale-overlay hidden" style="display: none; z-index: 3000;">
    <div class="modale-content highlight-box" style="text-align: center; padding: 30px;">
        <button class="modale-close" id="pwa-close-btn">&times;</button>
        <div id="pwa-icon" style="font-size: 48px; margin-bottom: 16px;">📱</div>
        <h2 id="pwa-title" class="section-title">Installa Campusly</h2>
        <p id="pwa-desc" class="section-subtitle" style="margin-bottom: 24px;">Aggiungi l'app per un accesso immediato e offline.</p>
        <div id="pwa-action-container"></div>
    </div>
</div>

<script src="<?= $url('/assets/js/app.js') ?>"></script>
<script src="<?= $url('/assets/js/calendar.js') ?>"></script>