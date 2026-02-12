<?php
require_once 'db.php';

// Check for any course with ITE in it
$result = $conn->query("SELECT course_code, course_title, prerequisites FROM curriculum WHERE course_code LIKE '%ITE%' OR course_title LIKE '%ITE%' LIMIT 10");
echo "Checking courses with ITE:\n";
while ($row = $result->fetch_assoc()) {
    echo "Course: " . $row['course_code'] . " - " . $row['course_title'] . " - Prereq: " . ($row['prerequisites'] ?? 'NULL') . "\n";
}

// Check all columns in curriculum table
echo "\nAll columns in curriculum table:\n";
$columns = $conn->query("SHOW COLUMNS FROM curriculum");
while ($col = $columns->fetch_assoc()) {
    echo "- " . $col['Field'] . "\n";
}

// Check if prerequisites column has any non-null values
echo "\nChecking prerequisites column values:\n";
$prereqResult = $conn->query("SELECT course_code, prerequisites FROM curriculum WHERE prerequisites IS NOT NULL AND prerequisites != '' AND prerequisites != 'None' LIMIT 10");
while ($row = $prereqResult->fetch_assoc()) {
    echo "Course: " . $row['course_code'] . " - Prereq: " . $row['prerequisites'] . "\n";
}
?>
