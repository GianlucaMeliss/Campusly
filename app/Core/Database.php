<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require BASEPATH . '/config/app.php';
            $db = $config['db'] ?? [];

            if (empty($db['name'])) {
                throw new Exception("Database non configurato. Verifica i parametri in config/app.php.");
            }

            $host = $db['host'] ?? 'localhost';
            $name = $db['name'];
            $charset = $db['charset'] ?? 'utf8mb4';
            $user = $db['user'] ?? '';
            $pass = $db['pass'] ?? '';

            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                $logDir = BASEPATH . '/app/logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $detail = sprintf(
                    "[%s] PDOException (host=%s, db=%s, user=%s, pass_len=%d): %s\n",
                    gmdate('Y-m-d H:i:s'),
                    $host,
                    $name,
                    $user,
                    strlen((string)$pass),
                    $e->getMessage()
                );
                @error_log($detail, 3, $logDir . '/error.log');
                throw new Exception("Impossibile stabilire una connessione al database. Riprova più tardi.");
            }
        }

        return self::$instance;
    }
}
