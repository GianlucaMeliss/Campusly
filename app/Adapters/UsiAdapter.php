<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class UsiAdapter
{
    public function getSchedule(string $dataInizio, string $dataFine, array $config): array
    {
        $urlWeb = $config['url'] ?? '';
        if (empty($urlWeb)) {
            throw new Exception("URL USI mancante nella configurazione del corso");
        }

        // Magia: Trasformiamo l'URL della pagina web in quello dell'API segreta
        // Da: https://search.usi.ch/it/offerte-formative/100/.../61/3
        // A:  https://search.usi.ch/api/educations/100/schedules/61/3
        $apiUrl = preg_replace('/\/[a-z]{2}\/offerte-formative\/(\d+)\/.*\/piano-orari\/(\d+)\/(\d+)/', '/api/educations/$1/schedules/$2/$3', $urlWeb);

        if (!$apiUrl || $apiUrl === $urlWeb) {
            throw new Exception("L'URL fornito non è nel formato USI corretto");
        }

        $jsonGrezzo = $this->fetchJson($apiUrl);
        if (!$jsonGrezzo) return [];

        $datiUSI = json_decode($jsonGrezzo, true);
        if (!is_array($datiUSI)) return [];

        $eventi = [];

        foreach ($datiUSI as $eventoUSI) {
            // Estrazione dati grezzi (basato sullo standard FullCalendar)
            $titoloGrezzo = $eventoUSI['title'] ?? 'Lezione';
            $start = $eventoUSI['start'] ?? '';
            $end = $eventoUSI['end'] ?? '';

            if (empty($start)) continue;

            // Il titolo dell'USI contiene "Aula", "Materia" e "Professore" separati da '\n' o ' - '
            $aula = 'Da definire';
            $titolo = $titoloGrezzo;
            $professore = '';

            // Pulizia del Titolo
            $linee = array_filter(array_map('trim', explode("\n", $titoloGrezzo)));
            if (!empty($linee)) {
                $primaRiga = array_shift($linee); // "A11 - Household Economics"
                
                // Separiamo eventuale Aula all'inizio
                $parti = explode(' - ', $primaRiga, 2);
                if (count($parti) === 2) {
                    $aula = trim($parti[0]); // "A11"
                    $titolo = trim($parti[1]); // "Household Economics"
                } else {
                    $titolo = trim($primaRiga);
                }

                // Le righe successive di solito contengono il professore
                if (!empty($linee)) {
                    $professore = implode(', ', $linee);
                }
            }

            // Aggiunta al formato standard Campusly
            $eventi[] = [
                'nome' => $titolo,
                'dataInizio' => (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'dataFine' => !empty($end) ? (new \DateTime($end))->format('Y-m-d\TH:i:s\Z') : (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'stato' => 'C', // Confermato
                'tipoAbbreviazione' => 'Lezione',
                'risorse' => [
                    [
                        'aula' => ['descrizione' => $aula],
                        'docente' => ['cognome' => $professore]
                    ]
                ]
            ];
        }

        return $eventi;
    }

    private function fetchJson(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // È importante fingersi un browser per evitare blocchi Cloudflare
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response) ? $response : null;
    }
}