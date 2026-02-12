<?php
require_once 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_title VARCHAR(255) NOT NULL,
    year_semester VARCHAR(10) NOT NULL,
    reason ENUM('failed', 'prerequisite', 'other') NOT NULL,
    assigned_by VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_assignment (student_id, course_code, year_semester, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table student_subjects created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Add indexes for better performance
$indexes = [
    "CREATE INDEX idx_student_id ON student_subjects(student_id)",
    "CREATE INDEX idx_course_code ON student_subjects(course_code)",
    "CREATE INDEX idx_year_semester ON student_subjects(year_semester)",
    "CREATE INDEX idx_is_active ON student_subjects(is_active)"
];

foreach ($indexes as $index) {
    if ($conn->query($index) === TRUE) {
        echo "Index created successfully\n";
    } else {
        echo "Error creating index: " . $conn->error . "\n";
    }
}

$conn->close();
?>
