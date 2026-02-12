-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 07, 2025 at 06:25 PM
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
(493, '2022-10873', 'ALG 101', 'Liner Algebra', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(494, '2022-10873', 'CS 101', 'Fundamentals of Programming', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(495, '2022-10873', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(496, '2022-10873', 'IT 101', 'Introduction to Computong with Laboratory', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(497, '2022-10873', 'MATH 101', 'Mathematics in the Modern World', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(498, '2022-10873', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(499, '2022-10873', 'PATHFit 1', 'Physical Fitness, Gymnastics and Aerobics', 2.0, 0.0, 2.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(500, '2022-10873', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(501, '2022-10873', 'US 101', 'Understanding the Self', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-02 08:46:19', '2025-12-02 08:46:19'),
(502, '2022-10873', 'CS 102', 'Object Oriented Programming', 2.0, 3.0, 3.0, 'CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(503, '2022-10873', 'IT 102', 'Information Management', 3.0, 0.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(504, '2022-10873', 'IT 201', 'Data Structures and Algorithim with Laboratory', 2.0, 3.0, 3.0, 'CS101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(505, '2022-10873', 'IT 231', 'Operating System', 2.0, 3.0, 3.0, 'IT 101,CS 101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(506, '2022-10873', 'NET 102', 'Computer Networking 1', 2.0, 3.0, 3.0, 'IT101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(507, '2022-10873', 'PATHFit 2', 'Excercised-Based Fitness Activities', 2.0, 0.0, 2.0, 'PATHFit 1', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(508, '2022-10873', 'PCOM 102', 'Purposive Communication', 3.0, 0.0, 3.0, 'IE101', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(509, '2022-10873', 'ACCTG 201', 'Accounting', 3.0, 0.0, 3.0, 'None', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
(510, '2022-10873', 'ENV 201', 'Environmental Science', 3.0, 0.0, 3.0, 'None', 1, 2, '1-2', 'BSIT', 'enrolled', '2025-12-02 08:47:16', '2025-12-02 08:47:16'),
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
(553, '2022-10697', 'ALG 101', 'Linear Algebra', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:24'),
(554, '2022-10697', 'CS 101', 'Fundamentals of Programming', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(555, '2022-10697', 'IE 101', 'Interactive English', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(556, '2022-10697', 'IT 101', 'Introduction to Computing', 2.0, 3.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(557, '2022-10697', 'Math 101', 'Math in the Modern World', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(558, '2022-10697', 'NSTP 101', 'National Service Training Program 1', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(559, '2022-10697', 'PATHFit 1', 'Movements Competency Training', 2.0, 0.0, 2.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(560, '2022-10697', 'SEC 101', 'Security Awareness', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(561, '2022-10697', 'US 101', 'Understanding the self', 3.0, 0.0, 3.0, 'None', 1, 1, '1-1', 'BSIT', 'enrolled', '2025-12-05 01:10:24', '2025-12-05 01:10:25'),
(562, '2022-10873', 'RIZAL 201', 'The Life and Works of Rizal', 3.0, 0.0, 3.0, 'None', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-05 01:19:10', '2025-12-05 01:19:10'),
(563, '2022-10873', 'RPH 201', 'Readings in Philippine History', 3.0, 0.0, 3.0, 'None', 2, 1, '2-1', 'BSIT', 'enrolled', '2025-12-05 01:19:10', '2025-12-05 01:19:10');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=564;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
