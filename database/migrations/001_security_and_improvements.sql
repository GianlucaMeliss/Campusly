-- =========================================================
-- Migrazione 001: Rate limits, Password resets e Indici (C5)
-- =========================================================
-- Query di verifica eventuali duplicati prima di eseguire gli ALTER TABLE:
-- SELECT user_id, course_name, COUNT(*) c FROM hidden_courses GROUP BY user_id, course_name HAVING c > 1;
-- SELECT user_id, curriculum_id, COUNT(*) c FROM user_academic_profiles GROUP BY user_id, curriculum_id HAVING c > 1;

CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bucket` VARCHAR(100) NOT NULL,
    `identifier` VARCHAR(191) NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_lookup` (`bucket`, `identifier`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    UNIQUE KEY `uq_token` (`token_hash`),
    INDEX `idx_user` (`user_id`),
    CONSTRAINT `fk_pw_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `hidden_courses`
    ADD UNIQUE KEY `uq_hidden_user_course` (`user_id`, `course_name`);

ALTER TABLE `user_academic_profiles`
    ADD UNIQUE KEY `uq_user_curriculum` (`user_id`, `curriculum_id`),
    ADD KEY `idx_curriculum_id` (`curriculum_id`),
    ADD CONSTRAINT `fk_uap_curriculum` FOREIGN KEY (`curriculum_id`) REFERENCES `course_curriculums`(`id`) ON DELETE CASCADE;

ALTER TABLE `personal_events`
    ADD INDEX `idx_personal_user_start` (`user_id`, `start_time`);
