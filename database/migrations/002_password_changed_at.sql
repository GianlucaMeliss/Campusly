-- =========================================================
-- Migrazione 002: Colonna password_changed_at su users
-- =========================================================

ALTER TABLE `users`
    ADD COLUMN `password_changed_at` DATETIME NULL DEFAULT NULL AFTER `password_hash`;
