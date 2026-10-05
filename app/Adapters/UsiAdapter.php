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

        $apiUrl = preg_replace('/\/[a-z]{2}\/offerte-formative\/(\d+)\/.*\/piano-orari\/(\d+)\/(\d+)/', '/api/educations/$1/schedules/$2/$3', $urlWeb);

        if (!$apiUrl || $apiUrl === $urlWeb) {
            throw new Exception("L'URL fornito non è nel formato USI corretto");
        }

        $jsonGrezzo = $this->fetchJson($apiUrl);
        if (!$jsonGrezzo) return [];

        $risposta = json_decode($jsonGrezzo, true);
        
        $datiUSI = $risposta['data'] ?? [];
        if (!is_array($datiUSI)) return [];

        $eventi = [];
        
        // Convertiamo le date richieste da Campusly in timestamp (numeri) per fare confronti veloci
        $filtroInizioTs = strtotime($dataInizio);
        $filtroFineTs = strtotime($dataFine);

        foreach ($datiUSI as $eventoUSI) {
            $start = $eventoUSI['start'] ?? '';
            $end = $eventoUSI['end'] ?? '';

            if (empty($start)) continue;

            // Convertiamo l'orario della lezione USI in timestamp
            $lezioneTs = strtotime($start);

            // IL FILTRO MAGICO: Se la lezione è prima dell'inizio richiesto o dopo la fine, la scartiamo!
            // Usiamo il segno '<' e '>' per un controllo rigoroso
            if ($lezioneTs < $filtroInizioTs || $lezioneTs > $filtroFineTs) {
                continue;
            }

            $titolo = $eventoUSI['course']['name_it'] ?? $eventoUSI['course']['name_en'] ?? 'Lezione USI';
            
            $aula = 'Da definire';
            if (!empty($eventoUSI['rooms']) && is_array($eventoUSI['rooms'])) {
                $auleNomi = array_map(function($r) { return $r['name'] ?? ''; }, $eventoUSI['rooms']);
                $aula = implode(' + ', array_filter($auleNomi));
            } elseif (!empty($eventoUSI['room'])) {
                $aula = is_array($eventoUSI['room']) ? ($eventoUSI['room']['name'] ?? '') : $eventoUSI['room'];
            }

            $professore = '';
            if (!empty($eventoUSI['teachers']) && is_array($eventoUSI['teachers'])) {
                $profNomi = array_map(function($t) { return ($t['last_name'] ?? '') . ' ' . ($t['first_name'] ?? ''); }, $eventoUSI['teachers']);
                $professore = implode(', ', array_filter($profNomi));
            }

            $eventi[] = [
                'nome' => trim($titolo),
                'dataInizio' => (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'dataFine' => !empty($end) ? (new \DateTime($end))->format('Y-m-d\TH:i:s\Z') : (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'stato' => 'C',
                'tipoAbbreviazione' => 'Lezione',
                'risorse' => [
                    [
                        'aula' => ['descrizione' => $aula],
                        'docente' => ['cognome' => trim($professore)]
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200 && $response) ? $response : null;
    }
}