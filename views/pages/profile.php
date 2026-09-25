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

        <!-- 4. GRUPPI DI STUDIO -->
        <div class="highlight-box">
            <h2 class="section-title">Gruppi di Studio & Carpooling</h2>
            <p class="section-subtitle">Condividi i tuoi orari con gli amici per organizzare viaggi, pause e sessioni di studio. Le materie nascoste non verranno mostrate agli altri.</p>
            
            <div style="margin-bottom: 20px;">
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $g): ?>
                        <div class="active-course-card">
                            <div class="course-info">
                                <h3><?= htmlspecialchars($g['name']) ?></h3>
                                <p>
                                    Codice Invito: <strong><?= htmlspecialchars($g['invite_code']) ?></strong> &bull; 
                                    Privacy: <?= $g['privacy_level'] === 'transparent' ? 'Dettaglio Completo' : ($g['privacy_level'] === 'logistical' ? 'Solo Sede' : 'Solo Occupato') ?>
                                </p>
                            </div>
                            <!-- In futuro qui aggiungeremo il tasto "Esci" o "Apri" -->
                            <a href="<?= htmlspecialchars($route('/dashboard?group=' . $g['id'])) ?>" class="btn btn-secondary btn-sm">Apri Calendario</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-state">Non fai parte di nessun gruppo al momento.</p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button onclick="document.getElementById('modal-crea-gruppo').style.display='flex'" class="btn btn-primary" style="flex: 1;">+ Crea Gruppo</button>
                <button onclick="document.getElementById('modal-unisciti-gruppo').style.display='flex'" class="btn btn-secondary" style="flex: 1;">Unisciti con Codice</button>
            </div>
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

<!-- MODALE CREA GRUPPO -->
<div id="modal-crea-gruppo" class="modale-overlay hidden" style="display: none;">
    <div class="modale-content highlight-box" style="margin: auto;">
        <button class="modale-close" onclick="this.closest('.modale-overlay').style.display='none'">&times;</button>
        <h2 class="section-title">Crea Nuovo Gruppo</h2>
        <form id="form-crea-gruppo" class="campusly-form" onsubmit="creaGruppo(event)">
            <div class="form-group">
                <label>Nome del Gruppo</label>
                <input type="text" id="new-group-name" required placeholder="Es. Compagni di Informatica">
            </div>
            <div class="form-group">
                <label>Tuo Livello di Privacy</label>
                <select id="new-group-privacy">
                    <option value="transparent">Trasparente (Mostra materie e aule esatte)</option>
                    <option value="logistical" selected>Logistico (Mostra solo in che sede ti trovi)</option>
                    <option value="opaque">Opaco (Mostra solo che sei occupato)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Genera Codice Invito</button>
        </form>
    </div>
</div>

<!-- MODALE UNISCITI GRUPPO -->
<div id="modal-unisciti-gruppo" class="modale-overlay hidden" style="display: none;">
    <div class="modale-content highlight-box" style="margin: auto;">
        <button class="modale-close" onclick="this.closest('.modale-overlay').style.display='none'">&times;</button>
        <h2 class="section-title">Unisciti a un Gruppo</h2>
        <form id="form-unisciti-gruppo" class="campusly-form" onsubmit="uniscitiGruppo(event)">
            <div class="form-group">
                <label>Codice di Invito</label>
                <input type="text" id="join-group-code" required placeholder="Es. A1B2C3D4" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Tuo Livello di Privacy</label>
                <select id="join-group-privacy">
                    <option value="transparent">Trasparente (Mostra materie e aule esatte)</option>
                    <option value="logistical" selected>Logistico (Mostra solo in che sede ti trovi)</option>
                    <option value="opaque">Opaco (Mostra solo che sei occupato)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Entra nel Gruppo</button>
        </form>
    </div>
</div>

<script>

    window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';

    window.creaGruppo = async function(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Creazione...';

        try {
            const res = await fetch(window.APP_BASE_PATH + '/api/gruppi/crea', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: document.getElementById('new-group-name').value,
                    privacy_level: document.getElementById('new-group-privacy').value,
                    csrf_token: window.CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                alert(`Gruppo creato! Il codice di invito è: ${data.invite_code}`);
                location.reload();
            } else {
                alert(data.error || 'Errore durante la creazione.');
            }
        } catch (error) {
            alert('Errore di connessione.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Genera Codice Invito';
        }
    };

    window.uniscitiGruppo = async function(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Verifica in corso...';

        try {
            const res = await fetch(window.APP_BASE_PATH + '/api/gruppi/unisciti', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    invite_code: document.getElementById('join-group-code').value.toUpperCase(),
                    privacy_level: document.getElementById('join-group-privacy').value,
                    csrf_token: window.CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.error || 'Codice non valido o errore server.');
            }
        } catch (error) {
            alert('Errore di connessione.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Entra nel Gruppo';
        }
    };

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