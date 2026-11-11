<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

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
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM rate_limits 
                WHERE bucket = :bucket 
                  AND identifier = :id 
                  AND created_at >= :since
            ");
            $stmt->execute([
                'bucket' => $bucket,
                'id'     => $id,
                'since'  => $since,
            ]);
            return (int)$stmt->fetchColumn() >= $max;
        } catch (Throwable $e) {
            try {
                $this->ensureTable();
            } catch (Throwable) {
            }
            return false;
        }
    }

    public function hit(string $bucket, string $id): void
    {
        if (random_int(1, 50) === 1) {
            $this->purgeOld();
        }

        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rate_limits (bucket, identifier, created_at) 
                VALUES (:bucket, :id, :now)
            ");
            $stmt->execute([
                'bucket' => $bucket,
                'id'     => $id,
                'now'    => $now,
            ]);
        } catch (Throwable) {
            try {
                $this->ensureTable();
                $stmt = $this->db->prepare("
                    INSERT INTO rate_limits (bucket, identifier, created_at) 
                    VALUES (:bucket, :id, :now)
                ");
                $stmt->execute(['bucket' => $bucket, 'id' => $id, 'now' => $now]);
            } catch (Throwable) {
            }
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
        } catch (Throwable) {
        }
    }

    private function purgeOld(): void
    {
        try {
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE created_at < :yesterday");
            $stmt->execute(['yesterday' => $yesterday]);
        } catch (Throwable) {
        }
    }
}
