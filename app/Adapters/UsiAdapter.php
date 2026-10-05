<?php
declare(strict_types=1);

namespace App\Adapters;

use DOMDocument;
use DOMXPath;
use Exception;

class UsiAdapter
{
    public function getSchedule(string $dataInizio, string $dataFine, array $config): array
    {
        $url = $config['url'] ?? '';
        if (empty($url)) {
            throw new Exception("URL USI mancante nella configurazione del corso");
        }

        $html = $this->fetchHtml($url);
        if (!$html) return [];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // Usa mb_convert_encoding per evitare problemi con gli accenti (es. "Shorokiy")
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $eventi = [];

        // 1. Troviamo tutte le colonne dei giorni (es. data-date="2026-10-05")
        $nodiGiorni = $xpath->query("//td[@role='gridcell' and @data-date]");

        foreach ($nodiGiorni as $nodoGiorno) {
            $dataString = $nodoGiorno->getAttribute('data-date'); // 2026-10-05

            // 2. Cerchiamo tutti gli eventi dentro quella colonna
            $nodiEventi = $xpath->query(".//a[contains(@class, 'fc-event')]", $nodoGiorno);

            foreach ($nodiEventi as $nodoEvento) {
                // Estraiamo orario e blocco di testo
                $orarioTesto = $xpath->query(".//div[contains(@class, 'fc-event-time')]", $nodoEvento)->item(0)?->nodeValue ?? '';
                $testoGrezzo = $xpath->query(".//div[contains(@class, 'fc-event-title')]", $nodoEvento)->item(0)?->nodeValue ?? '';

                if (empty($orarioTesto) || empty($testoGrezzo)) continue;

                // A. Parsing dell'orario (es. "15:30 - 17:00")
                $orari = explode('-', $orarioTesto);
                $oraInizio = trim($orari[0]);
                $oraFine = trim($orari[1] ?? $orari[0]);

                // Ricostruiamo le date ISO (aggiungiamo Z per semplicità, poi il JS le adatta)
                $startIso = $dataString . 'T' . $oraInizio . ':00Z';
                $endIso   = $dataString . 'T' . $oraFine . ':00Z';

                // B. Parsing intelligente di Aula, Titolo e Professore
                $testoGrezzo = trim($testoGrezzo);
                $linee = array_values(array_filter(array_map('trim', explode("\n", $testoGrezzo))));
                
                $primaRiga = $linee[0] ?? '';
                $secondaRiga = $linee[1] ?? ''; // Spesso contiene il prof
                
                $parti = explode(' - ', $primaRiga, 3);
                
                $aula = 'Da definire';
                $titolo = $primaRiga;
                $prof = $secondaRiga;

                // Se la prima riga è formattata come "A11 - Titolo Corso"
                if (count($parti) >= 2) {
                    $aula = trim($parti[0]);
                    $titolo = trim($parti[1]);
                    // A volte il prof è sulla stessa riga (es. "A12 - Business - Bostock")
                    if (count($parti) === 3 && empty($secondaRiga)) {
                        $prof = trim($parti[2]);
                    }
                }

                // C. Inserimento nel formato standard
                $eventi[] = [
                    'nome' => $titolo,
                    'dataInizio' => $startIso,
                    'dataFine' => $endIso,
                    'stato' => 'C',
                    'tipoAbbreviazione' => 'Lezione',
                    'risorse' => [
                        [
                            'aula' => ['descrizione' => $aula],
                            'docente' => ['cognome' => $prof]
                        ]
                    ]
                ];
            }
        }

        return $eventi;
    }

    private function fetchHtml(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response) ? $response : null;
    }
}