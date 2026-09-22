// Stato dell'onboarding
let wizardData = {
    uniId: null,
    courseName: null,
    sede: null,
    anno: null
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
    document.getElementById('input-sede').value = '';
    document.getElementById('input-anno').value = '';
    document.getElementById('btn-submit').disabled = true;
    
    const containerSede = document.getElementById('sede-toggles');
    const containerAnno = document.getElementById('anno-toggles');
    
    containerSede.innerHTML = '<p>Caricamento sedi...</p>';
    containerAnno.innerHTML = '<p>Caricamento anni...</p>';
    
    vaiAStep(3);

    try {
        const response = await fetch(`${apiBasePath}/api/corsi/${courseId}/curriculums`);
        const curriculums = await response.json();

        if (curriculums.length > 0) {
            // Estrapoliamo valori univoci e rimuoviamo null/vuoti
            const sedi = [...new Set(curriculums.map(c => c.campus_location).filter(Boolean))];
            const anni = [...new Set(curriculums.map(c => c.year).filter(Boolean))].sort();

            // LOGICA SEDI
            if (sedi.length === 1) {
                wizardData.sede = sedi[0];
                document.getElementById('input-sede').value = sedi[0];
                containerSede.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${sedi[0]} (Unica opzione)</div>`;
            } else {
                containerSede.innerHTML = sedi.map(s => `<div class="toggle-btn" onclick="selezionaToggle(this, 'sede', '${s}')">${s}</div>`).join('');
            }

            // LOGICA ANNI
            if (anni.length === 1) {
                wizardData.anno = anni[0];
                document.getElementById('input-anno').value = anni[0];
                containerAnno.innerHTML = `<div class="toggle-btn selected" style="cursor:default; pointer-events:none;">${anni[0]}° Anno (Unica opzione)</div>`;
            } else {
                containerAnno.innerHTML = anni.map(a => `<div class="toggle-btn" onclick="selezionaToggle(this, 'anno', '${a}')">${a}° Anno</div>`).join('');
            }
        } else {
            // Seleziona un default se il DB per questo corso non ha curriculums (Modalità Richiesta)
            wizardData.sede = 'Principale'; wizardData.anno = 1;
            document.getElementById('input-sede').value = 'Principale';
            document.getElementById('input-anno').value = 1;
            containerSede.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">Principale</div>`;
            containerAnno.innerHTML = `<div class="toggle-btn selected" style="pointer-events:none;">1° Anno</div>`;
        }

        // Se entrambi sono stati auto-selezionati, sblocca il bottone
        if (wizardData.sede && wizardData.anno) {
            document.getElementById('btn-submit').removeAttribute('disabled');
        }

    } catch (e) {
        containerSede.innerHTML = '<p style="color:red;">Errore di caricamento</p>';
        containerAnno.innerHTML = '';
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