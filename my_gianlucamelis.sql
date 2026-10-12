-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Ott 09, 2026 alle 09:40
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
(8, 2, 'Giurisprudenza', NULL),
(9, 1, 'Medicina', NULL),
(10, 1, 'Fisioterapia', NULL),
(11, 2, 'Scienze Economiche', NULL),
(12, 3, 'Bachelor of Arts in Scienze Economiche', NULL);

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
(28, 8, 'Milano', 5, '{\"anno\": \"2026\", \"anno2\": \"A41-0|5\", \"corso\": \"A41\"}'),
(29, 9, 'Varese', 1, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"687f8eb2182d4e001eac914e\"}'),
(30, 9, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"6ab3a34ce560910040d3d62f\"}'),
(31, 9, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86d7afa1539c0017ef2182\"}'),
(32, 9, 'Varese', 4, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86d7f225d48c0018ceb3b4\"}'),
(33, 9, 'Varese', 5, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86d82ba1539c0017ef2192\"}'),
(34, 9, 'Varese', 6, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86d874c8afae00199c98b3\"}'),
(35, 10, 'Varese', 1, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e045a1539c0017ef24c9\"}'),
(36, 10, 'Varese', 2, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e06fc36be80017330556\"}'),
(37, 10, 'Varese', 3, '{\"clienteId\": \"59f05192a635f443422fe8fd\", \"linkCalendarioId\": \"5f86e09bab04970018014f8e\"}'),
(38, 11, 'Lugano', 3, '{\"url\": \"[https://search.usi.ch/it/offerte-formative/100/bachelor-of-arts-in-scienze-economiche/piano-orari/61/3](https://search.usi.ch/it/offerte-formative/100/bachelor-of-arts-in-scienze-economiche/piano-orari/61/3)\"}'),
(39, 12, 'Lugano', 3, '{\"url\": \"https://search.usi.ch/it/offerte-formative/100/bachelor-of-arts-in-scienze-economiche/piano-orari/61/3\"}');

-- --------------------------------------------------------

--
-- Struttura della tabella `group_members`
--

CREATE TABLE `group_members` (
  `group_id` int NOT NULL,
  `user_id` int NOT NULL,
  `privacy_level` enum('transparent','logistical','opaque') DEFAULT 'logistical',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `group_members`
--

INSERT INTO `group_members` (`group_id`, `user_id`, `privacy_level`, `joined_at`) VALUES
(9, 31, 'logistical', '2026-10-09 07:38:34'),
(9, 35, 'logistical', '2026-10-09 07:38:24');

-- --------------------------------------------------------

--
-- Struttura della tabella `hidden_courses`
--

CREATE TABLE `hidden_courses` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `hidden_courses`
--

INSERT INTO `hidden_courses` (`id`, `user_id`, `course_name`) VALUES
(6, 21, 'AUTOMI E LINGUAGGI'),
(7, 21, 'FONDAMENTI DI SICUREZZA'),
(8, 21, 'RETI DI TELECOMUNICAZIONE'),
(9, 21, 'MICROCONTROLLORI'),
(10, 21, 'SISTEMI INFORMATIVI'),
(15, 37, 'ADVANCED SKILLS IN ENGLISH');

-- --------------------------------------------------------

--
-- Struttura della tabella `markers`
--

CREATE TABLE `markers` (
  `id` char(36) NOT NULL,
  `location_name` varchar(160) NOT NULL,
  `country` varchar(80) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(9,6) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `thumb_url` varchar(255) DEFAULT NULL,
  `original_file` varchar(255) DEFAULT NULL,
  `original_mime` varchar(30) DEFAULT NULL,
  `original_sha256` char(64) DEFAULT NULL,
  `contributor_name` varchar(80) NOT NULL,
  `message` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `consent_at` datetime DEFAULT NULL,
  `ip_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `markers`
--

INSERT INTO `markers` (`id`, `location_name`, `country`, `country_code`, `latitude`, `longitude`, `image_url`, `thumb_url`, `original_file`, `original_mime`, `original_sha256`, `contributor_name`, `message`, `status`, `consent_at`, `ip_hash`, `created_at`) VALUES
('6eb6b2ff-c0fe-4727-a4f1-a33c7b9f5f41', 'Aprica, provincia di Sondrio, Italia', 'Italia', 'IT', '46.165867', '10.148139', '/Simone/uploads/af0e309847e275994b868400388c8b27.jpg', '/Simone/uploads/af0e309847e275994b868400388c8b27_t.jpg', 'af0e309847e275994b868400388c8b27.jpg', 'image/jpeg', '60b53de8335fbdde8d93680acdf752be4feddef4ffaddf620eb193578250894c', 'Giangi', 'Aprica con gli zii! guarda che bel paesaggio infinito...', 'approved', '2026-10-07 11:12:28', 'd27db88c4d7b7b847eb9793b4fcd079351d540ae6e05f543fb6b2ae722908c1a', '2026-10-07 09:12:28'),
('af0bab11-dbbf-4e6f-9894-aa0fdaaab2cd', 'Castelleone, provincia di Cremona, Italia', 'Italia', 'IT', '45.269744', '9.791009', '/Simone/uploads/739035e7c578edab9cee7107ad47684d.jpg', '/Simone/uploads/739035e7c578edab9cee7107ad47684d_t.jpg', '739035e7c578edab9cee7107ad47684d.jpg', 'image/jpeg', '710c46648d681cee3cd44a1bde48577ab139a8dc448859dd273f15745db6fa65', 'Prova', 'test', 'pending', '2026-10-08 10:59:19', '5adb9cba2117dccab4dd6776234a0f2b96d29c29a62cf80fd55b4177df6f93b2', '2026-10-08 08:59:19');

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

--
-- Dump dei dati per la tabella `onboarding_requests`
--

INSERT INTO `onboarding_requests` (`id`, `user_id`, `request_type`, `request_data`, `status`, `created_at`) VALUES
(4, 29, 'new_course', 'Corso richiesto: fisioterapia | Università: Università dell&#039;Insubria', 'pending', '2026-10-02 21:31:15');

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
-- Struttura della tabella `study_groups`
--

CREATE TABLE `study_groups` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dump dei dati per la tabella `study_groups`
--

INSERT INTO `study_groups` (`id`, `name`, `invite_code`, `created_by`, `created_at`) VALUES
(9, 'CC', 'CC99057F', 35, '2026-10-09 07:38:24');

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
(2, 'Università degli Studi di Milano', 'unimi.it', 'App\\Adapters\\StataleAdapter', 1, '/assets/img/logo-statale.png'),
(3, 'Università della Svizzera italiana (USI)', 'usi.ch', 'App\\Adapters\\UsiAdapter', 1, '/assets/img/logo-usi.png');

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
(18, 'giancarnevali@gmail.com', '$2y$12$KQwALIxNGyWxPjtlg6euUeOUxO4pi4izxqGwlCAniVtu.IHJ2yREG', 'Giandomenico', 'Carnevali', 'student', '2026-09-24 11:57:03', '2026-09-24 11:57:03'),
(21, 'ariel@pazza.it', '$2y$12$fCjXL461TJ6r4nORThthO.0HrLjrrdR6z/7T3EoJxCJKd9bG5LFBe', 'Martina', 'Pignataro', 'student', '2026-09-24 15:08:11', '2026-09-24 15:08:11'),
(22, 'comerivero@gmail.com', '$2y$12$E7lIxg5flakPaKidDn.qxuX.cRF0gtWcuLYB6zOxYIduMXzsExq1G', 'Veronica', 'Comerio', 'student', '2026-09-24 16:08:41', '2026-09-24 16:08:41'),
(23, 'carlottaterragni@gmail.com', '$2y$12$Q3TorE0nEjs59hIiKfd/BuTjVhLdBnvQ5NJ1JoutnI9aarA/WoGlq', 'Carlotta', 'Terragni', 'student', '2026-09-24 19:28:01', '2026-09-24 19:28:01'),
(28, 'gio.caon@gmail.com', '$2y$12$ETKMeGvgQWKgiU5eb2k9Ee0HQyC0yB3Nz29CxoXKFHi7IqBPl37E.', 'Giorgia', 'Caon', 'student', '2026-10-02 21:30:58', '2026-10-02 21:30:58'),
(29, 'samuele.fontana05@gmail.com', '$2y$12$W42mYGnjVjLU/xxy.NEn9.yPiCfcjyoWAwX8y.mM9xI5.6L6K8Neu', 'samuele', 'fontana', 'student', '2026-10-02 21:31:00', '2026-10-02 21:31:00'),
(30, 'asfranzetti@studenti.uninsubria.it', '$2y$12$Jgs7HDLW5u.ZgRmtrKLqf.2CpIoWYxeoFlK.sbGExOx8yFcUZJa5W', 'Andrea', 'Franzetti', 'student', '2026-10-02 21:35:14', '2026-10-02 21:35:14'),
(31, 'gianluca.melis05@gmail.com', '$2y$12$.nIYuuMzTtwfdlCBo/2wEOyh65ZBeRdw/q62Y8rO5AIHdd5U3/cMS', 'Gianluca', 'Melis', 'student', '2026-10-05 07:21:54', '2026-10-05 07:21:54'),
(32, 'ereali@studenti.uninsubria.it', '$2y$12$PQJ/tJ7WBoqdrz61bMQgNOXfZsQlv4jhVg8xnDVJV/zYXFr8u0SYa', 'Emma', 'Reali', 'student', '2026-10-05 08:40:46', '2026-10-05 08:40:46'),
(33, 'glarizza@studenti.uninsubria.it', '$2y$12$QfIymqD/FCAG2/x5bVCLFe6dgCtmQB1zD4a/zb.8Q58YBYX8AtTa6', 'Gaia', 'Larizza', 'student', '2026-10-05 08:42:24', '2026-10-05 08:42:24'),
(34, 'averonelli5@studenti.uninsubria.it', '$2y$12$zLZB4lMDDHDxWkQ/AdgWJe1INQz1CuyOI0TA59AtZa2DND5kfEOti', 'Arianna', 'Veronelli', 'student', '2026-10-05 12:43:21', '2026-10-05 12:43:21'),
(35, 'melis.gianlucagm@gmail.com', '$2y$12$1ZWQ3PggGD.RdDsfsoK2H.HVhqtVVvYL/bc8APv8NxsEWfbEBCIpS', 'Gianluca', 'Melis', 'student', '2026-10-05 12:44:32', '2026-10-05 12:44:32'),
(36, 'slepori@studenti.uninsubia.it', '$2y$12$R0kMLWug5jySHPEbeo351uRzqdDB0cbevdbiNdFmixKaj7I9D7TAK', 'Sofia', 'Lepori', 'student', '2026-10-05 12:45:10', '2026-10-05 12:45:10'),
(37, 'tinelj@usi.ch', '$2y$12$u5S97rXx5y0g69DkK4gp2euSNMOq3xppjMwtZDG.GdnTi/5dX32nG', 'Jacopo', 'Tinelli', 'student', '2026-10-05 13:06:54', '2026-10-05 13:06:54'),
(38, 'egiorgetti@studenti.uninsubria.it', '$2y$12$a31Zm2eiaQsHFdFvtppxTOKE/skgAxDI0Y1oIWH.u0ce72nRe3sn6', 'Emanuele', 'Giorgetti', 'student', '2026-10-05 20:16:52', '2026-10-05 20:16:52');

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
(27, 21, 4, 2026, 1, 11),
(29, 22, 7, 2026, 1, 22),
(31, 23, 8, 2026, 1, 25),
(40, 28, 6, 2026, 1, 16),
(41, 30, 6, 2026, 1, 16),
(43, 29, 10, 2026, 1, 37),
(46, 33, 6, 2026, 1, 16),
(47, 32, 6, 2026, 1, 16),
(48, 34, 5, 2026, 1, 12),
(50, 36, 5, 2026, 1, 12),
(51, 37, 12, 2026, 1, 39),
(52, 38, 10, 2026, 1, 37),
(54, 31, 3, 2026, 1, 5),
(56, 35, 7, 2026, 1, 22);

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
(62, 29, 'dark', NULL);

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
(17, 21, 'f3d718bdcaa14d4cdfd55005eba100f0639fd798f52d23be3af257e3db407105', '2027-09-25 08:44:06', '2026-09-25 06:44:06'),
(25, 28, 'ca5a99d68f87283c4cdecfdf41178d8113672a5a06d3e4c6d2cfe6538760efcf', '2027-10-02 23:34:04', '2026-10-02 21:34:04'),
(26, 31, '4bd19662c89355f6aa336afb4e3503dfb64c832e954acc01bd0ac479d28b1ffd', '2027-10-05 11:43:52', '2026-10-05 09:43:52'),
(27, 33, '965cc5332439021035f9927e9698000a75c81c00e777034234aad81a3469dcdf', '2027-10-05 18:05:10', '2026-10-05 16:05:10'),
(28, 36, 'd840c45870f8f292aca3332430cb54edfff23e4c642317fea1816b28f889b30d', '2027-10-06 11:14:34', '2026-10-06 09:14:34'),
(29, 36, '74e2c6dbb087bf223bf55476a978806fa721bb9f20392cdd8e5ba0de81910e42', '2027-10-06 11:14:35', '2026-10-06 09:14:35'),
(30, 31, '10a426e83a8068a8aaf9edea880eccf91be3fddc6aab035460f39ee9b052cb0f', '2027-10-06 22:08:11', '2026-10-06 20:08:11'),
(31, 35, '94e3f66dd332f16b827928d7dc9cf68c7c73d200ae7562bb8eef52ccd446760d', '2027-10-09 09:37:48', '2026-10-09 07:37:48');

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
-- Indici per le tabelle `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_ip_created` (`ip_hash`,`created_at`);

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
-- Indici per le tabelle `study_groups`
--
ALTER TABLE `study_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `created_by` (`created_by`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `course_curriculums`
--
ALTER TABLE `course_curriculums`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT per la tabella `hidden_courses`
--
ALTER TABLE `hidden_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT per la tabella `onboarding_requests`
--
ALTER TABLE `onboarding_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `personal_events`
--
ALTER TABLE `personal_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `study_groups`
--
ALTER TABLE `study_groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT per la tabella `user_academic_profiles`
--
ALTER TABLE `user_academic_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT per la tabella `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT per la tabella `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
-- Limiti per la tabella `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_member_group` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Limiti per la tabella `study_groups`
--
ALTER TABLE `study_groups`
  ADD CONSTRAINT `fk_group_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
