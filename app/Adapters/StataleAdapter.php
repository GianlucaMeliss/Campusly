<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class StataleAdapter implements UniversityAdapterInterface
{
    protected const API_URL = 'https://orari.unimi.it/PortaleStudenti/grid_call.php';
    protected const UNI_LABEL = 'Statale';

    public function buildRequest(string $startDate, string $endDate, array $courseConfig): array
    {
        $corso = $courseConfig['corso'] ?? '';
        $anno2 = $courseConfig['anno2'] ?? '';
        $annoAccademico = $courseConfig['anno'] ?? date('Y');

        if (empty($corso) || empty($anno2)) {
            throw new Exception("Configurazione corso " . static::UNI_LABEL . " mancante.");
        }

        $dataInizioTs = strtotime($startDate);
        $dataEasyStaff = date('d-m-Y', $dataInizioTs ?: time());

        $queryData = http_build_query([
            'view'       => 'easycourse',
            'form-type'  => 'corso',
            'include'    => 'corso',
            'anno'       => $annoAccademico,
            'corso'      => $corso,
            'anno2[]'    => $anno2,
            'date'       => $dataEasyStaff,
            '_lang'      => 'it',
            'all_events' => '0',
        ]);

        return [
            'url'     => static::API_URL . '?' . $queryData,
            'method'  => 'GET',
            'headers' => [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'X-Requested-With: XMLHttpRequest',
            ],
        ];
    }

    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array
    {
        if ($httpCode !== 200) {
            throw new Exception("Errore API " . static::UNI_LABEL . ": HTTP " . $httpCode);
        }

        $data = json_decode($body, true);
        $eventiOriginali = is_array($data) ? ($data['celle'] ?? []) : [];
        $eventiNormalizzati = [];

        foreach ($eventiOriginali as $evento) {
            if (empty($evento['data']) || empty($evento['ora_inizio']) || empty($evento['ora_fine'])) {
                continue;
            }

            $dataPulita = str_replace('/', '-', (string)$evento['data']);
            $startStr = $dataPulita . ' ' . $evento['ora_inizio'];
            $endStr = $dataPulita . ' ' . $evento['ora_fine'];

            $isoStart = date('Y-m-d\TH:i:s.000', (int)strtotime($startStr));
            $isoEnd = date('Y-m-d\TH:i:s.000', (int)strtotime($endStr));

            $rawAula = (string)($evento['aula'] ?? 'Aula non assegnata');
            $descrizioneAula = $rawAula;
            $edificioAula = '';

            if (preg_match('/^(.*?)\[(.*?)\]$/', trim($rawAula), $matches)) {
                $descrizioneAula = trim($matches[1]);
                $edificioAula = trim($matches[2]);
            }

            $stato = (isset($evento['Annullato']) && (string)$evento['Annullato'] === '1') ? 'A' : 'C';

            $eventiNormalizzati[] = [
                'idPersonale'      => null,
                'nome'             => $evento['nome_insegnamento'] ?? 'Lezione',
                'dataInizio'       => $isoStart,
                'dataFine'         => $isoEnd,
                'stato'            => $stato,
                'tipoAbbreviazione'=> $evento['tipo'] ?? 'Lezione',
                'dettagliDidattici'=> [
                    [
                        'partizione' => [
                            'descrizione' => $evento['percorso_didattico'] ?? '',
                        ],
                    ],
                ],
                'risorse'          => [
                    [
                        'aula' => [
                            'descrizione' => $descrizioneAula,
                            'edificio'    => [
                                'descrizione' => $edificioAula,
                            ],
                        ],
                        'docente' => [
                            'cognome' => $evento['docente'] ?? 'Non assegnato',
                        ],
                    ],
                ],
                'isPersonale'      => false,
            ];
        }

        return $eventiNormalizzati;
    }

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        $request = $this->buildRequest($startDate, $endDate, $courseConfig);
        $res = HttpClient::send($request);
        return $this->parseResponse($res['body'], $res['http_code'], $startDate, $endDate);
    }
}
