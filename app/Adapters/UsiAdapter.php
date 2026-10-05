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
            
            // 2. Estrazione Aula (Cerchiamo in tutte le varianti usate dalle API)
            $aula = '';
            if (!empty($eventoUSI['rooms']) && is_array($eventoUSI['rooms'])) {
                $auleNomi = array_map(fn($r) => $r['name'] ?? '', $eventoUSI['rooms']);
                $aula = implode(' + ', array_filter($auleNomi));
            } elseif (!empty($eventoUSI['locations']) && is_array($eventoUSI['locations'])) {
                $auleNomi = array_map(fn($l) => $l['name'] ?? '', $eventoUSI['locations']);
                $aula = implode(' + ', array_filter($auleNomi));
            } elseif (!empty($eventoUSI['room'])) {
                $aula = is_array($eventoUSI['room']) ? ($eventoUSI['room']['name'] ?? '') : $eventoUSI['room'];
            }

            // 3. Estrazione Docente
            $professore = '';
            if (!empty($eventoUSI['teachers']) && is_array($eventoUSI['teachers'])) {
                $profNomi = array_map(fn($t) => trim(($t['last_name'] ?? '') . ' ' . ($t['first_name'] ?? '')), $eventoUSI['teachers']);
                $professore = implode(', ', array_filter($profNomi));
            } elseif (!empty($eventoUSI['professors']) && is_array($eventoUSI['professors'])) {
                $profNomi = array_map(fn($t) => trim(($t['last_name'] ?? '') . ' ' . ($t['first_name'] ?? '')), $eventoUSI['professors']);
                $professore = implode(', ', array_filter($profNomi));
            }

            // 4. ASSEMBLAGGIO INTELLIGENTE DEL TITOLO
            $aulaFormattata = $aula ?: 'Da definire';
            
            // Se c'è un'aula, la mettiamo all'inizio come fa l'USI (es. "A31 - Advanced Skills")
            $titoloCard = $materia;
            if (!empty($aula)) {
                $titoloCard = $aula . ' - ' . $materia;
            }
            
            // Opzionale: Se vuoi che il nome del prof si veda subito sulla card (e non solo cliccandoci)
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
                        'aula' => ['descrizione' => $aulaFormattata],
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