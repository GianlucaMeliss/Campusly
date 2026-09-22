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
                list.innerHTML += `<div class="course-item" onclick="selezionaCorso('${nomeSafe}')">${corso.name}</div>`;
            });
        } else {
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
        alert("Richiesta inviata con successo! Il nostro team la valuterà a breve.");
    }
}