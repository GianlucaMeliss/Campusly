<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class StataleAdapter implements UniversityAdapterInterface
{
    private const API_URL = 'https://orari.unimi.it/PortaleStudenti/grid_call.php';

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        $corso = $courseConfig['corso'] ?? '';
        $anno2 = $courseConfig['anno2'] ?? '';
        $annoAccademico = $courseConfig['anno'] ?? date('Y');

        if (empty($corso) || empty($anno2)) {
            throw new Exception("Configurazione corso Statale mancante.");
        }

        $dataInizioTs = strtotime($startDate);
        $dataEasyStaff = date('d-m-Y', $dataInizioTs);

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

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
        
        $eventiOriginali = $data['celle'] ?? [];
        $eventiNormalizzati = [];

        foreach ($eventiOriginali as $evento) {
            // 1. GESTIONE DATE (Senza la \Z finale per evitare fusi orari sballati)
            $dataPulita = str_replace('/', '-', $evento['data']);
            $startStr = $dataPulita . ' ' . $evento['ora_inizio'];
            $endStr = $dataPulita . ' ' . $evento['ora_fine'];
            
            $isoStart = date('Y-m-d\TH:i:s.000', strtotime($startStr));
            $isoEnd = date('Y-m-d\TH:i:s.000', strtotime($endStr));

            // 2. GESTIONE AULE ED EDIFICI (Regex per separare "Aula 6 [Conservatorio]")
            $rawAula = $evento['aula'] ?? 'Aula non assegnata';
            $descrizioneAula = $rawAula;
            $edificioAula = '';

            // Cerca uno schema: "Qualsiasi testo [Testo tra parentesi]"
            if (preg_match('/^(.*?)\[(.*?)\]$/', trim($rawAula), $matches)) {
                $descrizioneAula = trim($matches[1]); // Es: "Aula 6"
                $edificioAula = trim($matches[2]);    // Es: "Conservatorio (Edificio 4)"
            }

            // 3. STATO DELLA LEZIONE
            $stato = (isset($evento['Annullato']) && $evento['Annullato'] === "1") ? 'A' : 'C';

            // 4. MAPPA EVENTO PER IL CALENDARIO FRONTEND
            $eventiNormalizzati[] = [
                'idPersonale' => null,
                'nome' => $evento['nome_insegnamento'] ?? 'Lezione',
                'dataInizio' => $isoStart,
                'dataFine' => $isoEnd,
                'stato' => $stato,
                // Passiamo il tipo (Es. "Lezione", "Esercitazione") che il JS inserirà nel badge rosso/rosa
                'tipoAbbreviazione' => $evento['tipo'] ?? 'Lezione',
                
                // Sfruttiamo il campo "dettagliDidattici" per mostrare il curriculum sotto l'orario
                'dettagliDidattici' => [
                    [
                        'partizione' => [
                            'descrizione' => $evento['percorso_didattico'] ?? ''
                        ]
                    ]
                ],

                // Array risorse compatibile con il parser Insubria
                'risorse' => [
                    [
                        'aula' => [
                            'descrizione' => $descrizioneAula,
                            'edificio' => [
                                'descrizione' => $edificioAula
                            ]
                        ],
                        'docente' => [
                            'cognome' => $evento['docente'] ?? 'Non assegnato'
                        ]
                    ]
                ],
                'isPersonale' => false
            ];
        }

        return $eventiNormalizzati;
    }
}