-- Select the database
USE ccc_curriculum_evaluation;

-- First, create a backup of the current data
CREATE TABLE IF NOT EXISTS students_db_backup AS SELECT * FROM students_db;

-- Create a new table with the correct schema
CREATE TABLE students_db_new (
    student_id VARCHAR(20) PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    curriculum VARCHAR(100) NOT NULL,
    classification VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL,
    programs VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    status VARCHAR(50),
    gender VARCHAR(20)
);

-- Copy data from old table to new table with formatted student_id
INSERT INTO students_db_new (
    student_id,
    student_name,
    curriculum,
    classification,
    category,
    programs,
    created_at,
    updated_at,
    academic_year,
    semester,
    status,
    gender
)
SELECT 
    CONCAT('2022-', LPAD(student_id, 5, '0')),
    student_name,
    curriculum,
    classification,
    category,
    programs,
    created_at,
    updated_at,
    academic_year,
    semester,
    status,
    gender
FROM students_db;

-- Drop the old table
DROP TABLE students_db;

-- Rename new table to original name
RENAME TABLE students_db_new TO students_db;

-- Verify the changes
SELECT * FROM students_db LIMIT 10;
