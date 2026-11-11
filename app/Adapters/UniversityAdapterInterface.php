<?php
declare(strict_types=1);

namespace App\Adapters;

interface UniversityAdapterInterface
{
    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array;

    /**
     * Costruisce i parametri della richiesta HTTP per l'esecuzione sincrona o parallela.
     *
     * @return array{url: string, method: string, headers: array<int, string>, body?: string|null}
     */
    public function buildRequest(string $startDate, string $endDate, array $courseConfig): array;

    /**
     * Normalizza la risposta HTTP grezza nel formato eventi di Campusly.
     */
    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array;
}
