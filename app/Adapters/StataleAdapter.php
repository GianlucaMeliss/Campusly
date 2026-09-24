<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class StataleAdapter implements UniversityAdapterInterface
{
    // L'endpoint standard di EasyStaff per recuperare gli eventi della griglia
    private const API_URL = 'https://orari.unimi.it/PortaleStudenti/grid_call.php';

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        $corso = $courseConfig['corso'] ?? '';
        $anno2 = $courseConfig['anno2'] ?? '';
        $annoAccademico = $courseConfig['anno'] ?? date('Y');

        if (empty($corso) || empty($anno2)) {
            throw new Exception("Configurazione corso Statale mancante.");
        }

        // Converte la data ISO in formato testuale per EasyStaff (es. 24-09-2026)
        $dataInizioTs = strtotime($startDate);
        $dataEasyStaff = date('d-m-Y', $dataInizioTs);

        // Parametri richiesti dal server EasyStaff
        $queryData = http_build_query([
            'view' => 'easycourse',
            'form-type' => 'corso',
            'include' => 'corso',
            'anno' => $annoAccademico,
            'corso' => $corso,
            'anno2[]' => $anno2,
            'date' => $dataEasyStaff,
            '_lang' => 'it',
            'all_events' => '0'
        ]);

        $url = self::API_URL . '?' . $queryData;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'X-Requested-With: XMLHttpRequest',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("Errore cURL (Statale): " . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Errore API Statale: HTTP " . $httpCode);
        }

        $data = json_decode($response, true);
        
        // EasyStaff restituisce solitamente un array 'celle'
        $eventiOriginali = $data['celle'] ?? [];
        $eventiNormalizzati = [];

        foreach ($eventiOriginali as $evento) {
            // Uniamo data e ora (es. "24-09-2026" + "08:30") e convertiamo in ISO
            $startStr = $evento['data'] . ' ' . $evento['ora_inizio'];
            $endStr = $evento['data'] . ' ' . $evento['ora_fine'];
            
            // FormatDateTime converte "d-m-Y H:i" in "Y-m-d\TH:i:s.000\Z" per JS
            $isoStart = date('Y-m-d\TH:i:s.000\Z', strtotime(str_replace('-', '/', $startStr)));
            $isoEnd = date('Y-m-d\TH:i:s.000\Z', strtotime(str_replace('-', '/', $endStr)));

            // Formattazione Aule
            $risorse = [];
            if (!empty($evento['aule'])) {
                foreach ($evento['aule'] as $aula) {
                    $risorse[] = [
                        'aula' => [
                            'descrizione' => $aula['des_indirizzo'] ?? $aula['descrizione'] ?? 'Aula ignota'
                        ],
                        'docente' => [
                            'cognome' => $evento['docente'] ?? ''
                        ]
                    ];
                }
            } else {
                $risorse[] = [
                    'aula' => ['descrizione' => 'Aula non assegnata'],
                    'docente' => ['cognome' => $evento['docente'] ?? '']
                ];
            }

            $eventiNormalizzati[] = [
                'idPersonale' => null,
                'nome' => $evento['nome_insegnamento'] ?? 'Lezione',
                'dataInizio' => $isoStart,
                'dataFine' => $isoEnd,
                'stato' => isset($evento['annullato']) && $evento['annullato'] ? 'A' : 'C',
                'risorse' => $risorse,
                'isPersonale' => false
            ];
        }

        return $eventiNormalizzati;
    }
}