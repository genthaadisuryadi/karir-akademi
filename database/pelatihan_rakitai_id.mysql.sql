-- ============================================================
-- Dump MySQL untuk pelatihan.rakitai.id (dikonversi dari SQLite)
-- Kompatibel MySQL 5.7+ / 8.x dan MariaDB 10.3+
-- Cara pakai:  mysql -u root -p < pelatihan_rakitai_id.mysql.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `pelatihan_rakitai_id`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pelatihan_rakitai_id`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `registrations`, `programs`, `failed_jobs`, `job_batches`, `jobs`,
  `cache_locks`, `cache`, `sessions`, `password_reset_tokens`, `users`, `migrations`;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_title` varchar(255) NOT NULL,
  `audience` varchar(255) NOT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `price_label` varchar(255) NOT NULL DEFAULT 'GRATIS',
  `jp` smallint unsigned NOT NULL DEFAULT '0',
  `date_label` varchar(255) NOT NULL,
  `time_label` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text,
  `instructor` varchar(255) DEFAULT NULL,
  `whatsapp_group_link` varchar(255) DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `min_participants` smallint unsigned DEFAULT NULL,
  `quota` int unsigned DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint unsigned NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_level` varchar(255) DEFAULT NULL,
  `city` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `student_count` smallint unsigned DEFAULT NULL,
  `preferred_schedule` varchar(255) DEFAULT NULL,
  `motivation` text,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registrations_code_unique` (`code`),
  UNIQUE KEY `registrations_program_id_email_unique` (`program_id`,`email`),
  CONSTRAINT `registrations_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data: migrations
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_13_000001_create_programs_table', 1),
(5, '2026_10_13_000002_create_registrations_table', 1),
(6, '2026_10_13_000003_add_role_to_users_table', 1),
(7, '2026_10_13_000004_add_description_and_whatsapp_to_programs_table', 1),
(8, '2026_10_13_000005_add_instructor_to_programs_table', 1);

-- Data: users
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `is_active`) VALUES
(1, 'Administrator', 'admin@karirakademi.id', NULL, '$2y$12$YYJkP0rpWWtkmORyAyfd5OjV/NejQkiwDMeTg.hmAqU7cJHtfUOHm', 'GhExJJaSo8Hvhy2oVoQ9orEqc9XVRA3SaoC79r88Ta9Tnz5361BetfRZJs9e', '2026-10-13 05:08:43', '2026-10-13 05:08:43', 'admin', 1);

-- Data: programs
INSERT INTO `programs` (`id`, `slug`, `title`, `short_title`, `audience`, `poster`, `price_label`, `jp`, `date_label`, `time_label`, `location`, `meta`, `min_participants`, `quota`, `is_featured`, `is_active`, `sort_order`, `created_at`, `updated_at`, `description`, `whatsapp_group_link`) VALUES
(1, 'ai-iot-robotik-coding-sma-smk', 'Pelatihan AI IoT Robotik Coding SMA/SMK', 'Pelatihan AI IoT Robotik Coding SMA/SMK', 'sekolah', 'images/poster-siswa.jpg', 'GRATIS', 3, 'Menyesuaikan Jadwal Sekolah', NULL, 'Kampus 2 UCIC - Lab AI IoT, Gedung CIC HUB', '[{"icon":"calendar","text":"Menyesuaikan Jadwal Sekolah"},{"icon":"people","text":"Minimal 1 Siswa Per Sekolah"}]', 1, NULL, 0, 1, 1, '2026-10-13 05:08:43', '2026-10-13 05:17:54', 'Transformasi kemampuan siswa menjadi inovator teknologi di bidang AI, IoT, Robotik, dan Coding.\r\n\r\nBenefit:\r\n- Sertifikat pelatihan 6 JP\r\n- Silabus untuk 1 tahun ajaran bagi sekolah\r\n- Target akhir: peserta mampu membuat proyek IoT dan robotik sendiri\r\n\r\nDurasi 2 jam dengan akses materi online. Pelaksanaan menyesuaikan jadwal sekolah (minimal 16 siswa per sekolah).', NULL),
(2, 'ai-iot-robotik-coding-guru-batch-1', 'Pelatihan AI IoT Robotik Coding Bagi Guru Batch #1', 'Pelatihan AI IoT Robotik Coding Bagi Guru Batch #1', 'guru', 'images/poster-guru-online.jpg', 'GRATIS', 12, '10 - 12 Agustus 2026', '19.00 - 21.00 WIB', 'Online (Live)', '[{"icon":"calendar","text":"10 - 12 Agustus 2026"},{"icon":"clock","text":"19.00 - 21.00 WIB"}]', NULL, NULL, 1, 1, 2, '2026-10-13 05:08:43', '2026-10-13 05:08:43', 'Transformasi peran guru menjadi inovator teknologi dengan panduan merintis, mengelola, dan mengajar program ekstrakurikuler AI + IoT + Robotik + Coding.\n\nBenefit:\n- Sertifikat pelatihan 12 JP\n- Ebook untuk pengajaran di sekolah\n- Silabus untuk 1 tahun ajaran bagi sekolah\n- Guru mampu membuat sendiri proyek AI, IoT, dan Robotik', NULL),
(3, 'bootcamp-guru-mengajar-dengan-ai-batch-1', 'Bootcamp Bagi Guru Mengajar Dengan AI Batch #1', 'Bootcamp Bagi Guru Mengajar Dengan AI Batch #1', 'guru', 'images/poster-guru-bootcamp.jpg', 'GRATIS', 6, '20 Juli 2026', '09.00 - 15.00 WIB', 'UCIC Kampus 2 - Lab Agentic AI Center', '[{"icon":"calendar","text":"20 Juli 2026"},{"icon":"clock","text":"09.00 - 15.00 WIB"}]', NULL, NULL, 0, 1, 3, '2026-10-13 05:08:43', '2026-10-13 05:08:43', 'Bootcamp bagi guru SMA/SMK/MA untuk menggunakan tools AI sebagai alat peraga pembelajaran di kelas dan sebagai alat bantu mengajar.\n\nBenefit & tujuan:\n- Sertifikat pelatihan 6 JP\n- Guru mampu memanfaatkan AI sebagai alat bantu mengajar', NULL);

-- Data: registrations
INSERT INTO `registrations` (`id`, `program_id`, `code`, `name`, `email`, `phone`, `school_name`, `school_level`, `city`, `position`, `student_count`, `preferred_schedule`, `motivation`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'RKT-FNXNZR', 'Novvalino Putra Gianto', 'novvalputra@gmail.com', '088222116744', 'SMK negeri 1 kota cirbeon', 'SMK', 'Cirebon', 's1', 1, 'Senin', NULL, 'pending', '2026-10-13 05:11:25', '2026-10-13 05:11:25');

SET FOREIGN_KEY_CHECKS = 1;
