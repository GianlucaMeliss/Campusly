<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\ItemModel;

final class PageController
{
    public function about(): void
    {
        View::render('pages/about', [
            'pageTitle'       => 'Chi siamo - Campusly',
            'pageDescription' => 'Informazioni sul progetto Campusly.',
            'pageCss'         => 'about',
        ]);
    }

    public function privacy(): void
    {
        View::render('pages/legal', [
            'pageTitle' => 'Privacy Policy - Campusly',
            'titolo'    => 'Informativa sulla Privacy',
            'contenuto' => '
                <h2>1. Titolare del Trattamento</h2>
                <p>Il trattamento dei dati personali raccolti tramite Campusly avviene nel rispetto del Regolamento UE 2016/679 (GDPR). Per qualsiasi richiesta relativa alla privacy puoi contattarci tramite i recapiti indicati sul sito.</p>
                <h2>2. Dati Raccolti</h2>
                <p>Raccogliamo esclusivamente i dati necessari al funzionamento del servizio: nome, cognome, indirizzo email, preferenze relative al corso di studio, materie nascoste, eventi personali inseriti nel calendario e partecipazione volontaria ai gruppi di studio.</p>
                <h2>3. Finalità del Trattamento</h2>
                <p>I dati sono utilizzati unicamente per erogare il servizio di calendario universitario personalizzato, sincronizzare le preferenze tra dispositivi e consentire la condivisione degli orari nei gruppi di studio secondo il livello di privacy scelto dall\'utente.</p>
                <h2>4. Conservazione e Diritti dell\'Utente</h2>
                <p>I dati sono conservati per il tempo in cui l\'account rimane attivo. L\'utente può richiedere in ogni momento l\'accesso, la rettifica o la cancellazione del proprio account e dei relativi dati.</p>
            ',
        ]);
    }

    public function cookie(): void
    {
        View::render('pages/legal', [
            'pageTitle' => 'Cookie Policy - Campusly',
            'titolo'    => 'Cookie Policy',
            'contenuto' => '
                <h2>1. Cosa sono i Cookie</h2>
                <p>I cookie sono piccoli file di testo che i siti visitati inviano al terminale dell\'utente per mantenere la sessione attiva e memorizzare le preferenze.</p>
                <h2>2. Cookie Tecnici Essenziali</h2>
                <p>Campusly utilizza cookie tecnici strettamente necessari al funzionamento del servizio: cookie di sessione (per mantenere l\'accesso e proteggere i moduli tramite token CSRF), cookie "Ricordami" opzionale e archiviazione locale (localStorage) per la cache offline della PWA.</p>
                <h2>3. Cookie di Terze Parti</h2>
                <p>Eventuali script analitici o di terze parti vengono caricati esclusivamente previo consenso esplicito dell\'utente tramite l\'apposito banner.</p>
            ',
        ]);
    }

    public function terms(): void
    {
        View::render('pages/legal', [
            'pageTitle' => 'Termini e Condizioni - Campusly',
            'titolo'    => 'Termini e Condizioni di Utilizzo',
            'contenuto' => '
                <h2>1. Oggetto del Servizio</h2>
                <p>Campusly è uno strumento gratuito di supporto agli studenti che aggrega e organizza gli orari delle lezioni pubblicati dagli atenei.</p>
                <h2>2. Esclusione di Responsabilità sugli Orari</h2>
                <p>Gli orari mostrati provengono dai portali pubblici delle rispettive università. Sebbene Campusly aggiorni periodicamente i dati, fanno sempre fede le comunicazioni ufficiali del proprio ateneo.</p>
                <h2>3. Uso Corretto</h2>
                <p>L\'utente si impegna a non utilizzare il servizio in modo improprio, a non tentare accessi non autorizzati e a rispettare gli altri membri all\'interno dei gruppi di studio condivisi.</p>
            ',
        ]);
    }

    public function archive(): void
    {
        $items = ItemModel::getAll();

        View::render('pages/archive', [
            'pageTitle'       => 'I nostri servizi',
            'pageDescription' => 'Esplora le nostre soluzioni.',
            'items'           => $items
        ]);
    }

    public function showItem(string $slug): void
    {
        $item = ItemModel::getBySlug($slug);

        if (!$item) {
            $this->notFound();
            return;
        }

        View::render('pages/single', [
            'pageTitle'       => $item['title'],
            'pageDescription' => $item['short_desc'],
            'item'            => $item
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);

        View::render('pages/404', [
            'pageTitle'       => '404 - Page not found',
            'pageDescription' => 'La pagina richiesta non è disponibile.',
        ]);
    }
}
