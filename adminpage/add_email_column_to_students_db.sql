-- Add email column to students_db table
ALTER TABLE `students_db` ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `student_name`;
