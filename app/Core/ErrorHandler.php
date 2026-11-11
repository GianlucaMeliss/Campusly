<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;
use ErrorException;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(function (int $level, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $level)) {
                return false;
            }
            throw new ErrorException($message, 0, $level, $file, $line);
        });

        set_exception_handler([self::class, 'handleException']);

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handleException(new ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));
            }
        });
    }

    public static function handleException(Throwable $exception): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }

        $logMessage = sprintf(
            "[%s] Exception: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        $logDir = BASEPATH . '/app/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @error_log($logMessage, 3, $logDir . '/error.log');

        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false 
                 || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';

        if ($isApi) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode([
                'status' => 'error',
                'message' => 'Errore interno del server. I nostri tecnici sono stati avvisati.'
            ]);
            exit;
        }

        try {
            View::render('pages/500', [
                'pageTitle' => '500 - Errore Interno',
                'pageDescription' => 'Si è verificato un errore tecnico.'
            ]);
        } catch (Throwable) {
            echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
            echo "<h1>500 - Errore di Sistema</h1>";
            echo "<p>Si è verificato un problema critico. Riprova più tardi.</p>";
            echo "</div>";
        }
        
        exit;
    }
}
