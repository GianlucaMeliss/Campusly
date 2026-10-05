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

        // Conversione URL da Web ad API
        $apiUrl = preg_replace('/\/[a-z]{2}\/offerte-formative\/(\d+)\/.*\/piano-orari\/(\d+)\/(\d+)/', '/api/educations/$1/schedules/$2/$3', $urlWeb);

        if (!$apiUrl || $apiUrl === $urlWeb) {
            throw new Exception("L'URL fornito non è nel formato USI corretto");
        }

        $jsonGrezzo = $this->fetchJson($apiUrl);
        if (!$jsonGrezzo) return [];

        $risposta = json_decode($jsonGrezzo, true);
        
        // IL SEGRETO ERA QUI: I dati sono dentro la chiave "data"
        $datiUSI = $risposta['data'] ?? [];
        if (!is_array($datiUSI)) return [];

        $eventi = [];

        foreach ($datiUSI as $eventoUSI) {
            $start = $eventoUSI['start'] ?? '';
            $end = $eventoUSI['end'] ?? '';

            if (empty($start)) continue;

            // Il nome del corso si trova annidato dentro "course" > "name_it"
            $titolo = $eventoUSI['course']['name_it'] ?? $eventoUSI['course']['name_en'] ?? 'Lezione USI';
            
            // Cerchiamo le aule (spesso sono in un array "rooms")
            $aula = 'Da definire';
            if (!empty($eventoUSI['rooms']) && is_array($eventoUSI['rooms'])) {
                $auleNomi = array_map(function($r) { return $r['name'] ?? ''; }, $eventoUSI['rooms']);
                $aula = implode(' + ', array_filter($auleNomi));
            } elseif (!empty($eventoUSI['room'])) {
                $aula = is_array($eventoUSI['room']) ? ($eventoUSI['room']['name'] ?? '') : $eventoUSI['room'];
            }

            // Cerchiamo i professori (spesso in un array "teachers")
            $professore = '';
            if (!empty($eventoUSI['teachers']) && is_array($eventoUSI['teachers'])) {
                $profNomi = array_map(function($t) { return ($t['last_name'] ?? '') . ' ' . ($t['first_name'] ?? ''); }, $eventoUSI['teachers']);
                $professore = implode(', ', array_filter($profNomi));
            }

            // Aggiunta al formato standard Campusly
            $eventi[] = [
                'nome' => trim($titolo),
                'dataInizio' => (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'dataFine' => !empty($end) ? (new \DateTime($end))->format('Y-m-d\TH:i:s\Z') : (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'stato' => 'C', // Confermato
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