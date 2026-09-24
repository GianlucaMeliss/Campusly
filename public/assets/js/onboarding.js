// Stato dell'onboarding
let wizardData = {
    uniId: null,
    courseName: null,
    sede: null,
    anno: null,
    loadedCurriculums: []
};

// Usa la variabile globale dichiarata nella vista
const apiBasePath = window.APP_BASE_PATH || '';

function vaiAStep(stepNum) {
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + stepNum).classList.add('active');
}

async function selezionaUni(id, nome) {
    wizardData.uniId = id;
    document.getElementById('subtitle-uni-name').textContent = nome;
    document.getElementById('input-uni-id').value = id;
    
    const list = document.getElementById('course-list');
    list.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text-secondary);">Caricamento corsi...</div>';
    
    vaiAStep(2);

    try {
        const response = await fetch(`${apiBasePath}/api/universita/${id}/corsi`);
        if (!response.ok) throw new Error("Errore API");
        
        const corsi = await response.json();
        list.innerHTML = '';
        
        if (corsi.length > 0) {
            corsi.forEach(corso => {
                const nomeSafe = corso.name.replace(/'/g, "\\'");
                // AGGIUNTO IL PASSAGGIO DELL'ID CORSO:
                list.innerHTML += `<div class="course-item" onclick="selezionaCorso(${corso.id}, '${nomeSafe}')">${corso.name}</div>`;
            });
        } else {
            list.innerHTML = `<div style="text-align:center; padding: 20px;"><button class="btn btn-secondary" onclick="apriRichiesta('course')">Nessun corso, richiedilo ora</button></div>`;
        }
    } catch (error) {
        list.innerHTML = '<div style="text-align:center; padding: 20px; color: #ef4444;">Errore di connessione.</div>';
    }
}

async function selezionaCorso(courseId, nomeCorso) {
    wizardData.courseName = nomeCorso;
    document.getElementById('subtitle-course-name').textContent = nomeCorso;
    document.getElementById('input-course-name').value = nomeCorso;
    
    // Reset di tutti i campi
    wizardData.sede = null; 
    wizardData.anno = null;
    wizardData.loadedCurriculums = [];
    document.getElementById('input-sede').value = '';
    document.getElementById('input-anno').value = '';
    document.getElementById('btn-submit').disabled = true;
    
    const containerSede = document.getElementById('sede-toggles');
    const containerAnno = document.getElementById('anno-toggles');
    
    containerSede.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Caricamento sedi...</p>';
    containerAnno.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Seleziona prima una sede</p>';
    
    vaiAStep(3);

    try {
        const response = await fetch(`${apiBasePath}/api/corsi/${courseId}/curriculums`);
        const text = await response.text();
        
        let curriculums;
        try {
            curriculums = JSON.parse(text);
        } catch (e) {
            console.error("Risposta non JSON dal server:", text);
            throw new Error("Dati non validi");
        }

        wizardData.loadedCurriculums = curriculums;

        if (curriculums.length > 0) {
            const sedi = [...new Set(curriculums.map(c => c.campus_location).filter(Boolean))];

            if (sedi.length === 1) {
                // CASO 1: C'è una sola sede. La stampiamo e prepariamo subito gli anni.
                const sedeScelta = sedi[0];
                wizardData.sede = sedeScelta;
                document.getElementById('input-sede').value = sedeScelta;
                containerSede.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${sedeScelta} (Unica)</div>`;
                
                // Filtriamo gli anni per questa unica sede
                const curriculumsSede = curriculums.filter(c => c.campus_location === sedeScelta);
                const anniSede = [...new Set(curriculumsSede.map(c => c.year).filter(Boolean))].sort();

                if (anniSede.length === 1) {
                    // C'è pure un solo anno! (Caso perfetto, sblocca il bottone submit)
                    wizardData.anno = anniSede[0];
                    document.getElementById('input-anno').value = anniSede[0];
                    containerAnno.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${anniSede[0]}° Anno (Unico)</div>`;
                    document.getElementById('btn-submit').removeAttribute('disabled');
                } else if (anniSede.length > 1) {
                    // Ci sono più anni per l'unica sede, falli scegliere
                    containerAnno.innerHTML = anniSede.map(a => `<div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '${a}')">${a}° Anno</div>`).join('');
                }
            } else {
                // CASO 2: Ci sono più sedi. L'utente deve cliccarne una per sbloccare gli anni.
                containerSede.innerHTML = sedi.map(s => `<div class="toggle-btn" onclick="selezionaToggle(this, 'sede', '${s}')">${s}</div>`).join('');
                containerAnno.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Seleziona prima una sede</p>';
            }
        } else {
            // CASO 3: Fallback. Il corso esiste ma non ha configurazioni nel DB.
            wizardData.sede = 'Principale'; wizardData.anno = 1;
            document.getElementById('input-sede').value = 'Principale';
            document.getElementById('input-anno').value = 1;
            containerSede.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">Principale</div>`;
            containerAnno.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">1° Anno</div>`;
            document.getElementById('btn-submit').removeAttribute('disabled');
        }

    } catch (e) {
        console.error("Errore JS:", e);
        containerSede.innerHTML = '<p style="color:#ef4444;">Errore di caricamento</p>';
        containerAnno.innerHTML = '';
    }
}

function selezionaToggle(element, type, value) {
    if (element) {
        const siblings = element.parentElement.querySelectorAll('.toggle-btn');
        siblings.forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
    }
    
    wizardData[type] = value;
    document.getElementById('input-' + type).value = value;

    // Se stiamo selezionando una sede, calcoliamo dinamicamente gli anni
    if (type === 'sede') {
        wizardData.anno = null;
        document.getElementById('input-anno').value = '';
        document.getElementById('btn-submit').disabled = true;

        const containerAnno = document.getElementById('anno-toggles');
        const curriculumsSede = wizardData.loadedCurriculums.filter(c => c.campus_location === value);
        const anniSede = [...new Set(curriculumsSede.map(c => c.year).filter(Boolean))].sort();

        if (anniSede.length === 1) {
            wizardData.anno = anniSede[0];
            document.getElementById('input-anno').value = anniSede[0];
            containerAnno.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${anniSede[0]}° Anno (Unico)</div>`;
            document.getElementById('btn-submit').removeAttribute('disabled');
        } else if (anniSede.length > 1) {
            containerAnno.innerHTML = anniSede.map(a => `<div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '${a}')">${a}° Anno</div>`).join('');
        } else {
            containerAnno.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Nessun anno trovato</p>';
        }
    }

    // Se abbiamo sia la sede che l'anno, abilitiamo il form
    if (wizardData.sede && wizardData.anno) {
        document.getElementById('btn-submit').removeAttribute('disabled');
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

// --- GESTIONE RICHIESTE (EMAIL + DB) ---

// --- GESTIONE RICHIESTE (EMAIL + DB) ---

function apriRichiesta(tipo) {
    const modal = document.getElementById('modal-richiesta');
    const titolo = document.getElementById('modal-richiesta-titolo');
    const label = document.getElementById('modal-richiesta-label');
    const tipoInput = document.getElementById('richiesta-tipo');
    
    const gruppoCorsoExtra = document.getElementById('gruppo-corso-extra');
    const inputCorsoExtra = document.getElementById('richiesta-corso-extra');
    
    if (tipo === 'uni') {
        titolo.textContent = "Richiedi Università";
        label.textContent = "Nome esatto dell'Ateneo";
        gruppoCorsoExtra.style.display = 'flex'; // Mostra il campo del corso aggiuntivo
        inputCorsoExtra.value = '';
    } else {
        titolo.textContent = "Richiedi Corso";
        label.textContent = "Nome esatto del Corso di Laurea";
        gruppoCorsoExtra.style.display = 'none'; // Nascondilo se sta già richiedendo solo il corso
        inputCorsoExtra.value = '';
    }
    
    tipoInput.value = tipo;
    document.getElementById('richiesta-nome').value = '';
    
    modal.style.display = 'flex';
}

function chiudiRichiesta() {
    document.getElementById('modal-richiesta').style.display = 'none';
}

document.getElementById('form-richiesta').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btn-richiesta-submit');
    const originalText = btn.textContent;
    btn.textContent = 'Invio in corso...';
    btn.disabled = true;

    const payload = {
        type: document.getElementById('richiesta-tipo').value,
        name: document.getElementById('richiesta-nome').value,
        course_extra: document.getElementById('richiesta-corso-extra').value, // Invio del nuovo campo extra
        csrf_token: window.CSRF_TOKEN
    };

    try {
        const response = await fetch(`${apiBasePath}/api/richiesta-onboarding`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (response.ok) {
            alert("Richiesta inviata con successo! Ti avviseremo appena sarà disponibile.");
            chiudiRichiesta();
        } else {
            alert("Impossibile inviare la richiesta. Riprova più tardi.");
        }
    } catch (err) {
        alert("Errore di rete. Controlla la connessione.");
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
});