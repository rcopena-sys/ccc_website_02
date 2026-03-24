-- This script creates the `signin_db` table with the correct structure for student accounts.
-- Run this in your database (e.g., via phpMyAdmin) to fix login and registration issues.

CREATE TABLE IF NOT EXISTS `signin_db` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(50) NOT NULL,
  `lastname` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `student_number` VARCHAR(20) NOT NULL UNIQUE,
  `academic_year` VARCHAR(20) NOT NULL,
  `course` VARCHAR(50) NOT NULL,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
); 