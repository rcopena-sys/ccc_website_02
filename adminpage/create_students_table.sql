-- This script drops the existing students_db table and creates a new one.
-- WARNING: All existing data in the students_db table will be deleted.

DROP TABLE IF EXISTS `students_db`;

CREATE TABLE `students_db` (
  `student_id` VARCHAR(20) NOT NULL,
  `student_name` VARCHAR(255) NOT NULL,
  `curriculum` VARCHAR(100) DEFAULT NULL,
  `classification` ENUM('Regular', 'Irregular', 'Transferee') DEFAULT 'Regular',
  `category` VARCHAR(100) DEFAULT NULL,
  `programs` ENUM('BSIT', 'BSCS') DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `fiscal_year` VARCHAR(20) DEFAULT NULL,
  `semester` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('Regular', 'Irregular') DEFAULT 'Regular',
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

