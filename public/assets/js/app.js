let dataRiferimento = new Date();
let haFattoAutoAvanzamento = false;

const corsiDaNascondere = [
    "MODELLI INNOVATIVI PER LA GESTIONE DEI DATI",
    "INTERFACCE UOMO-MACCHINA",
    "BASI DI DATI II"
];

let eventiSettimana = [];
let indiceEvidenza = 0;

const icnOra = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>`;
const icnAula = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
const icnProf = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`;
const icnData = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`;
const icnPartizione = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`;

// Funzione sicura per estrarre la partizione dal JSON
function estraiPartizione(evento) {
    let part = null;
    // Metodo 1: dai dettagli didattici dell'evento
    if (evento.evento && evento.evento.dettagliDidattici && evento.evento.dettagliDidattici.length > 0) {
        part = evento.evento.dettagliDidattici[0].partizione;
    }
    // Metodo 2 (Fallback): dal fattore di partizione esterno
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
    
    if (hue >= 140 && hue <= 200) {
        hue = (hue + 100) % 360; 
    }
    return hue; 
}

function formattaTitolo(titolo) {
    if (!titolo) return "Lezione";
    return titolo.toLowerCase().split(' ').map(word => {
        if (word.length < 3 && !['ii', 'iii', 'iv'].includes(word)) return word;
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join(' ');
}

document.addEventListener('DOMContentLoaded', () => {
    const btnTheme = document.getElementById('theme-toggle');
    const iconMoon = document.getElementById('icon-moon');
    const iconSun = document.getElementById('icon-sun');
    
    function impostaTema(scuro) {
        if (scuro) {
            document.body.classList.add('dark-mode');
            iconMoon.style.display = 'none';
            iconSun.style.display = 'block';
        } else {
            document.body.classList.remove('dark-mode');
            iconMoon.style.display = 'block';
            iconSun.style.display = 'none';
        }
    }

    const temaSalvato = localStorage.getItem('theme');

    if (temaSalvato === 'dark') {
        impostaTema(true);
    } else if (temaSalvato === 'light') {
        impostaTema(false);
    } else {
        const sistemaScuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
        impostaTema(sistemaScuro);
    }

    btnTheme.addEventListener('click', () => {
        const diventaScuro = !document.body.classList.contains('dark-mode');
        impostaTema(diventaScuro);
        localStorage.setItem('theme', diventaScuro ? 'dark' : 'light');
    });

    document.getElementById('btn-prec').addEventListener('click', () => cambiaSettimana(-7));
    document.getElementById('btn-succ').addEventListener('click', () => cambiaSettimana(7));

    document.getElementById('btn-export').addEventListener('click', esportaSettimanaICS);

    caricaSettimana(dataRiferimento);
});

function cambiaSettimana(giorni) {
    dataRiferimento.setDate(dataRiferimento.getDate() + giorni);
    caricaSettimana(dataRiferimento);
}

function ottieniLunedi(d) {
    const data = new Date(d);
    const giorno = data.getDay();
    const diff = data.getDate() - giorno + (giorno === 0 ? -6 : 1);
    return new Date(data.setDate(diff));
}

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
            if (r.aula && isAulaVarese(r.aula)) {
                aule.push(r.aula.descrizione);
            } 
        });
    }
    const auleTesto = aule.length > 0 ? aule.join(', ') : "Da definire";

    let docenti = [];
    if (evento.risorse) evento.risorse.forEach(r => { if (r.docente && r.docente.cognome) docenti.push(r.docente.cognome); });
    const docentiTesto = docenti.length > 0 ? docenti.join(', ') : "Non assegnato";

    const adesso = new Date();
    let badgeTesto = "Prossima Lezione";
    let badgeClass = "badge-future"; 
    
    if (adesso > dataFine) {
        badgeTesto = "Lezione Passata";
        badgeClass = "badge-past";
    } else if (adesso >= dataInizio && adesso <= dataFine) {
        badgeTesto = "In Corso Ora!";
        badgeClass = "badge-now";
    }

    // --- NUOVO: Calcolo Sovrapposizione ---
    const haSovrapposizioni = eventiSettimana.some((altroEvento, index) => {
        if (index === indiceEvidenza) return false; 
        const altroInizio = new Date(altroEvento.dataInizio);
        const altraFine = new Date(altroEvento.dataFine);
        return (dataInizio < altraFine && dataFine > altroInizio);
    });

    let overlapBadgeHtml = "";
    if (haSovrapposizioni) {
        // Il badge ora permette esplicitamente al testo di andare a capo (white-space: normal)
        overlapBadgeHtml = `<span class="hl-badge" style="background: #f59e0b; color: #fff; border: none; display: inline-block; width: fit-content; max-width: 100%; white-space: normal; text-align: left; font-size: 0.85em; line-height: 1.3; box-sizing: border-box;">⚠️ Altre lezioni in corso</span>`;
    }

    const hueMateria = getColoreHue(evento.nome || "");
    const titoloFormattato = formattaTitolo(evento.nome);

    let partizioneHtml = "";
    const partizione = estraiPartizione(evento);
    if (partizione && partizione.descrizione) {
        partizioneHtml = `<div class="hl-riga">${icnPartizione} <span>${partizione.codice ? partizione.codice + ' - ' : ''}${partizione.descrizione}</span></div>`;
    }

    // HTML aggiornato: flex-wrap rimosso (nowrap), blocco di sx con flex: 1, nav bloccata con flex: 0 0 auto
    box.innerHTML = `
        <div class="hl-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; flex-wrap: nowrap;">
            <div style="display: flex; flex-direction: column; gap: 6px; flex: 1 1 auto; min-width: 0;">
                <span class="hl-badge ${badgeClass}" style="width: fit-content;">${badgeTesto}</span>
                ${overlapBadgeHtml}
            </div>
            <div class="hl-nav" style="flex: 0 0 auto; display: flex; align-items: center; white-space: nowrap;">
                <button onclick="cambiaEvidenza(-1)" ${indiceEvidenza === 0 ? 'disabled' : ''}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <span>${indiceEvidenza + 1} di ${eventiSettimana.length}</span>
                <button onclick="cambiaEvidenza(1)" ${indiceEvidenza === eventiSettimana.length - 1 ? 'disabled' : ''}>
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
            <div class="hl-riga">${icnProf} <span>Prof. ${docentiTesto}</span></div>
        </div>
    `;
    
    document.getElementById('box-evidenza-main').style.setProperty('--card-hue', hueMateria);
}

window.cambiaEvidenza = function(direzione) {
    indiceEvidenza += direzione;
    aggiornaBoxEvidenza();
};

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
                // Se la fine dell'ultima lezione in questa colonna è <= inizio di quella nuova, possono stare nella stessa colonna
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
                // Caso Normale (1 o 2 lezioni): Affiancate standard (100% o 50% di spazio)
                lezione.widthCSS = `calc(${100 / numColonne}% - 4px)`;
                lezione.leftCSS = `calc(${lezione.colIndex * (100 / numColonne)}% + 2px)`;
                lezione.zIndex = 10;
            } else {
                // Caso Critico (3+ lezioni): Effetto "Mazzo di Carte" in cascata
                // Manteniamo le card leggibili al 65% della larghezza e le slittiamo per farle entrare tutte
                let cardWidth = 65; 
                let offset = (100 - cardWidth) / (numColonne - 1); 
                
                lezione.widthCSS = `calc(${cardWidth}% - 4px)`;
                lezione.leftCSS = `calc(${lezione.colIndex * offset}% + 2px)`;
                lezione.zIndex = 10 + lezione.colIndex; // Quella più a destra sta "sopra" le altre
            }
        });
    });
}

function mostraSkeleton() {
    const container = document.getElementById('calendario-container');
    container.innerHTML = `
        <div class="skeleton-box skeleton"></div>
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
    
    // Prima sottraevamo 45, ora sottraiamo 55 per compensare la colonna orari allargata
    const scrollX = colonnaOggi.offsetLeft - 55; 
    container.scrollTo({ left: scrollX, behavior: 'smooth' });
}

// ==========================================
// CORE: Logica Stale-While-Revalidate
// ==========================================
async function caricaSettimana(dataRif) {
    const labelSettimana = document.getElementById('label-settimana');
    const lunedi = ottieniLunedi(dataRif);
    lunedi.setHours(0, 0, 0, 0);
    const domenica = new Date(lunedi);
    domenica.setDate(lunedi.getDate() + 6);
    domenica.setHours(23, 59, 59, 999);

    labelSettimana.textContent = `${lunedi.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' })} - ${domenica.toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' })}`;

    const urlProxy = `/api/calendario?inizio=${encodeURIComponent(lunedi.toISOString())}&fine=${encodeURIComponent(domenica.toISOString())}`;
    
    mostraSkeleton();

    let datiCacheText = null;

    try {
        if ('caches' in window) {
            const cacheResponse = await caches.match(urlProxy);
            if (cacheResponse) {
                datiCacheText = await cacheResponse.text();
                const eventiGrezzi = JSON.parse(datiCacheText);
                console.log("Dati caricati istantaneamente dalla cache!");
                renderizzaCalendario(eventiGrezzi, lunedi, true);
            }
        }
    } catch (e) {
        console.warn("Nessuna cache trovata o errore lettura cache:", e);
    }

    try {
        const response = await fetch(urlProxy);
        if (!response.ok) throw new Error(`Errore HTTP: ${response.status}`);
        
        const datiReteText = await response.text();

        if (datiReteText !== datiCacheText) {
            console.log("I dati della rete sono diversi. Aggiorno il calendario in background...");
            
            // --- NUOVO: Controllo anti-sovrascrittura ---
            // Se nel frattempo l'app ha saltato settimana (es. auto-avanzamento), blocchiamo il render vecchio
            const lunediCheck = ottieniLunedi(dataRiferimento);
            lunediCheck.setHours(0, 0, 0, 0);
            if (lunedi.getTime() !== lunediCheck.getTime()) {
                console.log("La settimana è cambiata prima che arrivassero i dati. Ignoro la vecchia fetch.");
                return;
            }
            // -------------------------------------------

            const eventiGrezzi = JSON.parse(datiReteText);
            renderizzaCalendario(eventiGrezzi, lunedi, datiCacheText === null); 
        } else {
            console.log("La cache è già aggiornata con la rete. Nessun re-render necessario.");
        }

    } catch (error) {
        console.error("Errore di rete durante la validazione:", error);
        if (!datiCacheText) {
            document.getElementById('calendario-container').innerHTML = `<div class="errore" style="color:red; padding:20px;">⚠️ Debug Errore: ${error.message}</div>`;
        }
    }
}

function renderizzaCalendario(eventiGrezzi, lunedi, faiScroll = false) {
    const container = document.getElementById('calendario-container');
    
    let eventi = [];
    if (eventiGrezzi && eventiGrezzi.length > 0) {
        if(Array.isArray(eventiGrezzi)) {
            eventi = eventiGrezzi.filter(evento => {
                if (evento.stato === 'A') return false; 
                const nomeCorso = (evento.nome || "").toUpperCase();
                return !corsiDaNascondere.some(c => nomeCorso.includes(c.toUpperCase()));
            });
        }
    }

    // --- NUOVO: INIEZIONE EVENTI PERSONALI ---
    const eventiPersonali = JSON.parse(localStorage.getItem('eventiPersonali')) || [];
    // Filtriamo solo gli eventi personali di questa settimana per ottimizzare
    const domenica = new Date(lunedi);
    domenica.setDate(lunedi.getDate() + 6);
    domenica.setHours(23, 59, 59, 999);
    
    const personaliSettimana = eventiPersonali.filter(e => {
        const d = new Date(e.dataInizio);
        return d >= lunedi && d <= domenica;
    });
    
    // Uniamo gli eventi Cineca con quelli Personali
    eventi = eventi.concat(personaliSettimana);
    // -----------------------------------------
    
    eventiSettimana = eventi.sort((a, b) => new Date(a.dataInizio) - new Date(b.dataInizio));
    
    //new
    const adesso = new Date();
    const lunediReale = ottieniLunedi(adesso);
    lunediReale.setHours(0, 0, 0, 0);
    
    const lunediRender = new Date(lunedi);
    lunediRender.setHours(0, 0, 0, 0);

    // Controlliamo se stiamo guardando la settimana corrente E se non abbiamo già fatto il salto
    if (lunediRender.getTime() === lunediReale.getTime() && !haFattoAutoAvanzamento) {
        const ciSonoEventi = eventiSettimana.length > 0;
        
        // Verifica se tutti gli eventi presenti sono passati
        const tuttiTerminati = ciSonoEventi && eventiSettimana.every(e => new Date(e.dataFine) < adesso);
        
        // Bonus: se non ci sono eventi ma è weekend, consideriamo la settimana "finita"
        const weekendSenzaEventi = !ciSonoEventi && (adesso.getDay() === 0 || adesso.getDay() === 6);

        if (tuttiTerminati || weekendSenzaEventi) {
            console.log("Eventi terminati per questa settimana. Auto-avanzamento alla prossima...");
            haFattoAutoAvanzamento = true; // Segniamo che il salto è avvenuto
            setTimeout(() => cambiaSettimana(7), 50); // Eseguiamo il salto in modo asincrono per non bloccare il thread
            return; // Blocchiamo il rendering della settimana ormai passata
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
        container.innerHTML = `<div style="padding: 2rem; text-align: center; color: var(--text-sec);">Nessuna lezione in programma per questa settimana 🎉</div>`;
        return;
    }

    let maxGiorni = 5;
    eventiSettimana.forEach(evento => {
        const dataInizio = new Date(evento.dataInizio);
        const giornoSettimana = dataInizio.getDay(); // 0 = Domenica, 1 = Lunedì... 6 = Sabato
        if (giornoSettimana === 6) maxGiorni = Math.max(maxGiorni, 6); // Se c'è un evento di Sabato, mostra 6 giorni
        if (giornoSettimana === 0) maxGiorni = 7; // Se c'è un evento di Domenica, mostra tutti e 7 i giorni
    });
    // ------------------------------------------------------------------------------------------------

    const giorniLavorativi = [];
    // Usiamo maxGiorni invece del 5 fisso!
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
    
    wrapper.innerHTML += `<div class="angolo-vuoto"></div>`;

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
            card.addEventListener('click', () => apriModaleDettagli(evento));
            
            let titolo = formattaTitolo(evento.nome);
            
            // Calcolo partizione per la mini-card
            const partizione = estraiPartizione(evento);
            if (partizione && partizione.descrizione) {
                const descShort = partizione.descrizione.replace(/cognomi\s*/i, "").trim();
                titolo += ` <span style="font-size: 0.85em; opacity: 0.9;">(${descShort})</span>`;
            }

            const orario = `${dataInizio.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' })} - ${dataFine.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' })}`;
            
            let aule = [];
            if (evento.risorse) {
                evento.risorse.forEach(r => { 
                    if (r.aula && isAulaVarese(r.aula)) {
                        aule.push(r.aula.descrizione);
                    } 
                });
            }

            const auleTesto = aule.length > 0 ? aule.join(', ') : "?";

            const hueMateria = getColoreHue(evento.nome || "");
            card.style.setProperty('--card-hue', hueMateria);

            card.innerHTML = `
                <div class="lezione-titolo">${titolo}</div>
                <div class="lezione-orario">${icnOra} ${orario}</div>
                <div class="lezione-dettaglio">${icnAula} ${auleTesto}</div>
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

// ==========================================
// REGISTRAZIONE SERVICE WORKER (PWA OFFLINE)
// ==========================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js')
            .then(registration => {
                console.log('Service Worker registrato con successo!');
            })
            .catch(error => {
                console.error('Errore nella registrazione del SW:', error);
            });
    });
}

// ==========================================
// GESTIONE MODALE DETTAGLI
// ==========================================
function apriModaleDettagli(evento) {
    const modale = document.getElementById('modale-dettagli');
    const modaleBody = document.getElementById('modale-body');
    
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
            if (r.aula && isAulaVarese(r.aula)) {
                const nomeAula = r.aula.descrizione || "Aula non specificata";
                const edificio = (r.aula.edificio && r.aula.edificio.descrizione) ? r.aula.edificio.descrizione : "Padiglione non specificato";
                const capienza = r.aula.capienza ? `Capienza: ${r.aula.capienza} posti` : "Capienza ignota";
                infoAule.push(`<li><strong>${nomeAula}</strong><small>${edificio} • ${capienza}</small></li>`);
            }
            if (r.docente) {
                const nomeDoc = r.docente.nome || "";
                const cognomeDoc = r.docente.cognome || "";
                infoDocenti.push(`${nomeDoc} ${cognomeDoc}`.trim());
            }
        });
    }
    
    const auleHtml = infoAule.length > 0 ? `<ul class="lista-aule">${infoAule.join('')}</ul>` : "<span style='margin-left: 8px;'>Da definire</span>";
    const docentiTesto = infoDocenti.length > 0 ? infoDocenti.join(', ') : "Non assegnato";
    
    const tipoLezione = evento.tipoAbbreviazione || "Lezione";
    const stato = evento.stato === "A" ? " - ANNULLATA" : "";
    
    // Calcolo Partizione Modale
    let partizioneHtml = "";
    const partizione = estraiPartizione(evento);
    if (partizione && partizione.descrizione) {
        partizioneHtml = `<div class="hl-riga">${icnPartizione} <span><strong>Partizione:</strong> ${partizione.codice ? partizione.codice + ' - ' : ''}${partizione.descrizione}</span></div>`;
    }

    document.querySelector('.modale-content').style.setProperty('--card-hue', hueMateria);

    modaleBody.innerHTML = `
        <span class="hl-badge ${evento.stato === 'A' ? 'badge-now' : 'badge-future'} modale-badge">${tipoLezione}${stato}</span>
        <h2 class="hl-titolo" style="margin-top: 0;">${titoloFormattato}</h2>
        
        <div class="hl-dettagli" style="grid-template-columns: 1fr; gap: 16px;">
            <div class="hl-riga">${icnData} <span style="text-transform: capitalize;">${dataTesto}</span></div>
            <div class="hl-riga">${icnOra} <strong>${orario}</strong></div>
            ${partizioneHtml}
            <div class="hl-riga">${icnProf} <span><strong>Docente:</strong> ${docentiTesto}</span></div>
            <div class="hl-riga" style="align-items: flex-start;">
                <div style="margin-top: 2px;">${icnAula}</div>
                <div>
                    <strong style="margin-left: 8px;">Luogo:</strong>
                    ${auleHtml}
                </div>
            </div>
        </div>
    `// Aggiungi questo in fondo alla stringa modaleBody.innerHTML:
    if (evento.isPersonale) {
        modaleBody.innerHTML += `
            <button onclick="eliminaEventoPersonale('${evento.idPersonale}')" style="margin-top: 15px; width: 100%; padding: 10px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                🗑️ Elimina Evento
            </button>
        `;
    };
    
    modale.style.display = 'flex';
}

document.addEventListener('DOMContentLoaded', () => {
    const modale = document.getElementById('modale-dettagli');
    const btnChiudi = document.getElementById('chiudi-modale');
    
    if(btnChiudi) {
        btnChiudi.addEventListener('click', () => modale.style.display = 'none');
    }
    
    if(modale) {
        modale.addEventListener('click', (e) => {
            if (e.target === modale) modale.style.display = 'none';
        });
    }
});

function isAulaVarese(aula) {
    if (!aula) return false;
    
    const nomeAula = (aula.descrizione || "").toLowerCase();
    const edificio = (aula.edificio && aula.edificio.descrizione) ? aula.edificio.descrizione.toLowerCase() : "";
    
    const paroleComo = [
        'como', 
        'valleggio', 
        'sant\'abbondio', 
        'castelnuovo', 
        'cavaliere',
        'va1', 'va2', 'va3', 'va4', 'va5', 'va6', 'va7', 'va8'
    ];
    
    for (let parola of paroleComo) {
        if (nomeAula.includes(parola) || edificio.includes(parola)) {
            return false;
        }
    }

    return true; 
}

// ==========================================
// ESPORTAZIONE CALENDARIO (.ICS)
// ==========================================
function esportaSettimanaICS() {
    if (!eventiSettimana || eventiSettimana.length === 0) {
        alert("Non ci sono lezioni in questa settimana da esportare.");
        return;
    }

    let icsContent = "BEGIN:VCALENDAR\r\n";
    icsContent += "VERSION:2.0\r\n";
    icsContent += "PRODID:-//Calendario Uni Insubria//IT\r\n";
    icsContent += "CALSCALE:GREGORIAN\r\n";
    icsContent += "METHOD:PUBLISH\r\n";

    eventiSettimana.forEach(evento => {
        const dataInizio = new Date(evento.dataInizio);
        const dataFine = new Date(evento.dataFine);
        
        const startStr = dataInizio.toISOString().replace(/[-:]/g, '').split('.')[0] + "Z";
        const endStr = dataFine.toISOString().replace(/[-:]/g, '').split('.')[0] + "Z";
        
        let titolo = formattaTitolo(evento.nome);
        
        // Aggiungiamo partizione al titolo ICS
        const partizione = estraiPartizione(evento);
        if (partizione && partizione.descrizione) {
            const descShort = partizione.descrizione.replace(/cognomi\s*/i, "").trim();
            titolo += ` (${descShort})`;
        }
        
        let infoAule = [];
        if (evento.risorse) {
            evento.risorse.forEach(r => {
                if (r.aula && isAulaVarese(r.aula)) {
                    const edificio = (r.aula.edificio && r.aula.edificio.descrizione) ? ` (${r.aula.edificio.descrizione})` : "";
                    infoAule.push(r.aula.descrizione + edificio);
                }
            });
        }
        const luogo = infoAule.length > 0 ? infoAule.join(', ') : "Varese (Aula da definire)";
        
        let infoDocenti = [];
        if (evento.risorse) {
            evento.risorse.forEach(r => {
                if (r.docente) {
                    infoDocenti.push(`${r.docente.nome || ""} ${r.docente.cognome || ""}`.trim());
                }
            });
        }
        const descrizione = infoDocenti.length > 0 ? `Docente: ${infoDocenti.join(', ')}` : "Nessun docente specificato";

        icsContent += "BEGIN:VEVENT\r\n";
        icsContent += `SUMMARY:${titolo}\r\n`;
        icsContent += `DTSTART:${startStr}\r\n`;
        icsContent += `DTEND:${endStr}\r\n`;
        icsContent += `LOCATION:${luogo}\r\n`;
        icsContent += `DESCRIPTION:${descrizione}\r\n`;
        icsContent += "END:VEVENT\r\n";
    });

    icsContent += "END:VCALENDAR\r\n";

    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    const strDataFile = ottieniLunedi(dataRiferimento).toISOString().split('T')[0];
    
    link.href = url;
    link.setAttribute('download', `Lezioni_Insubria_${strDataFile}.ics`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ==========================================
// GESTIONE EVENTI PERSONALI
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const modaleForm = document.getElementById('modale-form-evento');
    const btnAdd = document.getElementById('btn-add-evento');
    const btnChiudiForm = document.getElementById('chiudi-modale-form');
    const form = document.getElementById('form-nuovo-evento');

    btnAdd.addEventListener('click', () => modaleForm.style.display = 'flex');
    btnChiudiForm.addEventListener('click', () => modaleForm.style.display = 'none');
    modaleForm.addEventListener('click', (e) => { if (e.target === modaleForm) modaleForm.style.display = 'none'; });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const titolo = document.getElementById('form-titolo').value;
        const data = document.getElementById('form-data').value;
        const inizio = document.getElementById('form-inizio').value;
        const fine = document.getElementById('form-fine').value;
        const luogo = document.getElementById('form-luogo').value;

        // Creiamo date compatibili (assumendo timezone locale)
        const dataInizio = new Date(`${data}T${inizio}:00`).toISOString();
        const dataFine = new Date(`${data}T${fine}:00`).toISOString();

        const nuovoEvento = {
            idPersonale: Date.now().toString(), // ID univoco
            nome: titolo,
            dataInizio: dataInizio,
            dataFine: dataFine,
            tipoAbbreviazione: "Personale",
            risorse: [{ aula: { descrizione: luogo || "Luogo non specificato" } }],
            isPersonale: true // Flag per riconoscerlo
        };

        const eventiPersonali = JSON.parse(localStorage.getItem('eventiPersonali')) || [];
        eventiPersonali.push(nuovoEvento);
        localStorage.setItem('eventiPersonali', JSON.stringify(eventiPersonali));

        modaleForm.style.display = 'none';
        form.reset();
        
        // Ricarichiamo la settimana per mostrare il nuovo evento
        caricaSettimana(dataRiferimento);
    });
});

window.eliminaEventoPersonale = function(id) {
    if(confirm("Vuoi davvero eliminare questo evento personale?")) {
        let eventi = JSON.parse(localStorage.getItem('eventiPersonali')) || [];
        eventi = eventi.filter(e => e.idPersonale !== id);
        localStorage.setItem('eventiPersonali', JSON.stringify(eventi));
        document.getElementById('modale-dettagli').style.display = 'none';
        caricaSettimana(dataRiferimento);
    }
};