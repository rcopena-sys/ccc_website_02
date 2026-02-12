-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 22, 2025 at 08:10 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u353705507_ccc_cureval`
--

-- --------------------------------------------------------

--
-- Table structure for table `students_db`
--

CREATE TABLE `students_db` (
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `curriculum` varchar(100) DEFAULT NULL,
  `classification` enum('Regular','Irregular','Transferee') DEFAULT 'Regular',
  `category` varchar(100) DEFAULT NULL,
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

INSERT INTO `students_db` (`student_id`, `student_name`, `curriculum`, `classification`, `category`, `programs`, `academic_year`, `fiscal_year`, `semester`, `status`, `gender`) VALUES
('2022-10873', 'rozz krewpek', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2022-2023', '1', 'Regular', 'Male'),
('2022-12345', 'Maria C. Santos', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2022-2023', '1', 'Regular', 'Male'),
('2022-12346', 'Juan M. Crus', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2022-2024', '1', 'Regular', 'Male'),
('2022-12347', 'Ana L. Reyes', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2020-2021', '1', 'Regular', 'Male'),
('2022-12348', 'Jose R. Bautista', 'BSIT', 'Irregular', 'BSIT', 'BSIT', NULL, '2021-2022', NULL, 'Regular', NULL),
('2022-12349', 'Lea G. Fernandes', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2021-2022', '1', 'Regular', 'Female'),
('2022-12350', 'Carlos T. Lim', 'BSCS', 'Irregular', 'BSCS', 'BSCS', NULL, '2021-2022', NULL, 'Regular', NULL),
('2022-12351', 'Sofia D. Aquino', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2022-12352', 'Antonio K. Navaro', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2020-2021', '1', 'Regular', 'Male'),
('2022-12353', 'Carmen V. Torres', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2022-12354', 'Luis P. Gonsales', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2021-2022', '1', 'Regular', 'Male'),
('2023-23456', 'Isabel S. Mercado', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Female'),
('2023-23457', 'Miguel H. Castro', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2023-23458', 'Elena F. Ramos', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2023-23459', 'Gabriel A. Santiago', 'BSIT', 'Irregular', 'BSIT', 'BSIT', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23460', 'Rosa N. Marques', 'BSIT', 'Regular', 'BSIT', 'BSIT', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23461', 'Andres B. Villanueva', 'BSCS', 'Irregular', 'BSCS', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23462', 'Corason J. Rosa', 'BSCS', 'Regular', 'BSCS', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23463', 'Manuel S. Rivera', 'BSCS', 'Irregular', 'BSCS', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23464', 'Lourdes E. Mendosa', 'BSCS', 'Regular', 'BSCS', 'BSCS', NULL, '2024-2025', NULL, 'Regular', NULL),
('2023-23465', 'Felipe K. Dison', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2024-34567', 'Adrian O. Tan', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34568', 'Imelda U. Gutieres', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Female'),
('2024-34569', 'Ramon C. Salasar', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34570', 'Patricia L. Alcaras', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Female'),
('2024-34571', 'Enrico L. Valdes', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34572', 'Angeliata M. Coronel', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34573', 'Emilio D. Soriano', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34574', 'Clara F. Romero', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34575', 'Ricardo J. Cordero', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Male'),
('2024-34576', 'Beatri F. Galang', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2023-2024', '1', 'Regular', 'Female'),
('2025-45678', 'Dante G. Ilagan', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45679', 'Epera P. Macapagal', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45680', 'Hector Q. Trinidad', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45681', 'Lisa T. Panganiban', 'BSIT', 'Irregular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45682', 'Arnel D. Pena', 'BSIT', 'Regular', 'BSIT', 'BSIT', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45683', 'Maricel B. Soliman', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45684', 'Gregorio N. Malabanan', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45685', 'Ligaya S. Satwina', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45686', 'Rosalinda B. Magbanua', 'BSCS', 'Regular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male'),
('2025-45687', 'Minda D. Abad', 'BSCS', 'Irregular', 'BSCS', 'BSCS', '1', '2024-2025', '1', 'Regular', 'Male');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students_db`
--
ALTER TABLE `students_db`
  ADD PRIMARY KEY (`student_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
