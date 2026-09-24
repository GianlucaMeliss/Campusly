<section class="section">
    <div class="container narrow">
        <div class="highlight-box wizard-container">
            
            <!-- STEP 1: UNIVERSITÀ -->
            <div id="step-1" class="wizard-step active">
                <h1 class="page-title">Scegli la tua Università</h1>
                <p class="page-subtitle">Seleziona l'ateneo per scaricare il catalogo dei corsi.</p>
                
                <div class="uni-grid">
                    <?php if (!empty($universities)): ?>
                        <?php foreach ($universities as $uni): ?>
                            <?php 
                            // Fallback al logo di default se non è impostato nel DB
                            $logo = !empty($uni['logo_path']) ? $uni['logo_path'] : '/assets/img/icon-192.png'; 
                            ?>
                            <div class="uni-card" onclick="selezionaUni(<?= $uni['id'] ?>, '<?= htmlspecialchars(addslashes($uni['name'])) ?>')">
                                <img src="<?= htmlspecialchars($url($logo)) ?>" alt="<?= htmlspecialchars($uni['name']) ?>">
                                <span><?= htmlspecialchars($uni['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-secondary); grid-column: 1 / -1; text-align: center;">
                            Nessuna università attiva trovata.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button class="btn btn-ghost" onclick="apriRichiesta('uni')">Non trovi la tua università?</button>
                </div>
            </div>

            <!-- STEP 2: RICERCA CORSO -->
            <div id="step-2" class="wizard-step">
                <h1 class="page-title">Cerca il tuo corso</h1>
                <p class="page-subtitle" id="subtitle-uni-name">Insubria</p>
                
                <div class="search-bar">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="course-search" placeholder="Es. Informatica, Ostetricia..." onkeyup="filtraCorsi()">
                </div>

                <div class="course-list" id="course-list">
                    <!-- Popolato via JS -->
                </div>

                <div class="wizard-nav">
                    <button class="btn btn-ghost" onclick="vaiAStep(1)">Indietro</button>
                    <button class="btn btn-ghost" style="color: var(--primary-color);" onclick="apriRichiesta('course')">Non trovi il corso? Richiedilo</button>
                </div>
            </div>

            <!-- STEP 3: ANNO E SEDE -->
            <div id="step-3" class="wizard-step">
                <h1 class="page-title">Dettagli del Corso</h1>
                <p class="page-subtitle" id="subtitle-course-name">Nome Corso</p>
                
                <form action="<?= htmlspecialchars($route('/onboarding')) ?>" method="POST" id="form-onboarding">
                    <!-- Campi nascosti per inviare i dati al PHP -->
                    <input type="hidden" name="university_id" id="input-uni-id">
                    <input type="hidden" name="course_name" id="input-course-name">
                    <input type="hidden" name="sede" id="input-sede">
                    <input type="hidden" name="anno" id="input-anno">
                    <!-- Per la modalità guidata i codici Cineca verrebbero recuperati in backend, per ora passiamo flag -->
                    <input type="hidden" name="link_calendario_id" id="input-link-id" value="AUTO">
                    <input type="hidden" name="cliente_id" id="input-client-id" value="AUTO">

                    <h3 class="section-title" style="font-size: 1.1rem;">Sede di frequenza</h3>
                    <div class="toggle-group" id="sede-toggles">
                        <div class="toggle-btn" onclick="selezionaToggle(this, 'sede', 'Varese')">Varese</div>
                        <div class="toggle-btn" onclick="selezionaToggle(this, 'sede', 'Como')">Como</div>
                    </div>

                    <h3 class="section-title" style="font-size: 1.1rem; margin-top: 20px;">Anno accademico</h3>
                    <div class="toggle-group" id="anno-toggles">
                        <div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '1')">1° Anno</div>
                        <div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '2')">2° Anno</div>
                        <div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '3')">3° Anno</div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn btn-ghost" onclick="vaiAStep(2)">Indietro</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit" disabled>Conferma e Vai</button>
                    </div>

                    <div style="text-align: center; margin-top: 25px;">
                    <button type="button" class="btn btn-ghost" style="font-size: 0.85rem;" onclick="apriRichiesta('curriculum')">Manca la tua sede o il tuo anno? Segnalacelo</button>
                </div>

                <div class="wizard-nav">
                    <button type="button" class="btn btn-ghost" onclick="vaiAStep(2)">Indietro</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit" disabled>Conferma e Vai</button>
                </div>
                
                </form>
            </div>

            <!-- MODALE RICHIESTA INSERIMENTO -->
            <div id="modal-richiesta" class="modale-overlay hidden" style="display: none;">
                <div class="modale-content highlight-box" style="margin: auto;">
                    <button class="modale-close" onclick="chiudiRichiesta()">&times;</button>
                    <h2 id="modal-richiesta-titolo" class="section-title">Richiedi Inserimento</h2>
                    <p class="section-subtitle">Inserisci i dettagli. Il nostro team mapperà i codici e li aggiungerà il prima possibile.</p>
                    
                    <form id="form-richiesta" class="campusly-form">
                        <input type="hidden" id="richiesta-tipo" value="">
                        
                        <div class="form-group">
                            <label id="modal-richiesta-label">Nome</label>
                            <input type="text" id="richiesta-nome" required placeholder="Scrivi qui...">
                        </div>
                        
                        <!-- Campo aggiuntivo visibile dinamicamente solo se richiede un'università -->
                        <div class="form-group" id="gruppo-corso-extra" style="display: none;">
                            <label>Nome del tuo Corso di Laurea (Opzionale ma consigliato)</label>
                            <input type="text" id="richiesta-corso-extra" placeholder="Es. Informatica">
                        </div>

                        <button type="submit" class="btn btn-primary" id="btn-richiesta-submit">Invia Richiesta</button>
                    </form>
                </div>
            </div>

<!-- Passiamo le variabili essenziali al JS -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
    window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
</script>

<!-- 2. Carichiamo il file JS esterno corretto -->
<script src="<?= htmlspecialchars($url('/assets/js/onboarding.js')) ?>"></script>