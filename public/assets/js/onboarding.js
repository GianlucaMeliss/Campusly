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
    
    // Reset campi e UI
    wizardData.sede = null; wizardData.anno = null;
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
        const curriculums = await response.json();
        wizardData.loadedCurriculums = curriculums; // Salviamo i dati per poterli filtrare dopo

        if (curriculums.length > 0) {
            // Estrapoliamo SOLO le sedi per ora
            const sedi = [...new Set(curriculums.map(c => c.campus_location).filter(Boolean))];

            if (sedi.length === 1) {
                // Se c'è una sola sede, la selezioniamo e carichiamo subito gli anni disponibili per lei
                selezionaToggle(null, 'sede', sedi[0], true);
            } else {
                // Se ci sono più sedi, le mostriamo e aspettiamo il clic
                containerSede.innerHTML = sedi.map(s => `<div class="toggle-btn" onclick="selezionaToggle(this, 'sede', '${s}')">${s}</div>`).join('');
            }
        } else {
            // Caso di fallback: il corso è nel DB ma non ha nessun curriculum
            wizardData.sede = 'Principale'; wizardData.anno = 1;
            document.getElementById('input-sede').value = 'Principale';
            document.getElementById('input-anno').value = 1;
            containerSede.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">Principale</div>`;
            containerAnno.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">1° Anno</div>`;
            document.getElementById('btn-submit').removeAttribute('disabled');
        }
    } catch (e) {
        containerSede.innerHTML = '<p style="color:#ef4444;">Errore di caricamento</p>';
        containerAnno.innerHTML = '';
    }
}

function selezionaToggle(element, type, value, isAuto = false) {
    if (element) {
        const siblings = element.parentElement.querySelectorAll('.toggle-btn');
        siblings.forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
    }
    
    wizardData[type] = value;
    document.getElementById('input-' + type).value = value;

    // Se l'utente ha appena scelto una SEDE, calcoliamo dinamicamente gli ANNI disponibili per quella sede
    if (type === 'sede') {
        // Resettiamo l'anno precedente
        wizardData.anno = null;
        document.getElementById('input-anno').value = '';
        document.getElementById('btn-submit').disabled = true;

        const containerAnno = document.getElementById('anno-toggles');
        
        // Filtriamo i curriculums salvati tenendo solo quelli della sede scelta
        const curriculumsSede = wizardData.loadedCurriculums.filter(c => c.campus_location === value);
        const anniSede = [...new Set(curriculumsSede.map(c => c.year).filter(Boolean))].sort();

        // Renderizziamo il blocco Sede (se forzato dall'isAuto)
        if (isAuto) {
            const containerSede = document.getElementById('sede-toggles');
            containerSede.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${value} (Unica)</div>`;
        }

        // Renderizziamo dinamicamente gli anni
        if (anniSede.length === 1) {
            // Se c'è un solo anno per questa sede, selezionalo in automatico
            wizardData.anno = anniSede[0];
            document.getElementById('input-anno').value = anniSede[0];
            containerAnno.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${anniSede[0]}° Anno (Unico)</div>`;
        } else if (anniSede.length > 1) {
            // Se ci sono più anni, mostrali
            containerAnno.innerHTML = anniSede.map(a => `<div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '${a}')">${a}° Anno</div>`).join('');
        } else {
            containerAnno.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Nessun anno specificato</p>';
        }
    }

    // Abilita il submit solo se abbiamo sia sede che anno validi
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

async function apriRichiesta(tipo) {
    const msg = tipo === 'uni' ? "la tua Università" : "il tuo Corso";
    const request = prompt(`Come si chiama ${msg}? Lo aggiungeremo il prima possibile!`);
    
    if (request && request.trim() !== '') {
        alert("Richiesta inviata con successo! Il nostro team la valuterà a breve.");
    }
}