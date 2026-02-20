-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 05:39 AM
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
-- Database: `community_issues`
--

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
(2, NULL, 'sambit@gmail.com', 'Broken Streetlight', 'Streetlight not working since 3 days', 20.3510126, 85.8054596, 'w1', NULL, 'Reported', 'report_sample2.png', '2026-01-21 04:19:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('citizen','admin','municipal_admin','ward_admin','super_admin') NOT NULL DEFAULT 'citizen',
  `ward` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `user_type`, `ward`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'Ward 9 Admin', 'ward9@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w9', 1, NULL, '2026-01-21 04:19:46'),
(2, 'Ward 15 Admin', 'ward15@bmc.gov.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ward_admin', 'w15', 1, NULL, '2026-01-21 04:19:46'),
(3, 'Yash Dubey', 'yashd4025@gmail.com', '$2y$10$Hw2bH5YWyEYde8KcvoRkQelS6fuwp.YraIaFrYxNaP62pCHKzrkne', 'citizen', NULL, 1, '2026-01-21 09:50:33', '2026-01-21 04:19:46'),
(4, 'Amit Kumar', 'amit@citizen.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', NULL, 1, NULL, '2026-01-21 04:19:46'),
(5, 'Riya Sharma', 'riya@citizen.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', NULL, 1, NULL, '2026-01-21 04:19:46');

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
