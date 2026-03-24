<?php
require_once 'db.php';

// Check if the table already exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'student_subjects'");

if ($tableCheck->num_rows === 0) {
    // Table doesn't exist, create it
    $sql = "
    CREATE TABLE student_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        course_code VARCHAR(50) NOT NULL,
        course_title VARCHAR(255) NOT NULL,
        year_semester VARCHAR(10) NOT NULL,
        reason ENUM('failed', 'prerequisite', 'other') NOT NULL,
        notes TEXT,
        assigned_by VARCHAR(50) NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        UNIQUE KEY unique_assignment (student_id, course_code, year_semester, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    -- Add indexes for better performance
    ALTER TABLE student_subjects 
    ADD INDEX idx_student_id (student_id),
    ADD INDEX idx_course_code (course_code),
    ADD INDEX idx_year_semester (year_semester),
    ADD INDEX idx_is_active (is_active);
    ";

    if ($conn->multi_query($sql)) {
        echo "Table 'student_subjects' created successfully with indexes.\n";
        // Clear any remaining results from multi_query
        while ($conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table 'student_subjects' already exists.\n";
    
    // Check if we need to add any missing columns
    $columns = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'student_id' => 'VARCHAR(50) NOT NULL',
        'course_code' => 'VARCHAR(50) NOT NULL',
        'course_title' => 'VARCHAR(255) NOT NULL',
        'year_semester' => 'VARCHAR(10) NOT NULL',
        'reason' => "ENUM('failed', 'prerequisite', 'other') NOT NULL",
        'notes' => 'TEXT',
        'assigned_by' => 'VARCHAR(50) NOT NULL',
        'assigned_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'is_active' => 'BOOLEAN DEFAULT TRUE'
    ];
    
    $result = $conn->query("SHOW COLUMNS FROM student_subjects");
    $existingColumns = [];
    while ($row = $result->fetch_assoc()) {
        $existingColumns[$row['Field']] = $row['Type'];
    }
    
    foreach ($columns as $column => $definition) {
        if (!array_key_exists($column, $existingColumns)) {
            $addColumn = "ALTER TABLE student_subjects ADD COLUMN $column $definition";
            if ($conn->query($addColumn)) {
                echo "Added column '$column' to student_subjects table.\n";
            } else {
                echo "Error adding column '$column': " . $conn->error . "\n";
            }
        }
    }
    
    // Add unique constraint if it doesn't exist
    $indexResult = $conn->query("SHOW INDEX FROM student_subjects WHERE Key_name = 'unique_assignment'");
    if ($indexResult->num_rows === 0) {
        $addUnique = "ALTER TABLE student_subjects ADD CONSTRAINT unique_assignment UNIQUE (student_id, course_code, year_semester, is_active)";
        if ($conn->query($addUnique)) {
            echo "Added unique constraint to student_subjects table.\n";
        } else {
            echo "Error adding unique constraint: " . $conn->error . "\n";
        }
    }
}

// Close the database connection
$conn->close();

// Output a success message with instructions
echo "\nSetup completed. You can now use the student subject assignment feature.\n";
echo "You can delete this file (setup_student_subjects.php) for security reasons.\n";
?>
