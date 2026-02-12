<?php
require_once 'db.php';

$student_id = '2022-10973';

echo "<h2>Debug Grades for Year 1, Semester 1</h2>";

// Get all grades for this student
$sql = "SELECT * FROM grades_db WHERE student_id = ? AND year = 1 AND semester = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Grades for Year 1, Semester 1:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Course Code</th><th>Final Grade</th><th>Is Passing</th><th>Units</th></tr>";
    $totalUnits = 0;
    while ($row = $result->fetch_assoc()) {
        $grade = $row['final_grade'];
        $isPassed = false;
        
        if (is_numeric($grade)) {
            $gradeValue = floatval($grade);
            if ($gradeValue <= 3.25) {
                $isPassed = true;
            }
        } elseif (is_string($grade) && strtoupper(trim($grade)) === 'PASSED') {
            $isPassed = true;
        }
        
        $units = $isPassed ? 3 : 0;
        if ($isPassed) {
            $totalUnits += $units;
        }
        
        echo "<tr>";
        echo "<td>" . $row['course_code'] . "</td>";
        echo "<td>" . $grade . "</td>";
        echo "<td style='color: " . ($isPassed ? 'green' : 'red') . "'>" . ($isPassed ? 'YES' : 'NO') . "</td>";
        echo "<td>" . $units . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<h3>Total Units: $totalUnits</h3>";
} else {
    echo "<p>No grades found for Year 1, Semester 1.</p>";
}

$conn->close();
?>
