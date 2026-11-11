<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        return (string)($_SESSION['csrf_token'] ?? '');
    }

    private static function provided(): string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_string($header) && $header !== '') {
            return $header;
        }

        if (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        $data = Http::input();
        if (isset($data['csrf_token']) && is_string($data['csrf_token'])) {
            return $data['csrf_token'];
        }

        return '';
    }

    /**
     * @deprecated Compatibilità temporanea per client con tab aperte pre-CSRF.
     * Da rimuovere dopo il 2026-12-31 quando tutte le sessioni legacy saranno scadute.
     */
    private static function isSameOriginJsonApi(): bool
    {
        $site = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
        $type = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;

        return $isApi && $site === 'same-origin' && strpos($type, 'application/json') === 0;
    }

    public static function verifyOrFail(): void
    {
        $expected = self::token();
        $given = self::provided();

        if ($expected !== '' && $given !== '' && hash_equals($expected, $given)) {
            return;
        }

        if (self::isSameOriginJsonApi()) {
            return;
        }

        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        if ($isApi) {
            Http::json(['error' => 'Token CSRF non valido'], 403);
        }

        http_response_code(403);
        $target = isset($_SESSION['user_id']) ? '/dashboard' : '/login?error=csrf';
        header('Location: ' . BASE_PATH . $target);
        exit;
    }
}
