<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;
use CurlHandle;

final class HttpClient
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    private static function createHandle(array $request, int $timeout = 10): CurlHandle
    {
        $url = $request['url'] ?? '';
        $method = strtoupper($request['method'] ?? 'GET');
        $headers = $request['headers'] ?? [];
        $body = $request['body'] ?? null;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::DEFAULT_USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        return $ch;
    }

    /**
     * @return array{body: string, http_code: int}
     */
    public static function send(array $request, int $timeout = 15): array
    {
        $ch = self::createHandle($request, $timeout);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("Errore cURL: " . $err);
        }
        curl_close($ch);

        return [
            'body'      => is_string($response) ? $response : '',
            'http_code' => $httpCode,
        ];
    }

    /**
     * Esegue più richieste HTTP in parallelo con curl_multi.
     *
     * @param array<int|string, array> $requests
     * @return array<int|string, array{ok: bool, body: string, http_code: int, error: ?string}>
     */
    public static function sendMulti(array $requests, int $totalTimeout = 10): array
    {
        if (empty($requests)) {
            return [];
        }

        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($requests as $key => $req) {
            $ch = self::createHandle($req, $totalTimeout);
            $handles[$key] = $ch;
            curl_multi_add_handle($mh, $ch);
        }

        $active = null;
        $startTime = microtime(true);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh, 0.2);
            }
            if ((microtime(true) - $startTime) > $totalTimeout) {
                break;
            }
        } while ($active && $status === CURLM_OK);

        foreach ($handles as $key => $ch) {
            $errno = curl_errno($ch);
            $error = $errno ? curl_error($ch) : null;
            $body = curl_multi_getcontent($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $results[$key] = [
                'ok'        => ($errno === 0 && $httpCode >= 200 && $httpCode < 300),
                'body'      => is_string($body) ? $body : '',
                'http_code' => $httpCode,
                'error'     => $error,
            ];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $results;
    }
}
