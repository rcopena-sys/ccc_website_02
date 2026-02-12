<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    die("Please log in first");
}

require_once 'db_connect.php';
$student_id = $_SESSION['student_id'];

echo "<h2>Grade Debug Report for Student: " . htmlspecialchars($student_id) . "</h2>";

// Check grades_db table
echo "<h3>Grades in grades_db table:</h3>";
$gradeStmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ? ORDER BY year, sem, course_code");
$gradeStmt->bind_param("s", $student_id);
$gradeStmt->execute();
$gradeResult = $gradeStmt->get_result();

if ($gradeResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Course Code</th><th>Course Title</th><th>Grade</th><th>Year</th><th>Sem</th><th>Remarks</th></tr>";
    while ($grade = $gradeResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($grade['course_code'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($grade['course_title'] ?? '') . "</td>";
        echo "<td><strong>" . htmlspecialchars($grade['final_grade'] ?? $grade['grade'] ?? '') . "</strong></td>";
        echo "<td>" . htmlspecialchars($grade['year'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($grade['sem'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($grade['remarks'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No grades found in grades_db table</p>";
}

// Check irregular_db table for second year courses
echo "<h3>Second Year Courses from irregular_db:</h3>";
$irregularStmt = $conn->prepare("SELECT * FROM irregular_db WHERE student_id = ? AND year_level = 2 ORDER BY semester, course_code");
$irregularStmt->bind_param("s", $student_id);
$irregularStmt->execute();
$irregularResult = $irregularStmt->get_result();

if ($irregularResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Course Code</th><th>Course Title</th><th>Units</th><th>Semester</th><th>Prerequisites</th><th>Status</th></tr>";
    while ($course = $irregularResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($course['course_code'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($course['course_title'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($course['total_units'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($course['semester'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($course['prerequisites'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($course['status'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No second year courses found in irregular_db table</p>";
}

// Check for prerequisite issues
echo "<h3>Prerequisite Analysis:</h3>";
$irregularStmt->execute();
$irregularResult = $irregularStmt->get_result();

$gradesByCode = [];
while ($grade = $gradeResult->fetch_assoc()) {
    $courseCode = trim(strtoupper($grade['course_code']));
    $gradesByCode[$courseCode] = [
        'grade' => $grade['final_grade'] ?? $grade['grade'] ?? 'N/A',
        'remarks' => $grade['remarks'] ?? 'N/A'
    ];
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Course</th><th>Prerequisites</th><th>Prereq Status</th><th>Your Grade</th><th>Block Status</th></tr>";

while ($course = $irregularResult->fetch_assoc()) {
    if ($course['semester'] == 2) { // Second semester courses
        $subjectCode = trim($course['course_code']);
        $prereqText = $course['prerequisites'] ?? '';
        $blockStatus = "CLEAR";
        $prereqStatus = "N/A";
        
        if (!empty($prereqText)) {
            $prereqCodes = preg_split('/\s*,\s*/', $prereqText);
            $prereqStatus = "";
            
            foreach ($prereqCodes as $pr) {
                $pr = trim($pr);
                if ($pr === '') continue;
                $prUpper = strtoupper($pr);
                
                if (isset($gradesByCode[$prUpper])) {
                    $prGrade = $gradesByCode[$prUpper]['grade'];
                    $prGradeUpper = strtoupper($prGrade);
                    $prFailKeywords = ['FAIL','FAILED','F','FA','U'];
                    
                    if (in_array($prGradeUpper, $prFailKeywords) || (is_numeric($prGrade) && floatval($prGrade) > 3.0)) {
                        $prereqStatus .= "$prUpper: FAILED ($prGrade) ";
                        $blockStatus = "BLOCKED - Failed Prerequisite";
                    } else {
                        $prereqStatus .= "$prUpper: PASSED ($prGrade) ";
                    }
                } else {
                    $prereqStatus .= "$prUpper: NO GRADE FOUND ";
                    $blockStatus = "BLOCKED - Missing Prerequisite Grade";
                }
            }
        }
        
        $yourGrade = $gradesByCode[strtoupper($subjectCode)]['grade'] ?? 'NO GRADE';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($subjectCode) . "</td>";
        echo "<td>" . htmlspecialchars($prereqText) . "</td>";
        echo "<td>" . htmlspecialchars($prereqStatus) . "</td>";
        echo "<td><strong>" . htmlspecialchars($yourGrade) . "</strong></td>";
        echo "<td style='background-color: " . ($blockStatus == "CLEAR" ? "#d4edda" : "#f8d7da") . "; font-weight: bold;'>" . htmlspecialchars($blockStatus) . "</td>";
        echo "</tr>";
    }
}
echo "</table>";

$gradeStmt->close();
$irregularStmt->close();
$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background-color: #f0f0f0; padding: 8px; }
td { padding: 8px; }
h3 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style>
