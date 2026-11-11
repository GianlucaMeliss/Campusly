<?php
declare(strict_types=1);

namespace App\Services;

use App\Adapters\HttpClient;
use App\Adapters\UniversityAdapterInterface;
use InvalidArgumentException;
use Throwable;

final class ScheduleService
{
    private const CACHE_TTL_SECONDS = 300;
    private const MAX_RANGE_DAYS = 35;

    public function validateDateRange(string $from, string $to): array
    {
        $startTs = strtotime($from);
        $endTs = strtotime($to);

        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            throw new InvalidArgumentException('Intervallo di date non valido.');
        }

        if (($endTs - $startTs) > (self::MAX_RANGE_DAYS * 86400)) {
            throw new InvalidArgumentException('Intervallo di date superiore al limite consentito.');
        }

        return [$startTs, $endTs];
    }

    public function cacheKey(string $from, string $to, array $userCourses): string
    {
        [$startTs, $endTs] = $this->validateDateRange($from, $to);
        $startClean = date('Ymd', $startTs);
        $endClean = date('Ymd', $endTs);

        $configs = array_column($userCourses, 'external_course_id');
        sort($configs);
        $hashCorsi = md5(serialize($configs));

        return "settimana_{$startClean}_{$endClean}_{$hashCorsi}.json";
    }

    public function getEventsForUser(int $userId, array $userCourses, string $from, string $to): array
    {
        $this->validateDateRange($from, $to);

        if (empty($userCourses)) {
            return [
                'events'          => [],
                'cache_status'    => 'EMPTY',
                'partial_failure' => false,
            ];
        }

        $cacheDir = BASEPATH . '/data/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/' . $this->cacheKey($from, $to, $userCourses);

        if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile) < self::CACHE_TTL_SECONDS)) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return [
                    'events'          => $cached,
                    'cache_status'    => 'HIT-MICROCACHE',
                    'partial_failure' => false,
                ];
            }
        }

        $adapters = [];
        $requests = [];
        $partialFailure = false;

        foreach ($userCourses as $idx => $courseData) {
            $extConfig = json_decode((string)($courseData['external_course_id'] ?? ''), true);
            if (!is_array($extConfig)) {
                continue;
            }
            if (($extConfig['linkCalendarioId'] ?? '') === 'AUTO') {
                continue;
            }

            $adapterClass = ltrim((string)($courseData['adapter_class'] ?? ''), '\\');
            if ($adapterClass === '' || !class_exists($adapterClass)) {
                continue;
            }

            try {
                $adapter = new $adapterClass();
                if ($adapter instanceof UniversityAdapterInterface) {
                    $requests[$idx] = $adapter->buildRequest($from, $to, $extConfig);
                    $adapters[$idx] = $adapter;
                }
            } catch (Throwable $e) {
                error_log(sprintf('[ScheduleService buildRequest user=%d] %s', $userId, $e->getMessage()));
                $partialFailure = true;
            }
        }

        $tuttiGliEventi = [];
        $successCount = 0;

        if (!empty($requests)) {
            $responses = HttpClient::sendMulti($requests, 10);

            foreach ($responses as $idx => $res) {
                $adapter = $adapters[$idx];
                try {
                    if ($res['error'] !== null) {
                        throw new \RuntimeException($res['error']);
                    }
                    $eventiCorso = $adapter->parseResponse($res['body'], $res['http_code'], $from, $to);
                    $tuttiGliEventi = array_merge($tuttiGliEventi, $eventiCorso);
                    $successCount++;
                } catch (Throwable $e) {
                    error_log(sprintf('[ScheduleService parseResponse user=%d] %s', $userId, $e->getMessage()));
                    $partialFailure = true;
                }
            }
        }

        if ($successCount === 0 && !empty($requests)) {
            if (is_file($cacheFile)) {
                $stale = json_decode((string)@file_get_contents($cacheFile), true);
                if (is_array($stale)) {
                    return [
                        'events'          => $stale,
                        'cache_status'    => 'STALE',
                        'partial_failure' => true,
                    ];
                }
            }
            return [
                'events'          => [],
                'cache_status'    => 'ERROR-FALLBACK',
                'partial_failure' => true,
            ];
        }

        if (!$partialFailure) {
            $this->writeCacheAtomically($cacheDir, $cacheFile, $tuttiGliEventi);
        }

        return [
            'events'          => $tuttiGliEventi,
            'cache_status'    => 'MISS-FETCHING-API',
            'partial_failure' => $partialFailure,
        ];
    }

    private function writeCacheAtomically(string $cacheDir, string $cacheFile, array $events): void
    {
        if (random_int(1, 100) === 1) {
            $this->purgeOldCacheFiles($cacheDir);
        }

        $json = json_encode($events, JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return;
        }

        $tmpFile = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmpFile, $json, LOCK_EX) !== false) {
            @rename($tmpFile, $cacheFile);
        } else {
            @unlink($tmpFile);
        }
    }

    private function purgeOldCacheFiles(string $cacheDir): void
    {
        $files = glob($cacheDir . '/settimana_*.json') ?: [];
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - (int)@filemtime($file)) > 86400) {
                @unlink($file);
            }
        }
    }
}
