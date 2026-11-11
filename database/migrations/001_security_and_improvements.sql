-- =========================================================
-- Migrazione 001: Rate limits, Password resets e Indici mancanti
-- =========================================================

-- 1. Creazione tabelle di sicurezza
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bucket` VARCHAR(100) NOT NULL,
    `identifier` VARCHAR(191) NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX idx_lookup (`bucket`, `identifier`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    UNIQUE KEY uq_token (`token_hash`),
    INDEX idx_user (`user_id`),
    CONSTRAINT fk_pw_reset_user FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2. Ottimizzazione indici e vincoli su tabelle già esistenti
-- Aggiunge il blocco anti-duplicati per i corsi nascosti
ALTER TABLE `hidden_courses`
    ADD UNIQUE KEY `uq_hidden_user_course` (`user_id`, `course_name`);

-- Aggiunge chiave esterna e blocco anti-duplicati sui profili accademici
ALTER TABLE `user_academic_profiles`
    ADD UNIQUE KEY `uq_user_curriculum` (`user_id`, `curriculum_id`),
    ADD KEY `idx_curriculum_id` (`curriculum_id`),
    ADD CONSTRAINT `fk_uap_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `course_curriculums`(`id`) ON DELETE CASCADE;

-- Velocizza il caricamento del calendario per gli eventi personali
ALTER TABLE `personal_events`
    ADD INDEX `idx_personal_user_start` (`user_id`, `start_time`);
