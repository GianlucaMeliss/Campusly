<?php
declare(strict_types=1);

namespace App\Core;

final class Http
{
    private static ?string $rawBody = null;
    private static ?array $decodedBody = null;

    public static function rawBody(): string
    {
        if (self::$rawBody === null) {
            $raw = file_get_contents('php://input');
            self::$rawBody = is_string($raw) ? $raw : '';
        }
        return self::$rawBody;
    }

    public static function input(): array
    {
        if (self::$decodedBody !== null) {
            return self::$decodedBody;
        }

        $raw = self::rawBody();
        if ($raw === '') {
            self::$decodedBody = [];
            return self::$decodedBody;
        }

        $data = json_decode($raw, true);
        self::$decodedBody = is_array($data) ? $data : [];
        return self::$decodedBody;
    }

    public static function json($data, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            foreach ($headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function requireUserId(): int
    {
        if (!isset($_SESSION['user_id'])) {
            self::json(['error' => 'Utente non autenticato'], 401);
        }
        return (int)$_SESSION['user_id'];
    }

    public static function clientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function anonymizeIp(string $ip): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return 'unknown';
        }

        if (strlen($packed) === 4) {
            $masked = $packed & "\xff\xff\xff\x00";
            return inet_ntop($masked) ?: 'unknown';
        }

        if (strlen($packed) === 16) {
            $masked = $packed & "\xff\xff\xff\xff\xff\xff\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
            return inet_ntop($masked) ?: 'unknown';
        }

        return 'unknown';
    }
}
