<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Exception;

final class Database
{
    private static ?PDO $instance = null;

    /**
     * Costruttore privato per impedire l'istanziamento diretto (Pattern Singleton)
     */
    private function __construct() {}

    /**
     * Restituisce l'istanza univoca della connessione PDO
     * * @throws Exception Se il database non è configurato o la connessione fallisce
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require BASEPATH . '/config/app.php';
            $db = $config['db'] ?? [];

            // Se il nome del database è vuoto, lanciamo un'eccezione controllata
            if (empty($db['name'])) {
                throw new Exception("Database non configurato. Verifica i parametri in config/app.php.");
            }

            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
                PDO::ATTR_EMULATE_PREPARES   => false,                  
            ];

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], $options);
            } catch (PDOException $e) {
                //errore tecnico (password errata, host irraggiungibile) nel file di log del server
                error_log("Errore di connessione DB: " . $e->getMessage());
                throw new Exception("Impossibile stabilire una connessione al database. Riprova più tardi.");
            }
        }

        return self::$instance;
    }
}