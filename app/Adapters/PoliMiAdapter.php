<?php
declare(strict_types=1);

namespace App\Adapters;

class PoliMiAdapter implements UniversityAdapterInterface
{
    public function buildRequest(string $startDate, string $endDate, array $courseConfig): array
    {
        return [
            'url'     => (string)($courseConfig['url'] ?? ''),
            'method'  => 'GET',
            'headers' => ['Accept: application/json'],
        ];
    }

    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array
    {
        return [];
    }

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        return [];
    }
}
