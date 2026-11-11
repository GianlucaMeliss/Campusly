<?php
declare(strict_types=1);

namespace App\Adapters;

use RuntimeException;

class PoliMiAdapter implements UniversityAdapterInterface
{
    public function buildRequest(string $startDate, string $endDate, array $courseConfig): array
    {
        throw new RuntimeException('Adapter PoliMi non ancora implementato');
    }

    public function parseResponse(string $body, int $httpCode, string $startDate = '', string $endDate = ''): array
    {
        throw new RuntimeException('Adapter PoliMi non ancora implementato');
    }

    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array
    {
        throw new RuntimeException('Adapter PoliMi non ancora implementato');
    }
}
