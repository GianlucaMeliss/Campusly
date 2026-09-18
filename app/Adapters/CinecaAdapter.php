<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

class CinecaAdapter implements UniversityAdapterInterface
{
    private const API_URL = 'https://unins.prod.up.cineca.it/api/Impegni/getImpegniCalendarioPubblico';

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        $linkCalendarioId = $courseConfig['linkCalendarioId'] ?? '';
        $clienteId = $courseConfig['clienteId'] ?? '';

        if (empty($linkCalendarioId) || empty($clienteId)) {
            throw new \Exception("Codici Insubria mancanti. Rifai l'onboarding.");
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

        $ch = curl_init(self::API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadArray));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=utf-8',
            'Accept: application/json, text/plain, */*',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Opzioni di velocità importate dal tuo proxy.php originale
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("Errore cURL: " . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : [];
        }

        throw new Exception("Errore API Cineca: HTTP " . $httpCode);
    }
}