--
-- Database: `ccc_curriculum_evaluation`
--

-- --------------------------------------------------------

--
-- Table structure for table `list_db`
--
-- This table stores student information, including their assigned curriculum.
-- Note: The application's PHP scripts currently reference a table named `students_db`.
-- To use this `list_db` table, you will need to update the table name in files like
-- `addstu.php`, `bulk.php`, and `student_curriculum.php`.
--

CREATE TABLE `list_db` (
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `curriculum` varchar(50) DEFAULT NULL,
  `classification` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `programs` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `list_db`
--

INSERT INTO `list_db` (`student_id`, `student_name`, `curriculum`, `classification`, `category`, `programs`) VALUES
('2023-00001', 'John Doe', 'BSIT 2022', 'Freshman', 'Regular', 'BSIT'),
('2023-00002', 'Jane Smith', 'BSCS 2022', 'Sophomore', 'Regular', 'BSCS');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `list_db`
--
ALTER TABLE `list_db`
  ADD PRIMARY KEY (`student_id`);
COMMIT; 