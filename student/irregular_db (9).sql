-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 03, 2025 at 04:48 AM
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
(520, '2022-10873', 'ALG 101', 'Liner Algebra', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(521, '2022-10873', 'CS 101', 'Fundamentals of Programming', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(522, '2022-10873', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(523, '2022-10873', 'IT 101', 'Introduction to Computong with Laboratory', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(524, '2022-10873', 'MATH 101', 'Mathematics in the Modern World', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(525, '2022-10873', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(526, '2022-10873', 'PATHFit 1', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(527, '2022-10873', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(528, '2022-10873', 'US 101', 'Understanding the Self', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-01 14:17:48', '2025-12-01 14:17:48'),
(529, '2022-10873', 'CALC 102', 'Mechanics', 3.0, 0.0, 2.0, 'MATH 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(530, '2022-10873', 'CS 102', 'Object Oriented Programming', 2.0, 3.0, 3.0, 'CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(531, '2022-10873', 'IT 102', 'Information Management', 3.0, 0.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(532, '2022-10873', 'IT 201', 'Data Structures and Algorithim with Laboratory', 2.0, 3.0, 3.0, 'CS101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(533, '2022-10873', 'IT 231', 'Operating System', 2.0, 3.0, 3.0, 'IT 101,CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(534, '2022-10873', 'NET 102', 'Computer Networking 1', 2.0, 3.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(535, '2022-10873', 'NSTP 102', 'National Service Training Program 2', 3.0, 0.0, 3.0, 'NSTP 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05'),
(536, '2022-10873', 'PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-01 14:30:05', '2025-12-01 14:30:05');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `irregular_db`
--
ALTER TABLE `irregular_db`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=537;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
