<?php
require_once 'db.php';

// Check what's actually in the database for ITE 110
echo "Checking ITE 110 in curriculum table:\n";
$result = $conn->query("SELECT course_code, course_title, prerequisites FROM curriculum WHERE course_code LIKE '%ITE110%' OR course_code LIKE '%ITE 110%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Course: " . $row['course_code'] . "\n";
        echo "Title: " . $row['course_title'] . "\n";
        echo "Prerequisites: '" . $row['prerequisites'] . "' (length: " . strlen($row['prerequisites']) . ")\n";
        echo "Prerequisites var_dump: ";
        var_dump($row['prerequisites']);
        echo "\n-------------------\n";
    }
} else {
    echo "ITE 110 not found in curriculum\n";
}

// Check irregular_db
echo "\nChecking ITE 110 in irregular table:\n";
$result = $conn->query("SELECT course_code, course_title, prerequisites FROM irregular_db WHERE course_code LIKE '%ITE110%' OR course_code LIKE '%ITE 110%'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Course: " . $row['course_code'] . "\n";
        echo "Title: " . $row['course_title'] . "\n";
        echo "Prerequisites: '" . $row['prerequisites'] . "' (length: " . strlen($row['prerequisites']) . ")\n";
        echo "Prerequisites var_dump: ";
        var_dump($row['prerequisites']);
        echo "\n-------------------\n";
    }
} else {
    echo "ITE 110 not found in irregular table\n";
}

// Check if there's a space or other character
echo "\nChecking all courses with spaces in code:\n";
$result = $conn->query("SELECT course_code, course_title, prerequisites FROM curriculum WHERE course_code LIKE '% %' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "Course: '" . $row['course_code'] . "' -> Prereq: '" . $row['prerequisites'] . "'\n";
}
?>
