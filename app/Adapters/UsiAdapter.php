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

            $lezioneTs = strtotime($start);
            if ($lezioneTs < $filtroInizioTs || $lezioneTs > $filtroFineTs) {
                continue;
            }

            // 1. Estrazione Materia
            $materia = $eventoUSI['course']['name_it'] ?? $eventoUSI['course']['name_en'] ?? $eventoUSI['title'] ?? 'Lezione USI';
            
            // 2. Estrazione Aula e Padiglione separati
            $aula = $eventoUSI['place']['office'] ?? '';
            $padiglione = $eventoUSI['place']['building']['name_it'] ?? '';

            // 3. Estrazione Docente
            $professore = '';
            if (!empty($eventoUSI['course']['lecturers']['data']) && is_array($eventoUSI['course']['lecturers']['data'])) {
                $profNomi = array_map(function($l) { 
                    return $l['person']['short_name'] ?? $l['person']['last_name'] ?? trim(($l['person']['first_name'] ?? '') . ' ' . ($l['person']['last_name'] ?? '')); 
                }, $eventoUSI['course']['lecturers']['data']);
                $professore = implode(', ', array_filter($profNomi));
            }

            // 4. Assemblaggio del titolo visivo (es. "A11 - Corporate Strategy")
            $titoloCard = $materia;
            if (!empty($aula)) {
                $titoloCard = $aula . ' - ' . $materia;
            }
            if (!empty($professore)) {
                $titoloCard .= "\n" . $professore; 
            }

            // 5. Inserimento nel formato Campusly
            $eventi[] = [
                'nome' => trim($titoloCard),
                'dataInizio' => (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'dataFine' => !empty($end) ? (new \DateTime($end))->format('Y-m-d\TH:i:s\Z') : (new \DateTime($start))->format('Y-m-d\TH:i:s\Z'),
                'stato' => 'C',
                'tipoAbbreviazione' => 'Lezione',
                'risorse' => [
                    [
                        'aula' => [
                            'descrizione' => $aula ?: 'Da definire',
                            'padiglione' => $padiglione // Il tuo campo separato
                        ],
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