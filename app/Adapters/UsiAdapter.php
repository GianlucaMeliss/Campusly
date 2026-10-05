<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class UsiAdapter implements UniversityAdapterInterface // Assicurati di avere l'interfaccia se la usi
{
    public function getSchedule(string $startDate, string $endDate, array $config): array
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
        
        // Conversione per il filtro sulle date
        $filtroInizioTs = strtotime($startDate);
        $filtroFineTs = strtotime($endDate);

        foreach ($datiUSI as $eventoUSI) {
            $start = $eventoUSI['start'] ?? '';
            $end = $eventoUSI['end'] ?? '';

            if (empty($start)) continue;

            $lezioneTs = strtotime($start);
            if ($lezioneTs < $filtroInizioTs || $lezioneTs > $filtroFineTs) {
                continue;
            }

            /// 1. Estrazione Materia
            $materia = $eventoUSI['course']['name_it'] ?? $eventoUSI['course']['name_en'] ?? $eventoUSI['title'] ?? 'Lezione USI';
            
            // 2. Estrazione Aula ed Edificio (Padiglione)
            $aula = $eventoUSI['place']['office'] ?? 'Aula non assegnata';
            $edificio = $eventoUSI['place']['building']['name_it'] ?? '';

            // 3. Estrazione Docente
            $professore = 'Non assegnato';
            if (!empty($eventoUSI['course']['lecturers']['data']) && is_array($eventoUSI['course']['lecturers']['data'])) {
                $profNomi = array_map(function($l) { 
                    return $l['person']['short_name'] ?? $l['person']['last_name'] ?? trim(($l['person']['first_name'] ?? '') . ' ' . ($l['person']['last_name'] ?? '')); 
                }, $eventoUSI['course']['lecturers']['data']);
                $professore = implode(', ', array_filter($profNomi));
            }

            // 4. TITOLO PULITO: Solo la materia
            $titoloCard = trim($materia);

            // 5. Date Formattate come la Statale (.000 al posto della Z)
            $isoStart = date('Y-m-d\TH:i:s.000', $lezioneTs);
            $isoEnd = date('Y-m-d\TH:i:s.000', !empty($end) ? strtotime($end) : $lezioneTs);

            // 6. MAPPA EVENTO ESATTA PER IL CALENDARIO FRONTEND
            $eventi[] = [
                'idPersonale' => null,
                'nome' => trim($titoloCard),
                'dataInizio' => $isoStart,
                'dataFine' => $isoEnd,
                'stato' => 'C',
                'tipoAbbreviazione' => 'Lezione',
                
                'dettagliDidattici' => [
                    [
                        'partizione' => [
                            'descrizione' => '' // Lasciato vuoto o riempibile se USI fornisce percorsi
                        ]
                    ]
                ],

                'risorse' => [
                    [
                        'aula' => [
                            'descrizione' => $aula,
                            'edificio' => [
                                'descrizione' => $edificio
                            ]
                        ],
                        'docente' => [
                            'cognome' => trim($professore)
                        ]
                    ]
                ],
                'isPersonale' => false
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("Errore cURL (USI): " . curl_error($ch));
        }
        curl_close($ch);

        return ($httpCode === 200 && $response) ? $response : null;
    }
}