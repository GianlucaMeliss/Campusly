<?php
declare(strict_types=1);

namespace App\Adapters;

use Exception;

final class HttpClient
{
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

    private static function resolveCaBundle(): ?string
    {
        $configured = ini_get('curl.cainfo');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/cert.pem',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function createHandle(array $request, int $timeout = 10)
    {
        $url = (string)($request['url'] ?? '');
        $method = strtoupper((string)($request['method'] ?? 'GET'));
        $headers = $request['headers'] ?? [];
        $body = $request['body'] ?? null;
        $verifySsl = (bool)($request['verify_ssl'] ?? true);

        if (!$verifySsl) {
            error_log('[HttpClient] Attenzione: verifica SSL disabilitata esplicitamente per URL: ' . $url);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, self::DEFAULT_USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

        if ($verifySsl) {
            $customCa = $request['ca_info'] ?? self::resolveCaBundle();
            if (is_string($customCa) && $customCa !== '' && is_file($customCa)) {
                curl_setopt($ch, CURLOPT_CAINFO, $customCa);
            }
        }

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

    public static function send(array $request, int $timeout = 15): array
    {
        $ch = self::createHandle($request, $timeout);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($errno !== 0) {
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
