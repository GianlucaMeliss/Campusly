<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class CinecaAdapter implements UniversityAdapterInterface
{
    private const API_URL = 'https://unins.prod.up.cineca.it/api/Impegni/getImpegniCalendarioPubblico';

    public function buildRequest(string $startDate, string $endDate, array $courseConfig): array
    {
        $linkCalendarioId = $courseConfig['linkCalendarioId'] ?? '';
        $clienteId = $courseConfig['clienteId'] ?? '';

        if (empty($linkCalendarioId) || empty($clienteId)) {
            throw new Exception("Codici Insubria mancanti. Rifai l'onboarding.");
        }

        $payloadArray = [
            "linkCalendarioId" => $linkCalendarioId,
            "clienteId" => $clienteId,
            "bachecaPrenotazioneSpaziAttiva" => false,
            "bachecaRisoluzioneImpegniAttiva" => false,
            "calcoloTassonomiaAule" => false,
            "mostraImpegniAnnullati" => false,
            "mostraPianificazioneInterna" => false,
            "pianificazioneInternaAttiva" => false,
            "dataInizio" => $startDate,
            "dataFine" => $endDate
        ];

        return [
            'url'     => self::API_URL,
            'method'  => 'POST',
            'headers' => [
                'Content-Type: application/json;charset=utf-8',
                'Accept: application/json, text/plain, */*',
            ],
            'body'    => (string)json_encode($payloadArray),
        ];
    }

    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array
    {
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("Errore API Cineca: HTTP " . $httpCode);
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        $request = $this->buildRequest($startDate, $endDate, $courseConfig);
        $res = HttpClient::send($request);
        return $this->parseResponse($res['body'], $res['http_code'], $startDate, $endDate);
    }
}
