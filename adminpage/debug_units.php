<?php
require_once 'db.php';

$student_id = '2022-10973';
$program = 'BSIT';
$ysKey = '1-1'; // Year 1, Semester 1

echo "<h2>Debug Units Calculation for Student: $student_id</h2>";
echo "<h3>Semester: $ysKey</h3>";

// Get grades for the student
$gradesByCode = [];
$gradesByPrefixYearSem = [];

$gstmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ? ORDER BY year, sem");
if ($gstmt) {
    $gstmt->bind_param('s', $student_id);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    while ($g = $gres->fetch_assoc()) {
        $code = !empty($g['course_code']) ? $g['course_code'] : $g['course_code'];
        if (empty($code)) continue;
        
        $norm = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        $gradeValue = !empty($g['final_grade']) ? $g['final_grade'] : ($g['grade'] ?? '');
        
        if (empty($gradeValue)) continue;
        
        $gradesByCode[$norm] = $gradeValue;
        $gradesByCode[$code] = $gradeValue;
        
        $year = intval($g['year'] ?? 0);
        $sem = intval($g['sem'] ?? 0);
        
        if ($year && $sem) {
            $gradesByCode["{$norm}_{$year}_{$sem}"] = $gradeValue;
            $gradesByCode["{$norm}-{$year}-{$sem}"] = $gradeValue;
            
            if (preg_match('/^([A-Z]+)/', $norm, $matches)) {
                $prefix = $matches[1];
                $gradesByCode["{$prefix}-{$year}-{$sem}"] = $gradeValue;
                $gradesByPrefixYearSem["{$prefix}-{$year}-{$sem}"] = $gradeValue;
            }
        }
    }
}

// Get curriculum for Year 1, Semester 1
$curriculum = [];
// Check if year_semester column exists
$checkYearSem = $conn->query("SHOW COLUMNS FROM curriculum LIKE 'year_semester'");
if ($checkYearSem && $checkYearSem->num_rows > 0) {
    $sql = "SELECT course_code, course_title, total_units FROM curriculum WHERE year_semester = '1-1' AND program = '$program'";
} else {
    $sql = "SELECT course_code, course_title, total_units FROM curriculum WHERE year = 1 AND semester = 1 AND program = '$program'";
}
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $curriculum[$row['course_code']] = $row;
    }
}

// Calculate units
[$yr, $sm] = array_map('intval', explode('-', $ysKey));
$gradedUnits = 0;

echo "<h3>Curriculum Subjects Being Counted:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Course Code</th><th>Title</th><th>Units</th><th>Grade</th><th>Is Passing</th><th>Counted</th></tr>";

if (isset($curriculum)) {
    foreach ($curriculum as $courseCode => $course) {
        $grade = '';
        
        if (isset($gradesByCode[$courseCode])) {
            $grade = $gradesByCode[$courseCode];
        }
        
        if (empty($grade)) {
            $normalizedCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $courseCode));
            foreach ($gradesByCode as $key => $value) {
                $normalizedKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $key));
                if ($normalizedKey === $normalizedCode) {
                    $grade = $value;
                    break;
                }
            }
        }
        
        $isPassed = false;
        if (!empty($grade)) {
            if (is_numeric($grade)) {
                $gradeValue = floatval($grade);
                if ($gradeValue <= 3.25) {
                    $isPassed = true;
                }
            } elseif (is_string($grade) && strtoupper(trim($grade)) === 'PASSED') {
                $isPassed = true;
            }
        }
        
        $counted = false;
        if ($isPassed) {
            $courseUnits = floatval($course['units'] ?? 0);
            $gradedUnits += $courseUnits;
            $counted = true;
        }
        
        echo "<tr>";
        echo "<td>" . $courseCode . "</td>";
        echo "<td>" . $course['title'] . "</td>";
        echo "<td>" . ($course['units'] ?? 0) . "</td>";
        echo "<td>" . $grade . "</td>";
        echo "<td style='color: " . ($isPassed ? 'green' : 'red') . "'>" . ($isPassed ? 'YES' : 'NO') . "</td>";
        echo "<td style='color: " . ($counted ? 'green' : 'red') . "'>" . ($counted ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "<h3>Additional Graded Subjects:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Key</th><th>Grade</th><th>Is Passing</th><th>Units</th></tr>";

foreach ($gradesByPrefixYearSem as $key => $grade) {
    if (preg_match('/^(.+)-(\d+)-(\d+)$/', $key, $matches)) {
        $gradeYear = intval($matches[2]);
        $gradeSem = intval($matches[3]);
        
        if ($gradeYear == $yr && $gradeSem == $sm) {
            $isPassed = false;
            if (is_numeric($grade)) {
                $gradeValue = floatval($grade);
                if ($gradeValue <= 3.25) {
                    $isPassed = true;
                }
            } elseif (is_string($grade) && strtoupper(trim($grade)) === 'PASSED') {
                $isPassed = true;
            }
            
            if ($isPassed) {
                $gradedUnits += 3;
            }
            
            echo "<tr>";
            echo "<td>" . $key . "</td>";
            echo "<td>" . $grade . "</td>";
            echo "<td style='color: " . ($isPassed ? 'green' : 'red') . "'>" . ($isPassed ? 'YES' : 'NO') . "</td>";
            echo "<td>" . ($isPassed ? '3' : '0') . "</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";
echo "<h3>Total Calculation:</h3>";
echo "<p><strong>Total Graded Units: " . $gradedUnits . "</strong></p>";
echo "<p><strong>Max Units: 26</strong></p>";
echo "<p><strong>Is Over Limit: " . ($gradedUnits > 26 ? 'YES' : 'NO') . "</strong></p>";

$conn->close();
?>
