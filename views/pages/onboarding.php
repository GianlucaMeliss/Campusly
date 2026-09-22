<style>
/* CSS Specifico per il Wizard di Onboarding */
.wizard-container {
    overflow: hidden;
    position: relative;
    min-height: 400px;
}

.wizard-step {
    display: none;
    animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.wizard-step.active {
    display: block;
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(30px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Griglia Università */
.uni-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.uni-card {
    background: var(--surface);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 20px 10px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-speed);
}

.uni-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 8px 24px rgba(232, 62, 140, 0.15);
    transform: translateY(-2px);
}

.uni-card img {
    height: 48px;
    object-fit: contain;
    margin-bottom: 10px;
}

.uni-card span {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Ricerca Corsi */
.search-bar {
    position: relative;
    margin-bottom: 20px;
}

.search-bar input {
    padding-left: 45px;
    font-size: 1.1rem;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.course-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
}

.course-item {
    padding: 16px;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
}

.course-item:hover {
    background: var(--surface);
    border-color: var(--primary-color);
}

/* Selezione a bottoni (Anno/Sede) */
.toggle-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.toggle-btn {
    flex: 1;
    min-width: 80px;
    padding: 12px;
    text-align: center;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    color: var(--text-secondary);
    transition: all 0.2s;
}

.toggle-btn.selected {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.wizard-nav {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.btn-ghost {
    background: transparent;
    color: var(--text-secondary);
    border: none;
}
.btn-ghost:hover { color: var(--text-primary); }
</button>
</style>

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
                    <button class="btn btn-ghost" style="color: var(--primary-color);" onclick="vaiAStep(4)">Inserimento Manuale Avanzato</button>
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
                </form>
            </div>

            <!-- STEP 4: INSERIMENTO MANUALE (Per i corsi non mappati / Smanettoni) -->
            <div id="step-4" class="wizard-step">
                <h1 class="page-title">Inserimento Manuale</h1>
                <p class="page-subtitle">Incolla i codici identificativi del portale Cineca.</p>
                
                <form action="<?= htmlspecialchars($route('/onboarding')) ?>" method="POST" class="campusly-form">
                    <input type="hidden" name="university_id" value="1">
                    
                    <div class="form-group">
                        <label>Nome del tuo Corso (es. Informatica 1° Anno)</label>
                        <input type="text" name="course_name" required placeholder="Es. Informatica">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Link Calendario ID</label>
                            <input type="text" name="link_calendario_id" required placeholder="es. 6a69b9...">
                        </div>
                        <div class="form-group">
                            <label>Cliente ID</label>
                            <input type="text" name="cliente_id" required placeholder="es. 59f051...">
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn btn-ghost" onclick="vaiAStep(2)">Indietro</button>
                        <button type="submit" class="btn btn-primary">Salva Codici</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<script>
// Aggiungiamo il base path dinamico
window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';

let wizardData = {
    uniId: null,
    courseName: null,
    sede: null,
    anno: null
};

function vaiAStep(stepNum) {
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + stepNum).classList.add('active');
}

async function selezionaUni(id, nome) {
    wizardData.uniId = id;
    document.getElementById('subtitle-uni-name').textContent = nome;
    document.getElementById('input-uni-id').value = id;
    
    // Mostriamo un feedback di caricamento
    const list = document.getElementById('course-list');
    list.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text-secondary);">Caricamento corsi...</div>';
    
    // Andiamo allo Step 2 mentre i dati caricano in background
    vaiAStep(2);

    try {
        // Fetch dinamica al nuovo endpoint API
        const response = await fetch(`${window.APP_BASE_PATH}/api/universita/${id}/corsi`);
        if (!response.ok) throw new Error("Errore API");
        
        const corsi = await response.json();
        list.innerHTML = '';
        
        if (corsi.length > 0) {
            corsi.forEach(corso => {
                // Gestiamo gli apici singoli nei nomi dei corsi (es. "Scienze dell'Educazione")
                const nomeSafe = corso.name.replace(/'/g, "\\'");
                list.innerHTML += `<div class="course-item" onclick="selezionaCorso('${nomeSafe}')">${corso.name}</div>`;
            });
        } else {
            // Se il DB per questa uni è vuoto, mostriamo subito il bottone richiesta
            list.innerHTML = `
                <div id="no-results-btn" style="text-align:center; padding: 20px;">
                    <p style="color: var(--text-secondary); margin-bottom: 10px;">Nessun corso presente per questa università.</p>
                    <button class="btn btn-secondary" onclick="apriRichiesta('course')">Richiedi inserimento</button>
                </div>
            `;
        }
    } catch (error) {
        list.innerHTML = '<div style="text-align:center; padding: 20px; color: #ef4444;">Errore di connessione. Riprova.</div>';
    }
}

function filtraCorsi() {
    const term = document.getElementById('course-search').value.toLowerCase();
    const items = document.querySelectorAll('.course-item');
    let matchCount = 0;
    
    items.forEach(item => {
        if (item.textContent.toLowerCase().includes(term)) {
            item.style.display = 'block';
            matchCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const noResultsId = 'no-results-btn';
    let btn = document.getElementById(noResultsId);
    
    if (matchCount === 0) {
        if (!btn) {
            document.getElementById('course-list').insertAdjacentHTML('beforeend', `
                <div id="${noResultsId}" style="text-align:center; padding: 20px;">
                    <p style="color: var(--text-secondary); margin-bottom: 10px;">Corso non trovato.</p>
                    <button class="btn btn-secondary" onclick="apriRichiesta('course')">Richiedi inserimento</button>
                </div>
            `);
        }
    } else if (btn) {
        btn.remove();
    }
}

function selezionaCorso(nomeCorso) {
    wizardData.courseName = nomeCorso;
    document.getElementById('subtitle-course-name').textContent = nomeCorso;
    document.getElementById('input-course-name').value = nomeCorso;
    vaiAStep(3);
}

function selezionaToggle(element, type, value) {
    const siblings = element.parentElement.querySelectorAll('.toggle-btn');
    siblings.forEach(el => el.classList.remove('selected'));
    
    element.classList.add('selected');
    wizardData[type] = value;
    document.getElementById('input-' + type).value = value;

    if (wizardData.sede && wizardData.anno) {
        document.getElementById('btn-submit').removeAttribute('disabled');
    }
}

async function apriRichiesta(tipo) {
    const msg = tipo === 'uni' ? "la tua Università" : "il tuo Corso";
    const request = prompt(`Come si chiama ${msg}? Lo aggiungeremo il prima possibile!`);
    
    if (request && request.trim() !== '') {
        // In futuro collegherai questo payload a un endpoint POST per popolare `onboarding_requests`
        alert("Richiesta inviata con successo! Il nostro team la valuterà a breve.");
    }
}
</script>