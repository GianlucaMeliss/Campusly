<?php
declare(strict_types=1);

namespace App\Core;

use PDOException;

final class RateLimiter extends Model
{
    private static bool $tableEnsured = false;

    private function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                bucket VARCHAR(100) NOT NULL,
                identifier VARCHAR(191) NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_lookup (bucket, identifier, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        self::$tableEnsured = true;
    }

    public function tooManyAttempts(string $bucket, string $id, int $max, int $windowSeconds): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM rate_limits 
                WHERE bucket = :bucket 
                  AND identifier = :id 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :win SECOND)
            ");
            $stmt->execute([
                'bucket' => $bucket,
                'id'     => $id,
                'win'    => $windowSeconds,
            ]);
            return (int)$stmt->fetchColumn() >= $max;
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                $this->ensureTable();
                return false;
            }
            throw $e;
        }
    }

    public function hit(string $bucket, string $id): void
    {
        if ( random_int(1, 50) === 1 ) {
            $this->purgeOld();
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (bucket, identifier, created_at) 
                VALUES (:bucket, :id, NOW())
            ");
            $stmt->execute([
                'bucket' => $bucket,
                'id'     => $id,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                $this->ensureTable();
                $stmt = $this->db->prepare("
                    INSERT INTO rate_limits (bucket, identifier, created_at) 
                    VALUES (:bucket, :id, NOW())
                ");
                $stmt->execute(['bucket' => $bucket, 'id' => $id]);
                return;
            }
            throw $e;
        }
    }

    public function clear(string $bucket, string $id): void
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM rate_limits 
                WHERE bucket = :bucket AND identifier = :id
            ");
            $stmt->execute([
                'bucket' => $bucket,
                'id'     => $id,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
        }
    }

    private function purgeOld(): void
    {
        try {
            $this->db->exec("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        } catch (PDOException) {
            // Ignora se la tabella non è ancora presente
        }
    }
}
