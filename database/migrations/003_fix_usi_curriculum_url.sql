-- =========================================================
-- Migrazione 003: Pulizia URL Markdown nel curriculum USI (id=38)
-- =========================================================

UPDATE `course_curriculums`
SET `api_config` = '{"url": "https://search.usi.ch/it/offerte-formative/100/bachelor-of-arts-in-scienze-economiche/piano-orari/61/3"}'
WHERE `id` = 38;
