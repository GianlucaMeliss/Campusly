// ==========================================
// VARIABILI GLOBALI E STATO DEL CALENDARIO
// ==========================================
const API_BASE_PATH = window.APP_BASE_PATH || '';

let dataRiferimento = new Date();
let haFattoAutoAvanzamento = false;

// Dati dinamici dal Cloud
let corsiDaNascondere = []; 
let eventiPersonaliCloud = []; 
let eventiSettimana = [];
let indiceEvidenza = 0;
let giornoSelezionatoGruppo = 1; // 1 = Lunedì, 5 = Venerdì

// ==========================================
// ICONE SVG
// ==========================================
const icnOra = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
const icnAula = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
const icnProf = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
const icnData = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`;
const icnPartizione = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`;

const svgSync = `<svg class="icon-sync spinning" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>`;
const svgSuccess = `<svg class="icon-success" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
const svgOffline = `<svg class="icon-offline" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.61 10.61A6 6 0 0 0 4 15.5"></path><path d="M17.39 17.39A6 6 0 0 1 4 15.5"></path><line x1="1" y1="1" x2="23" y2="23"></line><path d="M16 16l-4-4"></path><path d="M12 12l-4 4"></path></svg>`;

// ==========================================
// HELPER FUNCTIONS
// ==========================================
function estraiPartizione(evento) {
    let part = null;
    if (evento.evento && evento.evento.dettagliDidattici && evento.evento.dettagliDidattici.length > 0) {
        part = evento.evento.dettagliDidattici[0].partizione;
    }
    if (!part && evento.fattoreDiPartizione && evento.fattoreDiPartizione.length > 0) {
        if (evento.fattoreDiPartizione[0].partizioni && evento.fattoreDiPartizione[0].partizioni.length > 0) {
            part = evento.fattoreDiPartizione[0].partizioni[0];
        }
    }
    return part;
}

function getColoreHue(titolo) {
    let hash = 0;
    for (let i = 0; i < titolo.length; i++) hash = titolo.charCodeAt(i) + ((hash << 5) - hash);
    let hue = Math.abs(hash % 360);
    if (hue >= 140 && hue <= 200) hue = (hue + 100) % 360; 
    return hue; 
}

function formattaTitolo(titolo) {
    if (!titolo) return "Lezione";
    return titolo.toLowerCase().split(' ').map(word => {
        if (word.length < 3 && !['ii', 'iii', 'iv'].includes(word)) return word;
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
}

function ottieniLunedi(d) {
    const data = new Date(d);
    const giorno = data.getDay();
    const diff = data.getDate() - giorno + (giorno === 0 ? -6 : 1);
    return new Date(data.setDate(diff));
}

function isAulaValida(aula) {
    return aula && aula.descrizione ? true : false; 
}

// ==========================================
// CORE DEL CALENDARIO: CARICAMENTO DATI
// ==========================================
function caricaDatiUtente() {
    try {
        const cacheCorsi = localStorage.getItem('campusly_corsi_nascosti');
        const cacheEventi = localStorage.getItem('campusly_eventi_personali');
        if (cacheCorsi) corsiDaNascondere = JSON.parse(cacheCorsi);
        if (cacheEventi) eventiPersonaliCloud = JSON.parse(cacheEventi);
    } catch(e) {
        console.error("Cache preferenze corrotta, pulizia.");
        localStorage.removeItem('campusly_corsi_nascosti');
        localStorage.removeItem('campusly_eventi_personali');
    }

    fetch(`${API_BASE_PATH}/api/corsi-nascosti`)
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (data) {
                corsiDaNascondere = data;
                localStorage.setItem('campusly_corsi_nascosti', JSON.stringify(data));
            }
        }).catch(() => {});

    fetch(`${API_BASE_PATH}/api/eventi-personali`)
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (data) {
                eventiPersonaliCloud = data;
                localStorage.setItem('campusly_eventi_personali', JSON.stringify(data));
            }
        }).catch(() => {});
}

async function caricaSettimana(dataRif) {
    const labelSettimana = document.getElementById('label-settimana');
    const syncStatus = document.getElementById('sync-status');
    
    const lunedi = ottieniLunedi(dataRif);
    lunedi.setHours(0, 0, 0, 0);
    const domenica = new Date(lunedi);
    domenica.setDate(lunedi.getDate() + 6);
    domenica.setHours(23, 59, 59, 999);

    const strLunedi = lunedi.toISOString().split('T')[0];
    const cacheKey = `campusly_week_${strLunedi}_g${window.ACTIVE_GROUP_ID || 'me'}`;

    if (labelSettimana) {
        labelSettimana.textContent = `${lunedi.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' })} - ${domenica.toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' })}`;
    }

    if (syncStatus) {
        syncStatus.innerHTML = svgSync;
        syncStatus.title = "Aggiornamento in corso...";
    }

    caricaDatiUtente(); 

    let datiCacheText = localStorage.getItem(cacheKey);
    let cacheValida = false;

    // Lettura sicura dalla memoria locale (Crash proof)
    try {
        if (datiCacheText) {
            const payloadGrezzo = JSON.parse(datiCacheText);
            if (window.ACTIVE_GROUP_ID) {
                renderizzaAgendaGruppo(payloadGrezzo, lunedi);
            } else {
                renderizzaCalendario(payloadGrezzo, lunedi, true);
            }
            cacheValida = true;
        }
    } catch (e) {
        console.warn("JSON in cache non valido. Pulizia forzata.");
        localStorage.removeItem(cacheKey);
    }

    if (!cacheValida) mostraSkeleton();

    let endpoint = window.ACTIVE_GROUP_ID ? `/api/calendario/gruppo/${window.ACTIVE_GROUP_ID}` : `/api/calendario`;
    const urlProxy = `${API_BASE_PATH}${endpoint}?inizio=${encodeURIComponent(lunedi.toISOString())}&fine=${encodeURIComponent(domenica.toISOString())}`;
    
    try {
        const response = await fetch(urlProxy);
        if (!response.ok) throw new Error(`Errore HTTP: ${response.status}`);
        
        const datiReteText = await response.text();
        const payloadGrezzo = JSON.parse(datiReteText); // Se non è JSON, salta nel catch e non salva

        if (datiReteText !== datiCacheText) {
            const lunediCheck = ottieniLunedi(dataRiferimento);
            lunediCheck.setHours(0, 0, 0, 0);
            if (lunedi.getTime() !== lunediCheck.getTime()) return; 
            
            localStorage.setItem(cacheKey, datiReteText);
            
            if (window.ACTIVE_GROUP_ID) {
                renderizzaAgendaGruppo(payloadGrezzo, lunedi);
            } else {
                renderizzaCalendario(payloadGrezzo, lunedi, !cacheValida); 
            }
        }

        if (syncStatus) {
            syncStatus.innerHTML = svgSuccess;
            syncStatus.title = "Calendario aggiornato";
            setTimeout(() => { if (syncStatus.innerHTML === svgSuccess) syncStatus.innerHTML = ''; }, 3000);
        }
    } catch (error) {
        if (syncStatus) {
            syncStatus.innerHTML = svgOffline;
            syncStatus.title = "Modalità Offline";
        }
        if (!cacheValida) {
            const container = document.getElementById('calendario-container');
            if (container) container.innerHTML = `<div class="errore" style="color:red; padding:20px; text-align:center;">⚠️ Nessuna connessione e nessun dato in memoria.</div>`;
        }
    }
}

// ==========================================
// RENDERING UI
// ==========================================
function mostraSkeleton() {
    const container = document.getElementById('calendario-container');
    if (!container) return;
    container.innerHTML = `
        <div class="skeleton-box skeleton" style="height: 150px; border-radius: var(--radius-lg); margin-bottom: 20px;"></div>
        <div class="calendario-main-container" style="opacity: 0.5;">
            <div class="angolo-vuoto"></div>
            ${'<div class="header-colonna skeleton" style="height:20px; margin:5px;"></div>'.repeat(5)}
            <div class="orari-colonna"></div>
            ${'<div class="giorno-colonna"><div class="skeleton-card skeleton" style="top:45px; height:90px;"></div></div>'.repeat(5)}
        </div>
    `;
}

function posizionaSuOggi() {
    const container = document.querySelector('.calendario-main-container');
    const colonnaOggi = document.getElementById('colonna-oggi');
    if (!container || !colonnaOggi) return; 
    const scrollX = colonnaOggi.offsetLeft - 55; 
    container.scrollTo({ left: scrollX, behavior: 'smooth' });
}

function gestisciSovrapposizioni(lezioni) {
    let clusters = [];
    let lastClusterEnd = null;

    lezioni.sort((a, b) => new Date(a.dataInizio) - new Date(b.dataInizio)).forEach(lezione => {
        let start = new Date(lezione.dataInizio).getTime();
        let end = new Date(lezione.dataFine).getTime();

        if (lastClusterEnd === null || start >= lastClusterEnd) {
            clusters.push([lezione]);
            lastClusterEnd = end;
        } else {
            clusters[clusters.length - 1].push(lezione);
            lastClusterEnd = Math.max(lastClusterEnd, end);
        }
    });

    clusters.forEach(cluster => {
        let colonne = [];
        cluster.forEach(lezione => {
            let posizionata = false;
            for (let c = 0; c < colonne.length; c++) {
                let ultima = colonne[c][colonne[c].length - 1];
                if (new Date(ultima.dataFine).getTime() <= new Date(lezione.dataInizio).getTime()) {
                    colonne[c].push(lezione);
                    lezione.colIndex = c;
                    posizionata = true;
                    break;
                }
            }
            if (!posizionata) {
                lezione.colIndex = colonne.length;
                colonne.push([lezione]);
            }
        });

        let numColonne = colonne.length;
        cluster.forEach(lezione => {
            if (numColonne <= 2) {
                lezione.widthCSS = `calc(${100 / numColonne}% - 4px)`;
                lezione.leftCSS = `calc(${lezione.colIndex * (100 / numColonne)}% + 2px)`;
                lezione.zIndex = 10;
            } else {
                let cardWidth = 65; 
                let offset = (100 - cardWidth) / (numColonne - 1); 
                lezione.widthCSS = `calc(${cardWidth}% - 4px)`;
                lezione.leftCSS = `calc(${lezione.colIndex * offset}% + 2px)`;
                lezione.zIndex = 10 + lezione.colIndex;
            }
        });
    });
}

function renderizzaCalendario(eventiGrezzi, lunedi, faiScroll = false) {
    const container = document.getElementById('calendario-container');
    if (!container) return;

    let eventi = [];
    if (eventiGrezzi && Array.isArray(eventiGrezzi)) {
        eventi = eventiGrezzi.filter(evento => {
            if (evento.stato === 'A') return false; 
            const nomeCorso = (evento.nome || "").toUpperCase();
            return !corsiDaNascondere.some(c => nomeCorso.includes(c.toUpperCase()));
        });
    }

    const domenica = new Date(lunedi);
    domenica.setDate(lunedi.getDate() + 6);
    domenica.setHours(23, 59, 59, 999);
    
    const personaliSettimana = eventiPersonaliCloud.filter(e => {
        const d = new Date(e.dataInizio);
        return d >= lunedi && d <= domenica;
    });
    
    eventi = eventi.concat(personaliSettimana);
    eventiSettimana = eventi.sort((a, b) => new Date(a.dataInizio) - new Date(b.dataInizio));
    
    const adesso = new Date();
    const lunediReale = ottieniLunedi(adesso);
    lunediReale.setHours(0, 0, 0, 0);
    const lunediRender = new Date(lunedi);
    lunediRender.setHours(0, 0, 0, 0);

    if (lunediRender.getTime() === lunediReale.getTime() && !haFattoAutoAvanzamento) {
        const ciSonoEventi = eventiSettimana.length > 0;
        const tuttiTerminati = ciSonoEventi && eventiSettimana.every(e => new Date(e.dataFine) < adesso);
        const weekendSenzaEventi = !ciSonoEventi && (adesso.getDay() === 0 || adesso.getDay() === 6);

        if (tuttiTerminati || weekendSenzaEventi) {
            haFattoAutoAvanzamento = true; 
            setTimeout(() => window.cambiaSettimana(7), 50); 
            return; 
        }
    }

    indiceEvidenza = eventiSettimana.findIndex(e => new Date(e.dataFine) > adesso);
    if (indiceEvidenza === -1) indiceEvidenza = 0;
    
    container.innerHTML = ''; 

    if (eventiSettimana.length > 0) {
        const boxEvidenza = document.createElement('div');
        boxEvidenza.id = 'box-evidenza-main';
        boxEvidenza.className = 'highlight-box';
        boxEvidenza.innerHTML = `<div id="highlight-content"></div>`;
        container.appendChild(boxEvidenza);
        aggiornaBoxEvidenza();
    } else {
        container.innerHTML = `<div style="padding: 3rem; text-align: center; color: var(--text-secondary); background: var(--surface); border-radius: var(--radius-lg); margin-top: 20px;">Nessuna lezione in programma per questa settimana 🎉</div>`;
        return;
    }

    let maxGiorni = 5;
    eventiSettimana.forEach(evento => {
        const dataInizio = new Date(evento.dataInizio);
        const giornoSettimana = dataInizio.getDay(); 
        if (giornoSettimana === 6) maxGiorni = Math.max(maxGiorni, 6); 
        if (giornoSettimana === 0) maxGiorni = 7; 
    });

    const giorniLavorativi = [];
    for (let i = 0; i < maxGiorni; i++) {
        const giorno = new Date(lunedi);
        giorno.setDate(lunedi.getDate() + i);
        giorniLavorativi.push({
            dataOggetto: giorno,
            dataTesto: giorno.toLocaleDateString('it-IT', { weekday: 'short', day: 'numeric' }),
            lezioni: []
        });
    }

    eventiSettimana.forEach(evento => {
        const dInizio = new Date(evento.dataInizio);
        const gTrovato = giorniLavorativi.find(g => g.dataOggetto.getDate() === dInizio.getDate());
        if (gTrovato) gTrovato.lezioni.push(evento);
    });

    const oraInizioCalendario = 8;
    const oraFineCalendario = 20; 
    const altezzaOra = 45; 
    const fattoreScala = altezzaOra / 60; 
    const altezzaTotale = (oraFineCalendario - oraInizioCalendario) * altezzaOra;

    const wrapper = document.createElement('div');
    wrapper.className = 'calendario-main-container';
    wrapper.innerHTML += `<div class="angolo-vuoto"></div>`;
    wrapper.style.setProperty('--num-giorni', maxGiorni);
    
    giorniLavorativi.forEach(giorno => {
        const isOggi = adesso.getDate() === giorno.dataOggetto.getDate() && adesso.getMonth() === giorno.dataOggetto.getMonth();
        const classeOggi = isOggi ? 'header-oggi' : '';
        wrapper.innerHTML += `<div class="header-colonna ${classeOggi}">${giorno.dataTesto}</div>`;
    });

    const orariCol = document.createElement('div');
    orariCol.className = 'orari-colonna';
    orariCol.style.height = `${altezzaTotale}px`;
    for (let h = oraInizioCalendario; h <= oraFineCalendario; h++) {
        const topPx = (h - oraInizioCalendario) * altezzaOra;
        orariCol.innerHTML += `<div class="orario-label" style="top: ${topPx}px">${h}:00</div>`;
    }
    wrapper.appendChild(orariCol);

    giorniLavorativi.forEach(giorno => {
        const colonna = document.createElement('div');
        colonna.className = 'giorno-colonna';
        colonna.style.height = `${altezzaTotale}px`;

        const isOggi = adesso.getDate() === giorno.dataOggetto.getDate() && adesso.getMonth() === giorno.dataOggetto.getMonth();
        
        if (isOggi) colonna.id = 'colonna-oggi';

        if (isOggi && adesso.getHours() >= oraInizioCalendario && adesso.getHours() < oraFineCalendario) {
            const topPx = (((adesso.getHours() - oraInizioCalendario) * 60) + adesso.getMinutes()) * fattoreScala;
            colonna.innerHTML += `<div class="time-indicator" style="top: ${topPx}px"></div>`;
        }

        gestisciSovrapposizioni(giorno.lezioni);

        giorno.lezioni.forEach(evento => {
            const dataInizio = new Date(evento.dataInizio);
            const dataFine = new Date(evento.dataFine);

            const topPx = (((dataInizio.getHours() - oraInizioCalendario) * 60) + dataInizio.getMinutes()) * fattoreScala;
            const altezzaPx = ((dataFine.getTime() - dataInizio.getTime()) / 60000) * fattoreScala;

            const card = document.createElement('div');
            card.className = 'lezione-card';
            card.style.top = `${topPx}px`;
            card.style.height = `${altezzaPx}px`;
            card.style.width = evento.widthCSS;
            card.style.left = evento.leftCSS;
            card.style.zIndex = evento.zIndex;
            card.style.cursor = 'pointer'; 
            
            card.addEventListener('click', () => window.apriModaleDettagli(evento));
            
            let titolo = formattaTitolo(evento.nome);
            const partizione = estraiPartizione(evento);
            if (partizione && partizione.descrizione) {
                const descShort = partizione.descrizione.replace(/cognomi\s*/i, "").trim();
                titolo += ` <span style="font-size: 0.85em; opacity: 0.9;">(${descShort})</span>`;
            }

            const orario = `${dataInizio.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' })} - ${dataFine.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' })}`;
            
            let aule = [];
            if (evento.risorse) {
                evento.risorse.forEach(r => { 
                    if (r.aula && isAulaValida(r.aula)) aule.push(r.aula.descrizione);
                });
            }
            const auleTesto = aule.length > 0 ? aule.join(', ') : "?";

            const hueMateria = getColoreHue(evento.nome || "");
            card.style.setProperty('--card-hue', hueMateria);

            if(evento.isPersonale) {
                card.style.backgroundColor = 'var(--brand-fuchsia)';
                card.style.color = '#fff';
            }

            card.innerHTML = `
                <div class="lezione-titolo">${titolo}</div>
                <div class="lezione-orario">${icnOra} ${orario}</div>
                <div class="lezione-dettaglio">${icnAula} ${evento.isPersonale ? (evento.luogo || 'Personale') : auleTesto}</div>
            `;
            colonna.appendChild(card);
        });
        
        wrapper.appendChild(colonna);
    });

    container.appendChild(wrapper);

    if (faiScroll) {
        setTimeout(posizionaSuOggi, 100);
    }
}

function renderizzaAgendaGruppo(payload, lunedi) {
    const container = document.getElementById('calendario-container');
    if (!container) return;

    const membri = payload.members || {};
    const eventiTutti = payload.events || [];

    const boxEvidenza = document.getElementById('box-evidenza-main');
    if (boxEvidenza) boxEvidenza.style.display = 'none';

    // 1. Tabs Giorni (L M M G V)
    let htmlTabs = `<div class="group-day-tabs">`;
    const giorniNomi = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
    for (let i = 0; i < 5; i++) {
        let d = new Date(lunedi);
        d.setDate(lunedi.getDate() + i);
        let isActive = (i + 1) === giornoSelezionatoGruppo ? 'active' : '';
        htmlTabs += `<div class="group-day-tab ${isActive}" onclick="cambiaGiornoGruppo(${i + 1})">
            ${giorniNomi[i]} ${d.getDate()}
        </div>`;
    }
    htmlTabs += `</div>`;
    container.innerHTML = htmlTabs;

    // 2. Logica Orari (Dalle 8:00 alle 20:00)
    const oraInizio = 8;
    const oraFine = 20;
    const altezzaOra = 65; 
    const fattoreScala = altezzaOra / 60;
    const altezzaTotale = (oraFine - oraInizio) * altezzaOra;
    const headerAltezza = 45;

    // Contenitore Principale
    const doodle = document.createElement('div');
    doodle.className = 'doodle-container';
    
    // Colonna Tempi a sinistra
    const timesCol = document.createElement('div');
    timesCol.className = 'doodle-times';
    timesCol.style.height = `${altezzaTotale + headerAltezza}px`;
    timesCol.innerHTML = `<div class="doodle-header" style="height:${headerAltezza}px; border-bottom:none; background:transparent;"></div>`;
    
    for (let h = oraInizio; h <= oraFine; h++) {
        const topPx = (h - oraInizio) * altezzaOra + headerAltezza;
        timesCol.innerHTML += `<div class="doodle-time-label" style="top: ${topPx}px">${h}:00</div>`;
    }
    doodle.appendChild(timesCol);

    // Contenitore scrollabile orizzontalmente per i membri
    const wrapper = document.createElement('div');
    wrapper.className = 'doodle-members-wrapper';
    wrapper.style.height = `${altezzaTotale + headerAltezza}px`;

    // Griglia orizzontale di sfondo (Linee delle ore)
    const bgGrid = document.createElement('div');
    bgGrid.style.cssText = `position:absolute; top:${headerAltezza}px; left:0; right:0; height:${altezzaTotale}px; pointer-events:none; z-index:0; min-width:100%;`;
    for (let h = oraInizio; h <= oraFine; h++) {
        const topPx = (h - oraInizio) * altezzaOra;
        bgGrid.innerHTML += `<div style="position:absolute; top:${topPx}px; width:100%; border-top:1px solid var(--border-color); opacity:0.6;"></div>`;
    }
    wrapper.appendChild(bgGrid);

    // Estraiamo gli eventi SOLO del giorno selezionato
    const dataRifGiorno = new Date(lunedi);
    dataRifGiorno.setDate(lunedi.getDate() + (giornoSelezionatoGruppo - 1));
    const strDataRif = dataRifGiorno.toISOString().split('T')[0];
    
    const eventiGiorno = eventiTutti.filter(ev => ev.dataInizio && ev.dataInizio.startsWith(strDataRif));

    // Ordiniamo i Membri: "Tu" stai sempre nella prima colonna a sinistra
    let keysMembri = Object.keys(membri);
    let mioId = null;
    const evMio = eventiTutti.find(e => e.is_me);
    if (evMio) mioId = evMio.member_id.toString();

    if (mioId && keysMembri.includes(mioId)) {
        keysMembri = [mioId, ...keysMembri.filter(id => id !== mioId)];
    }

    // 3. Creiamo la corsia (colonna) per ogni membro
    keysMembri.forEach(idMem => {
        const isMe = (idMem === mioId);
        const nome = isMe ? "Tu" : membri[idMem];

        const col = document.createElement('div');
        col.className = 'doodle-member-col';

        const header = document.createElement('div');
        header.className = `doodle-header ${isMe ? 'is-me' : ''}`;
        header.style.height = `${headerAltezza}px`;
        header.textContent = nome;
        col.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'doodle-grid';
        grid.style.height = `${altezzaTotale}px`;
        grid.style.overflow = 'hidden'; // Taglia fuori gli eventi prima delle 8 e dopo le 20

        let eventiMembro = eventiGiorno.filter(e => e.member_id.toString() === idMem);
        
        // Calcola le sovrapposizioni usando la funzione che già abbiamo!
        gestisciSovrapposizioni(eventiMembro); 

        eventiMembro.forEach(ev => {
            const dInizio = new Date(ev.dataInizio);
            const dFine = new Date(ev.dataFine);

            const topPx = (((dInizio.getHours() - oraInizio) * 60) + dInizio.getMinutes()) * fattoreScala;
            const hPx = ((dFine.getTime() - dInizio.getTime()) / 60000) * fattoreScala;

            const card = document.createElement('div');
            card.className = 'doodle-card';
            card.style.top = `${topPx}px`;
            card.style.height = `${hPx}px`;
            
            card.style.width = ev.widthCSS ? `calc(${ev.widthCSS} - 6px)` : 'calc(100% - 8px)';
            card.style.left = ev.leftCSS ? `calc(${ev.leftCSS} + 2px)` : '4px';
            card.style.zIndex = ev.zIndex || 10;

            const isOccupatoGenerico = ev.nome && ev.nome.includes("Occupato");
            
            // Colori eleganti e leggibili (stile pastello per le card)
            if (isOccupatoGenerico) {
                card.style.background = "var(--bg-main)";
                card.style.borderLeftColor = "var(--text-muted)";
                card.style.color = "var(--text-secondary)";
            } else {
                const hue = getColoreHue(ev.nome || "");
                card.style.background = `hsl(${hue}, 85%, 94%)`;
                card.style.borderLeftColor = `hsl(${hue}, 70%, 50%)`;
                card.style.color = `hsl(${hue}, 85%, 25%)`;
            }

            card.innerHTML = `
                <div class="doodle-card-title">${formattaTitolo(ev.nome)}</div>
                <div>${dInizio.getHours()}:${dInizio.getMinutes().toString().padStart(2,'0')} - ${dFine.getHours()}:${dFine.getMinutes().toString().padStart(2,'0')}</div>
            `;
            
            // Cliccando si apre la modale standard
            card.addEventListener('click', () => window.apriModaleDettagli(ev));
            grid.appendChild(card);
        });

        col.appendChild(grid);
        wrapper.appendChild(col);
    });

    doodle.appendChild(wrapper);
    container.appendChild(doodle);
}

// ==========================================
// HIGHLIGHT BOX & EXPORT
// ==========================================
function aggiornaBoxEvidenza() {
    const box = document.getElementById('highlight-content');
    if (!box || eventiSettimana.length === 0) return;

    const evento = eventiSettimana[indiceEvidenza];
    const dataInizio = new Date(evento.dataInizio);
    const dataFine = new Date(evento.dataFine);

    const orario = `${dataInizio.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })} - ${dataFine.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })}`;
    const dataTesto = dataInizio.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' });

    let aule = [];
    if (evento.risorse) {
        evento.risorse.forEach(r => { 
            if (r.aula && isAulaValida(r.aula)) aule.push(r.aula.descrizione);
        });
    }
    const auleTesto = aule.length > 0 ? aule.join(', ') : (evento.luogo || "Da definire");

    let docenti = [];
    if (evento.risorse) evento.risorse.forEach(r => { if (r.docente && r.docente.cognome) docenti.push(r.docente.cognome); });
    const docentiTesto = docenti.length > 0 ? docenti.join(', ') : "Non assegnato";

    const adesso = new Date();
    let badgeTesto = "Prossima Lezione";
    let badgeClass = "badge-future"; 
    
    if (adesso > dataFine) {
        badgeTesto = "Completato";
        badgeClass = "badge-past";
    } else if (adesso >= dataInizio && adesso <= dataFine) {
        badgeTesto = "In Corso Ora!";
        badgeClass = "badge-now";
    }

    const haSovrapposizioni = eventiSettimana.some((altroEvento, index) => {
        if (index === indiceEvidenza) return false; 
        const altroInizio = new Date(altroEvento.dataInizio);
        const altraFine = new Date(altroEvento.dataFine);
        return (dataInizio < altraFine && dataFine > altroInizio);
    });

    let overlapBadgeHtml = "";
    if (haSovrapposizioni) {
        overlapBadgeHtml = `<span class="hl-badge" style="background: #f59e0b; color: #fff; border: none; display: inline-block; width: fit-content; max-width: 100%; white-space: normal; text-align: left; font-size: 0.85em; line-height: 1.3; box-sizing: border-box;">⚠️ Altre lezioni in corso</span>`;
    }

    const titoloFormattato = formattaTitolo(evento.nome);
    const hueMateria = getColoreHue(evento.nome || "");
    let partizioneHtml = "";
    const partizione = estraiPartizione(evento);
    if (partizione && partizione.descrizione) {
        partizioneHtml = `<div class="hl-riga">${icnPartizione} <span>${partizione.codice ? partizione.codice + ' - ' : ''}${partizione.descrizione}</span></div>`;
    }

    box.innerHTML = `
        <div class="hl-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: nowrap;">
            <div style="display: flex; flex-direction: column; gap: 6px; flex: 1 1 auto; min-width: 0;">
                <span class="hl-badge ${badgeClass}" style="width: fit-content;">${badgeTesto}</span>
                ${overlapBadgeHtml}
            </div>
            <div class="hl-nav" style="flex: 0 0 auto; display: flex; align-items: center; white-space: nowrap;">
                <button onclick="window.cambiaEvidenza(-1)" ${indiceEvidenza === 0 ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <span>${indiceEvidenza + 1} di ${eventiSettimana.length}</span>
                <button onclick="window.cambiaEvidenza(1)" ${indiceEvidenza === eventiSettimana.length - 1 ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
        <h2 class="hl-titolo">${titoloFormattato}</h2>
        <div class="hl-dettagli">
            <div class="hl-riga">${icnData} <span>${dataTesto.charAt(0).toUpperCase() + dataTesto.slice(1)}</span></div>
            <div class="hl-riga">${icnOra} <strong>${orario}</strong></div>
            ${partizioneHtml}
            <div class="hl-riga">${icnAula} <span>${auleTesto}</span></div>
            ${!evento.isPersonale ? `<div class="hl-riga">${icnProf} <span>Prof.${docentiTesto}</span></div>` : ''}
        </div>
    `;
    document.getElementById('box-evidenza-main').style.setProperty('--card-hue', hueMateria);
}

function esportaSettimanaICS() {
    if (!eventiSettimana || eventiSettimana.length === 0) {
        alert("Non ci sono lezioni in questa settimana da esportare.");
        return;
    }
    let icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Campusly//IT\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
    eventiSettimana.forEach(evento => {
        const dataInizio = new Date(evento.dataInizio);
        const dataFine = new Date(evento.dataFine);
        const startStr = dataInizio.toISOString().replace(/[-:]/g, '').split('.')[0] + "Z";
        const endStr = dataFine.toISOString().replace(/[-:]/g, '').split('.')[0] + "Z";
        let titolo = formattaTitolo(evento.nome);
        const partizione = estraiPartizione(evento);
        if (partizione && partizione.descrizione) {
            const descShort = partizione.descrizione.replace(/cognomi\s*/i, "").trim();
            titolo += ` (${descShort})`;
        }
        
        let infoAule = [];
        if (evento.risorse) {
            evento.risorse.forEach(r => {
                if (r.aula && isAulaValida(r.aula)) {
                    const edificio = (r.aula.edificio && r.aula.edificio.descrizione) ? ` (${r.aula.edificio.descrizione})` : "";
                    infoAule.push(r.aula.descrizione + edificio);
                }
            });
        }
        const luogo = infoAule.length > 0 ? infoAule.join(', ') : "Varese (Aula da definire)";

        icsContent += "BEGIN:VEVENT\r\n";
        icsContent += `SUMMARY:${titolo}\r\n`;
        icsContent += `DTSTART:${startStr}\r\n`;
        icsContent += `DTEND:${endStr}\r\n`;
        icsContent += `LOCATION:${evento.isPersonale ? (evento.luogo || '') : luogo}\r\n`;
        icsContent += "END:VEVENT\r\n";
    });
    icsContent += "END:VCALENDAR\r\n";
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    const strDataFile = ottieniLunedi(dataRiferimento).toISOString().split('T')[0];
    link.href = url;
    link.setAttribute('download', `Campusly_${strDataFile}.ics`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ==========================================
// MODALI E AZIONI CLOUD
// ==========================================
window.apriModaleDettagli = function(evento) {
    const modale = document.getElementById('modale-dettagli');
    const modaleBody = document.getElementById('modale-body');
    if(!modale || !modaleBody) return;

    const dataInizio = new Date(evento.dataInizio);
    const dataFine = new Date(evento.dataFine);
    const orario = `${dataInizio.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })} - ${dataFine.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })}`;
    const dataTesto = dataInizio.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    
    const titoloFormattato = formattaTitolo(evento.nome);
    const hueMateria = getColoreHue(evento.nome || "");
    
    let infoAule = [];
    let infoDocenti = [];
    
    if (evento.risorse) {
        evento.risorse.forEach(r => {
            if (r.aula && isAulaValida(r.aula)) {
                const nomeAula = r.aula.descrizione || "Aula non specificata";
                const edificio = (r.aula.edificio && r.aula.edificio.descrizione) ? r.aula.edificio.descrizione : "Padiglione non specificato";
                const capienza = r.aula.capienza ? `Capienza: ${r.aula.capienza} posti` : "Capienza ignota";
                infoAule.push(`<li><strong>${nomeAula}</strong><small>${edificio} • ${capienza}</small></li>`);
            }
            if (r.docente) {
                infoDocenti.push(`${r.docente.nome || ""} ${r.docente.cognome || ""}`.trim());
            }
        });
    }
    
    const auleHtml = infoAule.length > 0 ? `<ul class="lista-aule" style="padding-left:24px; margin:4px 0;">${infoAule.join('')}</ul>` : `<span style='margin-left: 8px;'>${evento.luogo || 'Da definire'}</span>`;
    const docentiTesto = infoDocenti.length > 0 ? infoDocenti.join(', ') : "Non assegnato";
    const tipoLezione = evento.tipoAbbreviazione || (evento.isPersonale ? "Evento Personale" : "Lezione");
    const stato = evento.stato === "A" ? " - ANNULLATA" : "";
    
    let partizioneHtml = "";
    const partizione = estraiPartizione(evento);
    if (partizione && partizione.descrizione) {
        partizioneHtml = `<div class="hl-riga">${icnPartizione} <span><strong>Partizione:</strong> ${partizione.codice ? partizione.codice + ' - ' : ''}${partizione.descrizione}</span></div>`;
    }

    document.querySelector('.modale-content').style.setProperty('--card-hue', hueMateria);
    const isHidden = corsiDaNascondere.some(c => (evento.nome || "").toUpperCase().includes(c));
    const toggleBtnText = isHidden ? "👁️ Mostra di nuovo" : "🚫 Nascondi corso";
    const toggleBtnColor = isHidden ? "#10b981" : "#ef4444";

    modaleBody.innerHTML = `
        <span class="hl-badge ${evento.stato === 'A' ? 'badge-now' : 'badge-future'} modale-badge">${tipoLezione}${stato}</span>
        <h2 class="hl-titolo" style="margin-top: 0;">${titoloFormattato}</h2>
        
        <div class="hl-dettagli" style="grid-template-columns: 1fr; gap: 16px;">
            <div class="hl-riga">${icnData} <span style="text-transform: capitalize;">${dataTesto}</span></div>
            <div class="hl-riga">${icnOra} <strong>${orario}</strong></div>
            ${partizioneHtml}
            ${!evento.isPersonale ? `<div class="hl-riga">${icnProf} <span><strong>Docente:</strong>${docentiTesto}</span></div>` : ''}
            <div class="hl-riga" style="align-items: flex-start;">
                <div style="margin-top: 2px;">${icnAula}</div>
                <div><strong>Luogo:</strong> ${auleHtml}</div>
            </div>
        </div>
    `;

    if (!evento.isPersonale) {
        const nomeCorsoSafe = (evento.nome || "").replace(/'/g, "\\'");
        modaleBody.innerHTML += `
            <button onclick="window.toggleCorsoNascosto('${nomeCorsoSafe}')" style="margin-top: 24px; width: 100%; padding: 14px; background: ${toggleBtnColor}; color: white; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: 700;">
                ${toggleBtnText}
            </button>
        `;
    } else {
        modaleBody.innerHTML += `
            <button onclick="window.eliminaEventoPersonale('${evento.idPersonale}')" style="margin-top: 24px; width: 100%; padding: 14px; background: #ef4444; color: white; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: 700;">
                🗑️ Elimina Evento
            </button>
        `;
    }
    modale.style.display = 'flex';
};

window.toggleCorsoNascosto = async function(nomeCorso) {
    if(!nomeCorso) return;
    try {
        const response = await fetch(`${API_BASE_PATH}/api/corsi-nascosti/toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_name: nomeCorso })
        });
        if (response.ok) {
            document.getElementById('modale-dettagli').style.display = 'none';
            // Cancelliamo la cache per forzare un ricaricamento pulito
            localStorage.removeItem(`campusly_week_${ottieniLunedi(dataRiferimento).toISOString().split('T')[0]}_gme`);
            caricaSettimana(dataRiferimento);
        }
    } catch (error) { alert("Impossibile aggiornare l'impostazione in Cloud."); }
};

window.eliminaEventoPersonale = async function(id) {
    if(confirm("Vuoi davvero eliminare questo evento personale?")) {
        try {
            await fetch(`${API_BASE_PATH}/api/eventi-personali/delete/${id}`);
            document.getElementById('modale-dettagli').style.display = 'none';
            localStorage.removeItem(`campusly_week_${ottieniLunedi(dataRiferimento).toISOString().split('T')[0]}_gme`);
            caricaSettimana(dataRiferimento);
        } catch (error) { alert("Errore durante l'eliminazione dell'evento"); }
    }
};

// ==========================================
// FUNZIONI DI NAVIGAZIONE GLOBALI
// ==========================================
window.cambiaSettimana = function(giorni) {
    dataRiferimento.setDate(dataRiferimento.getDate() + giorni);
    caricaSettimana(dataRiferimento);
};
window.cambiaEvidenza = function(direzione) {
    indiceEvidenza += direzione;
    aggiornaBoxEvidenza();
};
window.cambiaGiornoGruppo = function(giornoIdx) {
    giornoSelezionatoGruppo = giornoIdx;
    caricaSettimana(dataRiferimento); 
};

// ==========================================
// INIZIALIZZAZIONE EVENT LISTENERS
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const btnPrec = document.getElementById('btn-prec');
    const btnSucc = document.getElementById('btn-succ');
    const btnExport = document.getElementById('btn-export');
    
    if(btnPrec) btnPrec.addEventListener('click', () => window.cambiaSettimana(-7));
    if(btnSucc) btnSucc.addEventListener('click', () => window.cambiaSettimana(7));
    if(btnExport) btnExport.addEventListener('click', esportaSettimanaICS);

    const modaleForm = document.getElementById('modale-form-evento');
    const btnAdd = document.getElementById('btn-add-evento');
    const form = document.getElementById('form-nuovo-evento');

    if(btnAdd && modaleForm) btnAdd.addEventListener('click', () => modaleForm.style.display = 'flex');

    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                nome: document.getElementById('form-titolo').value,
                dataInizio: new Date(`${document.getElementById('form-data').value}T${document.getElementById('form-inizio').value}:00`).toISOString(),
                dataFine: new Date(`${document.getElementById('form-data').value}T${document.getElementById('form-fine').value}:00`).toISOString(),
                luogo: document.getElementById('form-luogo').value || "Luogo non specificato"
            };
            try {
                await fetch(`${API_BASE_PATH}/api/eventi-personali`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                modaleForm.style.display = 'none';
                form.reset();
                localStorage.removeItem(`campusly_week_${ottieniLunedi(dataRiferimento).toISOString().split('T')[0]}_gme`);
                caricaSettimana(dataRiferimento);
            } catch (error) { alert("Errore durante il salvataggio in Cloud dell'evento"); }
        });
    }
    caricaSettimana(dataRiferimento);
});