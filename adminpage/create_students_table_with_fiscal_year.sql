-- Drop the table if it already exists
DROP TABLE IF EXISTS students;

-- Create the students table with fiscal year support
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE,
    program VARCHAR(10) NOT NULL, -- e.g., 'BSIT', 'BSCS'
    year_level ENUM('1st', '2nd', '3rd', '4th') NOT NULL,
    status ENUM('Regular', 'Irregular') NOT NULL DEFAULT 'Regular',
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    fiscal_year VARCHAR(9) NOT NULL, -- e.g., '2024-2025'
    semester ENUM('1st', '2nd', 'Summer') NOT NULL,
    date_enrolled DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Add indexes for better query performance
    INDEX idx_student_id (student_id),
    INDEX idx_program (program),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status),
    INDEX idx_year_level (year_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data with fiscal years
INSERT INTO students 
(student_id, first_name, last_name, email, program, year_level, status, gender, fiscal_year, semester, date_enrolled)
VALUES 
('2023-0001', 'Juan', 'Dela Cruz', 'juan.delacruz@example.com', 'BSIT', '2nd', 'Regular', 'Male', '2023-2024', '1st', '2023-06-01'),
('2023-0002', 'Maria', 'Santos', 'maria.santos@example.com', 'BSCS', '3rd', 'Regular', 'Female', '2023-2024', '1st', '2023-06-02'),
('2024-0001', 'Pedro', 'Reyes', 'pedro.reyes@example.com', 'BSIT', '1st', 'Regular', 'Male', '2024-2025', '1st', '2024-06-01'),
('2024-0002', 'Ana', 'Lopez', 'ana.lopez@example.com', 'BSCS', '1st', 'Regular', 'Female', '2024-2025', '1st', '2024-06-02');

-- Create a view for current fiscal year students
CREATE OR REPLACE VIEW current_fiscal_year_students AS
SELECT * FROM students 
WHERE fiscal_year = (
    SELECT CONCAT(
        YEAR(CURDATE()), 
        '-', 
        YEAR(CURDATE()) + 1
    )
    WHERE MONTH(CURDATE()) >= 6
    UNION
    SELECT CONCAT(
        YEAR(CURDATE()) - 1, 
        '-', 
        YEAR(CURDATE())
    )
    WHERE MONTH(CURDATE()) < 6
    LIMIT 1
);

-- Create a function to get the current fiscal year
DELIMITER //
CREATE FUNCTION get_current_fiscal_year() 
RETURNS VARCHAR(9)
DETERMINISTIC
BEGIN
    DECLARE current_fy VARCHAR(9);
    
    IF MONTH(CURDATE()) >= 6 THEN
        SET current_fy = CONCAT(YEAR(CURDATE()), '-', YEAR(CURDATE()) + 1);
    ELSE
        SET current_fy = CONCAT(YEAR(CURDATE()) - 1, '-', YEAR(CURDATE()));
    END IF;
    
    RETURN current_fy;
END //
DELIMITER ;

-- Example query to get students by fiscal year
-- SELECT * FROM students WHERE fiscal_year = get_current_fiscal_year();
