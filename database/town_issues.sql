-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Apr 27, 2026 at 08:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `town_issues`
--

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `otp`, `expires_at`, `created_at`) VALUES
(1, 'word00674@gmail.com', '874723', '2026-03-17 12:22:27', '2026-03-17 11:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `municipality` varchar(50) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `status` enum('Reported','Acknowledged','In Progress','Resolved') DEFAULT 'Reported',
  `image_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `email`, `category`, `description`, `latitude`, `longitude`, `municipality`, `zone`, `status`, `image_filename`, `created_at`) VALUES
(1, NULL, 'yash@gmail.com', 'Pothole', 'Road surface damaged near junction', 20.2813897, 85.8107758, 'w48', NULL, 'Reported', 'report_sample1.png', '2026-01-21 04:19:46'),
(2, NULL, 'sambit@gmail.com', 'Broken Streetlight', 'Streetlight not working since 3 days', 20.3510126, 85.8054596, 'w1', NULL, 'Resolved', 'report_sample2.png', '2026-01-21 04:19:46'),
(4, NULL, 'sucharitamohanty287@gmail.com', 'Pothole', 'gande gadhe bahut sara hai... gadi chalane m dikkat ho raha', 20.3237620, 85.8197021, 'w8', NULL, 'Resolved', 'report_69ba6d366a0a16.43692252.png', '2026-03-18 09:15:34'),
(6, NULL, 'yashd4025@gmail.com', 'Pothole', 'kasnfnsaf', 20.3340922, 85.8231997, 'w3', NULL, 'Resolved', 'report_69df60800297c3.87999852.jpg', '2026-04-15 09:55:12'),
(7, NULL, 'yashd4025@gmail.com', 'Trash', 'jbbadq', 20.3354091, 85.8233821, 'w3', NULL, 'Resolved', 'report_69df6095481db1.99537799.jpg', '2026-04-15 09:55:33'),
(8, 3, 'yashd4025@gmail.com', 'Trash', 'afsdfscsdc', 20.3504665, 85.8065029, 'W1', NULL, 'Resolved', 'report_69eeee42240299.50786831.jpg', '2026-04-27 05:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('citizen','municipal_admin','ward_admin','super_admin') NOT NULL,
  `ward` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `user_type`, `ward`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'Ward 9 Admin', 'ward9@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w9', 1, '2026-04-17 10:04:58', '2026-01-21 04:19:46'),
(2, 'Ward 15 Admin', 'ward15@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w15', 1, '2026-04-17 10:05:31', '2026-01-21 04:19:46'),
(3, 'Yash Dubey', 'yashd4025@gmail.com', '$2y$10$XmhfFVu5Q8sK55cfBzo3LeE5drnGPd8FQ4e1Z7vhhc29IQusaPzWG', 'citizen', NULL, 1, '2026-04-27 10:39:32', '2026-01-21 04:19:46'),
(8, 'Super Admin', 'superadmin@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 1, '2026-04-27 10:34:29', '2026-04-15 13:37:05'),
(9, 'Ward 1 Admin', 'ward1@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w1', 1, '2026-04-27 10:37:01', '2026-04-15 13:37:05'),
(10, 'Ward 2 Admin', 'ward2@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w2', 1, NULL, '2026-04-15 13:37:05'),
(11, 'Ward 3 Admin', 'ward3@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w3', 1, '2026-04-17 10:08:03', '2026-04-15 13:37:05'),
(12, 'Ward 4 Admin', 'ward4@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w4', 1, NULL, '2026-04-15 13:37:05'),
(13, 'Ward 5 Admin', 'ward5@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w5', 1, NULL, '2026-04-15 13:37:05'),
(14, 'Ward 6 Admin', 'ward6@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w6', 1, NULL, '2026-04-15 13:37:05'),
(15, 'Ward 7 Admin', 'ward7@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w7', 1, NULL, '2026-04-15 13:37:05'),
(16, 'Ward 8 Admin', 'ward8@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w8', 1, '2026-04-17 10:07:31', '2026-04-15 13:37:05'),
(17, 'Ward 10 Admin', 'ward10@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w10', 1, NULL, '2026-04-15 13:37:05'),
(18, 'Ward 11 Admin', 'ward11@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w11', 1, NULL, '2026-04-15 13:37:05'),
(19, 'Ward 12 Admin', 'ward12@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w12', 1, NULL, '2026-04-15 13:37:05'),
(20, 'Ward 13 Admin', 'ward13@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w13', 1, NULL, '2026-04-15 13:37:05'),
(21, 'Ward 14 Admin', 'ward14@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w14', 1, NULL, '2026-04-15 13:37:05'),
(22, 'Ward 16 Admin', 'ward16@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w16', 1, NULL, '2026-04-15 13:37:05'),
(23, 'Ward 17 Admin', 'ward17@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w17', 1, NULL, '2026-04-15 13:37:05'),
(24, 'Ward 18 Admin', 'ward18@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w18', 1, NULL, '2026-04-15 13:37:05'),
(25, 'Ward 19 Admin', 'ward19@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w19', 1, '2026-04-17 10:05:15', '2026-04-15 13:37:05'),
(26, 'Ward 20 Admin', 'ward20@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w20', 1, NULL, '2026-04-15 13:37:05'),
(27, 'Ward 21 Admin', 'ward21@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w21', 1, NULL, '2026-04-15 13:37:05'),
(28, 'Ward 22 Admin', 'ward22@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w22', 1, NULL, '2026-04-15 13:37:05'),
(29, 'Ward 23 Admin', 'ward23@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w23', 1, NULL, '2026-04-15 13:37:05'),
(30, 'Ward 24 Admin', 'ward24@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w24', 1, NULL, '2026-04-15 13:37:05'),
(31, 'Ward 25 Admin', 'ward25@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w25', 1, NULL, '2026-04-15 13:37:05'),
(32, 'Ward 26 Admin', 'ward26@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w26', 1, NULL, '2026-04-15 13:37:05'),
(33, 'Ward 27 Admin', 'ward27@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w27', 1, NULL, '2026-04-15 13:37:05'),
(34, 'Ward 28 Admin', 'ward28@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w28', 1, NULL, '2026-04-15 13:37:05'),
(35, 'Ward 29 Admin', 'ward29@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w29', 1, NULL, '2026-04-15 13:37:05'),
(36, 'Ward 30 Admin', 'ward30@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w30', 1, NULL, '2026-04-15 13:37:05'),
(37, 'Ward 31 Admin', 'ward31@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w31', 1, NULL, '2026-04-15 13:37:05'),
(38, 'Ward 32 Admin', 'ward32@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w32', 1, NULL, '2026-04-15 13:37:05'),
(39, 'Ward 33 Admin', 'ward33@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w33', 1, NULL, '2026-04-15 13:37:05'),
(40, 'Ward 34 Admin', 'ward34@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w34', 1, NULL, '2026-04-15 13:37:05'),
(41, 'Ward 35 Admin', 'ward35@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w35', 1, NULL, '2026-04-15 13:37:05'),
(42, 'Ward 36 Admin', 'ward36@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w36', 1, NULL, '2026-04-15 13:37:05'),
(43, 'Ward 37 Admin', 'ward37@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w37', 1, NULL, '2026-04-15 13:37:05'),
(44, 'Ward 38 Admin', 'ward38@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w38', 1, NULL, '2026-04-15 13:37:05'),
(45, 'Ward 39 Admin', 'ward39@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w39', 1, NULL, '2026-04-15 13:37:05'),
(46, 'Ward 40 Admin', 'ward40@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w40', 1, NULL, '2026-04-15 13:37:05'),
(47, 'Ward 41 Admin', 'ward41@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w41', 1, NULL, '2026-04-15 13:37:05'),
(48, 'Ward 42 Admin', 'ward42@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w42', 1, NULL, '2026-04-15 13:37:05'),
(49, 'Ward 43 Admin', 'ward43@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w43', 1, NULL, '2026-04-15 13:37:05'),
(50, 'Ward 44 Admin', 'ward44@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w44', 1, NULL, '2026-04-15 13:37:05'),
(51, 'Ward 45 Admin', 'ward45@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w45', 1, NULL, '2026-04-15 13:37:05'),
(52, 'Ward 46 Admin', 'ward46@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w46', 1, NULL, '2026-04-15 13:37:05'),
(53, 'Ward 47 Admin', 'ward47@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w47', 1, NULL, '2026-04-15 13:37:05'),
(54, 'Ward 48 Admin', 'ward48@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w48', 1, NULL, '2026-04-15 13:37:05'),
(55, 'Ward 49 Admin', 'ward49@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w49', 1, NULL, '2026-04-15 13:37:05'),
(56, 'Ward 50 Admin', 'ward50@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w50', 1, NULL, '2026-04-15 13:37:05'),
(57, 'Ward 51 Admin', 'ward51@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w51', 1, NULL, '2026-04-15 13:37:05'),
(58, 'Ward 52 Admin', 'ward52@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w52', 1, NULL, '2026-04-15 13:37:05'),
(59, 'Ward 53 Admin', 'ward53@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w53', 1, NULL, '2026-04-15 13:37:05'),
(60, 'Ward 54 Admin', 'ward54@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w54', 1, NULL, '2026-04-15 13:37:05'),
(61, 'Ward 55 Admin', 'ward55@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w55', 1, NULL, '2026-04-15 13:37:05'),
(62, 'Ward 56 Admin', 'ward56@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w56', 1, '2026-04-15 19:08:12', '2026-04-15 13:37:05'),
(63, 'Ward 57 Admin', 'ward57@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w57', 1, NULL, '2026-04-15 13:37:05'),
(64, 'Ward 58 Admin', 'ward58@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w58', 1, NULL, '2026-04-15 13:37:05'),
(65, 'Ward 59 Admin', 'ward59@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w59', 1, NULL, '2026-04-15 13:37:05'),
(66, 'Ward 60 Admin', 'ward60@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w60', 1, NULL, '2026-04-15 13:37:05'),
(67, 'Ward 61 Admin', 'ward61@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w61', 1, NULL, '2026-04-15 13:37:05'),
(68, 'Ward 62 Admin', 'ward62@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w62', 1, NULL, '2026-04-15 13:37:05'),
(69, 'Ward 63 Admin', 'ward63@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w63', 1, NULL, '2026-04-15 13:37:05'),
(70, 'Ward 64 Admin', 'ward64@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w64', 1, NULL, '2026-04-15 13:37:05'),
(71, 'Ward 65 Admin', 'ward65@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w65', 1, NULL, '2026-04-15 13:37:05'),
(72, 'Ward 66 Admin', 'ward66@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w66', 1, NULL, '2026-04-15 13:37:05'),
(73, 'Ward 67 Admin', 'ward67@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w67', 1, NULL, '2026-04-15 13:37:05'),
(136, 'System Administrator', 'admin@municipal.gov.ph', '$2y$10$WuK40SW4HLNRACNcOxrjnemgkpFiQwxbYSeidY6KXkkgHy1TkNBRe', 'super_admin', NULL, 1, NULL, '2026-04-15 13:43:13'),
(137, 'Juan Dela Cruz', 'citizen@example.com', '$2y$10$upu12MFjFRc1Y7kixGyBO.WLLorKfcY7pMOhaOBnOpAwmTMfP0.K.', 'citizen', NULL, 1, '2026-04-27 10:44:07', '2026-04-15 13:43:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_email` (`email`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_category` (`category`),
  ADD KEY `idx_reports_municipality` (`municipality`),
  ADD KEY `idx_reports_location` (`latitude`,`longitude`),
  ADD KEY `idx_reports_created_at` (`created_at`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_ward_admin` (`ward`,`user_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
