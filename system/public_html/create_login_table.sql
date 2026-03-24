-- Create login table
-- Drop existing tables if they exist (be careful with this in production!)
DROP TABLE IF EXISTS signin_db;
DROP TABLE IF EXISTS roles;

-- First create the roles table
CREATE TABLE roles (
    role_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default roles
INSERT INTO roles (role_id, role_name, description) VALUES
(1, 'Super Admin', 'Super Administrator with full system access'),
(2, 'Dean', 'Dean with administrative access'),
(3, 'Registrar', 'Registrar with student records access'),
(4, 'BSIT', 'Bachelor of Science in Information Technology student'),
(5, 'BSCS', 'Bachelor of Science in Computer Science student'),
(6, 'Student', 'Default student role');

-- Then create the signin_db table
CREATE TABLE signin_db (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_id VARCHAR(50) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    course VARCHAR(50) DEFAULT NULL,
    role_id INT NOT NULL DEFAULT 6, -- Default to general student role (role_id = 6)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    profile_image VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY unique_email (email),
    UNIQUE KEY unique_student_id (student_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- Add indexes for better performance
CREATE INDEX idx_firstname ON signin_db(firstname);
CREATE INDEX idx_lastname ON signin_db(lastname);
CREATE INDEX idx_student_id ON signin_db(student_id);
CREATE INDEX idx_email ON signin_db(email);

-- Add some helpful comments to the table
ALTER TABLE signin_db 
COMMENT = 'Stores user authentication and profile information for the website login system';