<?php
require_once 'db_connect.php';

// Query to check 1-1 and 1-2 semester courses
$sql = "SELECT * FROM curriculum WHERE program = 'BSCS' AND (year_semester = '1-1' OR year_semester = '1-2') ORDER BY year_semester, subject_code";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "<h2>Courses in Database (BSCS Program)</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Subject Code</th><th>Course Title</th><th>Year/Semester</th><th>Program</th><th>Units</th></tr>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['subject_code'] . "</td>";
        echo "<td>" . $row['course_title'] . "</td>";
        echo "<td>" . $row['year_semester'] . "</td>";
        echo "<td>" . $row['program'] . "</td>";
        echo "<td>" . $row['total_units'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No courses found for BSCS program in 1-1 or 1-2 semesters</td></tr>";
}

echo "</table>";

// Check if table has any data at all
$check = $conn->query("SELECT COUNT(*) as count FROM curriculum");
$row = $check->fetch_assoc();
echo "<p>Total courses in curriculum table: " . $row['count'] . "</p>";

// Show table structure
$columns = $conn->query("SHOW COLUMNS FROM curriculum");
echo "<h3>Table Structure:</h3><ul>";
while($col = $columns->fetch_assoc()) {
    echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
}
echo "</ul>";

$conn->close();
?>
