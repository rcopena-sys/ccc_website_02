--
-- Database: `ccc_curriculum_evaluation`
--
CREATE DATABASE IF NOT EXISTS `ccc_curriculum_evaluation` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ccc_curriculum_evaluation`;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_subjects`
--
-- This table stores the details for each subject in the curriculum.
--

CREATE TABLE `curriculum_subjects` (
  `subject_code` varchar(20) NOT NULL,
  `subject_title` varchar(100) NOT NULL,
  `lecture_units` decimal(3,1) NOT NULL DEFAULT 0.0,
  `lab_units` decimal(3,1) NOT NULL DEFAULT 0.0,
  `units` decimal(3,1) NOT NULL,
  `prerequisites` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `curriculum_subjects`
--

INSERT INTO `curriculum_subjects` (`subject_code`, `subject_title`, `lecture_units`, `lab_units`, `units`, `prerequisites`) VALUES
('CS101', 'Discrete Structures', '3.0', '0.0', '3.0', 'None'),
('IT101', 'Introduction to Computing', '2.0', '1.0', '3.0', 'None'),
('IT102', 'Programming 1', '2.0', '1.0', '3.0', 'IT101'),
('IT103', 'Programming 2', '2.0', '1.0', '3.0', 'IT102');

-- --------------------------------------------------------

--
-- Table structure for table `grades_db`
--
-- This table stores individual student grades for each course they are enrolled in.
-- It also contains denormalized student information.
--

CREATE TABLE `grades_db` (
  `student_id` varchar(20) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `program` varchar(50) NOT NULL,
  `major` varchar(50) DEFAULT NULL,
  `year_level` varchar(10) NOT NULL,
  `year` int(4) NOT NULL,
  `sem` varchar(10) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(100) NOT NULL,
  `lec` decimal(3,1) DEFAULT 0.0,
  `lab` decimal(3,1) DEFAULT 0.0,
  `units` decimal(3,1) NOT NULL,
  `final_grade` varchar(5) DEFAULT NULL,
  `professor` varchar(100) DEFAULT NULL,
  `pre_req` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `grades_db`
--

INSERT INTO `grades_db` (`student_id`, `last_name`, `first_name`, `middle_name`, `program`, `major`, `year_level`, `year`, `sem`, `course_code`, `course_title`, `lec`, `lab`, `units`, `final_grade`, `professor`, `pre_req`) VALUES
('2022-00123', 'DELA CRUZ', 'JUAN', 'P.', 'BSIT', 'Web Development', '1st', 2022, '1st', 'IT101', 'Introduction to Computing', '2.0', '1.0', '3.0', '1.75', 'Dr. Smith', 'None'),
('2022-00123', 'DELA CRUZ', 'JUAN', 'P.', 'BSIT', 'Web Development', '1st', 2022, '1st', 'IT102', 'Programming 1', '2.0', '1.0', '3.0', '2.00', 'Prof. Jones', 'IT101'),
('2022-00123', 'DELA CRUZ', 'JUAN', 'P.', 'BSIT', 'Web Development', '1st', 2022, '2nd', 'IT103', 'Programming 2', '2.0', '1.0', '3.0', '1.50', 'Prof. Davis', 'IT102');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  ADD PRIMARY KEY (`subject_code`);

--
-- Indexes for table `grades_db`
--
ALTER TABLE `grades_db`
  ADD PRIMARY KEY (`student_id`,`course_code`,`year`,`sem`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */; 