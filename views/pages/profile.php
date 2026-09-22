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
                            <button onclick="toggleCorsoNascosto('<?= htmlspecialchars(addslashes($hc)) ?>'); setTimeout(() => location.reload(), 500);" class="btn-restore">
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

<!-- Manteniamo $url qui per garantire il cache-busting sull'asset statico -->
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>"></script>