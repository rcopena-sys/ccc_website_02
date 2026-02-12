-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2025 at 01:52 PM
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
-- Table structure for table `evaluation_signatures`
--

CREATE TABLE `evaluation_signatures` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `evaluator_id` int(11) NOT NULL COMMENT 'ID of the admin/staff who evaluated',
  `year_semester` varchar(10) NOT NULL COMMENT 'Format: 1-1, 1-2, 2-1, etc.',
  `signature_filename` varchar(255) DEFAULT NULL COMMENT 'E-signature filename',
  `evaluation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `evaluation_signatures`
--
ALTER TABLE `evaluation_signatures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_evaluation` (`student_id`,`evaluator_id`,`year_semester`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_evaluator_id` (`evaluator_id`),
  ADD KEY `idx_year_semester` (`year_semester`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluation_signatures`
--
ALTER TABLE `evaluation_signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
