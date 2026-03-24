-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2026 at 06:28 AM
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
-- Database: `ccc_curriculum_evaluation`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log_db`
--

CREATE TABLE `activity_log_db` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log_db`
--

INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'rcopena@ccc.edu.ph', 'Login', 'User rozz opena logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:02:16'),
(2, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:03:06'),
(3, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:03:08'),
(4, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:05:33'),
(5, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:07:13'),
(6, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:07:27'),
(7, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:09:00'),
(8, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:09:13'),
(9, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:14:34'),
(10, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:14:51'),
(11, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:52:24'),
(12, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:52:33'),
(13, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:52:39'),
(14, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-14 19:54:09'),
(15, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-15 02:34:51'),
(16, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-15 02:37:18'),
(17, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-15 02:37:27'),
(18, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-15 02:38:30'),
(19, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:36:16'),
(20, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:36:28'),
(21, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:36:43'),
(22, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:37:29'),
(23, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:37:37'),
(24, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 02:40:02'),
(26, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:01:01'),
(27, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:08:44'),
(28, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:10:52'),
(29, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:11:55'),
(30, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:12:49'),
(31, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-16 16:12:52'),
(32, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 16:09:51'),
(33, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:10:45'),
(34, NULL, 'nico@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:12:27'),
(35, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:14:40'),
(36, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:15:16'),
(37, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:15:44'),
(38, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:15:51'),
(39, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:19:39'),
(40, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:19:59'),
(41, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:21:27'),
(42, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:21:41'),
(43, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:21:52'),
(44, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:23:44'),
(45, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:24:01'),
(46, NULL, 'nico@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:25:25'),
(47, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:25:33'),
(48, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:29:10'),
(49, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:29:57'),
(50, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:31:23'),
(51, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:31:30'),
(52, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:35:10'),
(53, NULL, 'nico@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:44:43'),
(54, NULL, 'nico@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:44:47'),
(55, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 17:44:55'),
(56, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 18:27:47'),
(57, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 01:40:53'),
(58, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 03:57:47'),
(59, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 03:57:55'),
(60, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 04:46:23'),
(61, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 04:46:32'),
(62, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 17:28:32'),
(63, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 18:04:37'),
(64, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-19 18:04:44'),
(65, NULL, 'b2fg@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:00:17'),
(66, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:00:24'),
(67, NULL, 'b2fg@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:13:58'),
(68, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:14:04'),
(69, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:15:16'),
(70, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:15:44'),
(71, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:15:51'),
(72, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:15:57'),
(73, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:16:08'),
(74, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:16:12'),
(75, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 01:16:23'),
(76, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 04:37:57'),
(77, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 04:38:06'),
(78, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 04:38:28'),
(79, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 04:56:53'),
(80, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 14:26:29'),
(81, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 14:29:12'),
(82, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 14:29:34'),
(83, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 14:57:10'),
(84, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 14:57:39'),
(85, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:17:36'),
(86, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:19:15'),
(87, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:37:51'),
(88, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:38:01'),
(89, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:44:21'),
(90, NULL, 'b2fg@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:44:33'),
(91, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:44:37'),
(92, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:44:40'),
(93, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:44:53'),
(94, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:50:30'),
(95, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:50:50'),
(96, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:51:39'),
(97, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 15:51:46'),
(98, NULL, 'b2fg@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:22:16'),
(99, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:22:21'),
(100, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:23:31'),
(101, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:23:39'),
(102, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:24:05'),
(103, NULL, 'b2fg@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:24:10'),
(104, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:24:16'),
(105, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:25:10'),
(106, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:25:16'),
(107, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:30:31'),
(108, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:30:39'),
(109, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:48:59'),
(110, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:49:11'),
(111, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:49:59'),
(112, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:50:09'),
(113, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:55:00'),
(114, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 04:55:06'),
(115, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 05:10:25'),
(116, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 05:10:34'),
(117, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 05:24:56'),
(118, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 05:25:48'),
(119, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 05:32:02'),
(120, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:06:35'),
(121, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:49:09'),
(122, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:49:16'),
(123, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:51:44'),
(124, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:51:50'),
(125, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:52:33'),
(126, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:53:00'),
(127, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:53:06'),
(128, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:53:15'),
(129, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:54:11'),
(130, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:54:29'),
(131, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:55:48'),
(132, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:56:01'),
(133, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:56:25'),
(134, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:56:56'),
(135, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:57:15'),
(136, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 16:57:29'),
(137, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 00:42:21'),
(138, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 00:42:28'),
(139, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:01:58'),
(140, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:02:04'),
(141, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:02:14'),
(142, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:02:28'),
(143, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:02:46'),
(144, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:03:55'),
(145, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:06:33'),
(146, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 01:06:50'),
(147, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 02:59:11'),
(148, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 02:59:20'),
(149, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 05:43:17'),
(150, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 07:07:47'),
(151, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 07:07:56'),
(152, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 07:10:20'),
(153, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 07:11:15'),
(154, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 08:14:23'),
(155, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 08:14:30'),
(156, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 14:42:05'),
(157, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 14:42:21'),
(158, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:04:51'),
(159, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:04:58'),
(160, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:05:04'),
(161, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:59:58'),
(162, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 16:02:34'),
(163, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 17:04:27'),
(164, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 17:04:35'),
(165, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 17:34:31'),
(166, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 17:34:36'),
(167, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 17:34:49'),
(168, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 19:07:29'),
(169, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 19:12:33'),
(170, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 19:12:41'),
(171, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 19:28:18'),
(172, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:11:32'),
(173, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:11:38'),
(174, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:12:35'),
(175, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:12:53'),
(176, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:17:06'),
(177, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:17:15'),
(178, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:18:25'),
(179, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:19:04'),
(180, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:19:55'),
(181, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:51:48'),
(182, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 01:51:52'),
(183, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:18:25'),
(184, NULL, 'dane@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:18:32'),
(185, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:18:37'),
(186, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:33:58'),
(187, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:34:06'),
(188, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:36:40'),
(189, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 03:36:50'),
(190, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 05:15:00'),
(191, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 05:15:16'),
(192, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 07:03:33'),
(193, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 07:03:41'),
(194, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 11:47:20'),
(195, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 11:47:27'),
(196, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 11:48:32'),
(197, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 11:48:54'),
(198, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 12:19:22'),
(199, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 12:19:29'),
(200, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 12:30:52'),
(201, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 12:31:00'),
(202, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 16:03:55'),
(203, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 01:15:43'),
(204, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 01:16:22'),
(205, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 05:09:19'),
(206, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 05:09:30'),
(207, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 05:20:23'),
(208, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 05:20:32'),
(209, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 12:26:15'),
(210, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 12:26:25'),
(211, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 12:27:02'),
(212, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 12:27:13'),
(213, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:30:38'),
(214, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:30:46'),
(215, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:30:49');
INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(216, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:30:55'),
(217, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:31:17'),
(218, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:31:33'),
(219, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:32:18'),
(220, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:35:57'),
(221, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 10:36:03'),
(222, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 11:24:17'),
(223, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:59:35'),
(224, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:00:51'),
(225, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (Registrar)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:01:14'),
(226, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (Registrar)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:01:35'),
(227, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:02:31'),
(228, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:02:46'),
(229, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:03:06'),
(230, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:03:12'),
(231, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:03:23'),
(232, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:05:05'),
(233, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:05:31'),
(234, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '192.168.100.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:08:00'),
(235, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:08:42'),
(236, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 17:09:01'),
(237, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 02:39:06'),
(238, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:03:41'),
(239, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:03:50'),
(240, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:52:07'),
(241, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:52:15'),
(242, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:53:27'),
(243, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 06:55:40'),
(244, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-02 18:27:33'),
(245, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:12:06'),
(246, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:12:13'),
(247, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:12:32'),
(248, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:12:45'),
(249, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:30:43'),
(250, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:30:51'),
(251, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:15'),
(252, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:43'),
(253, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:49'),
(254, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:51'),
(255, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:52'),
(256, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:54'),
(257, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:33:56'),
(258, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:37:51'),
(259, NULL, 'rozz@gmail.com', 'Failed Login', 'Invalid password attempt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 05:43:07'),
(260, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 06:47:25'),
(261, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 06:47:29'),
(262, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 06:49:29'),
(263, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 06:50:39'),
(264, NULL, 'rcopena@ccc.edu.ph', 'Failed Login', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 06:50:45'),
(265, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 07:59:20'),
(266, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 08:03:28'),
(267, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 08:27:05'),
(268, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 16:12:30'),
(269, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 16:12:58'),
(270, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 16:13:20'),
(271, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 16:30:17'),
(272, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 16:30:23'),
(273, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 17:22:46'),
(274, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 17:22:54'),
(275, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 20:51:58'),
(276, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-03 20:52:21'),
(277, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:19:01'),
(278, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:20:16'),
(279, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 17:20:23'),
(280, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:18:00'),
(281, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:19:05'),
(282, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:19:16'),
(283, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:19:23'),
(284, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:19:29'),
(285, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:20:37'),
(286, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:20:43'),
(287, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:48:28'),
(288, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:29:28'),
(289, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:29:58'),
(290, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:30:07'),
(291, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:30:22'),
(292, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:30:35'),
(293, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:07:22'),
(294, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:07:29'),
(295, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:23:08'),
(296, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:42:28'),
(297, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:00:45'),
(298, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:00:58'),
(299, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:09:23'),
(300, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:10:19'),
(301, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:15:26'),
(302, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:15:40'),
(303, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:23:40'),
(304, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:24:54'),
(305, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:25:06'),
(306, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:25:37'),
(307, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:25:44'),
(308, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:27:33'),
(309, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:27:46'),
(310, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 03:28:14'),
(311, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 05:57:14'),
(312, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 05:57:36'),
(313, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 05:57:52'),
(314, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 06:17:44'),
(315, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 06:24:15'),
(316, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 06:42:12'),
(317, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 06:42:19'),
(318, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 07:02:52'),
(319, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 07:03:04'),
(320, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 07:15:58'),
(321, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 07:18:02'),
(322, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 07:19:14'),
(323, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 16:56:50'),
(324, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 15:06:04'),
(325, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:42:30'),
(326, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 16:49:26'),
(327, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 17:39:39'),
(328, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 04:26:43'),
(329, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 04:31:03'),
(330, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:01:05'),
(331, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:08:01'),
(332, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:08:09'),
(333, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:11:49'),
(334, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:13:53'),
(335, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:40:59'),
(336, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 05:45:42'),
(337, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 09:41:28'),
(338, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 09:53:29'),
(339, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 10:10:40'),
(340, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 10:12:03'),
(341, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 10:14:10'),
(342, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 10:32:24'),
(343, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 11:27:37'),
(344, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 11:50:34'),
(345, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 11:53:50'),
(346, 49, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 11:54:01'),
(347, 49, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - rozz B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 11:54:08'),
(348, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 12:26:49'),
(349, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 13:40:25'),
(350, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 13:40:32'),
(351, 1, 'rozz opena', 'Add User', 'Added user: vince b2fg (b2fg@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 13:49:24'),
(352, 1, 'rozz opena', 'Add User', 'Added user: vince B2FG (b2fg@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:27:50'),
(353, 1, 'rozz opena', 'Add User', 'Added user: vince B2FG (b2fg@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:31:49'),
(354, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:40:28'),
(355, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:41:03'),
(356, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:41:20'),
(357, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:41:24'),
(358, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:59:22'),
(359, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:01:52'),
(360, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 16:16:57'),
(361, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 16:17:02'),
(362, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 16:55:14'),
(363, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 16:55:40'),
(364, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 17:56:07'),
(365, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 18:03:02'),
(366, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 18:33:48'),
(367, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 18:43:17'),
(368, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 18:49:55'),
(369, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:23:40'),
(370, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:24:19'),
(371, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:24:37'),
(372, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:42:05'),
(373, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:42:11'),
(374, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 11:35:30'),
(375, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 11:36:01'),
(376, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:03:16'),
(377, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:03:25'),
(378, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:23:49'),
(379, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:23:56'),
(380, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:24:07'),
(381, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:24:14'),
(382, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:31:29'),
(383, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 14:31:35'),
(384, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 15:30:41'),
(385, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 15:32:10'),
(386, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:21:57'),
(387, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:22:20'),
(388, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:23:42'),
(389, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:23:57'),
(390, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:24:36'),
(391, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 17:24:49'),
(392, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 18:58:39'),
(393, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 18:58:45'),
(394, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 18:59:12'),
(395, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 18:59:19'),
(396, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 19:23:33'),
(397, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 19:29:05'),
(398, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 19:29:34'),
(399, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 19:29:42'),
(400, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 19:31:52'),
(401, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 15:18:26'),
(402, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 18:01:54'),
(403, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:07:24'),
(404, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:38:31'),
(405, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:41:43'),
(406, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:42:23'),
(407, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:49:09'),
(408, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:52:31'),
(409, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:52:44'),
(410, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:56:26'),
(411, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:07:14'),
(412, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:07:48'),
(413, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:12:17'),
(414, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:12:32'),
(415, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:38:21'),
(416, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:38:41'),
(417, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:51:16'),
(418, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 14:11:42'),
(419, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 14:57:41'),
(420, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 14:58:03'),
(421, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 15:36:16'),
(422, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 16:04:36'),
(423, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 18:45:39'),
(424, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 07:25:55'),
(425, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 07:36:12'),
(426, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 07:37:07'),
(427, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 08:00:14');
INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(428, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 08:00:32'),
(429, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 08:00:52'),
(430, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 08:26:31'),
(431, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:06:41'),
(432, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:10:57'),
(433, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:20:26'),
(434, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:20:33'),
(435, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:20:42'),
(436, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:47:09'),
(437, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:47:17'),
(438, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 19:49:51'),
(439, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 20:03:06'),
(440, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:03:09'),
(441, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:03:45'),
(442, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:39:57'),
(443, 13, 'nico@ccc.edu.ph', 'Login', 'User logged in successfully - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:40:09'),
(444, 13, 'nico@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:44:13'),
(445, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:45:15'),
(446, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:45:41'),
(447, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 22:46:14'),
(448, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 10:26:45'),
(449, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 14:05:11'),
(450, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 16:32:31'),
(451, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 16:33:43'),
(452, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 16:35:04'),
(453, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 17:20:39'),
(454, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 17:21:15'),
(455, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 19:03:54'),
(456, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:564:7482:88c6:be73:b0f0:34e9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 02:46:40'),
(457, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '175.176.48.87', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 08:28:52'),
(458, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 14:41:49'),
(459, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 19:09:25'),
(460, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:15:37'),
(461, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:16:44'),
(462, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:20:27'),
(463, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:49:26'),
(464, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:51:26'),
(465, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 01:51:33'),
(466, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:564:3ac4:f86c:3cf3:f13b:d24c', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 02:14:15'),
(467, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:560:947:8911:7278:d5ad:62b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 02:25:37'),
(468, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:560:947:8911:7278:d5ad:62b6', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 02:25:40'),
(469, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '175.176.58.129', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:25:40'),
(470, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 08:02:15'),
(471, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 08:16:34'),
(472, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 13:12:23'),
(473, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 13:12:33'),
(474, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 23:19:55'),
(475, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 23:28:24'),
(476, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 00:01:11'),
(477, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 01:53:11'),
(478, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 01:53:43'),
(479, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:05:04'),
(480, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:05:17'),
(481, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:05:49'),
(482, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:06:20'),
(483, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:06:41'),
(484, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 02:57:53'),
(485, 50, 'rozz@gmail.com', 'Login', 'User logged in successfully - rozz KREWPEK (DCI Student)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:01:12'),
(486, 50, 'rozz@gmail.com', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:03:59'),
(487, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:04:09'),
(488, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:25:43'),
(489, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:34:07'),
(490, 1, 'rozz opena', 'Add User', 'Added user: a 123 (yyy@yyy.yyy)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:40:19'),
(491, 1, 'rozz opena', 'Add User', 'Added user: s s (aaaaaaa@AAAAAA.AAAA)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:45:01'),
(492, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:46:06'),
(493, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:46:25'),
(494, 54, 'yyy@yyy.yyy', 'Login', 'User logged in successfully - a 123 (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:46:59'),
(495, 54, 'yyy@yyy.yyy', 'Logout', 'User logged out - a 123 (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 03:59:57'),
(496, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:00:17'),
(497, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:02:01'),
(498, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:02:12'),
(499, 1, 'rozz opena', 'Add User', 'Added user: ron ron (ron@ccc.edu.ph)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:03:46'),
(500, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:03:54'),
(501, 56, 'ron@ccc.edu.ph', 'Login', 'User logged in successfully - ron ron (DCI Student)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:04:38'),
(502, 56, 'ron@ccc.edu.ph', 'Login', 'User logged in successfully - ron ron (DCI Student)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:04:39'),
(503, 56, 'ron@ccc.edu.ph', 'Logout', 'User logged out - ron ron (DCI Student)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:04:46'),
(504, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:05:00'),
(505, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:07:38'),
(506, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:1a93:4a61:65da:4aec:d2d2:46dc', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 04:08:15'),
(507, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:44:58'),
(508, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:45:33'),
(509, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:45:49'),
(510, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:48:02'),
(511, 53, 'b2fg@ccc.edu.ph', 'Login', 'User logged in successfully - vince B2FG (Registrar)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:48:30'),
(512, 53, 'b2fg@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:49:21'),
(513, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:49:37'),
(514, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:53:46'),
(515, 12, 'dane@ccc.edu.ph', 'Login', 'User logged in successfully - dane dkaido (Admin)', '2001:fd8:1aa4:2e08:85f:2b97:73f4:2d68', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 07:53:56'),
(516, 12, 'dane@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 12:28:17'),
(517, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:06:32'),
(518, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:11:08'),
(519, 53, 'b2fg@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (b2fg@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:15:13'),
(520, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:15:44'),
(521, 53, 'b2fg@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (b2fg@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:17:25'),
(522, 12, 'dane@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (dane@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:29:04'),
(523, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:29:25'),
(524, 12, 'dane@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (dane@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:37:32'),
(525, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:37:50'),
(526, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:44:07'),
(527, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:52:38'),
(528, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:58:01'),
(529, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:58:43'),
(530, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 04:59:05'),
(531, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:01:59'),
(532, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:19:47'),
(533, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:24:06'),
(534, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:31:34'),
(535, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:35:19'),
(536, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:39:41'),
(537, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:40:32'),
(538, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:44:03'),
(539, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:44:37'),
(540, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 05:53:33'),
(541, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 06:00:57'),
(542, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 06:01:13'),
(543, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 06:11:16'),
(544, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 08:19:31'),
(545, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 08:19:47'),
(546, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 08:20:05'),
(547, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 16:07:00'),
(548, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 16:08:12'),
(549, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 16:13:14'),
(550, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 16:17:57'),
(551, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 17:52:31'),
(552, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 02:17:05'),
(553, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 04:33:16'),
(554, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 07:36:30'),
(555, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 09:55:47'),
(556, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 09:56:17'),
(557, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 09:59:04'),
(558, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 10:01:44'),
(559, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 11:38:28'),
(560, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 11:40:49'),
(561, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 11:46:15'),
(562, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:10:37'),
(563, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:11:39'),
(564, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:26:29'),
(565, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:28:31'),
(566, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:30:36'),
(567, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 12:31:37'),
(568, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:11:39'),
(569, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:20:50'),
(570, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:22:16'),
(571, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:22:42'),
(572, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:23:19'),
(573, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.129.13', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:24:17'),
(574, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:43:39'),
(575, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:44:44'),
(576, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:50:26'),
(577, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:51:22'),
(578, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 14:52:22'),
(579, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:42:19'),
(580, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 15:42:32'),
(581, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 16:21:23'),
(582, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:59:54'),
(583, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:00:50'),
(584, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:01:36'),
(585, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:03:20'),
(586, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:04:12'),
(587, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 10:04:52'),
(588, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 13:41:29'),
(589, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 13:43:26'),
(590, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '175.176.51.142', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 06:32:21'),
(591, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '175.176.51.142', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 06:43:34'),
(592, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 14:49:24'),
(593, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 14:51:49'),
(594, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:15:49'),
(595, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:16:46'),
(596, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:17:29'),
(597, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:18:03'),
(598, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:34:29'),
(599, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:41:54'),
(600, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:42:22'),
(601, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:45:12'),
(602, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:59:58'),
(603, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 01:11:25'),
(604, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 01:15:24'),
(605, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 01:16:20'),
(606, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 01:17:32'),
(607, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 01:19:11'),
(608, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '2001:fd8:563:f8d6:15c7:e592:89b3:6f3b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 02:05:49'),
(609, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '2001:fd8:563:f8d6:15c7:e592:89b3:6f3b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 02:07:03'),
(610, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '2001:fd8:563:f8d6:15c7:e592:89b3:6f3b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 02:14:46'),
(611, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '2001:fd8:563:f8d6:15c7:e592:89b3:6f3b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 02:15:32'),
(612, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 03:07:48'),
(613, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '2001:fd8:563:f8d6:14de:c4be:4e83:1d92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 03:49:09'),
(614, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '2001:fd8:563:f8d6:14de:c4be:4e83:1d92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 04:07:40'),
(615, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '2001:fd8:563:f8d6:14de:c4be:4e83:1d92', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 04:07:54'),
(616, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '175.176.51.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 04:39:15'),
(617, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 04:47:52'),
(618, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 05:09:54'),
(619, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 05:23:28'),
(620, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 06:07:21');
INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(621, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 07:47:03'),
(622, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 07:48:04'),
(623, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:36:08'),
(624, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 12:38:10'),
(625, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 12:39:10'),
(626, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 12:41:45'),
(627, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 16:32:51'),
(628, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 16:44:02'),
(629, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:21:26'),
(630, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:29:05'),
(631, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:30:47'),
(632, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:42:49'),
(633, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:43:05'),
(634, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:44:48'),
(635, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:49:44'),
(636, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 01:52:42'),
(637, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 04:11:59'),
(638, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 05:34:30'),
(639, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 05:35:44'),
(640, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 05:37:26'),
(641, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 13:40:17'),
(642, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 13:49:58'),
(643, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 14:34:54'),
(644, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 14:38:56'),
(645, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 08:54:36'),
(646, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 10:23:46'),
(647, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 10:27:00'),
(648, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 10:45:27'),
(649, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 10:47:06'),
(650, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:06:53'),
(651, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:07:44'),
(652, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:12:26'),
(653, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:13:42'),
(654, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:17:05'),
(655, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:18:45'),
(656, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:21:20'),
(657, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:25:21'),
(658, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 11:45:17'),
(659, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:45:42'),
(660, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:46:47'),
(661, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:47:58'),
(662, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:50:08'),
(663, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 11:50:39'),
(664, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:51:06'),
(665, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 11:51:45'),
(666, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 11:52:35'),
(667, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:52:49'),
(668, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 14; Infinix X6882 Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/142.0.7444.102 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/534.0.0.53.103;]', '2025-11-28 11:54:29'),
(669, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 10; PPA-LX2 Build/HUAWEIPPA-LX2; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/92.0.4515.105 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/522.0.0.58.109;]', '2025-11-28 11:57:21'),
(670, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 13:49:22'),
(671, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 13:55:03'),
(672, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-11-28 13:55:23'),
(673, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 14:31:12'),
(674, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 03:38:28'),
(675, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 04:38:40'),
(676, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 04:39:42'),
(677, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 09:05:41'),
(678, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 09:17:07'),
(679, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 09:22:49'),
(680, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 15:58:33'),
(681, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 16:24:24'),
(682, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 16:25:38'),
(683, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 16:28:54'),
(684, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 16:29:08'),
(685, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 16:29:20'),
(686, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 02:01:39'),
(687, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 02:11:48'),
(688, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:09:38'),
(689, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:11:58'),
(690, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:13:57'),
(691, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:17:03'),
(692, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:24:36'),
(693, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 03:30:49'),
(694, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 10:57:22'),
(695, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to dane dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 11:00:45'),
(696, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - dane dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 11:13:07'),
(697, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 15:12:55'),
(698, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 15:41:21'),
(699, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 16:02:42'),
(700, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 16:02:59'),
(701, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 16:28:44'),
(702, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 16:30:52'),
(703, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 16:59:57'),
(704, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 00:53:06'),
(705, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 01:40:48'),
(706, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 01:41:01'),
(707, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 01:47:24'),
(708, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 01:47:39'),
(709, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 01:51:41'),
(710, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 04:49:39'),
(711, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 05:13:02'),
(712, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 05:13:58'),
(713, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 05:23:36'),
(714, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 05:25:23'),
(715, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 05:25:54'),
(716, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 06:28:50'),
(717, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 06:51:41'),
(718, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 06:54:40'),
(719, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 06:56:44'),
(720, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 06:57:20'),
(721, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 07:45:20'),
(722, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 07:51:11'),
(723, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 07:52:21'),
(724, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 08:01:40'),
(725, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 08:02:36'),
(726, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 09:34:48'),
(727, NULL, ' ', 'Add User', 'Added user: Jayjay Pogi (pogi@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 10:06:44'),
(728, NULL, ' ', 'Add User', 'Added user: Rozz Rogi (ROz@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 10:11:52'),
(729, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 10:12:26'),
(730, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '110.54.189.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 00:22:35'),
(731, NULL, ' ', 'Add User', 'Added user: Elvinard Reyes (erreyes@ccc.edu.ph)', '110.54.189.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 00:25:42'),
(732, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '110.54.189.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 00:38:00'),
(733, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '110.54.189.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 00:40:03'),
(734, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 05:54:44'),
(735, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:15:37'),
(736, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:16:29'),
(737, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:37:00'),
(738, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:39:56'),
(739, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:41:44'),
(740, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:42:22'),
(741, NULL, ' ', 'Add User', 'Added user: rozz Opena pogi (rozz@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:44:19'),
(742, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:45:27'),
(743, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 06:46:22'),
(744, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '110.54.197.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:41:33'),
(745, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '110.54.197.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:42:11'),
(746, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '110.54.197.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:45:29'),
(747, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '110.54.197.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:48:29'),
(748, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 08:51:46'),
(749, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 21:30:16'),
(750, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:25:43'),
(751, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:31:02'),
(752, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:31:48'),
(753, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:33:35'),
(754, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:34:29'),
(755, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:40:46'),
(756, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 00:41:31'),
(757, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 01:02:59'),
(758, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 01:03:43'),
(759, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 01:58:12'),
(760, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 01:59:23'),
(761, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 02:35:56'),
(762, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 02:36:10'),
(763, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 02:55:51'),
(764, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 03:00:50'),
(765, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 03:10:48'),
(766, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to vince B2FG (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 03:13:41'),
(767, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - vince B2FG (Registrar)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 03:27:41'),
(768, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:13:49'),
(769, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '49.147.39.182', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 04:26:28'),
(770, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 05:39:27'),
(771, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 05:44:47'),
(772, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 05:44:59'),
(773, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:11:28'),
(774, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:14:13'),
(775, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:18:35'),
(776, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:20:03'),
(777, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:22:00'),
(778, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:22:24'),
(779, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 06:33:05'),
(780, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 08:03:46'),
(781, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 08:05:43'),
(782, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 10:29:42'),
(783, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 10:49:00'),
(784, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 11:01:44'),
(785, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 11:04:46'),
(786, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 11:41:58'),
(787, NULL, ' ', 'Add User', 'Added user: honda civic (civic@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 11:50:58'),
(788, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 13:14:54'),
(789, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 13:44:54'),
(790, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 13:50:40'),
(791, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 13:51:11'),
(792, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 13:52:54'),
(793, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:14:11'),
(794, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:17:27'),
(795, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:18:46'),
(796, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:24:28'),
(797, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:24:45'),
(798, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:27:04'),
(799, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:28:20'),
(800, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 14:48:16'),
(801, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 00:35:05'),
(802, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 00:58:22'),
(803, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 04:10:27'),
(804, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 04:15:31'),
(805, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 04:42:56'),
(806, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 04:43:35'),
(807, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 04:48:31'),
(808, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 05:09:18'),
(809, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 05:48:19');
INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(810, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 05:51:24'),
(811, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 05:57:59'),
(812, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 05:58:41'),
(813, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:11:24'),
(814, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:13:07'),
(815, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:14:25'),
(816, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:21:56'),
(817, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:24:34'),
(818, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:25:20'),
(819, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:25:47'),
(820, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:56:55'),
(821, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 07:04:20'),
(822, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 07:05:30'),
(823, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 07:05:56'),
(824, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:29:45'),
(825, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:38:33'),
(826, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:40:02'),
(827, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:49:28'),
(828, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:50:44'),
(829, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:59:13'),
(830, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:28:05'),
(831, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:32:20'),
(832, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:33:10'),
(833, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:34:25'),
(834, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:36:01'),
(835, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 09:37:15'),
(836, 12, 'jlmarquez@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (jlmarquez@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 11:02:52'),
(837, 12, 'jlmarquez@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 11:06:08'),
(838, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 11:59:40'),
(839, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 12:25:01'),
(840, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 12:28:34'),
(841, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 12:32:54'),
(842, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 12:34:26'),
(843, 12, 'jlmarquez@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (jlmarquez@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:09:37'),
(844, 12, 'jlmarquez@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:11:59'),
(845, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:13:21'),
(846, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:14:52'),
(847, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:16:09'),
(848, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:42:04'),
(849, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar B2FG (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 13:42:40'),
(850, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar B2FG (Registrar)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:17:54'),
(851, 12, 'jlmarquez@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (jlmarquez@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:18:49'),
(852, 12, 'jlmarquez@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:22:44'),
(853, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:24:30'),
(854, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:30:10'),
(855, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:31:34'),
(856, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:34:42'),
(857, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 14:43:18'),
(858, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:28:14'),
(859, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:56:10'),
(860, 12, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 16:57:53'),
(861, 12, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 18:07:03'),
(862, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 18:20:08'),
(863, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '149.30.138.192', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 18:45:40'),
(864, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 22:45:48'),
(865, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 23:00:14'),
(866, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 23:00:29'),
(867, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 23:04:13'),
(868, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 23:04:29'),
(869, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:10:56'),
(870, 12, 'acopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to DCI Dean dkaido (acopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:11:36'),
(871, 12, 'acopena@ccc.edu.ph', 'Logout', 'User logged out - DCI Dean dkaido (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:20:40'),
(872, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:33:39'),
(873, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:50:37'),
(874, 53, 'kmsaez@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (kmsaez@ccc.edu.ph)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 01:52:15'),
(875, 53, 'kmsaez@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:07:44'),
(876, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:08:46'),
(877, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:11:07'),
(878, 68, 'aeabuyo@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Sir Mark (aeabuyo@ccc.edu.ph)', '175.176.51.45', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Mobile Safari/537.36', '2025-12-05 02:11:46'),
(879, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:15:35'),
(880, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:23:52'),
(881, 68, 'aeabuyo@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Sir Mark (aeabuyo@ccc.edu.ph)', '1.37.88.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 02:32:36'),
(882, 68, 'aeabuyo@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Sir Mark (aeabuyo@ccc.edu.ph)', '175.176.56.84', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 04:48:49'),
(883, 68, 'aeabuyo@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Sir Mark (aeabuyo@ccc.edu.ph)', '175.176.56.84', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 04:48:53'),
(884, 68, 'aeabuyo@ccc.edu.ph', 'Logout', 'User logged out - Sir Mark (Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-09 14:54:46'),
(885, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 23:57:23'),
(886, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 11:18:57'),
(887, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 11:49:10'),
(888, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 11:52:25'),
(889, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:43:20'),
(890, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:47:17'),
(891, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:49:53'),
(892, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:52:27'),
(893, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:57:03'),
(894, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 12:57:06'),
(895, 1, 'rozz opena', 'Add User', 'Added user: Dci dean Dean (jlmarquez@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:02:45'),
(896, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:02:49'),
(897, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:03:52'),
(898, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:26:07'),
(899, 50, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz KREWPEK (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:27:29'),
(900, 50, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz KREWPEK (DCI Student)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:39:02'),
(901, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '124.217.42.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-22 13:39:45'),
(902, 13, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to nico krewpek (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:25:58'),
(903, 13, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - nico krewpek (CS Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 05:26:36'),
(904, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 03:32:12'),
(905, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 03:42:26'),
(906, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 06:56:44'),
(907, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 07:28:02'),
(908, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:46:45'),
(909, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:08:25'),
(910, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:14:50'),
(911, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:15:39'),
(912, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:16:17'),
(913, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '49.147.11.220', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 13:20:19'),
(914, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:10:06'),
(915, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:11:59'),
(916, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:18:04'),
(917, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:20:57'),
(918, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:21:39'),
(919, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '149.30.138.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:22:51'),
(920, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:42:54'),
(921, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:58:42'),
(922, 1, 'rcopena@ccc.edu.ph', 'Login', 'User logged in successfully - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 07:48:01'),
(923, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 07:49:06'),
(924, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 01:44:45'),
(925, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-16 07:24:13'),
(926, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 00:47:46'),
(927, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 05:24:27'),
(928, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-20 05:30:41'),
(929, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:17:15'),
(930, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:30:48'),
(931, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:31:37'),
(932, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:34:23'),
(933, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:38:57'),
(934, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:40:17'),
(935, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:43:21'),
(936, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:44:06'),
(937, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:45:43'),
(938, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 05:47:15'),
(939, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 06:42:49'),
(940, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 23:59:55'),
(941, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 00:03:53'),
(942, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 01:07:44'),
(943, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 05:25:49'),
(944, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 05:25:52'),
(945, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:14:30'),
(946, 1, 'rozz opena', 'Add User', 'Added user: Elvinard Reyes (erreyes@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:52:22'),
(947, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:55:52'),
(948, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 06:56:48'),
(949, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 07:08:51'),
(950, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 16:28:07'),
(951, 1, 'rozz opena', 'Add User', 'Added user: Lulu Moko (pls@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:05:53'),
(952, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:06:50'),
(953, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 18:07:16'),
(954, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 19:03:58'),
(955, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 19:04:47'),
(956, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 01:20:18'),
(957, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 02:29:47'),
(958, 1, 'rozz opena', 'Add User', 'Added user: Garry Valenciano (jdque@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 02:53:32'),
(959, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-27 02:51:51'),
(960, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:21:39'),
(961, 1, 'rozz opena', 'Add User', 'Added user: Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:26:49'),
(962, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:31:18'),
(963, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:50:39'),
(964, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:51:14'),
(965, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:52:28'),
(966, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:57:24'),
(967, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:59:50'),
(968, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 05:34:44'),
(969, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 05:50:58'),
(970, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 05:58:51'),
(971, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:00:11'),
(972, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:02:26'),
(973, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:02:38'),
(974, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:03:17'),
(975, 77, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Ernesto Nagalulu (jdque@ccc.edu.ph)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:07:45'),
(976, 77, 'jdque@ccc.edu.ph', 'Logout', 'User logged out - Ernesto Nagalulu (Student)', '209.35.174.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 06:10:51'),
(977, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 03:52:37'),
(978, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 00:35:30'),
(979, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:16:19'),
(980, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:17:13'),
(981, 1, 'rozz opena', 'Add User', 'Added user: Elvinard Reyes (erreyes@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:19:56'),
(982, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:20:35'),
(983, 78, 'erreyes@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Elvinard Reyes (erreyes@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:21:11'),
(984, 78, 'erreyes@ccc.edu.ph', 'Logout', 'User logged out - Elvinard Reyes (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:22:16'),
(985, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:22:33'),
(986, 1, 'rozz opena', 'Add User', 'Added user: Dwight Torres (pcdirige@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:28:29'),
(987, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:31:31'),
(988, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:31:44'),
(989, 79, 'pcdirige@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dwight Torres (pcdirige@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:31:58'),
(990, 79, 'pcdirige@ccc.edu.ph', 'Logout', 'User logged out - Dwight Torres (Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:35:55'),
(991, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:36:21'),
(992, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:37:43'),
(993, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:44:11'),
(994, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:45:04'),
(995, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:49:55'),
(996, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 01:50:42'),
(997, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 02:05:20'),
(998, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 02:06:04'),
(999, 69, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Dci dean Dean (Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 02:07:28'),
(1000, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 03:38:14'),
(1001, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 06:04:19');
INSERT INTO `activity_log_db` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1002, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-06 06:06:22'),
(1003, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:00:46'),
(1004, 1, 'rozz opena', 'Add User', 'Added user: Rey Valera (jdque@ccc.edu.ph)', '149.30.138.207', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-08 00:15:13'),
(1005, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:13:30'),
(1006, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:18:25'),
(1007, 53, 'jdque@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (jdque@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:19:41'),
(1008, 69, 'jmllorera@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (jmllorera@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:26:01'),
(1009, 69, 'jmllorera@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (jmllorera@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:27:19'),
(1010, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:46:21'),
(1011, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 06:55:11'),
(1012, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:28:59'),
(1013, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:29:30'),
(1014, 1, 'rozz opena', 'Add User', 'Added user: Elvinard Reyes (erreyes@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:34:21'),
(1015, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:45:20'),
(1016, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:47:10'),
(1017, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:50:37'),
(1018, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:51:01'),
(1019, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:53:56'),
(1020, NULL, NULL, 'Logout', 'User logged out - Unknown User (Student)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:53:57'),
(1021, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '115.146.194.18', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-12 08:54:41'),
(1022, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 07:45:08'),
(1023, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 07:57:09'),
(1024, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:18:05'),
(1025, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:20:33'),
(1026, NULL, 'rcopena@ccc.edu.ph', 'Password Reset Requested', 'Requested password reset OTP for rcopena@ccc.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:46:37'),
(1027, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:47:33'),
(1028, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:48:03'),
(1029, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:51:49'),
(1030, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:52:41'),
(1031, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:53:01'),
(1032, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:29:05'),
(1033, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:31:42'),
(1034, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:31:53'),
(1035, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 13:20:06'),
(1036, 53, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Registrar Office (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 13:30:30'),
(1037, 53, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - Registrar Office (Registrar)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 15:35:32'),
(1038, 1, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to rozz opena (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 15:36:06'),
(1039, 1, 'rcopena@ccc.edu.ph', 'Logout', 'User logged out - rozz opena (Super Admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 15:37:59'),
(1040, 69, 'rcopena@ccc.edu.ph', '2FA Code Sent', '2FA verification code sent to Dci dean Dean (rcopena@ccc.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 15:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `admin_recipients_db`
--

CREATE TABLE `admin_recipients_db` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(50) NOT NULL,
  `recipient_type` enum('dean','registrar','misd') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_recipients_db`
--

INSERT INTO `admin_recipients_db` (`id`, `admin_id`, `recipient_type`) VALUES
(1, 'DEAN001', 'dean'),
(2, 'REG001', 'registrar'),
(3, 'MISD001', 'misd');

-- --------------------------------------------------------

--
-- Table structure for table `assign_curriculum`
--

CREATE TABLE `assign_curriculum` (
  `program_id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `program` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fiscal_year` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assign_curriculum`
--

INSERT INTO `assign_curriculum` (`program_id`, `curriculum_id`, `program`, `created_at`, `fiscal_year`) VALUES
(13, 50, 'BSCS', '2025-10-19 03:06:48', '2022-2023'),
(50, 17, 'BSIT', '2025-09-26 18:31:07', '2022-2023'),
(73, 23, 'BSIT', '2026-02-23 05:49:01', '2022-2023');

-- --------------------------------------------------------

--
-- Table structure for table `calendar`
--

CREATE TABLE `calendar` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `calendar`
--

INSERT INTO `calendar` (`id`, `title`, `description`, `event_date`, `created_at`, `updated_at`) VALUES
(1, 'event', 'basketball', '2025-09-13', '2025-09-12 06:42:47', '2025-09-12 06:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

CREATE TABLE `curriculum` (
  `id` int(11) NOT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `program` varchar(20) NOT NULL,
  `year_semester` varchar(10) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(200) NOT NULL,
  `lec_units` decimal(3,1) NOT NULL,
  `lab_units` decimal(3,1) NOT NULL,
  `total_units` decimal(3,1) NOT NULL,
  `prerequisites` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`id`, `fiscal_year`, `program`, `year_semester`, `course_code`, `course_title`, `lec_units`, `lab_units`, `total_units`, `prerequisites`, `created_at`, `updated_at`) VALUES
(1, '2022-2023', 'BSIT', '1-1', 'IT 101', 'Introduction to Computong with Laboratory', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(2, '2022-2023', 'BSIT', '1-1', 'CS 101', 'Fundamentals of Programming ', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-10-08 14:58:27'),
(3, '2022-2023', 'BSIT', '1-1', 'MATH 101', 'Mathematics in the Modern World', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(4, '2022-2023', 'BSIT', '1-1', 'US 101', 'Understanding the Self', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(5, '2022-2023', 'BSIT', '1-1', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-10-08 14:58:38'),
(6, '2022-2023', 'BSIT', '1-1', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-10-08 14:59:04'),
(7, '2022-2023', 'BSIT', '1-1', 'ALG 101', 'Liner Algebra', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-10-08 14:59:13'),
(8, '2022-2023', 'BSIT', '1-1', 'PATHFit 1', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, 'None', '2025-08-12 17:14:53', '2025-10-22 05:28:04'),
(9, '2022-2023', 'BSIT', '1-1', 'NSTP 101 ', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-10-08 15:04:08'),
(10, '2022-2023', 'BSIT', '1-2', 'CS 102', 'Object Oriented Programming ', 2.0, 3.0, 3.0, 'CS 101', '2025-08-12 17:14:53', '2025-10-10 04:22:41'),
(11, '2022-2023', 'BSIT', '1-2', 'IT 102', 'Information Management ', 3.0, 0.0, 3.0, 'IT101', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(12, '2022-2023', 'BSIT', '1-2', 'NET 102', 'Computer Networking 1', 2.0, 3.0, 3.0, 'IT101', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(14, '2022-2023', 'BSIT', '1-2', 'PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE101', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(15, '2022-2023', 'BSIT', '1-2', 'IT 231', 'Operating System ', 2.0, 3.0, 3.0, 'IT 101,CS 101', '2025-08-12 17:14:53', '2025-10-10 04:23:01'),
(16, '2022-2023', 'BSIT', '1-2', 'CALC 102', 'Mechanics', 3.0, 0.0, 2.0, 'MATH 101', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(17, '2022-2023', 'BSIT', '1-2', 'PATHFit 2', 'Excercised-Based Fitness Activities', 2.0, 0.0, 2.0, 'PATHFit 1', '2025-08-12 17:14:53', '2025-10-22 05:28:15'),
(18, '2022-2023', 'BSIT', '1-2', 'NSTP 102', 'National Service Training Program 2', 3.0, 0.0, 3.0, 'NSTP 101', '2025-08-12 17:14:53', '2025-10-10 04:23:17'),
(19, '2022-2023', 'BSIT', '2-1', 'DIS 201', 'Discrete Mathematics', 3.0, 0.0, 3.0, 'CALC 102', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(20, '2022-2023', 'BSIT', '2-1', 'IT 211', 'Database Management System', 2.0, 3.0, 3.0, 'IT 102', '2025-08-12 17:14:53', '2025-10-08 17:29:06'),
(21, '2022-2023', 'BSIT', '2-1', 'RPH 201', 'Readings in Philippine History', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(22, '2022-2023', 'BSIT', '2-1', 'ENV 201', 'Environmental Science', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(23, '2022-2023', 'BSIT', '2-1', 'IT 221', 'Web Design and Programming', 2.0, 3.0, 3.0, 'CS 102', '2025-08-12 17:14:53', '2025-10-08 17:29:17'),
(24, '2022-2023', 'BSIT', '2-1', 'NET 201', 'Computer Networking 2', 2.0, 3.0, 3.0, 'NET 102', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(25, '2022-2023', 'BSIT', '2-1', 'RIZAL 201', 'The Life and Works of Rizal', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(26, '2022-2023', 'BSIT', '2-1', 'ACCTG 201', 'Accounting', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(27, '2022-2023', 'BSIT', '2-1', 'PATHFit 3', ' Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities', 2.0, 0.0, 2.0, 'PATHFit 2', '2025-08-12 17:14:53', '2025-10-22 05:29:18'),
(28, '2022-2023', 'BSIT', '2-2', 'IAS 202', 'Information Assurance Security ', 2.0, 0.0, 3.0, 'NET 201', '2025-08-12 17:14:53', '2025-10-10 04:23:35'),
(29, '2022-2023', 'BSIT', '2-2', 'SAM 202', 'System Administration and Maintenance', 3.0, 3.0, 3.0, 'NET 201', '2025-08-12 17:14:53', '2025-10-10 04:23:42'),
(30, '2022-2023', 'BSIT', '2-2', 'CS 333', 'Application Development and Emerging Technologies', 2.0, 0.0, 3.0, 'IT 211,IT 221', '2025-08-12 17:14:53', '2025-10-10 04:23:50'),
(31, '2022-2023', 'BSIT', '2-2', 'LDS 202', 'Logic Design and Switching ', 3.0, 3.0, 3.0, 'NET 201', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(32, '2022-2023', 'BSIT', '2-2', 'IT 202', 'Software Engineering 1', 2.0, 0.0, 3.0, 'IT 211,IT 221', '2025-08-12 17:14:53', '2025-10-10 04:24:26'),
(33, '2022-2023', 'BSIT', '2-2', 'NET 202', 'Computer Networking 3', 3.0, 3.0, 3.0, 'NET 201', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(34, '2022-2023', 'BSIT', '2-2', 'HCI 302', 'Human Computer Interaction', 2.0, 0.0, 3.0, 'IT 211,IT 221', '2025-08-12 17:14:53', '2025-10-10 04:24:36'),
(35, '2022-2023', 'BSIT', '2-2', 'PATHFit 4', ' Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities 2', 2.0, 0.0, 2.0, 'PATHFit 3', '2025-08-12 17:14:53', '2025-10-22 05:30:37'),
(36, '2022-2023', 'BSIT', '3-1', 'AIS 301', 'Advance Information Assurance & Security', 3.0, 3.0, 3.0, 'IAS 202', '2025-08-12 17:14:53', '2025-10-10 04:24:45'),
(37, '2022-2023', 'BSIT', '3-1', ' SIA 301', 'System Integration & Architectural with Laboratory ', 2.0, 3.0, 3.0, 'SAM 202', '2025-08-12 17:14:53', '2025-10-10 04:25:10'),
(38, '2022-2023', 'BSIT', '3-1', 'IT 301', 'System Engineering 2', 3.0, 0.0, 3.0, 'IT 201,CS 333', '2025-08-12 17:14:53', '2025-10-10 04:25:26'),
(39, '2022-2023', 'BSIT', '3-1', 'NET 301', 'Computer Networking 4', 2.0, 3.0, 3.0, 'NET 202', '2025-08-12 17:14:53', '2025-10-10 04:25:40'),
(40, '2022-2023', 'BSIT', '3-1', 'CEEL 301', 'Graphics Design', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(41, '2022-2023', 'BSIT', '3-1', 'SQA 302', 'Software Quality Assurance', 3.0, 0.0, 3.0, 'IT202', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(42, '2022-2023', 'BSIT', '3-1', 'ETHICS 301', 'Professional Ethics', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(43, '2022-2023', 'BSIT', '3-1', 'TECH 301', 'Technopreneurship', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(44, '2022-2023', 'BSIT', '3-2', 'CEEL 311', 'Internet of Things', 2.0, 3.0, 3.0, 'None ', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(45, '2022-2023', 'BSIT', '3-2', 'CEEL 302', 'Multimedia', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(46, '2022-2023', 'BSIT', '3-2', 'CEEL 312', 'Cloud Computing', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(47, '2022-2023', 'BSIT', '3-2', 'CAP 302', 'Capstone Project 1', 3.0, 0.0, 3.0, 'Regular', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(48, '2022-2023', 'BSIT', '4-1', 'CAP 401', 'Capstone Project 2', 0.0, 0.0, 3.0, 'Regular', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(49, '2022-2023', 'BSIT', '4-2', 'PRAC 401', 'IT Internship (600 Hours)', 0.0, 0.0, 6.0, 'Regular', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(50, '2022-2023', 'BSCS', '1-1', 'IT 101', 'Introduction to Computing', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(51, '2022-2023', 'BSCS', '1-1', 'CS 101', 'Fundamentals of Programming ', 2.0, 3.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(52, '2022-2023', 'BSCS', '1-1', 'Math 101', 'Math in the Modern World', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(53, '2022-2023', 'BSCS', '1-1', 'US 101', 'Understanding the self', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(54, '2022-2023', 'BSCS', '1-1', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(55, '2022-2023', 'BSCS', '1-1', 'SEC 101', 'Security Awareness ', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(56, '2022-2023', 'BSCS', '1-1', 'ALG 101', 'Linear Algebra', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(57, '2022-2023', 'BSCS', '1-1', 'PATHFit 1', 'Movements Competency Training ', 2.0, 0.0, 2.0, 'None', '2025-08-12 17:40:27', '2025-10-22 04:32:52'),
(58, '2022-2023', 'BSCS', '1-1', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(59, '2022-2023', 'BSCS', '1-2', 'CS 102', 'Object Oriented Programming ', 2.0, 3.0, 3.0, 'CS101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(60, '2022-2023', 'BSCS', '1-2', 'IT 102', 'Information Management ', 2.0, 3.0, 3.0, 'IT 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(61, '2022-2023', 'BSCS', '1-2', 'IT 201', 'Data Structures and Algorithms', 2.0, 3.0, 3.0, 'CS 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(62, '2022-2023', 'BSCS', '1-2', 'NC 102', 'Network and Communication ', 3.0, 0.0, 3.0, 'IT 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(63, '2022-2023', 'BSCS', '1-2', 'NUM 102', 'Number Theory', 3.0, 0.0, 3.0, 'MATH 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(64, '2022-2023', 'BSCS', '1-2', 'PCOM 102', 'Purposive Communication ', 3.0, 0.0, 3.0, 'IE 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(65, '2022-2023', 'BSCS', '1-2', 'CALC 102', 'Mechanics', 3.0, 0.0, 3.0, 'MATH 101, ALG 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(66, '2022-2023', 'BSCS', '1-2', 'PATHFit 2', 'Excercise-Based Fitness Activities', 2.0, 0.0, 2.0, 'PATHFit 1', '2025-08-12 17:40:27', '2025-10-22 04:35:38'),
(67, '2022-2023', 'BSCS', '1-2', 'NSTP 102', 'National Service Training Program 2', 3.0, 0.0, 3.0, 'NSTP 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(68, '2022-2023', 'BSCS', '2-1', 'DIS 201', 'Discrete Mathematics ', 3.0, 0.0, 3.0, 'NUM 102', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(69, '2022-2023', 'BSCS', '2-1', 'ENV 201', 'Environmental Sciences', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(70, '2022-2023', 'BSCS', '2-1', 'RPH 201', 'Readings in the Philippine History ', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(71, '2022-2023', 'BSCS', '2-1', 'CS 201', 'Database Management System', 2.0, 3.0, 3.0, 'IT 102', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(72, '2022-2023', 'BSCS', '2-1', 'CS 211', 'Web design and Programming ', 2.0, 3.0, 3.0, 'CS 102', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(73, '2022-2023', 'BSCS', '2-1', 'CS 221', 'Programming Language ', 3.0, 0.0, 3.0, 'CS 102', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(74, '2022-2023', 'BSCS', '2-1', 'CS 231', 'Advanced Object Oriented Programming ', 2.0, 3.0, 3.0, 'CS 102', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(75, '2022-2023', 'BSCS', '2-1', 'ACCTG 201', 'Accounting ', 3.0, 0.0, 3.0, 'None', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(76, '2022-2023', 'BSCS', '2-1', 'PATHFit 3', 'Choice of Dance, Sports, Martial Arts, Group Excercise, Outdoor, and Adventure Activities', 2.0, 3.0, 2.0, 'PATHFit 2', '2025-08-12 17:40:27', '2025-10-22 04:39:28'),
(78, '2022-2023', 'BSCS', '2-2', 'CS 202', 'Software Engineering 1', 2.0, 3.0, 3.0, 'CS 201', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(79, '2022-2023', 'BSCS', '2-2', 'CS 212', 'Computer Organization', 2.0, 3.0, 3.0, 'CS 201', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(80, '2022-2023', 'BSCS', '2-2', 'CS 222', 'Operating Systems', 3.0, 0.0, 3.0, 'CS 201', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(81, '2022-2023', 'BSCS', '2-2', 'CS 232', 'Automata and Formal Languages ', 3.0, 0.0, 3.0, 'DIS 201', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(82, '2022-2023', 'BSCS', '2-2', 'CS 242', 'Human Computer Interaction ', 3.0, 0.0, 3.0, 'IE 101', '2025-08-12 17:40:27', '2025-08-12 17:40:27'),
(83, '2022-2023', 'BSCS', '2-2', 'CS 252', ' Web Systems and Technologies', 2.0, 3.0, 3.0, '\r\nCS211, CS201', '2025-08-12 17:40:27', '2025-10-22 04:53:20'),
(84, '2022-2023', 'BSCS', '2-2', 'PATHFit 4', ' Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities 2', 2.0, 0.0, 2.0, 'PATHFit 3', '2025-08-12 17:40:27', '2025-10-22 04:40:18'),
(85, '2022-2023', 'BSCS', '3-1', 'HCI 301', 'Human Computer Interaction', 3.0, 0.0, 3.0, 'CS 333', '2025-08-12 17:40:27', '2025-10-20 14:49:19'),
(86, '2022-2023', 'BSCS', '3-1', 'CS 301', 'Computer Organization & Assembly Language', 2.0, 3.0, 3.0, 'IT 201', '2025-08-12 17:40:27', '2025-10-20 14:44:05'),
(87, '2022-2023', 'BSCS', '3-1', 'CS 311', 'Software Engineering 2', 2.0, 3.0, 3.0, 'CS 232', '2025-08-12 17:40:27', '2025-11-22 10:42:55'),
(88, '2022-2023', 'BSCS', '3-1', 'SQ 301', 'Software Quality Assurance ', 3.0, 0.0, 3.0, 'CS 242', '2025-08-12 17:40:27', '2025-10-20 14:47:44'),
(89, '2022-2023', 'BSCS', '3-1', 'CS 321', 'Visual Programming', 2.0, 3.0, 3.0, 'CS 252', '2025-08-12 17:40:27', '2025-10-20 14:54:24'),
(90, '2022-2023', 'BSCS', '3-1', 'CEEL 301', 'Graphics Design', 2.0, 3.0, 3.0, '-', '2025-08-12 17:40:27', '2025-10-20 14:57:03'),
(91, '2022-2023', 'BSCS', '3-1', 'CEEL 311', 'Mobile Application Development ', 2.0, 3.0, 3.0, '-', '2025-08-12 17:40:27', '2025-10-20 14:56:44'),
(92, '2022-2023', 'BSCS', '3-2', 'CEEL 302', 'Introduction To Data Security', 3.0, 0.0, 3.0, 'NONE', '2025-08-12 17:40:27', '2025-11-22 10:44:07'),
(93, '2022-2023', 'BSCS', '3-2', 'Ethics 302 ', 'Professional Ethics ', 3.0, 0.0, 3.0, 'NONE', '2025-08-12 17:40:27', '2025-10-20 15:47:40'),
(94, '2022-2023', 'BSCS', '3-2', 'TECH 302', 'Technopreneurship ', 3.0, 0.0, 3.0, 'none', '2025-08-12 17:40:27', '2025-10-20 15:48:43'),
(95, '2022-2023', 'BSCS', '3-2', 'CS 302', 'capstone Project 1', 3.0, 0.0, 3.0, 'Regular 3rd Year', '2025-08-12 17:40:27', '2025-10-22 05:14:21'),
(96, '2022-2023', 'BSCS', '3-2', 'GCE 302', 'Global Citizenship Education ', 1.0, 0.0, 1.0, 'none', '2025-08-12 17:40:27', '2025-10-20 15:50:08'),
(100, '2022-2023', 'BSCS', '4-1', 'CS 411', 'Capstone Project 2', 3.0, 0.0, 3.0, 'Regular 4th year', '2025-08-12 17:40:27', '2025-10-22 05:14:55'),
(104, '2022-2023', 'BSCS', '4-2', 'PRAC 402', 'CS Internship (400 Hours)', 3.0, 0.0, 3.0, 'Regular 4th Year', '2025-08-12 17:40:27', '2025-10-22 05:15:28'),
(1049, '2022-2023', 'BSIT', '1-2', 'IT 201', 'Data Structures and Algorithim with Laboratory', 2.0, 3.0, 3.0, 'CS101', '2025-08-12 17:14:53', '2025-08-12 17:14:53'),
(1050, '2022-2023', 'BSCS', '3-1', 'RIZAL 301', 'The Life and Works of Rizal', 3.0, 0.0, 3.0, '-', '2025-10-20 14:59:52', '2025-12-03 14:32:53');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_db`
--

CREATE TABLE `feedback_db` (
  `id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_db`
--

INSERT INTO `feedback_db` (`id`, `is_read`, `email`, `message`, `created_at`, `submitted_at`) VALUES
(1, 1, 'rcopena@ccc.edu.ph', 'Name: rozz krewpek\nEmail: rcopena@ccc.edu.ph\nRating: ⭐⭐⭐⭐⭐ (5/5)\nFeedback: nice', '2025-12-04 14:28:39', '2025-12-04 14:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_years`
--

CREATE TABLE `fiscal_years` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fiscal_years`
--

INSERT INTO `fiscal_years` (`id`, `label`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, '2022-2023', '2022-07-18', '2023-05-22', 1, '2026-01-12 01:04:50'),
(5, '20-hgfg', '2026-02-04', '2026-02-04', 0, '2026-02-24 07:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `grades_db`
--

CREATE TABLE `grades_db` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `year` varchar(10) NOT NULL,
  `sem` varchar(10) NOT NULL,
  `final_grade` varchar(10) NOT NULL,
  `course_title` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades_db`
--

INSERT INTO `grades_db` (`id`, `student_id`, `course_code`, `year`, `sem`, `final_grade`, `course_title`, `created_at`, `updated_at`) VALUES
(18, '2022-10873', 'ALG 101', '1', '1', '2.0', 'Linear Algebra', '2025-08-13 12:04:46', '2025-11-26 10:33:03'),
(21, '2022-10873', 'IE 101', '1', '1', '2.00', 'Interactive English', '2025-08-13 12:05:41', '2025-11-22 12:25:49'),
(22, '2022-10873', 'US 101', '1', '1', '1', 'Understanding the Self', '2025-08-13 12:05:54', '2025-08-13 12:05:54'),
(24, '2022-10873', 'MATH 101', '1', '1', '1', 'Mathematics in Modern World', '2025-08-13 12:06:19', '2025-12-04 07:03:14'),
(25, '2022-10873', 'IT 101', '1', '1', '3', 'Introduction to Computing  with Laboratory', '2025-08-13 12:07:31', '2025-08-13 12:07:31'),
(30, '2022-10873                      ', 'SEC 101                      ', '1', '1', '3', 'Security Awareness ', '2025-09-24 17:54:52', '2025-11-16 17:20:24'),
(34, '2022-10873', 'PE 101', '1', '1', '2.00', 'Physical Fitness, Gymnastics and Aerobics', '2025-10-07 15:41:19', '2025-10-07 15:41:35'),
(35, '2022-10873', 'PATHFit 1', '1', '1', '5', 'Physical Fitness, Gymnastics and Aerobics', '2025-10-08 05:12:01', '2025-12-04 17:31:27'),
(36, '2022-10873', 'CS 101', '1', '1', '1.00', 'Fundamentals of Programming with Laboratory', '2025-10-08 15:11:32', '2025-11-26 10:33:11'),
(37, '2022-10873', 'NSTP 101', '1', '1', '3.00', 'National Service Training Program 1', '2025-10-08 15:16:44', '2025-12-03 00:34:59'),
(89, '2022-10973', 'IT 101', '1', '1', '1.75', 'Introduction to Computing with Laboratory', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(90, '2022-10973', 'CS 101', '1', '1', '3', 'Fundamentals of Programming ', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(91, '2022-10973', 'MATH 101', '1', '1', '3', 'Mathematics in the Modern World', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(92, '2022-10973', 'US 101', '1', '1', '1', 'Understanding the Self', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(93, '2022-10973', 'IE 101', '1', '1', '1.5', 'Interactive English', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(94, '2022-10973', 'SEC 101', '1', '1', '2.25', 'Security Awareness', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(95, '2022-10973', 'ALG 101', '1', '1', '2.00', 'Liner Algebra', '2025-10-20 14:28:58', '2025-10-30 03:34:53'),
(96, '2022-10973', 'PATHFit 1', '1', '1', '1.75', 'Movements Competency Training', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(97, '2022-10973', ' NSTP 101', '1', '1', '2', 'National Service Training Program 1', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(98, '2022-10973', 'CS 102', '1', '2', '1', 'Object Oriented Programming ', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(99, '2022-10973', 'IT 102', '1', '2', '1.75', 'Information Management ', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(100, '2022-10973', 'IT 201', '1', '2', '1.5', 'Data Stuctures and Algorithms with Laboratory', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(101, '2022-10973', 'NC 102', '1', '2', '2.75', 'Networks and Communicaiton', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(102, '2022-10973', 'NUM 102', '1', '2', '1.75', 'Number Theory', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(103, '2022-10973', 'PCOM 102', '1', '2', '2', 'Purposive Communication', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(104, '2022-10973', 'CALC 102', '1', '2', '2.00', 'Mechanics', '2025-10-20 14:28:58', '2025-11-02 19:05:40'),
(105, '2022-10973', 'PATHFit 2', '1', '2', '1', 'Exercise-Based Fitness Activities', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(106, '2022-10973', 'NSTP 102', '1', '2', '1', 'National Service Training Program 2', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(107, '2022-10973', 'DIS 201', '2', '1', '1', 'Discrete Mathematics', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(108, '2022-10973', 'ENV 201', '2', '1', '2', 'Environmental Science', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(109, '2022-10973', 'RPH 201', '2', '1', '2', 'Readings in Philippine History', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(110, '2022-10973', 'CS 201', '2', '1', '2', 'Database Management System', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(111, '2022-10973', 'CS 211', '2', '1', '2', 'Web Design and Programming', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(112, '2022-10973', 'CS 221', '2', '1', '2', 'Programming Language', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(113, '2022-10973', 'CS 231', '2', '1', '2', 'Advance Object-Oriented Programming', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(114, '2022-10973', 'ACCTG 201', '2', '1', '3', 'Accounting', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(115, '2022-10973', 'PATHFit 3', '2', '1', '3', 'Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(116, '2022-10973', 'CS 202', '2', '2', '2', 'Design and Analysis of Algorithms', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(117, '2022-10973', 'CS 212', '2', '2', '2', 'Modeling and Simulation', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(118, '2022-10973', 'CS 333', '2', '2', '2', 'Application Development and Emerging Technologies', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(119, '2022-10973', 'CS 222', '2', '2', '2', 'Theory of Automata & Formal Language', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(120, '2022-10973', 'CS 232', '2', '2', '2', 'Software Engineering 1', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(121, '2022-10973', 'CS 242', '2', '2', '2', 'Operating System', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(122, '2022-10973', 'CS 252', '2', '2', '2', 'Web Systems and Technologies', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(123, '2022-10973', 'CS 262', '2', '2', '2', 'Fundamentals of Data Science', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(124, '2022-10973', 'PATHFit 4', '2', '2', '1', 'Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(125, '2022-10973', 'HCI 301', '3', '1', '2', 'Human Computer Interaction', '2025-10-20 14:28:58', '2025-12-04 01:00:32'),
(126, '2022-10973', 'CS 301', '3', '1', '1', 'Computer Organization & Assembly Language', '2025-10-20 14:28:58', '2025-12-04 17:38:00'),
(127, '2022-10973', 'SQ 301', '3', '1', '1', 'Software Quality Assurance', '2025-10-20 14:28:58', '2025-12-04 07:02:18'),
(128, '2022-10973', 'CS 311', '3', '1', '2', 'Software Engineering 2', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(129, '2022-10973', 'CS 321', '3', '1', '2', 'Visual Programming ', '2025-10-20 14:28:58', '2025-10-20 14:28:58'),
(131, '2022-10973', 'CEEL 311', '3', '1', '1.0', 'Mobile Application Development ', '2025-10-20 14:28:58', '2025-10-30 05:04:46'),
(141, '2022-10973', 'CEEL 301', '3', '1', '1', 'Graphics Design', '2025-10-30 12:30:24', '2025-10-30 12:30:24'),
(143, '2022-10973', 'RIZAL 301', '3', '1', '2', 'The Life and Works of Rizal', '2025-12-03 14:27:01', '2025-12-03 14:34:28');

-- --------------------------------------------------------

--
-- Table structure for table `irregular_db`
--

CREATE TABLE `irregular_db` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(100) NOT NULL,
  `lec_units` decimal(3,1) NOT NULL DEFAULT 0.0,
  `lab_units` decimal(3,1) NOT NULL DEFAULT 0.0,
  `total_units` decimal(3,1) DEFAULT 0.0,
  `prerequisites` text DEFAULT NULL,
  `year_level` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `year_semester` varchar(10) DEFAULT NULL,
  `program` varchar(10) NOT NULL,
  `status` enum('pending','enrolled','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `irregular_db`
--

INSERT INTO `irregular_db` (`id`, `student_id`, `course_code`, `course_title`, `lec_units`, `lab_units`, `total_units`, `prerequisites`, `year_level`, `semester`, `year_semester`, `program`, `status`, `created_at`, `updated_at`) VALUES
(493, '2022-10873', 'ALG 101', 'Liner Algebra', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(494, '2022-10873', 'CS 101', 'Fundamentals of Programming', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(495, '2022-10873', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(496, '2022-10873', 'IT 101', 'Introduction to Computong with Laboratory', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(497, '2022-10873', 'MATH 101', 'Mathematics in the Modern World', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-04 18:02:58'),
(498, '2022-10873', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(499, '2022-10873', 'PATHFit 1', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(500, '2022-10873', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(501, '2022-10873', 'US 101', 'Understanding the Self', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(511, '2022-10973', 'ALG 101', 'Linear Algebra', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(512, '2022-10973', 'CS 101', 'Fundamentals of Programming', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(513, '2022-10973', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(514, '2022-10973', 'IT 101', 'Introduction to Computing', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(515, '2022-10973', 'Math 101', 'Math in the Modern World', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(516, '2022-10973', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(517, '2022-10973', 'PATHFit 1', 'Movements Competency Training', 2.0, 0.0, 2.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(518, '2022-10973', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(519, '2022-10973', 'US 101', 'Understanding the self', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-03 05:40:17', '2025-12-03 05:40:17'),
(520, '2022-10973', 'CALC 102', 'Mechanics', 3.0, 0.0, 2.0, 'MATH 101, ALG 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(521, '2022-10973', 'CS 102', 'Object Oriented Programming', 2.0, 3.0, 3.0, 'CS101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(522, '2022-10973', 'IT 102', 'Information Management', 3.0, 0.0, 3.0, 'IT 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(523, '2022-10973', 'IT 201', 'Data Structures and Algorithms', 2.0, 3.0, 3.0, 'CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(524, '2022-10973', 'NC 102', 'Network and Communication', 3.0, 0.0, 3.0, 'IT 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(525, '2022-10973', 'NSTP 102', 'National Service Training Program 2', 3.0, 0.0, 3.0, 'NSTP 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(526, '2022-10973', 'NUM 102', 'Number Theory', 3.0, 0.0, 3.0, 'MATH 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(527, '2022-10973', 'PATHFit 2', 'Excercise-Based Fitness Activities', 2.0, 0.0, 2.0, 'PATHFit 1', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(528, '2022-10973', 'PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-03 05:42:31', '2025-12-03 05:42:31'),
(529, '2022-10973', 'ACCTG 201', 'Accounting', 3.0, 0.0, 3.0, 'None', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(530, '2022-10973', 'CS 201', 'Database Management System', 2.0, 3.0, 3.0, 'IT 102', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(531, '2022-10973', 'CS 211', 'Web design and Programming', 2.0, 3.0, 3.0, 'CS 102', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(532, '2022-10973', 'CS 221', 'Programming Language', 3.0, 0.0, 3.0, 'CS 102', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(533, '2022-10973', 'CS 231', 'Advanced Object Oriented Programming', 2.0, 3.0, 3.0, 'CS 102', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(534, '2022-10973', 'DIS 201', 'Discrete Mathematics', 3.0, 0.0, 3.0, 'NUM 102', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(535, '2022-10973', 'ENV 201', 'Environmental Sciences', 3.0, 0.0, 3.0, 'None', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(536, '2022-10973', 'PATHFit 3', 'Choice of Dance, Sports, Martial Arts, Group Excercise, Outdoor, and Adventure Activities', 2.0, 0.0, 2.0, 'PATHFit 2', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(537, '2022-10973', 'RPH 201', 'Readings in the Philippine History', 3.0, 0.0, 3.0, 'None', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-03 05:42:44', '2025-12-03 05:42:44'),
(538, '2022-10973', 'CS 202', 'Software Engineering 1', 2.0, 3.0, 3.0, 'CS 201', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(539, '2022-10973', 'CS 212', 'Computer Organization', 2.0, 3.0, 3.0, 'CS 201', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(541, '2022-10973', 'CS 222', 'Operating Systems', 3.0, 0.0, 3.0, 'CS 201', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(542, '2022-10973', 'CS 232', 'Automata and Formal Languages', 3.0, 0.0, 3.0, 'DIS 201', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(543, '2022-10973', 'CS 242', 'Human Computer Interaction', 3.0, 0.0, 3.0, 'IE 101', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(544, '2022-10973', 'CS 252', 'Web Systems and Technologies', 2.0, 3.0, 3.0, 'CS211, CS201', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(545, '2022-10973', 'PATHFit 4', 'Choice of Dance, Sports, Martial Arts, Group Exercise, Outdoor, and Adventure Activities 2', 2.0, 0.0, 2.0, 'PATHFit 3', 2, 2, '2-2', 'BSIT', 'enrolled', '2025-12-03 05:43:00', '2025-12-03 05:43:00'),
(546, '2022-10973', 'CEEL 301', 'Graphics Design', 2.0, 3.0, 3.0, '-', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(547, '2022-10973', 'CEEL 311', 'Mobile Application Development', 2.0, 3.0, 3.0, '-', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(548, '2022-10973', 'CS 301', 'Computer Organization & Assembly Language', 2.0, 3.0, 3.0, 'IT 201', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(549, '2022-10973', 'CS 311', 'Software Engineering 2', 2.0, 3.0, 3.0, 'CS 232', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(550, '2022-10973', 'CS 321', 'Visual Programming', 2.0, 3.0, 3.0, 'CS 252', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(551, '2022-10973', 'HCI 301', 'Human Computer Interaction', 3.0, 0.0, 3.0, 'CS 333', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(552, '2022-10973', 'SQ 301', 'Software Quality Assurance', 3.0, 0.0, 3.0, 'CS 242', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 05:43:30', '2025-12-03 05:43:30'),
(553, '2022-10973', 'RIZAL 301', 'The Life and Works of Rizal', 3.0, 0.0, 3.0, '-', 3, 1, '3-1', 'BSIT', 'enrolled', '2025-12-03 14:33:22', '2025-12-03 14:33:22'),
(563, '2022-10873', 'CALC 102', 'Mechanics', 3.0, 0.0, 2.0, 'MATH 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(564, '2022-10873', 'CS 102', 'Object Oriented Programming', 2.0, 3.0, 3.0, 'CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(565, '2022-10873', 'IT 102', 'Information Management', 3.0, 0.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(566, '2022-10873', 'IT 201', 'Data Structures and Algorithim with Laboratory', 2.0, 3.0, 3.0, 'CS101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(567, '2022-10873', 'IT 231', 'Operating System', 2.0, 3.0, 3.0, 'IT 101,CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(568, '2022-10873', 'NET 102', 'Computer Networking 1', 2.0, 3.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(569, '2022-10873', 'NSTP 102', 'National Service Training Program 2', 3.0, 0.0, 3.0, 'NSTP 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(570, '2022-10873', 'PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-05 01:17:49', '2025-12-05 01:17:49'),
(572, '2022-10873', 'PATHFit 1', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, 'None', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-09 14:15:47', '2025-12-09 14:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `list_db`
--

CREATE TABLE `list_db` (
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `curriculum` varchar(50) NOT NULL,
  `classification` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `programs` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages_db`
--

CREATE TABLE `messages_db` (
  `id` int(11) NOT NULL,
  `sender_id` varchar(20) NOT NULL,
  `recipient_type` enum('student','dean','registrar','misd','admin') NOT NULL,
  `recipient_id` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages_db`
--

INSERT INTO `messages_db` (`id`, `sender_id`, `recipient_type`, `recipient_id`, `subject`, `message`, `is_read`, `created_at`, `status`) VALUES
(1, '2022-10873', 'admin', NULL, 'change password', 'can you change my password\r\n', 1, '2025-11-12 10:33:51', 'read'),
(4, '2022-10873', 'dean', NULL, 'my grades', 'can you change it', 0, '2025-11-12 14:03:09', 'read'),
(7, '2022-10873', 'registrar', NULL, 'check grades', 'can you check my grades?\r\n', 1, '2025-11-12 17:24:30', 'read'),
(10, '53', 'student', '2022-10873', 'Re: check grades', 'yes sure', 0, '2025-11-12 19:10:44', 'unread');

-- --------------------------------------------------------

--
-- Table structure for table `notifications_db`
--

CREATE TABLE `notifications_db` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications_db`
--

INSERT INTO `notifications_db` (`id`, `user_id`, `role_id`, `sender_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, '2022', NULL, NULL, 'New Reply', 'New reply from Registrar: yes sure...', 'view_message.php?id=10', 1, '2025-11-12 19:10:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `course` enum('BSIT','BSCS') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `status`, `course`, `created_at`) VALUES
(1, 'Super Admin', 'Super Administrator with full system access', 'Active', NULL, '2025-09-14 11:30:31'),
(2, 'Dean', 'Dean with administrative access', 'Active', NULL, '2025-09-14 11:30:31'),
(3, 'Registrar', 'Registrar with student records access', 'Active', NULL, '2025-09-14 11:30:31'),
(4, 'BSIT', 'Bachelor of Science in Information Technology student', 'Active', NULL, '2025-09-14 11:30:31'),
(5, 'BSCS', 'Bachelor of Science in Computer Science student', 'Active', NULL, '2025-09-14 11:30:31'),
(6, 'Student', 'Default student role', 'Active', NULL, '2025-09-14 11:30:31'),
(10, 'Staff', 'Handles administrative and support tasks', 'Active', NULL, '2026-03-03 03:26:10'),
(11, 'Program Head', 'Oversees academic program and faculty', 'Active', NULL, '2026-03-03 03:26:10');

-- --------------------------------------------------------

--
-- Table structure for table `signin_db`
--

CREATE TABLE `signin_db` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 6,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active' COMMENT 'User account status (Active/Inactive)',
  `classification` enum('Regular','Irregular') DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `last_failed_attempt` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `esignature` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signin_db`
--

INSERT INTO `signin_db` (`id`, `firstname`, `lastname`, `email`, `password`, `student_id`, `academic_year`, `course`, `role_id`, `created_at`, `updated_at`, `profile_image`, `status`, `classification`, `failed_attempts`, `last_failed_attempt`, `remember_token`, `token_expires`, `esignature`) VALUES
(1, 'rozz', 'opena', 'rcopena@ccc.edu.pht', '$2y$10$J8bqNNNiyZNRhfxG9kolBu/fpFILUTfNTalkg8jhahBLrtTSBEGTq', '1', NULL, NULL, 1, '2025-09-14 21:08:08', '2026-03-17 23:38:07', NULL, 'Active', NULL, 0, NULL, NULL, NULL, NULL),
(13, 'nico', 'krewpek', 'rzcopena@ccc.edu.ph', '$2y$10$LnUbyS6PgPPXopI/oU22WuUicXnHKGP5GC7zYL62t574CVIsfat/G', '2022-10973', '', 'BSCS', 5, '2025-09-18 02:08:34', '2026-01-28 03:31:17', NULL, 'Active', 'Regular', 1, '2026-01-28 03:28:25', NULL, NULL, NULL),
(50, 'rozz', 'KREWPEK', 'qqrcopena@ccc.edu.ph', '$2y$10$ETMgAUm4P7mpp8n3x6DL8uOwdt.7NUkUoQeE6mvxqO.JMwWvqvhbK', '2022-10873', '2022-2023', 'BSIT', 4, '2025-09-22 20:37:51', '2026-01-22 13:39:20', NULL, 'Active', 'Irregular', 0, NULL, NULL, NULL, NULL),
(53, 'Registrar', 'Office', 'rcopena@ccc.edu.phi', '$2y$10$Gy3rcxGJPiazPXC5VG1Ma.ZLKg1j7OkIf0d0QANv.AV7Tj0q.ij8O', '53', '', '', 3, '2025-11-11 22:31:49', '2026-03-17 23:35:48', NULL, 'Active', '', 0, NULL, NULL, NULL, NULL),
(69, 'Dci dean', 'Dean', 'rcopena@ccc.edu.ph', '$2y$10$vK9vIInzJH7t7SqljthJx.G0Kd/7EJMvymG9zerRoZozA2y/SlMoa', NULL, NULL, '', 2, '2026-01-22 13:02:45', '2026-03-17 23:38:11', NULL, 'Active', 'Regular', 0, NULL, NULL, NULL, NULL),
(79, 'Dwight', 'Torres', 'pcdirige@ccc.edu.ph', '$2y$10$98r24kD5pixTgxqEr.4LvufpP5eC69Ld28oO67eiIneh7357PHKv2', NULL, NULL, '', 11, '2026-03-06 01:28:25', '2026-03-06 01:28:25', NULL, 'Active', '', 0, NULL, NULL, NULL, NULL),
(82, 'Elvin', 'Reyes', 'erreyes@ccc.edu.ph', '$2y$10$d6Diy6FlHlPfULnd0W1OOO/B2agq5SEKVpGWCxDVz8wkFgNLqyl7m', '2022-10813', NULL, 'BSIT', 4, '2026-03-17 23:37:46', '2026-03-17 23:37:46', NULL, 'Active', 'Regular', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students_db`
--

CREATE TABLE `students_db` (
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `curriculum` varchar(100) DEFAULT NULL,
  `classification` enum('Regular','Irregular','Transferee') DEFAULT 'Regular',
  `programs` enum('BSIT','BSCS') DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `fiscal_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Regular','Irregular') DEFAULT 'Regular',
  `gender` enum('Male','Female','Other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_db`
--

INSERT INTO `students_db` (`student_id`, `student_name`, `email`, `curriculum`, `classification`, `programs`, `academic_year`, `fiscal_year`, `semester`, `status`, `gender`) VALUES
('2022-10813', 'Elvin Reyes', 'erreyes@ccc.edu.ph', 'BSIT (2022-2023)', 'Regular', 'BSIT', '2nd Year', '2022-2023', '1st Semester', 'Regular', 'Male'),
('2022-10873', 'rozz krewpek', 'rcopena@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2022-2023', '1', 'Regular', 'Male'),
('2022-10877', 'Puline Dungan', 'mrdungan@ccc.edu.ph', 'BSIT (2022-2023)', 'Regular', 'BSIT', '4th Year', '2022-2023', '1st Semester', 'Regular', 'Male'),
('2022-10890', 'Rey Valera', 'jdque@ccc.edu.ph', 'BSIT (2022-2023)', 'Regular', 'BSCS', '4th Year', '2022-2023', '1st Semester', 'Regular', 'Male'),
('2022-10893', 'rozz pinaka pogi', 'raider@ccc.edu.ph', 'BSCS (2022-2023)', 'Regular', 'BSIT', '4th Year', '2022-2023', '1st Semester', 'Regular', 'Male'),
('2022-10973', 'nico krewpek', 'jlmarquez@ccc.edeu.ph', 'BSCS', 'Regular', 'BSCS', '4th', '2022-2023', '1', 'Regular', 'Male'),
('2022-11111', 'juan', 'juan@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '3rd', '2021-2022', '1', 'Regular', 'Male'),
('2022-12345', 'Maria C. Santos', 'mrc@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2022-2023', '1', 'Regular', 'Male'),
('2022-12346', 'Juan M. Crus', 'jncrus@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2022-2024', '1', 'Regular', 'Male'),
('2022-12347', 'Ana L. Reyes', 'alreyes@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2020-2021', '1', 'Regular', 'Male'),
('2022-12348', 'Jose R. Bautista', 'jr@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', NULL, '2021-2022', NULL, 'Regular', NULL),
('2022-12349', 'Lea G. Fernandes', 'lgfernandes@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2021-2022', '1', 'Regular', 'Female'),
('2022-12350', 'Carlos T. Lim', 'cllim@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', NULL, '2021-2022', NULL, 'Regular', NULL),
('2022-12351', 'Sofia D. Aquino', 'sdaquino@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2022-12352', 'Antonio K. Navaro', 'aknavaro@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2020-2021', '1', 'Regular', 'Male'),
('2022-12353', 'Carmen V. Torres', 'cntorres@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2022-12354', 'Luis P. Gonsales', 'lp@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2022-13999', 'b2fg GANG', 'b2fg@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', NULL, '2', 'Regular', 'Male'),
('2022-16773', 'Ron Santos', 'ron@ccc.edu.ph', 'BSCS', 'Regular', 'BSIT', '1', NULL, '1', 'Regular', 'Male'),
('2022-17777', 'Pedro penduco', 'Dirige@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '5', '2022-2023', '1', 'Regular', 'Male'),
('2023-23456', 'Isabel S. Mercado', 'ils@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Female'),
('2023-23457', 'Miguel H. Castro', 'mgc@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2023-23458', 'Elena F. Ramos', 'efr@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2023-23459', 'Gabriel A. Santiago', 'g@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23460', 'Rosa N. Marques', 'rosa@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23461', 'Andres B. Villanueva', 'andres@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23462', 'Corason J. Rosa', 'cr@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23463', 'Manuel S. Rivera', 'mns@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23464', 'Lourdes E. Mendosa', 'len@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23465', 'Felipe K. Dison', 'fkd@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2024-34567', 'Adrian O. Tan', 'aot@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34568', 'Imelda U. Gutieres', 'iu@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Female'),
('2024-34569', 'Ramon C. Salasar', 'salasar@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34570', 'Patricia L. Alcaras', 'pat@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Female'),
('2024-34571', 'Enrico L. Valdes', 'enrico@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34572', 'Angeliata M. Coronel', 'am@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34573', 'Emilio D. Soriano', 'soriano@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34574', 'Clara F. Romero', 'romero@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34575', 'Ricardo J. Cordero', 'codero@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34576', 'Beatri F. Galang', 'galang@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Female'),
('2025-45678', 'Dante G. Ilagan', 'ilagan@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45679', 'Epera P. Macapagal', 'avad@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45680', 'Hector Q. Trinidad', 'tri@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45681', 'Lisa T. Panganiban', 'panganiban@ccc.edu.ph', 'BSIT', 'Irregular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45682', 'Arnel D. Pena', 'pena@ccc.edu.ph', 'BSIT', 'Regular', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45683', 'Maricel B. Soliman', 'maricel@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45684', 'Gregorio N. Malabanan', 'gm@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45685', 'Ligaya S. Satwina', 'ligaya@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45686', 'Rosalinda B. Magbanua', 'b@ccc.edu.ph', 'BSCS', 'Regular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45687', 'Minda D. Abad', 'abad@ccc.edu.ph', 'BSCS', 'Irregular', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male');

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_auth`
--

CREATE TABLE `two_factor_auth` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `two_factor_auth`
--

INSERT INTO `two_factor_auth` (`id`, `user_id`, `token`, `created_at`, `expires_at`, `used`) VALUES
(79, 50, '936347', '2025-11-27 01:49:41', '2025-11-27 01:54:41', 1),
(80, 50, '751199', '2025-11-27 01:52:38', '2025-11-27 01:57:38', 1),
(81, 53, '107369', '2025-11-27 05:34:27', '2025-11-27 05:39:27', 1),
(83, 50, '023676', '2025-11-27 13:40:13', '2025-11-27 13:45:13', 1),
(84, 1, '758039', '2025-11-27 14:34:50', '2025-11-27 14:39:50', 1),
(85, 1, '559431', '2025-11-28 08:54:32', '2025-11-28 08:59:32', 1),
(86, 50, '471610', '2025-11-28 10:26:57', '2025-11-28 10:31:57', 1),
(87, 13, '568252', '2025-11-28 10:47:02', '2025-11-28 10:52:02', 1),
(90, 50, '610818', '2025-11-28 11:17:02', '2025-11-28 11:22:02', 1),
(91, 13, '318317', '2025-11-28 11:21:17', '2025-11-28 11:26:17', 1),
(92, 13, '341855', '2025-11-28 11:25:15', '2025-11-28 11:30:15', 0),
(93, 13, '890321', '2025-11-28 11:25:17', '2025-11-28 11:30:17', 0),
(94, 13, '831532', '2025-11-28 11:25:18', '2025-11-28 11:30:18', 1),
(95, 13, '718391', '2025-11-28 11:45:39', '2025-11-28 11:50:39', 1),
(96, 13, '833347', '2025-11-28 11:46:44', '2025-11-28 11:51:44', 1),
(97, 13, '071275', '2025-11-28 11:47:55', '2025-11-28 11:52:55', 1),
(98, 13, '670099', '2025-11-28 11:50:05', '2025-11-28 11:55:05', 1),
(99, 13, '624714', '2025-11-28 11:51:03', '2025-11-28 11:56:03', 1),
(100, 13, '592101', '2025-11-28 11:51:42', '2025-11-28 11:56:42', 1),
(101, 13, '677981', '2025-11-28 11:52:46', '2025-11-28 11:57:46', 0),
(102, 13, '384750', '2025-11-28 11:54:26', '2025-11-28 11:59:26', 1),
(103, 13, '943175', '2025-11-28 11:57:18', '2025-11-28 12:02:18', 1),
(104, 13, '587632', '2025-11-28 13:49:19', '2025-11-28 13:54:19', 1),
(105, 13, '115851', '2025-11-28 13:55:19', '2025-11-28 14:00:19', 1),
(108, 1, '145150', '2025-11-29 04:39:38', '2025-11-29 04:44:38', 0),
(109, 1, '520678', '2025-11-29 04:39:39', '2025-11-29 04:44:39', 1),
(110, 1, '202315', '2025-11-29 09:05:37', '2025-11-29 09:10:37', 1),
(111, 1, '752895', '2025-11-29 09:17:04', '2025-11-29 09:22:04', 1),
(112, 1, '788407', '2025-11-29 15:58:30', '2025-11-29 16:03:30', 1),
(113, 1, '491937', '2025-11-29 15:58:53', '2025-11-29 16:03:53', 0),
(115, 1, '040701', '2025-11-29 16:29:04', '2025-11-29 16:34:04', 1),
(116, 1, '949279', '2025-11-30 02:01:35', '2025-11-30 02:06:35', 1),
(117, 1, '535674', '2025-11-30 03:09:35', '2025-11-30 03:14:35', 1),
(119, 53, '544454', '2025-11-30 03:24:32', '2025-11-30 03:29:32', 1),
(120, 13, '929169', '2025-11-30 10:57:18', '2025-11-30 11:02:18', 1),
(122, 13, '721471', '2025-11-30 15:12:50', '2025-11-30 15:17:50', 1),
(123, 13, '062974', '2025-11-30 15:41:18', '2025-11-30 15:46:18', 1),
(124, 50, '199597', '2025-11-30 16:02:55', '2025-11-30 16:07:55', 1),
(126, 1, '048624', '2025-12-01 00:53:02', '2025-12-01 00:58:02', 1),
(128, 50, '218036', '2025-12-01 01:47:35', '2025-12-01 01:52:35', 1),
(129, 1, '048692', '2025-12-01 04:49:36', '2025-12-01 04:54:36', 1),
(130, 50, '932973', '2025-12-01 05:13:54', '2025-12-01 05:18:54', 1),
(131, 50, '477902', '2025-12-01 05:25:20', '2025-12-01 05:30:20', 1),
(132, 50, '621403', '2025-12-01 06:28:47', '2025-12-01 06:33:47', 1),
(133, 50, '713644', '2025-12-01 06:51:38', '2025-12-01 06:56:38', 1),
(134, 1, '958444', '2025-12-01 06:56:41', '2025-12-01 07:01:41', 1),
(135, 50, '844666', '2025-12-01 07:45:16', '2025-12-01 07:50:16', 1),
(136, 1, '079735', '2025-12-01 07:52:17', '2025-12-01 07:57:17', 1),
(138, 1, '308651', '2025-12-01 09:34:44', '2025-12-01 09:39:44', 1),
(139, 1, '073547', '2025-12-02 00:22:32', '2025-12-02 00:27:32', 1),
(142, 1, '446866', '2025-12-02 06:16:26', '2025-12-02 06:21:26', 1),
(144, 1, '559384', '2025-12-02 06:42:19', '2025-12-02 06:47:19', 1),
(146, 50, '106945', '2025-12-02 08:41:29', '2025-12-02 08:46:29', 1),
(148, 50, '179907', '2025-12-02 08:51:43', '2025-12-02 08:56:43', 1),
(149, 50, '331613', '2025-12-03 00:25:39', '2025-12-03 00:30:39', 1),
(151, 53, '391159', '2025-12-03 00:34:26', '2025-12-03 00:39:26', 1),
(153, 50, '635757', '2025-12-03 01:03:40', '2025-12-03 01:08:40', 1),
(156, 53, '618013', '2025-12-03 02:36:07', '2025-12-03 02:41:07', 1),
(158, 53, '427857', '2025-12-03 03:13:37', '2025-12-03 03:18:37', 1),
(159, 50, '810051', '2025-12-03 04:13:46', '2025-12-03 04:18:46', 1),
(161, 13, '891468', '2025-12-03 05:44:56', '2025-12-03 05:49:56', 1),
(163, 50, '408340', '2025-12-03 06:20:00', '2025-12-03 06:25:00', 1),
(164, 50, '035812', '2025-12-03 06:22:21', '2025-12-03 06:27:21', 1),
(168, 1, '362267', '2025-12-03 11:41:54', '2025-12-03 11:46:54', 1),
(170, 53, '984791', '2025-12-03 13:51:07', '2025-12-03 13:56:07', 1),
(171, 53, '535731', '2025-12-03 14:14:08', '2025-12-03 14:19:08', 1),
(173, 53, '402890', '2025-12-03 14:24:41', '2025-12-03 14:29:41', 1),
(177, 53, '341603', '2025-12-04 04:15:27', '2025-12-04 04:20:27', 1),
(179, 53, '885555', '2025-12-04 05:09:14', '2025-12-04 05:14:14', 1),
(180, 1, '805870', '2025-12-04 05:51:21', '2025-12-04 05:56:21', 1),
(183, 53, '456772', '2025-12-04 06:21:53', '2025-12-04 06:26:53', 1),
(186, 50, '382206', '2025-12-04 07:05:26', '2025-12-04 07:10:26', 1),
(188, 1, '573018', '2025-12-04 08:39:58', '2025-12-04 08:44:58', 1),
(189, 53, '633620', '2025-12-04 08:50:41', '2025-12-04 08:55:41', 1),
(190, 13, '392535', '2025-12-04 09:28:02', '2025-12-04 09:33:02', 1),
(191, 50, '305676', '2025-12-04 09:33:07', '2025-12-04 09:38:07', 1),
(192, 13, '993864', '2025-12-04 09:35:57', '2025-12-04 09:40:57', 1),
(194, 13, '523311', '2025-12-04 11:59:36', '2025-12-04 12:04:36', 1),
(195, 53, '275375', '2025-12-04 12:24:58', '2025-12-04 12:29:58', 1),
(196, 53, '802811', '2025-12-04 12:32:51', '2025-12-04 12:37:51', 1),
(198, 1, '560698', '2025-12-04 13:13:17', '2025-12-04 13:18:17', 1),
(199, 53, '409055', '2025-12-04 13:16:05', '2025-12-04 13:21:05', 1),
(200, 53, '472969', '2025-12-04 13:42:36', '2025-12-04 13:47:36', 1),
(202, 13, '344004', '2025-12-04 14:24:27', '2025-12-04 14:29:27', 1),
(203, 1, '838707', '2025-12-04 14:31:30', '2025-12-04 14:36:30', 1),
(204, 1, '933293', '2025-12-04 14:43:14', '2025-12-04 14:48:14', 1),
(205, 1, '859356', '2025-12-04 16:28:09', '2025-12-04 16:33:09', 1),
(207, 50, '090013', '2025-12-04 18:20:04', '2025-12-04 18:25:04', 1),
(208, 1, '182835', '2025-12-04 22:45:45', '2025-12-04 22:50:45', 1),
(209, 1, '138205', '2025-12-04 23:00:25', '2025-12-04 23:05:25', 1),
(210, 1, '289585', '2025-12-04 23:04:26', '2025-12-04 23:09:26', 1),
(212, 1, '057374', '2025-12-05 01:33:35', '2025-12-05 01:38:35', 1),
(213, 53, '323134', '2025-12-05 01:52:11', '2025-12-05 01:57:11', 1),
(214, 1, '641257', '2025-12-05 02:08:41', '2025-12-05 02:13:41', 1),
(216, 1, '349566', '2025-12-05 02:15:32', '2025-12-05 02:20:32', 1),
(220, 1, '875408', '2026-01-15 23:57:20', '2026-01-16 00:02:20', 1),
(221, 1, '091202', '2026-01-22 11:18:54', '2026-01-22 11:23:54', 1),
(222, 53, '874263', '2026-01-22 11:52:22', '2026-01-22 11:57:22', 1),
(223, 1, '173064', '2026-01-22 12:56:58', '2026-01-22 13:01:58', 1),
(224, 1, '441948', '2026-01-22 12:57:03', '2026-01-22 13:02:03', 0),
(225, 69, '539200', '2026-01-22 13:03:48', '2026-01-22 13:08:48', 1),
(226, 50, '137471', '2026-01-22 13:27:26', '2026-01-22 13:32:26', 1),
(227, 13, '788643', '2026-01-22 13:39:42', '2026-01-22 13:44:42', 1),
(228, 13, '365258', '2026-01-27 05:25:55', '2026-01-27 05:30:55', 1),
(229, 53, '625748', '2026-01-28 03:42:22', '2026-01-28 03:47:22', 1),
(230, 53, '616476', '2026-01-29 06:56:40', '2026-01-29 07:01:40', 0),
(231, 53, '837158', '2026-01-29 07:27:59', '2026-01-29 07:32:59', 0),
(232, 53, '872057', '2026-01-30 02:46:41', '2026-01-30 02:51:41', 1),
(233, 53, '868870', '2026-01-30 03:14:46', '2026-01-30 03:19:46', 0),
(234, 1, '861754', '2026-01-30 03:15:36', '2026-01-30 03:20:36', 1),
(235, 1, '350799', '2026-02-11 13:20:16', '2026-02-11 13:25:16', 1),
(236, 53, '270803', '2026-02-11 15:11:56', '2026-02-11 15:16:56', 1),
(237, 69, '599002', '2026-02-11 15:21:35', '2026-02-11 15:26:35', 1),
(238, 1, '405854', '2026-02-12 01:42:50', '2026-02-12 01:47:50', 1),
(239, 1, '869468', '2026-02-12 05:58:38', '2026-02-12 06:03:38', 1),
(240, 1, '167461', '2026-02-13 01:44:42', '2026-02-13 01:49:42', 1),
(241, 1, '445464', '2026-02-13 01:45:11', '2026-02-13 01:50:11', 0),
(242, 1, '868343', '2026-02-16 07:24:10', '2026-02-16 07:29:10', 1),
(243, 1, '727468', '2026-02-20 00:47:42', '2026-02-20 00:52:42', 1),
(244, 53, '497448', '2026-02-20 05:30:38', '2026-02-20 05:35:38', 1),
(245, 53, '454587', '2026-02-23 05:17:11', '2026-02-23 05:22:11', 1),
(246, 1, '580335', '2026-02-23 05:31:33', '2026-02-23 05:36:33', 1),
(247, 53, '224207', '2026-02-23 05:38:54', '2026-02-23 05:43:54', 0),
(248, 53, '716693', '2026-02-23 05:40:13', '2026-02-23 05:45:13', 1),
(249, 1, '412282', '2026-02-23 05:44:03', '2026-02-23 05:49:03', 1),
(250, 69, '492228', '2026-02-23 05:47:12', '2026-02-23 05:52:12', 1),
(251, 69, '126104', '2026-02-23 23:59:51', '2026-02-24 00:04:51', 0),
(252, 69, '397315', '2026-02-24 00:03:20', '2026-02-24 00:08:20', 0),
(253, 69, '721987', '2026-02-24 00:03:50', '2026-02-24 00:08:50', 1),
(254, 1, '777429', '2026-02-24 05:25:45', '2026-02-24 05:30:45', 1),
(255, 1, '443123', '2026-02-24 05:25:49', '2026-02-24 05:30:49', 0),
(256, 1, '452166', '2026-02-24 06:14:26', '2026-02-24 06:19:26', 1),
(257, 53, '125687', '2026-02-24 06:56:45', '2026-02-24 07:01:45', 1),
(258, 1, '773816', '2026-02-25 16:28:04', '2026-02-25 16:33:04', 1),
(259, 53, '706997', '2026-02-25 18:07:13', '2026-02-25 18:12:13', 1),
(260, 1, '982610', '2026-02-25 19:04:43', '2026-02-25 19:09:43', 1),
(261, 1, '768840', '2026-02-26 01:20:15', '2026-02-26 01:25:15', 1),
(262, 1, '875386', '2026-02-26 02:29:44', '2026-02-26 02:34:44', 1),
(263, 1, '617151', '2026-02-27 02:51:47', '2026-02-27 02:56:47', 1),
(264, 1, '419236', '2026-03-03 03:21:35', '2026-03-03 03:26:35', 1),
(272, 1, '121251', '2026-03-03 05:58:47', '2026-03-03 06:03:47', 0),
(273, 1, '291884', '2026-03-03 05:59:28', '2026-03-03 06:04:28', 0),
(274, 1, '719331', '2026-03-03 06:00:08', '2026-03-03 06:05:08', 1),
(279, 53, '989828', '2026-03-05 03:52:33', '2026-03-05 03:57:33', 1),
(280, 53, '853394', '2026-03-06 00:35:26', '2026-03-06 00:40:26', 1),
(281, 1, '901360', '2026-03-06 01:17:10', '2026-03-06 01:22:10', 1),
(283, 1, '413798', '2026-03-06 01:22:30', '2026-03-06 01:27:30', 1),
(284, 1, '511318', '2026-03-06 01:31:40', '2026-03-06 01:36:40', 0),
(285, 79, '949244', '2026-03-06 01:31:54', '2026-03-06 01:36:54', 1),
(286, 53, '916525', '2026-03-06 01:36:17', '2026-03-06 01:41:17', 0),
(287, 53, '854681', '2026-03-06 01:37:39', '2026-03-06 01:42:39', 1),
(288, 69, '451789', '2026-03-06 01:45:00', '2026-03-06 01:50:00', 1),
(289, 53, '936996', '2026-03-06 01:50:38', '2026-03-06 01:55:38', 1),
(290, 69, '727468', '2026-03-06 02:06:00', '2026-03-06 02:11:00', 1),
(291, 53, '089047', '2026-03-06 03:38:10', '2026-03-06 03:43:10', 1),
(292, 53, '969393', '2026-03-06 06:06:19', '2026-03-06 06:11:19', 1),
(293, 1, '144569', '2026-03-08 00:00:42', '2026-03-08 00:05:42', 1),
(294, 1, '367746', '2026-03-12 06:13:27', '2026-03-12 06:18:27', 0),
(295, 1, '248825', '2026-03-12 06:18:21', '2026-03-12 06:23:21', 1),
(296, 53, '452516', '2026-03-12 06:19:37', '2026-03-12 06:24:37', 1),
(297, 69, '508996', '2026-03-12 06:25:58', '2026-03-12 06:30:58', 1),
(298, 69, '830191', '2026-03-12 06:27:16', '2026-03-12 06:32:16', 1),
(299, 1, '460038', '2026-03-12 06:46:17', '2026-03-12 06:51:17', 0),
(300, 1, '111009', '2026-03-12 06:55:08', '2026-03-12 07:00:08', 1),
(301, 1, '654040', '2026-03-12 08:28:55', '2026-03-12 08:33:55', 0),
(302, 1, '399906', '2026-03-12 08:29:27', '2026-03-12 08:34:27', 1),
(303, 53, '890397', '2026-03-12 08:47:07', '2026-03-12 08:52:07', 1),
(304, 1, '069745', '2026-03-12 08:50:58', '2026-03-12 08:55:58', 1),
(305, 69, '201252', '2026-03-12 08:54:38', '2026-03-12 08:59:38', 1),
(306, 53, '273408', '2026-03-17 07:45:02', '2026-03-17 00:50:02', 0),
(308, 53, '631345', '2026-03-17 07:49:37', '2026-03-17 07:54:37', 0),
(309, 53, '631984', '2026-03-17 07:57:01', '2026-03-17 08:02:01', 1),
(310, 53, '692838', '2026-03-17 08:17:58', '2026-03-17 08:22:58', 1),
(311, 53, '266935', '2026-03-17 08:47:27', '2026-03-17 08:52:27', 1),
(312, 53, '496656', '2026-03-17 08:51:41', '2026-03-17 08:56:41', 0),
(313, 53, '286867', '2026-03-17 08:52:32', '2026-03-17 08:57:32', 1),
(314, 53, '815759', '2026-03-17 09:29:00', '2026-03-17 09:34:00', 1),
(315, 1, '464881', '2026-03-17 09:31:47', '2026-03-17 09:36:47', 1),
(316, 53, '324968', '2026-03-17 13:30:21', '2026-03-17 13:35:21', 1),
(317, 1, '189594', '2026-03-17 15:36:01', '2026-03-17 15:41:01', 1),
(318, 69, '732009', '2026-03-17 15:38:16', '2026-03-17 15:43:16', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log_db`
--
ALTER TABLE `activity_log_db`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `admin_recipients_db`
--
ALTER TABLE `admin_recipients_db`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assign_curriculum`
--
ALTER TABLE `assign_curriculum`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `unique_assignment` (`program_id`,`curriculum_id`);

--
-- Indexes for table `calendar`
--
ALTER TABLE `calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curriculum_program` (`program`),
  ADD KEY `idx_curriculum_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_curriculum_year_semester` (`year_semester`),
  ADD KEY `idx_curriculum_subject_code` (`course_code`);

--
-- Indexes for table `feedback_db`
--
ALTER TABLE `feedback_db`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_label` (`label`);

--
-- Indexes for table `grades_db`
--
ALTER TABLE `grades_db`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `irregular_db`
--
ALTER TABLE `irregular_db`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_irregular_subject` (`student_id`,`course_code`,`year_level`,`semester`,`program`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `semester` (`semester`);

--
-- Indexes for table `messages_db`
--
ALTER TABLE `messages_db`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `notifications_db`
--
ALTER TABLE `notifications_db`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `signin_db`
--
ALTER TABLE `signin_db`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_student_id` (`student_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `students_db`
--
ALTER TABLE `students_db`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `uniq_student_id` (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log_db`
--
ALTER TABLE `activity_log_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1041;

--
-- AUTO_INCREMENT for table `admin_recipients_db`
--
ALTER TABLE `admin_recipients_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assign_curriculum`
--
ALTER TABLE `assign_curriculum`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1053;

--
-- AUTO_INCREMENT for table `feedback_db`
--
ALTER TABLE `feedback_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grades_db`
--
ALTER TABLE `grades_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `irregular_db`
--
ALTER TABLE `irregular_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=573;

--
-- AUTO_INCREMENT for table `messages_db`
--
ALTER TABLE `messages_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications_db`
--
ALTER TABLE `notifications_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `signin_db`
--
ALTER TABLE `signin_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=319;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages_db`
--
ALTER TABLE `messages_db`
  ADD CONSTRAINT `messages_db_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `signin_db` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications_db`
--
ALTER TABLE `notifications_db`
  ADD CONSTRAINT `notifications_db_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `signin_db`
--
ALTER TABLE `signin_db`
  ADD CONSTRAINT `signin_db_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `two_factor_auth`
--
ALTER TABLE `two_factor_auth`
  ADD CONSTRAINT `two_factor_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `signin_db` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
