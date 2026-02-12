<?php
require_once 'db.php';

$student_id = '2022-10973';

echo "<h2>Debug Grades for Student: $student_id</h2>";

// Get all grades for this student
$sql = "SELECT * FROM grades_db WHERE student_id = ? ORDER BY course_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>All Grades:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Course Code</th><th>Final Grade</th><th>Is Passing</th></tr>";
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
        
        echo "<tr>";
        echo "<td>" . $row['course_code'] . "</td>";
        echo "<td>" . $grade . "</td>";
        echo "<td style='color: " . ($isPassed ? 'green' : 'red') . "'>" . ($isPassed ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No grades found for this student.</p>";
}

// Also check what curriculum subjects exist for Year 1, Semester 1
echo "<h3>Curriculum Subjects (Year 1, Semester 1):</h3>";
$sql = "SELECT * FROM curriculum WHERE year = 1 AND semester = 1 ORDER BY code";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Code</th><th>Title</th><th>Units</th><th>Grade</th><th>Is Passing</th></tr>";
    while ($row = $result->fetch_assoc()) {
        // Check if this subject has a grade
        $grade_sql = "SELECT final_grade FROM grades_db WHERE student_id = ? AND course_code = ?";
        $grade_stmt = $conn->prepare($grade_sql);
        $grade_stmt->bind_param('ss', $student_id, $row['code']);
        $grade_stmt->execute();
        $grade_result = $grade_stmt->get_result();
        $grade = $grade_result->num_rows > 0 ? $grade_result->fetch_assoc()['final_grade'] : 'NO GRADE';
        
        $isPassed = false;
        if ($grade !== 'NO GRADE') {
            if (is_numeric($grade)) {
                $gradeValue = floatval($grade);
                if ($gradeValue <= 3.25) {
                    $isPassed = true;
                }
            } elseif (is_string($grade) && strtoupper(trim($grade)) === 'PASSED') {
                $isPassed = true;
            }
        }
        
        echo "<tr>";
        echo "<td>" . $row['code'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['units'] . "</td>";
        echo "<td>" . $grade . "</td>";
        echo "<td style='color: " . ($isPassed ? 'green' : 'red') . "'>" . ($isPassed ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No curriculum subjects found.</p>";
}

$conn->close();
?>
