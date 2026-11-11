<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class UsiAdapter implements UniversityAdapterInterface
{
    public function buildRequest(string $startDate, string $endDate, array $config): array
    {
        $urlWeb = (string)($config['url'] ?? '');
        if ($urlWeb === '') {
            throw new Exception("URL USI mancante nella configurazione del corso");
        }

        // Pulisce eventuale sintassi Markdown [url](url) presente nel DB
        if (preg_match('/https?:\/\/[^\s\]\)]+/', $urlWeb, $m)) {
            $urlWeb = $m[0];
        }

        $apiUrl = preg_replace(
            '/\/[a-z]{2}\/offerte-formative\/(\d+)\/.*\/piano-orari\/(\d+)\/(\d+)/',
            '/api/educations/$1/schedules/$2/$3',
            $urlWeb
        );

        if (!$apiUrl || $apiUrl === $urlWeb) {
            throw new Exception("L'URL fornito non è nel formato USI corretto");
        }

        return [
            'url'     => $apiUrl,
            'method'  => 'GET',
            'headers' => ['Accept: application/json'],
        ];
    }

    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array
    {
        if ($httpCode !== 200) {
            throw new Exception("Errore API USI: HTTP " . $httpCode);
        }

        if (trim($body) === '') {
            throw new Exception("Errore API USI: risposta vuota");
        }

        $risposta = json_decode($body, true);
        if (!is_array($risposta) || json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Errore API USI: JSON non valido");
        }

        $datiUSI = $risposta['data'] ?? [];
        if (!is_array($datiUSI)) {
            throw new Exception("Errore API USI: struttura dati mancante");
        }

        $eventi = [];
        $filtroInizioTs = $startDate !== '' ? (int)strtotime($startDate) : 0;
        $filtroFineTs = $endDate !== '' ? (int)strtotime($endDate) : PHP_INT_MAX;

        foreach ($datiUSI as $eventoUSI) {
            $start = $eventoUSI['start'] ?? '';
            $end = $eventoUSI['end'] ?? '';

            if (empty($start)) {
                continue;
            }

            $lezioneTs = (int)strtotime((string)$start);
            if ($lezioneTs < $filtroInizioTs || $lezioneTs > $filtroFineTs) {
                continue;
            }

            $materia = $eventoUSI['course']['name_it'] ?? $eventoUSI['course']['name_en'] ?? $eventoUSI['title'] ?? 'Lezione USI';
            $aula = $eventoUSI['place']['office'] ?? 'Aula non assegnata';
            $edificio = $eventoUSI['place']['building']['name_it'] ?? '';

            $professore = 'Non assegnato';
            if (!empty($eventoUSI['course']['lecturers']['data']) && is_array($eventoUSI['course']['lecturers']['data'])) {
                $profNomi = array_map(function ($l) {
                    return $l['person']['short_name'] ?? $l['person']['last_name'] ?? trim(($l['person']['first_name'] ?? '') . ' ' . ($l['person']['last_name'] ?? ''));
                }, $eventoUSI['course']['lecturers']['data']);
                $professore = implode(', ', array_filter($profNomi));
            }

            $isoStart = date('Y-m-d\TH:i:s.000', $lezioneTs);
            $isoEnd = date('Y-m-d\TH:i:s.000', !empty($end) ? (int)strtotime((string)$end) : $lezioneTs);

            $eventi[] = [
                'idPersonale'       => null,
                'nome'              => trim((string)$materia),
                'dataInizio'        => $isoStart,
                'dataFine'          => $isoEnd,
                'stato'             => 'C',
                'tipoAbbreviazione' => 'Lezione',
                'dettagliDidattici' => [
                    [
                        'partizione' => [
                            'descrizione' => '',
                        ],
                    ],
                ],
                'risorse'           => [
                    [
                        'aula' => [
                            'descrizione' => $aula,
                            'edificio'    => [
                                'descrizione' => $edificio,
                            ],
                        ],
                        'docente' => [
                            'cognome' => trim($professore),
                        ],
                    ],
                ],
                'isPersonale'       => false,
            ];
        }

        return $eventi;
    }

    public function getSchedule(string $startDate, string $endDate, array $config): array
    {
        $request = $this->buildRequest($startDate, $endDate, $config);
        $res = HttpClient::send($request);
        return $this->parseResponse($res['body'], $res['http_code'], $startDate, $endDate);
    }
}
