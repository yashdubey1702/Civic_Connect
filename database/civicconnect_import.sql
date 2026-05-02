-- CivicConnect Bhubaneswar - single database import
-- Import this one file in phpMyAdmin/MySQL to create a fresh working database.
-- Default seeded password for starter admin accounts is: password

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `town_issues`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `town_issues`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notification_reads`;
DROP TABLE IF EXISTS `volunteer_task_updates`;
DROP TABLE IF EXISTS `volunteer_tasks`;
DROP TABLE IF EXISTS `volunteer_profiles`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `app_sessions`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('citizen','volunteer','municipal_admin','ward_admin','super_admin') NOT NULL,
  `ward` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_ward_admin` (`ward`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `municipality` varchar(50) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `status` enum('Reported','Acknowledged','In Progress','Resolved') DEFAULT 'Reported',
  `tracking_token` varchar(32) DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_reports_tracking_token` (`tracking_token`),
  KEY `idx_reports_email` (`email`),
  KEY `idx_reports_status` (`status`),
  KEY `idx_reports_category` (`category`),
  KEY `idx_reports_municipality` (`municipality`),
  KEY `idx_reports_location` (`latitude`,`longitude`),
  KEY `idx_reports_created_at` (`created_at`),
  KEY `fk_reports_user` (`user_id`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `volunteer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `ward_no` varchar(50) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `availability` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Suspended') DEFAULT 'Pending',
  `admin_note` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_volunteer_user` (`user_id`),
  KEY `idx_volunteer_profiles_status` (`status`),
  KEY `idx_volunteer_profiles_ward` (`ward_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `volunteer_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `volunteer_user_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `status` enum('Assigned','Accepted','In Progress','Completed','Verified','Rejected','Cancelled') DEFAULT 'Assigned',
  `assigned_note` text DEFAULT NULL,
  `completion_note` text DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `admin_review_note` text DEFAULT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_volunteer_tasks_report` (`report_id`),
  KEY `idx_volunteer_tasks_user` (`volunteer_user_id`),
  KEY `idx_volunteer_tasks_assigned_by` (`assigned_by`),
  KEY `idx_volunteer_tasks_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `volunteer_task_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_volunteer_task_updates_task` (`task_id`),
  KEY `idx_volunteer_task_updates_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_key` varchar(120) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notification_read` (`user_id`,`notification_key`),
  KEY `idx_notification_reads_read_at` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `app_sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumblob NOT NULL,
  `updated_at` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_app_sessions_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `user_type`, `ward`, `is_active`) VALUES
(1, 'Super Admin', 'superadmin@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 1),
(2, 'Ward 1 Admin', 'ward1@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w1', 1),
(3, 'Ward 2 Admin', 'ward2@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w2', 1),
(4, 'Ward 3 Admin', 'ward3@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w3', 1),
(5, 'Ward 4 Admin', 'ward4@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w4', 1),
(6, 'Ward 5 Admin', 'ward5@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w5', 1),
(7, 'Ward 6 Admin', 'ward6@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w6', 1),
(8, 'Ward 7 Admin', 'ward7@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w7', 1),
(9, 'Ward 8 Admin', 'ward8@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w8', 1),
(10, 'Ward 9 Admin', 'ward9@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w9', 1),
(11, 'Municipal Admin', 'municipaladmin@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'municipal_admin', NULL, 1);

ALTER TABLE `users` AUTO_INCREMENT = 12;

COMMIT;
