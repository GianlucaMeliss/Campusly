<section class="section">
    <div class="container narrow profile-container">
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div class="alert alert-success">
                Impostazioni aggiornate con successo!
            </div>
        <?php endif; ?>

        <!-- 1. Gestione Corso -->
        <div class="highlight-box">
            <h2 class="section-title">Configurazione Corso</h2>
            
            <!-- FIX ROUTE -->
            <form action="<?= htmlspecialchars($route('/profilo/aggiorna-corso')) ?>" method="POST" class="campusly-form">
                <div class="form-group">
                    <label>Nome del Corso</label>
                    <input type="text" name="course_name" value="<?= htmlspecialchars($course['name']) ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Link Calendario ID</label>
                        <input type="text" name="link_calendario_id" value="<?= htmlspecialchars($course['linkId']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Cliente ID</label>
                        <input type="text" name="cliente_id" value="<?= htmlspecialchars($course['clienteId']) ?>" required>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Aggiorna Codici</button>
                </div>
            </form>
        </div>

        <!-- 2. Corsi Nascosti -->
        <div class="highlight-box">
            <h2 class="section-title">Corsi Nascosti</h2>
            <p class="section-subtitle">Queste materie sono invisibili nel tuo calendario. Clicca su "Ripristina" per tornare a vederle.</p>
            
            <?php if (empty($hiddenCourses)): ?>
                <p style="color: var(--text-secondary); font-style: italic;">Nessun corso nascosto al momento.</p>
            <?php else: ?>
                <ul class="hidden-courses-list">
                    <?php foreach ($hiddenCourses as $hc): ?>
                        <li class="hidden-course-item">
                            <span class="course-name"><?= htmlspecialchars($hc) ?></span>
                            <!-- La funzione JS è globale, funziona perfettamente -->
                            <!-- Sostituisci il <button> esistente dei corsi nascosti con questo: -->
                            <button onclick="ripristinaCorso('<?= htmlspecialchars(addslashes($hc)) ?>')" class="btn-restore">
                                Ripristina
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
    </div>
</section>

<!-- Necessario per far funzionare il bottone ripristina (che chiama l'API JSON) -->
<script>window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';</script>

<!-- Metti questo a fondo pagina in profile.php, PRIMA dell'inclusione di app.js -->
<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';

    // Nuova funzione indipendente per la pagina Profilo
    window.ripristinaCorso = async function(nomeCorso) {
        try {
            const response = await fetch(window.APP_BASE_PATH + '/api/corsi-nascosti/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_name: nomeCorso })
            });
            if (response.ok) {
                // Ricarica la pagina automaticamente per mostrare l'aggiornamento
                location.reload(); 
            }
        } catch (error) { 
            alert("Errore di connessione al Cloud. Riprova."); 
        }
    };
</script>

<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>"></script>