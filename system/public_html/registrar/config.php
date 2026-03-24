<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ccc_curriculum_evaluation');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create grades_db table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS grades_db (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    year VARCHAR(10) NOT NULL,
    sem VARCHAR(10) NOT NULL,
    grade VARCHAR(10) NOT NULL,
    course_title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die('Failed to create table: ' . $conn->error);
}
?>
