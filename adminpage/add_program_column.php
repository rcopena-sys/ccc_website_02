<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'ccc_curriculum_evaluation');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if program column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM irregular_db LIKE 'program'");
if ($checkColumn->num_rows > 0) {
    echo "Program column already exists in irregular_db table.";
} else {
    // Add program column
    $sql = "ALTER TABLE irregular_db ADD COLUMN program VARCHAR(50) AFTER student_id";
    
    if ($conn->query($sql) === TRUE) {
        echo "Program column added successfully to irregular_db table.";
    } else {
        echo "Error adding program column: " . $conn->error;
    }
}

$conn->close();
?>
