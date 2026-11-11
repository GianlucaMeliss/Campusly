<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Str;

final class ContactController
{
    public function sendContact(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_PATH . '/');
            exit;
        }

        $referer = (string)($_SERVER['HTTP_REFERER'] ?? (BASE_PATH . '/'));
        $baseUrl = strtok($referer, '?') ?: (BASE_PATH . '/');

        $timeBetweenRequests = 60;
        if (isset($_SESSION['last_submission_time'])) {
            $secondsSinceLast = time() - (int)$_SESSION['last_submission_time'];
            if ($secondsSinceLast < $timeBetweenRequests) {
                header('Location: ' . $baseUrl . '?status=error&msg=ratelimit');
                exit;
            }
        }

        $allowedFields = [
            'name'    => 100,
            'nome'    => 100,
            'email'   => 255,
            'phone'   => 40,
            'telefono'=> 40,
            'subject' => 150,
            'oggetto' => 150,
            'message' => 2000,
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
                    header('Location: ' . $baseUrl . '?status=error&msg=invalid_email');
                    exit;
                }
                $replyTo = $val;
            }
            $collected[$field] = Str::cut($val, $maxLen);
        }

        if (empty($collected)) {
            header('Location: ' . $baseUrl . '?status=error&msg=empty');
            exit;
        }

        $appConfig = require BASEPATH . '/config/app.php';
        $to = (string)($appConfig['mail']['contact_email'] ?? 'info@campusly.it');
        $from = str_replace(["\r", "\n"], '', (string)($appConfig['mail']['from'] ?? 'noreply@campusly.it'));
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
            header('Location: ' . $baseUrl . '?status=success');
            exit;
        }

        header('Location: ' . $baseUrl . '?status=error&msg=server');
        exit;
    }

    public function logCookie(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Http::json(['status' => 'error', 'message' => 'Metodo non consentito.'], 405);
        }

        $timeBetweenRequests = 10;
        if (isset($_SESSION['last_cookie_log_time'])) {
            $secondsSinceLast = time() - (int)$_SESSION['last_cookie_log_time'];
            if ($secondsSinceLast < $timeBetweenRequests) {
                Http::json(['status' => 'error', 'message' => 'Rate limit exceeded.'], 429);
            }
        }

        $data = Http::input();

        if (!isset($data['consent_status'], $data['uuid']) || !is_string($data['consent_status']) || !is_string($data['uuid'])) {
            Http::json(['status' => 'error', 'message' => 'Dati mancanti.'], 400);
        }

        $logDir = BASEPATH . '/app/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/cookie_consents.csv';
        $isNewFile = !file_exists($logFile);

        $timestamp = date('Y-m-d H:i:s');
        $uuid = Str::cut(preg_replace('/[^a-zA-Z0-9\-]/', '', $data['uuid']) ?? '', 64);
        $status = in_array($data['consent_status'], ['accepted', 'rejected'], true) ? $data['consent_status'] : 'unknown';
        $userAgent = Str::cut((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 255);
        $anonIp = Http::anonymizeIp(Http::clientIp());

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
}
