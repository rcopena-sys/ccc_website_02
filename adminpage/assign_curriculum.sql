-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 02, 2025 at 08:05 AM
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
-- Table structure for table `assign_curriculum`
--

CREATE TABLE `assign_curriculum` (
  `program_id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `program` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fiscal_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assign_curriculum`
--

INSERT INTO `assign_curriculum` (`program_id`, `curriculum_id`, `program`, `created_at`, `fiscal_year`) VALUES
(13, 50, 'BSCS', '2025-10-19 03:06:48', 2022),
(50, 17, 'BSIT', '2025-09-26 18:31:07', 2022);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assign_curriculum`
--
ALTER TABLE `assign_curriculum`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `unique_assignment` (`program_id`,`curriculum_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assign_curriculum`
--
ALTER TABLE `assign_curriculum`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
