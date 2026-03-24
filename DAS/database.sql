-- Create database
CREATE DATABASE IF NOT EXISTS ccc_curriculum;
USE ccc_curriculum;

-- Create curriculum table
CREATE TABLE IF NOT EXISTS curriculum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    semester VARCHAR(10) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_title VARCHAR(200) NOT NULL,
    lecture_units DECIMAL(3,1) NOT NULL,
    lab_units DECIMAL(3,1) NOT NULL,
    total_units DECIMAL(3,1) NOT NULL,
    prerequisites TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create index for faster queries
CREATE INDEX idx_curriculum_student ON curriculum(student_id);
CREATE INDEX idx_curriculum_year_sem ON curriculum(year, semester);
CREATE INDEX idx_curriculum_course ON curriculum(course_code);

-- Create sample data
INSERT INTO curriculum (student_id, year, semester, course_code, course_title, lecture_units, lab_units, total_units, prerequisites) VALUES
('2020-0001', 2020, '1', 'IT101', 'Introduction to Information Technology', 3.0, 0.0, 3.0, NULL),
('2020-0001', 2020, '2', 'IT102', 'Programming Fundamentals', 2.0, 1.0, 3.0, 'IT101'),
('2020-0002', 2020, '1', 'IT101', 'Introduction to Information Technology', 3.0, 0.0, 3.0, NULL),
('2020-0003', 2020, '1', 'IT101', 'Introduction to Information Technology', 3.0, 0.0, 3.0, NULL),
('2020-0001', 2021, '1', 'IT201', 'Database Systems', 3.0, 1.0, 4.0, 'IT102'),
('2020-0001', 2021, '1', 'IT202', 'Web Development', 2.0, 2.0, 4.0, 'IT102');
