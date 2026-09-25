<section class="section">
    <div class="container narrow profile-container">
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'removed'): ?>
            <div class="alert alert-success">Corso rimosso dal tuo piano di studi.</div>
        <?php endif; ?>

        <!-- 1. I TUOI CORSI -->
        <div class="highlight-box">
            <h2 class="section-title">I Tuoi Corsi</h2>
            <p class="section-subtitle">Stai seguendo le lezioni per questi curriculum. Gli orari sono uniti automaticamente nel tuo calendario.</p>
            
            <div style="margin-bottom: 20px;">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $c): ?>
                        <div class="active-course-card">
                            <div class="course-info">
                                <h3><?= htmlspecialchars($c['course_name']) ?></h3>
                                <p><?= htmlspecialchars($c['uni_name']) ?> &bull; <?= htmlspecialchars($c['campus_location']) ?> &bull; <?= $c['year'] ?>&deg; Anno</p>
                            </div>
                            <form action="<?= htmlspecialchars($route('/profilo/rimuovi-corso')) ?>" method="POST" onsubmit="return confirm('Vuoi davvero rimuovere questo corso dal calendario?');">
                                <input type="hidden" name="profile_id" value="<?= $c['profile_id'] ?>">
                                <button type="submit" class="btn-danger" title="Rimuovi corso">X</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">Nessun corso configurato.</p>
                <?php endif; ?>
            </div>

            <a href="<?= htmlspecialchars($route('/onboarding?add=1')) ?>" class="btn btn-primary btn-full-width">+ Aggiungi un altro Corso / Anno</a>
        </div>

        <!-- 2. CORSI NASCOSTI -->
        <div class="highlight-box">
            <h2 class="section-title">Materie Nascoste</h2>
            <p class="section-subtitle">Queste materie sono invisibili nel tuo calendario.</p>
            
            <?php if (empty($hiddenCourses)): ?>
                <p class="empty-state">Nessun corso nascosto al momento.</p>
            <?php else: ?>
                <ul class="hidden-courses-list">
                    <?php foreach ($hiddenCourses as $hc): ?>
                        <li class="hidden-course-item">
                            <span class="course-name"><?= htmlspecialchars($hc) ?></span>
                            <button onclick="ripristinaCorso('<?= htmlspecialchars(addslashes($hc)) ?>')" class="btn-restore">Ripristina</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- 3. PREFERENZE -->
        <div class="highlight-box">
            <h2 class="section-title">Preferenze App</h2>
            <p class="section-subtitle">Scegli il tema dell'interfaccia. La scelta verrà sincronizzata automaticamente su tutti i tuoi dispositivi.</p>
            
            <div class="form-group" style="max-width: 300px;">
                <label for="theme-select">Tema visivo</label>
                <select id="theme-select" onchange="window.cambiaTemaCloud(this.value)">
                    <option value="light" <?= ($_SESSION['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>☀️ Modalità Chiara</option>
                    <option value="dark" <?= ($_SESSION['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>🌙 Modalità Scura</option>
                </select>
            </div>
        </div>
    </div>
</section>

<script>
    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
    window.ripristinaCorso = async function(nomeCorso) {
        try {
            const response = await fetch(window.APP_BASE_PATH + '/api/corsi-nascosti/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_name: nomeCorso })
            });
            if (response.ok) location.reload(); 
        } catch (error) { alert("Errore di connessione al Cloud."); }
    };
</script>
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>"></script>