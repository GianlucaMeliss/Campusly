<?php
declare(strict_types=1);

namespace App\Adapters;

interface UniversityAdapterInterface
{
    /**
     * Recupera l'orario delle lezioni per un dato intervallo di date.
     * 
     * @param string $startDate Data di inizio in formato ISO (es. 2026-09-18T00:00:00.000Z)
     * @param string $endDate Data di fine in formato ISO
     * @param array $courseConfig Configurazioni specifiche del corso (ID esterni, token, ecc.)
     * @return array Array associativo contenente gli eventi normalizzati
     */
    public function getSchedule(string $startDate, string $endDate, array $courseConfig): array;
}