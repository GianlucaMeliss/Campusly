<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;
use ErrorException;

final class ErrorHandler
{
    /**
     * Registra gli handler personalizzati per errori ed eccezioni.
     */
    public static function register(): void
    {
        // 1. Converte i normali errori PHP (es. warning, notice) in Eccezioni
        set_error_handler(function (int $level, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $level)) {
                return false; // Ignora gli errori soppressi con @
            }
            throw new ErrorException($message, 0, $level, $file, $line);
        });

        // 2. Cattura tutte le eccezioni non gestite (inclusi i vecchi errori fatali)
        set_exception_handler([self::class, 'handleException']);
    }

    /**
     * Gestisce l'eccezione, logga l'errore e restituisce la risposta corretta.
     */
    public static function handleException(Throwable $exception): void
    {
        // Imposta il codice HTTP a 500 (Internal Server Error)
        http_response_code(500);

        // Prepara il messaggio di log (dettagliato per il programmatore)
        $logMessage = sprintf(
            "[%s] Exception: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Scrive l'errore nel file log
        $logDir = BASEPATH . '/app/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        error_log($logMessage, 3, $logDir . '/error.log');

        // Capisce se la richiesta è per un'API o per una pagina Web
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false 
                 || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';

        if ($isApi) {
            // Risposta formattata per chiamate API (come il cookie log o futuri moduli JS)
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Errore interno del server. I nostri tecnici sono stati avvisati.'
            ]);
            exit;
        }

        // Risposta per la normale navigazione Web
        try {
            // Usa il tuo sistema View per mostrare la pagina 500
            View::render('pages/500', [
                'pageTitle' => '500 - Errore Interno',
                'pageDescription' => 'Si è verificato un errore tecnico.'
            ]);
        } catch (Throwable $e) {
            // Fallback di emergenza: se si rompe persino il sistema di View!
            echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
            echo "<h1>500 - Errore di Sistema</h1>";
            echo "<p>Si è verificato un problema critico. Riprova più tardi.</p>";
            echo "</div>";
        }
        
        exit;
    }
}