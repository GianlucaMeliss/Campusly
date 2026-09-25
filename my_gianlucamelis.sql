-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Set 25, 2026 alle 09:49
-- Versione del server: 8.0.45
-- Versione PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_gianlucamelis`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `university_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `courses`
--

INSERT INTO `courses` (`id`, `university_id`, `name`, `department`) VALUES
(3, 1, 'Informatica', NULL),
(4, 2, 'Economia', NULL),
(5, 1, 'Scienze Biologiche', NULL),
(6, 1, 'Ostetricia', NULL),
(7, 1, 'Scienze della Comunicazione', NULL),
(8, 2, 'Giurisprudenza', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `course_curriculums`
--

CREATE TABLE `course_curriculums` (
  `id` int NOT NULL,
  `course_id` int NOT NULL,
  `campus_location` varchar(100) DEFAULT NULL,
  `year` int NOT NULL,
  `api_config` json NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `course_curriculums`
--

INSERT INTO `course_curriculums` (`id`, `course_id`, `campus_location`, `year`, `api_config`) VALUES
(5, 3, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a71a9ef1d1e660014fb0aef\"}'),
(7, 3, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a71a8c57a011e0019803ed6\"}'),
(11, 4, 'Milano', 2, '{\"anno\": \"2026\", \"anno2\": \"BAE-0|2\", \"corso\": \"BAE\"}'),
(12, 5, 'Varese', 1, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a69b9d9c4a67700195312e8\"}'),
(13, 5, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a69ba368d967b0014f8891f\"}'),
(14, 5, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a69ba7c0a7c5e00284a8f63\"}'),
(15, 6, 'Varese', 1, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e3bd11341d0017d011c7\"}'),
(16, 6, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e3e111341d0017d011ca\"}'),
(17, 6, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e40cc36be80017330618\"}'),
(18, 3, 'Varese', 1, '{\"clienteId\": \"6a71a88e0b02d70019eb3983\", \"linkCalendarioId\": \"6a71a8c57a011e0019803ed6\"}'),
(19, 3, 'Como', 2, '{\"clienteId\": \"6a71a92111ef0b00197b3421\", \"linkCalendarioId\": \"6a71a8c57a011e0019803ed6\"}'),
(20, 3, 'Como', 3, '{\"clienteId\": \"6a71aa3f7a011e0019803fbc\", \"linkCalendarioId\": \"6a71a8c57a011e0019803ed6\"}'),
(21, 7, 'Varese', 1, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a6b3acf71fed90019b7dc64\"}'),
(22, 7, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a6b3be36cfa410014c152d2\"}'),
(23, 7, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6a6b3c4a962c4000193a67b0\"}'),
(24, 8, 'Milano', 1, '{\"anno\": \"2026\", \"anno2\": \"ACA-0|1\", \"corso\": \"ACA\"}'),
(25, 8, 'Milano', 2, '{\"anno\": \"2026\", \"anno2\": \"ACA-0|2\", \"corso\": \"ACA\"}'),
(26, 8, 'Milano', 3, '{\"anno\": \"2026\", \"anno2\": \"A41-0|3\", \"corso\": \"A41\"}'),
(27, 8, 'Milano', 4, '{\"anno\": \"2026\", \"anno2\": \"A41-0|4\", \"corso\": \"A41\"}'),
(28, 8, 'Milano', 5, '{\"anno\": \"2026\", \"anno2\": \"A41-0|5\", \"corso\": \"A41\"}');

-- --------------------------------------------------------

--
-- Struttura della tabella `hidden_courses`
--

CREATE TABLE `hidden_courses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `markers`
--

CREATE TABLE `markers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contributor_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `markers`
--

INSERT INTO `markers` (`id`, `location_name`, `latitude`, `longitude`, `image_url`, `contributor_name`, `message`, `status`, `created_at`) VALUES
('794c683d-404e-4011-9d2c-c464838e1f5c', 'San Francisco, California, Stati Uniti d’America', '37.77923800', '-122.41935900', '/Simone/uploads/simone_6aa1172761fe51.31383517.jpeg', 'Maso', 'Siamo andati in America Simo!', 'approved', '2026-09-09 08:21:59'),
('986e6c49-777b-4de2-baa4-fcec338f2bf5', 'Bologna, città metropolitana di Bologna, Italia', '44.49573500', '11.34467200', '/Simone/uploads/simone_6aa11ec1e4a598.41649263.jpeg', 'Carlotta', 'La mia città Studi!', 'approved', '2026-09-09 08:54:25'),
('ca2005ab-9f52-4cb1-b44c-4439b3d47e45', 'Sormano, provincia di Como, Italia', '45.88955738', '9.24364251', '/Simone/uploads/simone_6aa11fe8d9c380.85806154.jpg', 'Sara', 'Il nostro giro in moto!', 'approved', '2026-09-09 08:59:20');

-- --------------------------------------------------------

--
-- Struttura della tabella `onboarding_requests`
--

CREATE TABLE `onboarding_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `request_type` enum('new_university','new_course') NOT NULL,
  `request_data` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `personal_events`
--

CREATE TABLE `personal_events` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `universities`
--

CREATE TABLE `universities` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `adapter_class` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `universities`
--

INSERT INTO `universities` (`id`, `name`, `domain`, `adapter_class`, `is_active`, `logo_path`) VALUES
(1, 'Università dell\'Insubria', NULL, '\\App\\Adapters\\CinecaAdapter', 1, '/assets/img/logo-insubria.png'),
(2, 'Università degli Studi di Milano', 'unimi.it', 'App\\Adapters\\StataleAdapter', 1, '/assets/img/logo-statale.png');

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_at`, `updated_at`) VALUES
(17, 'gianluca.melis05@gmail.com', '$2y$12$xRkzgFS7W4LikVnlHVchBuF7.H1jOZ3asdlkKL9/v1daD2L2xldJa', 'Gianluca', 'Melis', 'student', '2026-09-24 10:04:48', '2026-09-24 10:04:48'),
(18, 'giancarnevali@gmail.com', '$2y$12$KQwALIxNGyWxPjtlg6euUeOUxO4pi4izxqGwlCAniVtu.IHJ2yREG', 'Giandomenico', 'Carnevali', 'student', '2026-09-24 11:57:03', '2026-09-24 11:57:03'),
(19, 'melis.gianlucagm@gmail.com', '$2y$12$rHMjnHqcGZiNlTUhja0baOuNtPpP9PDvK6mDuBD2ZJdmU4sTlvcDC', 'Miriam', 'Carnevali', 'student', '2026-09-24 14:48:09', '2026-09-24 14:48:09'),
(20, 'g@g.com', '$2y$12$a1izwfGQF6X6j8yt9K6HdOvxjwlTBLePeqaDiw6WkQoEgKTYfYpWK', 'pippo', 'pippo', 'student', '2026-09-24 15:04:55', '2026-09-24 15:04:55'),
(21, 'ariel@pazza.it', '$2y$12$fCjXL461TJ6r4nORThthO.0HrLjrrdR6z/7T3EoJxCJKd9bG5LFBe', 'Martina', 'Pignataro', 'student', '2026-09-24 15:08:11', '2026-09-24 15:08:11'),
(22, 'comerivero@gmail.com', '$2y$12$E7lIxg5flakPaKidDn.qxuX.cRF0gtWcuLYB6zOxYIduMXzsExq1G', 'Veronica', 'Comerio', 'student', '2026-09-24 16:08:41', '2026-09-24 16:08:41'),
(23, 'carlottaterragni@gmail.com', '$2y$12$Q3TorE0nEjs59hIiKfd/BuTjVhLdBnvQ5NJ1JoutnI9aarA/WoGlq', 'Carlotta', 'Terragni', 'student', '2026-09-24 19:28:01', '2026-09-24 19:28:01');

-- --------------------------------------------------------

--
-- Struttura della tabella `user_academic_profiles`
--

CREATE TABLE `user_academic_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `enrollment_year` year NOT NULL,
  `is_primary` tinyint(1) DEFAULT '1',
  `curriculum_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `user_academic_profiles`
--

INSERT INTO `user_academic_profiles` (`id`, `user_id`, `course_id`, `enrollment_year`, `is_primary`, `curriculum_id`) VALUES
(21, 18, 3, 2026, 1, 5),
(24, 19, 7, 2026, 1, 23),
(26, 20, 6, 2026, 1, 16),
(27, 21, 4, 2026, 1, 11),
(29, 22, 7, 2026, 1, 22),
(31, 23, 8, 2026, 1, 25),
(32, 17, 3, 2026, 1, 5);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `theme` enum('light','dark','system') DEFAULT 'system',
  `custom_colors` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `theme`, `custom_colors`) VALUES
(44, 17, 'light', NULL),
(46, 19, 'light', NULL),
(50, 20, 'dark', NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `user_tokens`
--

INSERT INTO `user_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `created_at`) VALUES
(9, 17, 'f137363cc6cb2af7e36d2b7dd76875711b1a42edbbf26ac86107972b79162b32', '2027-09-24 12:06:23', '2026-09-24 10:06:23'),
(10, 17, 'c51f866c98da8561857df76a1a313f49d46d9f49abeee09f3c4b3ec62b0431f2', '2027-09-24 12:08:56', '2026-09-24 10:08:56'),
(11, 17, '7ec481609154f969ac791fdf87b6a6fef1a2ce18c693de1ec0f4dfd6e402ae10', '2027-09-24 13:19:14', '2026-09-24 11:19:14'),
(12, 17, '848997b06b6b7dc293cc68cc5977863337ba18230cbf9f47c5345c67489694fe', '2027-09-24 15:32:07', '2026-09-24 13:32:07'),
(13, 17, 'b93ddd2142f69c2aa0228c286519844a0a8a7fe908c4bbc5ff475b1157b3e8a6', '2027-09-24 17:31:09', '2026-09-24 15:31:09'),
(14, 17, '218238d01396d81dbce54d768422d751047faa37c755836b72538a3b399ab3cd', '2027-09-24 18:56:23', '2026-09-24 16:56:23'),
(15, 17, '2ebf49e27828a96525a6d129ef1cdabd0d15164bd0af4fc09810c67b1cce97fd', '2027-09-24 19:13:12', '2026-09-24 17:13:12'),
(16, 17, 'efe7a8978eca1f663e2cbc4451e1d187dce38f7ffa113b7a79b71834b32e1b2d', '2027-09-24 22:18:33', '2026-09-24 20:18:33'),
(17, 21, 'f3d718bdcaa14d4cdfd55005eba100f0639fd798f52d23be3af257e3db407105', '2027-09-25 08:44:06', '2026-09-25 06:44:06'),
(18, 17, '20f3fd9ad0780c873a087d33d558a620ab498c3ea969d2b441006fd1f0c762ed', '2027-09-25 09:29:29', '2026-09-25 07:29:29');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indici per le tabelle `course_curriculums`
--
ALTER TABLE `course_curriculums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indici per le tabelle `hidden_courses`
--
ALTER TABLE `hidden_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indici per le tabelle `markers`
--
ALTER TABLE `markers`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `onboarding_requests`
--
ALTER TABLE `onboarding_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indici per le tabelle `personal_events`
--
ALTER TABLE `personal_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indici per le tabelle `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `user_academic_profiles`
--
ALTER TABLE `user_academic_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indici per le tabelle `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indici per le tabelle `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `course_curriculums`
--
ALTER TABLE `course_curriculums`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT per la tabella `hidden_courses`
--
ALTER TABLE `hidden_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `onboarding_requests`
--
ALTER TABLE `onboarding_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `personal_events`
--
ALTER TABLE `personal_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `user_academic_profiles`
--
ALTER TABLE `user_academic_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT per la tabella `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT per la tabella `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `course_curriculums`
--
ALTER TABLE `course_curriculums`
  ADD CONSTRAINT `course_curriculums_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `hidden_courses`
--
ALTER TABLE `hidden_courses`
  ADD CONSTRAINT `hidden_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `onboarding_requests`
--
ALTER TABLE `onboarding_requests`
  ADD CONSTRAINT `onboarding_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `personal_events`
--
ALTER TABLE `personal_events`
  ADD CONSTRAINT `personal_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_academic_profiles`
--
ALTER TABLE `user_academic_profiles`
  ADD CONSTRAINT `user_academic_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_academic_profiles_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
