<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Verifica centralizzata del token CSRF per TUTTE le richieste POST.
 * Il token può arrivare da: header X-CSRF-Token, campo form "csrf_token"
 * oppure campo "csrf_token" nel body JSON.
 */
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

        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['csrf_token']) && is_string($data['csrf_token'])) {
                return $data['csrf_token'];
            }
        }

        return '';
    }

    /**
     * Fetch Metadata: il browser imposta da solo Sec-Fetch-Site e nessun altro sito può falsificarlo.
     * Una chiamata JSON "same-origin" non può arrivare da una pagina di un altro sito, quindi non è CSRF.
     * Serve a non rompere chi ha ancora aperta una pagina caricata PRIMA dell'introduzione del token.
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

        http_response_code(403);

        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
        if ($isApi) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Token CSRF non valido']);
            exit;
        }

        // Form HTML: sessione scaduta o token mancante -> torna a una pagina sicura
        $target = isset($_SESSION['user_id']) ? '/dashboard' : '/login?error=csrf';
        header('Location: ' . BASE_PATH . $target);
        exit;
    }
}
