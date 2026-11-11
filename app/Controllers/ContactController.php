<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\RateLimiter;
use App\Core\Str;

final class ContactController
{
    private const MAX_CSV_BYTES = 5242880; // 5 MB

    private static function sanitizeCsvCell(string $value): string
    {
        $clean = str_replace(["\r", "\n"], ' ', trim($value));
        if ($clean !== '' && in_array($clean[0], ['=', '+', '-', '@', "\t"], true)) {
            return "'" . $clean;
        }
        return $clean;
    }

    public function sendContact(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/');
            exit;
        }

        $redirectBase = BASE_PATH . '/';

        if (!empty($_POST['website'])) {
            header('Location: ' . $redirectBase . '?status=success');
            exit;
        }

        $ip = Http::clientIp();
        $limiter = new RateLimiter();

        $timeBetweenRequests = 60;
        if (
            (isset($_SESSION['last_submission_time']) && (time() - (int)$_SESSION['last_submission_time']) < $timeBetweenRequests)
            || $limiter->tooManyAttempts('contact:ip', $ip, 3, 3600)
        ) {
            header('Location: ' . $redirectBase . '?status=error&msg=ratelimit');
            exit;
        }

        $allowedFields = [
            'name'      => 100,
            'nome'      => 100,
            'email'     => 255,
            'phone'     => 40,
            'telefono'  => 40,
            'subject'   => 150,
            'oggetto'   => 150,
            'message'   => 2000,
            'messaggio' => 2000,
        ];

        $collected = [];
        $replyTo = null;

        foreach ($allowedFields as $field => $maxLen) {
            if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
                continue;
            }
            $val = trim($_POST[$field]);
            if ($val === '') {
                continue;
            }
            if ($field === 'email') {
                $val = str_replace(["\r", "\n"], '', $val);
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    header('Location: ' . $redirectBase . '?status=error&msg=invalid_email');
                    exit;
                }
                $replyTo = $val;
            }
            $collected[$field] = Str::cut($val, $maxLen);
        }

        if (empty($collected)) {
            header('Location: ' . $redirectBase . '?status=error&msg=empty');
            exit;
        }

        $limiter->hit('contact:ip', $ip);

        $appConfig = require BASEPATH . '/config/app.php';
        $to = (string)($appConfig['mail']['contact_email'] ?? 'info@example.com');
        $from = str_replace(["\r", "\n"], '', (string)($appConfig['mail']['from'] ?? 'noreply@example.com'));
        $subject = str_replace(["\r", "\n"], '', (string)($appConfig['mail']['contact_subject'] ?? 'Nuovo contatto'));

        $message = "Hai ricevuto una nuova richiesta di contatto dal sito web.\n\n";
        $message .= "DETTAGLI CONTATTO\n";
        $message .= "-----------------------------------\n";
        foreach ($collected as $k => $v) {
            $label = ucfirst(str_replace('_', ' ', $k));
            $message .= "{$label}: {$v}\n";
        }
        $message .= "-----------------------------------\n";

        $safeReplyTo = $replyTo ?? $from;
        $headers = "From: {$from}\r\nReply-To: {$safeReplyTo}\r\nContent-Type: text/plain; charset=UTF-8\r\n";

        if (@mail($to, $subject, $message, $headers)) {
            $_SESSION['last_submission_time'] = time();
            header('Location: ' . $redirectBase . '?status=success');
            exit;
        }

        header('Location: ' . $redirectBase . '?status=error&msg=server');
        exit;
    }

    public function logCookie(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Http::json(['status' => 'error', 'message' => 'Metodo non consentito.'], 405);
        }

        $ip = Http::clientIp();
        $limiter = new RateLimiter();

        $timeBetweenRequests = 10;
        if (
            (isset($_SESSION['last_cookie_log_time']) && (time() - (int)$_SESSION['last_cookie_log_time']) < $timeBetweenRequests)
            || $limiter->tooManyAttempts('cookielog:ip', $ip, 60, 3600)
        ) {
            Http::json(['status' => 'error', 'message' => 'Rate limit exceeded.'], 429);
        }

        $data = Http::input();

        if (!isset($data['consent_status'], $data['uuid']) || !is_string($data['consent_status']) || !is_string($data['uuid'])) {
            Http::json(['status' => 'error', 'message' => 'Dati mancanti.'], 400);
        }

        $limiter->hit('cookielog:ip', $ip);

        $logDir = BASEPATH . '/app/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/cookie_consents.csv';

        if (is_file($logFile) && (int)@filesize($logFile) > self::MAX_CSV_BYTES) {
            @rename($logFile, $logDir . '/cookie_consents_' . gmdate('Ym') . '.csv');
        }

        $isNewFile = !file_exists($logFile);

        $timestamp = gmdate('Y-m-d H:i:s');
        $uuid = self::sanitizeCsvCell(Str::cut(preg_replace('/[^a-zA-Z0-9\-]/', '', $data['uuid']) ?? '', 64));
        $status = in_array($data['consent_status'], ['accepted', 'rejected'], true) ? $data['consent_status'] : 'unknown';
        $userAgent = self::sanitizeCsvCell(Str::cut((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 255));
        $anonIp = self::sanitizeCsvCell(Http::anonymizeIp($ip));

        $file = @fopen($logFile, 'a');
        if ($file) {
            if ($isNewFile) {
                fputcsv($file, ['Timestamp', 'UUID', 'Consent_Status', 'Anon_IP', 'User_Agent']);
            }
            fputcsv($file, [$timestamp, $uuid, $status, $anonIp, $userAgent]);
            fclose($file);

            $_SESSION['last_cookie_log_time'] = time();
            Http::json(['status' => 'success']);
        }

        Http::json(['status' => 'error', 'message' => 'Impossibile scrivere il log.'], 500);
    }

    public function cspReport(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Http::json(['status' => 'ignored'], 405);
        }

        $ip = Http::clientIp();
        $limiter = new RateLimiter();
        if ($limiter->tooManyAttempts('csp:ip', $ip, 20, 3600)) {
            Http::json(['status' => 'rate_limited'], 429);
        }
        $limiter->hit('csp:ip', $ip);

        $raw = Str::cut(Http::rawBody(), 4096);
        if ($raw !== '') {
            $logDir = BASEPATH . '/app/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/csp.log';
            if (!is_file($logFile) || (int)@filesize($logFile) < self::MAX_CSV_BYTES) {
                @error_log('[' . gmdate('Y-m-d H:i:s') . '] ' . str_replace(["\r", "\n"], ' ', $raw) . "\n", 3, $logFile);
            }
        }

        http_response_code(204);
        exit;
    }
}
