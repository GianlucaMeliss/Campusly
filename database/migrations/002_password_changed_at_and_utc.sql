-- =========================================================
-- Migrazione 002: Colonna password_changed_at (C7) e pulizia URL USI
-- =========================================================

ALTER TABLE `users`
    ADD COLUMN `password_changed_at` DATETIME NULL DEFAULT NULL AFTER `password_hash`;

-- Pulisce l'URL USI nel curriculum id=38 che conteneva formattazione Markdown
UPDATE `course_curriculums`
SET `api_config` = '{"url": "https://search.usi.ch/it/offerte-formative/100/bachelor-of-arts-in-scienze-economiche/piano-orari/61/3"}'
WHERE `id` = 38;
