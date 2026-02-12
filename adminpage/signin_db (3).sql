-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 07:05 AM
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
(1, 'rozz', 'opena', 'rcopena@ccc.edu.ph', '$2y$10$CVaZwdMLn81PRqV6yXP0q.wSkuj9MNk48x9Hb5Vycxau/dmBUIL6u', '1', NULL, NULL, 1, '2025-09-14 21:08:08', '2025-12-02 09:01:54', NULL, 'Active', NULL, 0, NULL, NULL, NULL, NULL),
(12, 'Dci', 'Dean', 'dane@ccc.edu.ph', '$2y$10$HJLtKw3y3QMdf6xUCAwJveyGd87ACVaySlN4u.dSpXDJTjhhbxbgq', '', NULL, '', 2, '2025-09-17 13:26:38', '2025-12-02 10:07:05', NULL, 'Active', NULL, 0, NULL, NULL, NULL, 'esign_12_1764605539.png'),
(13, 'nico', 'krewpek', 'nico@ccc.edu.ph', '$2y$10$LnUbyS6PgPPXopI/oU22WuUicXnHKGP5GC7zYL62t574CVIsfat/G', '2022-10973', '', 'BSCS', 5, '2025-09-18 02:08:34', '2025-11-30 23:19:32', NULL, 'Active', 'Regular', 0, NULL, NULL, NULL, NULL),
(50, 'rozz', 'KREWPEK', 'roz@ccc.edu.ph', '$2y$10$ETMgAUm4P7mpp8n3x6DL8uOwdt.7NUkUoQeE6mvxqO.JMwWvqvhbK', '2022-10873', '2022-2023', 'BSIT', 4, '2025-09-22 20:37:51', '2025-12-01 10:07:15', NULL, 'Active', 'Irregular', 0, NULL, NULL, NULL, NULL),
(53, 'vince', 'B2FG', 'vince@ccc.edu.ph', '$2y$10$B6Fv7mtKnvJLf0P4RwKH5ejE9.jaJLDryuwMvqXDyLtSnyAJONH8e', '53', '', '', 3, '2025-11-11 22:31:49', '2025-11-27 13:30:52', NULL, 'Active', '', 0, NULL, NULL, NULL, NULL),
(57, 'Rozz', 'Yawa', 'lulu@ccc.edu.ph', '$2y$10$rK3fn7SO5o4EOpuSKD8nKu8EKmUSuL1NKIfHVoWNJ9xSvEQ.uIlHG', NULL, NULL, '', 8, '2025-11-29 16:24:27', '2025-11-29 16:24:27', NULL, 'Active', '', 0, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `signin_db`
--
ALTER TABLE `signin_db`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_student_id` (`student_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `signin_db`
--
ALTER TABLE `signin_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `signin_db`
--
ALTER TABLE `signin_db`
  ADD CONSTRAINT `signin_db_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
