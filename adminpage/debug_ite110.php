<?php
require_once 'db.php';

// Check if ITE 110 exists in curriculum table
echo "Checking curriculum table for ITE 110:\n";
$result = $conn->query("SELECT * FROM curriculum WHERE course_code LIKE '%ITE110%' OR course_code LIKE '%ITE 110%' OR course_title LIKE '%ITE 110%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Found: " . $row['course_code'] . " - " . $row['course_title'] . " - Prereq: " . ($row['prerequisites'] ?? 'NULL') . "\n";
    }
} else {
    echo "ITE 110 not found in curriculum table\n";
}

// Check all ITE courses
echo "\nAll ITE courses in curriculum:\n";
$result = $conn->query("SELECT course_code, course_title, prerequisites FROM curriculum WHERE course_code LIKE '%ITE%' OR course_title LIKE '%ITE%' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['course_code'] . " - " . $row['course_title'] . " (Prereq: " . ($row['prerequisites'] ?? 'NULL') . ")\n";
}

// Check if it's coming from irregular subjects
echo "\nChecking irregular subjects for ITE 110:\n";
$result = $conn->query("SELECT * FROM irregular_db WHERE course_code LIKE '%ITE110%' OR course_code LIKE '%ITE 110%' OR course_title LIKE '%ITE 110%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Found in irregular: " . $row['course_code'] . " - " . $row['course_title'] . " - Prereq: " . ($row['prerequisites'] ?? 'NULL') . "\n";
    }
} else {
    echo "ITE 110 not found in irregular table\n";
}
?>
