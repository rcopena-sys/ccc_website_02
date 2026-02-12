<?php
require_once 'db.php';

// Check grades_db for ITE 110
echo "Checking grades table for ITE 110:\n";
$result = $conn->query("SELECT * FROM grades_db WHERE course_code LIKE '%ITE110%' OR course_code LIKE '%ITE 110%' LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Student " . $row['student_id'] . " has grade for: " . $row['course_code'] . " - Grade: " . $row['final_grade'] . "\n";
    }
} else {
    echo "No grades found for ITE 110\n";
}

// Check all ITE courses in grades
echo "\nAll ITE courses in grades:\n";
$result = $conn->query("SELECT DISTINCT course_code, course_title FROM grades_db WHERE course_code LIKE '%ITE%' OR course_title LIKE '%ITE%' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['course_code'] . " - " . $row['course_title'] . "\n";
}
?>
