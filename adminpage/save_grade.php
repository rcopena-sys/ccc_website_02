<?php
require_once '../db_connect.php';

/** @var mysqli $conn */

function columnExists(mysqli $conn, string $table, string $column): bool {
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}

function isPassingGradeValue($grade): bool {
    return is_numeric($grade) && (float)$grade <= 3.25;
}

function gradeColumn(mysqli $conn): string {
    return columnExists($conn, 'grades_db', 'final_grade') ? 'final_grade' : 'grade';
}

function semColumn(mysqli $conn): string {
    return columnExists($conn, 'grades_db', 'sem') ? 'sem' : 'semester';
}

function ensurePassedSubjectInIrregular(mysqli $conn, string $studentId, string $courseCode, string $courseTitle, int $year, int $semester): bool {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'irregular_db'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return false;
    }

    $colsRes = $conn->query("SHOW COLUMNS FROM irregular_db");
    if (!$colsRes) {
        return false;
    }

    $columns = [];
    while ($c = $colsRes->fetch_assoc()) {
        $columns[] = $c['Field'];
    }

    $yearCol = in_array('year_level', $columns, true) ? 'year_level' : (in_array('year', $columns, true) ? 'year' : null);
    $semCol = in_array('semester', $columns, true) ? 'semester' : (in_array('sem', $columns, true) ? 'sem' : null);

    if ($yearCol === null || $semCol === null) {
        return false;
    }

    // Prevent duplicate inserts for the same student/subject/year/semester.
    $dupSql = "SELECT id FROM irregular_db WHERE student_id = ? AND course_code = ? AND {$yearCol} = ? AND {$semCol} = ? LIMIT 1";
    $dupStmt = $conn->prepare($dupSql);
    if ($dupStmt) {
        $dupStmt->bind_param('ssii', $studentId, $courseCode, $year, $semester);
        $dupStmt->execute();
        $exists = $dupStmt->get_result()->num_rows > 0;
        $dupStmt->close();
        if ($exists) {
            return true;
        }
    }

    $lecUnits = 0.0;
    $labUnits = 0.0;
    $totalUnits = 0.0;
    $prereq = '';

    $curStmt = $conn->prepare("SELECT * FROM curriculum WHERE course_code = ? LIMIT 1");
    if ($curStmt) {
        $curStmt->bind_param('s', $courseCode);
        $curStmt->execute();
        $curRes = $curStmt->get_result();
        if ($curRes && ($curRow = $curRes->fetch_assoc())) {
            $courseTitle = trim((string)($curRow['course_title'] ?? $courseTitle));
            $lecUnits = (float)($curRow['lec_units'] ?? $curRow['lecture_units'] ?? 0);
            $labUnits = (float)($curRow['lab_units'] ?? 0);
            $totalUnits = (float)($curRow['total_units'] ?? $curRow['units'] ?? 0);
            if ($totalUnits <= 0) {
                $totalUnits = $lecUnits + $labUnits;
            }
            $prereq = trim((string)($curRow['prerequisites'] ?? $curRow['pre_req'] ?? ''));
        }
        $curStmt->close();
    }

    $program = '';
    if (in_array('program', $columns, true)) {
        $progStmt = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ? LIMIT 1");
        if ($progStmt) {
            $progStmt->bind_param('s', $studentId);
            $progStmt->execute();
            $progRes = $progStmt->get_result();
            if ($progRes && ($progRow = $progRes->fetch_assoc())) {
                $program = strtoupper(trim((string)($progRow['course'] ?? '')));
            }
            $progStmt->close();
        }
    }

    $insertCols = ['student_id', 'course_code', 'course_title', $yearCol, $semCol];
    $insertVals = [$studentId, $courseCode, $courseTitle, $year, $semester];
    $insertTypes = 'sssii';

    if (in_array('total_units', $columns, true)) {
        $insertCols[] = 'total_units';
        $insertVals[] = $totalUnits;
        $insertTypes .= 'd';
    } elseif (in_array('units', $columns, true)) {
        $insertCols[] = 'units';
        $insertVals[] = $totalUnits;
        $insertTypes .= 'd';
    }

    if (in_array('lec_units', $columns, true)) {
        $insertCols[] = 'lec_units';
        $insertVals[] = $lecUnits;
        $insertTypes .= 'd';
    } elseif (in_array('lecture_units', $columns, true)) {
        $insertCols[] = 'lecture_units';
        $insertVals[] = $lecUnits;
        $insertTypes .= 'd';
    }

    if (in_array('lab_units', $columns, true)) {
        $insertCols[] = 'lab_units';
        $insertVals[] = $labUnits;
        $insertTypes .= 'd';
    }

    if (in_array('prerequisites', $columns, true)) {
        $insertCols[] = 'prerequisites';
        $insertVals[] = $prereq;
        $insertTypes .= 's';
    } elseif (in_array('prereq', $columns, true)) {
        $insertCols[] = 'prereq';
        $insertVals[] = $prereq;
        $insertTypes .= 's';
    }

    if (in_array('program', $columns, true)) {
        $insertCols[] = 'program';
        $insertVals[] = $program;
        $insertTypes .= 's';
    }

    if (in_array('status', $columns, true)) {
        $insertCols[] = 'status';
        // Must match irregular_db enum values.
        $insertVals[] = 'completed';
        $insertTypes .= 's';
    }

    if (in_array('year_semester', $columns, true)) {
        $insertCols[] = 'year_semester';
        $insertVals[] = $year . '-' . $semester;
        $insertTypes .= 's';
    }

    $sql = "INSERT INTO irregular_db (" . implode(',', $insertCols) . ") VALUES (" . str_repeat('?,', count($insertCols) - 1) . '?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param($insertTypes, ...$insertVals);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('ensurePassedSubjectInIrregular insert failed: ' . $stmt->error);
    }
    $stmt->close();
    return (bool)$ok;
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the form data
$student_id = trim($_POST['student_id'] ?? '');
$course_code = trim($_POST['course_code'] ?? '');
$course_title = trim($_POST['course_title'] ?? '');
$grade = trim($_POST['grade'] ?? '');
$year = (int)($_POST['year'] ?? $_POST['year_level'] ?? date('Y'));
$semester = (int)($_POST['semester'] ?? $_POST['sem'] ?? 1);

// Validate the data
if (empty($student_id) || empty($course_code) || $grade === '') {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate grade format (0.00 to 5.00)
if (!is_numeric($grade) || $grade < 0 || $grade > 5) {
    echo json_encode(['success' => false, 'message' => 'Grade must be between 0.00 and 5.00']);
    exit;
}

try {
    $mysqli = ($conn instanceof mysqli) ? $conn : null;
    if ($mysqli === null) {
        throw new Exception('Invalid database connection type');
    }

    $gradeCol = gradeColumn($mysqli);
    $semCol = semColumn($mysqli);
    $yearStr = (string)$year;
    $semesterStr = (string)$semester;

    // Check if grade already exists for this student and course
    $checkStmt = $mysqli->prepare("SELECT id FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND {$semCol} = ? LIMIT 1");
    if (!$checkStmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }
    $checkStmt->bind_param('ssss', $student_id, $course_code, $yearStr, $semesterStr);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        // Update existing grade
        $stmt = $mysqli->prepare("UPDATE grades_db SET {$gradeCol} = ?, course_title = ?, updated_at = NOW() WHERE student_id = ? AND course_code = ? AND year = ? AND {$semCol} = ?");
    } else {
        // Insert new grade
        $stmt = $mysqli->prepare("INSERT INTO grades_db (student_id, course_code, course_title, {$gradeCol}, year, {$semCol}, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    }

    if (!$stmt) {
        throw new Exception('Failed to prepare grade save statement: ' . $mysqli->error);
    }

    if ($exists) {
        $stmt->bind_param('ssssss', $grade, $course_title, $student_id, $course_code, $yearStr, $semesterStr);
    } else {
        $stmt->bind_param('ssssss', $student_id, $course_code, $course_title, $grade, $yearStr, $semesterStr);
    }
    
    if ($stmt->execute()) {
        $irregularSynced = false;
        if (isPassingGradeValue($grade)) {
            $irregularSynced = ensurePassedSubjectInIrregular($mysqli, $student_id, $course_code, $course_title, $year, $semester);
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Grade saved successfully',
            'grade' => $grade,
            'course_code' => $course_code,
            'irregular_synced' => $irregularSynced,
            'year' => (string)$year,
            'sem' => (string)$semester
        ]);
    } else {
        throw new Exception('Failed to save grade: ' . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log('Error saving grade: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while saving the grade. Please try again.'
    ]);
}

if ($conn instanceof mysqli) {
    $conn->close();
}
?>
