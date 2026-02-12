<?php
require_once 'db.php';

// Check if a student should be classified as irregular
function checkIrregularStatus($conn, $studentId, $currentYear, $currentSem) {
    // Check regular courses
    $regularQuery = "SELECT COUNT(*) as failed_count FROM grades_db 
                    WHERE student_id = ? AND (final_grade >= 5.00 OR final_grade IS NULL)";
    $stmt = $conn->prepare($regularQuery);
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['failed_count'] > 0) return true;
        }
    }
    
    // Check irregular courses
    $irregularQuery = "SELECT COUNT(*) as failed_count FROM irregular_db 
                       WHERE student_id = ? AND year_level = ? AND semester = ?";
    $stmt = $conn->prepare($irregularQuery);
    if ($stmt) {
        $stmt->bind_param('sii', $studentId, $currentYear, $currentSem);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['failed_count'] > 0) return true;
        }
    }
    
    return false;
}

// Check if a student has failed any prerequisites for a course
function hasFailedPrerequisites($conn, $studentId, $courseCode, $currentYear, $currentSem) {
    // First, determine which prerequisite column exists
    $prereqColumn = null;
    $columns = ['prerequisites', 'prerequisites', 'pre_req', 'pre_requisites'];
    
    foreach ($columns as $col) {
        $checkCol = $conn->query("SHOW COLUMNS FROM curriculum LIKE '$col'");
        if ($checkCol && $checkCol->num_rows > 0) {
            $prereqColumn = $col;
            break;
        }
    }
    
    if (!$prereqColumn) {
        return false;
    }
    
    // Now query using the found column name
    $prereqQuery = "SELECT $prereqColumn FROM curriculum WHERE course_code = ?";
    $prereqStmt = $conn->prepare($prereqQuery);
    if (!$prereqStmt) {
        return false;
    }
    
    $prereqStmt->bind_param('s', $courseCode);
    if (!$prereqStmt->execute()) {
        return false;
    }
    
    $prereqResult = $prereqStmt->get_result();
    
    if ($prereqResult->num_rows === 0) {
        return false; // Course not found or has no prerequisites
    }
    
    $courseData = $prereqResult->fetch_assoc();
    $prerequisites = trim($courseData[$prereqColumn] ?? '');
    
    // If no prerequisites listed, return false (no prerequisites to check)
    if (empty($prerequisites)) {
        return false;
    }
    
    // Split prerequisite by comma and clean up
    $prereqCodes = array_map('trim', explode(',', $prerequisites));
    $prereqCodes = array_filter($prereqCodes); // Remove any empty entries
    
    // If no valid prerequisites found, return false
    if (empty($prereqCodes)) {
        return false;
    }
    
    // Check all prerequisites
    foreach ($prereqCodes as $prereqCode) {
        // Skip empty codes
        if (empty($prereqCode)) continue;
        
        // Check if the student has passed this prerequisites
        $gradeQuery = "SELECT final_grade FROM grades_db 
                      WHERE student_id = ? 
                      AND course_code = ?";
        
        $gradeStmt = $conn->prepare($gradeQuery);
        if (!$gradeStmt) {
            continue;
        }
        
        $gradeStmt->bind_param('ss', $studentId, $prereqCode);
        if (!$gradeStmt->execute()) {
            continue;
        }
        
        $gradeResult = $gradeStmt->get_result();
        
        if ($gradeResult->num_rows === 0) {
            // No grade found for this prerequisite - check if it's a first-semester course
            $isFirstSemCourse = $conn->query("SELECT 1 FROM curriculum WHERE course_code = '$prereqCode' AND semester = 1 AND year <= $currentYear");
            if ($isFirstSemCourse && $isFirstSemCourse->num_rows > 0) {
                // It's a first-semester course that should have been taken already but has no grade
                return true;
            }
        } else {
            // Check if the grade is a passing grade
            $gradeRow = $gradeResult->fetch_assoc();
            $grade = $gradeRow['final_grade'];
            
            if ($grade === null) {
                return true;
            } elseif (is_numeric($grade)) {
                if ($grade >= 5.00) {
                    return true;
                } else {
                    // Passing grade
                }
            } else {
                return true; // Invalid grade format, treat as failed
            }
        }
    }
    
    return false; // All prerequisites passed or no prerequisites
}

// Helpers
function columnExists(mysqli $conn, string $table, string $column): bool {
    // SHOW statements cannot use parameter markers in MySQL/MariaDB.
    // Safely interpolate identifiers and values.
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}

function normalizeSemesterKey(string $raw): string {
    $raw = strtolower(trim($raw));
    $raw = str_replace(['first','second','third','fourth','1st','2nd','3rd','4th'], ['1','2','3','4','1','2','3','4'], $raw);
    $raw = str_replace(['year','yr','semester','sem',' ', '-'], '', $raw);
    if (preg_match('/^([1-4])([1-2])$/', $raw, $m)) {
        return $m[1] . '-' . $m[2];
    }
    if (preg_match('/^[1-4]-[1-2]$/', $raw)) return $raw;
    return $raw;
}

function normalizeCourseCode(string $code): string {
    // Uppercase and strip all non-alphanumeric to match 'ALG 101' with 'ALG101' etc.
    $upper = strtoupper(trim($code));
    return preg_replace('/[^A-Z0-9]/', '', $upper);
}

function latestFiscalYear(mysqli $conn, string $program): ?string {
    if (!columnExists($conn, 'curriculum', 'fiscal_year') || !columnExists($conn, 'curriculum', 'program')) {
        return null;
    }
    $stmt = $conn->prepare("SELECT fiscal_year FROM curriculum WHERE program = ? ORDER BY fiscal_year DESC LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $program);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $fy = $row['fiscal_year'] ?? null;
    $stmt->close();
    return $fy ?: null;
}

// Add back inline grade edit support
function gradeColumn(mysqli $conn): string {
    return columnExists($conn, 'grades_db', 'final_grade') ? 'final_grade' : 'grade';
}

// Handle classification update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_classification') {
    header('Content-Type: application/json');
    
    try {
        $student_id = trim($_POST['student_id'] ?? '');
        $classification = trim($_POST['classification'] ?? '');
        
        if (empty($student_id) || empty($classification)) {
            throw new Exception('Student ID and classification are required');
        }
        
        // Validate classification value
        if (!in_array(strtolower($classification), ['regular', 'irregular'])) {
            throw new Exception('Invalid classification value');
        }
        
        // Update classification in signin_db
        $stmt = $conn->prepare("UPDATE signin_db SET classification = ? WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        
        $stmt->bind_param('ss', $classification, $student_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Classification updated successfully!',
                    'classification' => $classification
                ]);
            } else {
                // Check if student exists
                $checkStmt = $conn->prepare("SELECT student_id FROM signin_db WHERE student_id = ?");
                $checkStmt->bind_param('s', $student_id);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if ($exists) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Classification already set to this value.',
                        'classification' => $classification
                    ]);
                } else {
                    throw new Exception('Student not found in signin_db');
                }
            }
        } else {
            throw new Exception('Failed to update classification: ' . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle cleanup of duplicate irregular subjects
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup_duplicates') {
    header('Content-Type: application/json');
    
    try {
        $student_id = trim($_POST['student_id'] ?? '');
        
        if (empty($student_id)) {
            throw new Exception('Student ID is required');
        }
        
        // Find and remove duplicate records for this student
        $cleanupStmt = $conn->prepare("
            SELECT student_id, course_code, year_level, semester, COUNT(*) as count, GROUP_CONCAT(id) as ids
            FROM irregular_db 
            WHERE student_id = ?
            GROUP BY student_id, course_code, year_level, semester
            HAVING COUNT(*) > 1
        ");
        
        $cleanupStmt->bind_param('s', $student_id);
        $cleanupStmt->execute();
        $duplicates = $cleanupStmt->get_result();
        
        $cleanedCount = 0;
        $duplicateGroups = [];
        
        while ($dup = $duplicates->fetch_assoc()) {
            $duplicateGroups[] = $dup;
            $ids = explode(',', $dup['ids']);
            // Keep the first record (lowest ID), delete the rest
            array_shift($ids); // Remove first element
            $idsToDelete = implode(',', $ids);
            
            if (!empty($idsToDelete)) {
                $deleteStmt = $conn->prepare("DELETE FROM irregular_db WHERE id IN ($idsToDelete)");
                $deleteStmt->execute();
                $cleanedCount += $deleteStmt->affected_rows;
                $deleteStmt->close();
            }
        }
        
        $cleanupStmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => "Cleaned up $cleanedCount duplicate records",
            'duplicate_groups' => count($duplicateGroups),
            'details' => $duplicateGroups
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle Add Subject to Irregular DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_irregular_subject') {
    header('Content-Type: application/json');
    
    try {
        // Get form data
        $student_id = trim($_POST['student_id'] ?? '');
        $course_code = trim($_POST['course_code'] ?? '');
        $course_title = trim($_POST['course_title'] ?? '');
        $lec_units = floatval($_POST['lec_units'] ?? 0);
        $lab_units = floatval($_POST['lab_units'] ?? 0);
        $prerequisites = trim($_POST['prerequisites'] ?? '');
        $year = intval($_POST['year'] ?? 1);
        $sem = intval($_POST['sem'] ?? 1);
        $total_units = $lec_units + $lab_units;
        
        // Validate required fields
        if (empty($student_id) || empty($course_code) || empty($course_title)) {
            throw new Exception('Missing required fields');
        }
        
        // Get the student's program
        $program = '';
        $stmt = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $program = $row['course'];
        } else {
            throw new Exception('Student not found');
        }
        
        // Debug: Log the exact values being checked
        error_log("Checking for duplicate: student_id='$student_id', course_code='$course_code', year_level=$year, semester=$sem");
        
        // Check if the subject already exists for this student in this semester
        // Use a more comprehensive check to match the database constraint
        $checkStmt = $conn->prepare("
            SELECT id, student_id, course_code, year_level, semester, created_at 
            FROM irregular_db 
            WHERE student_id = ? AND course_code = ? AND year_level = ? AND semester = ?
        ");
        $checkStmt->bind_param('ssii', $student_id, $course_code, $year, $sem);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existingRecords = [];
        while ($row = $result->fetch_assoc()) {
            $existingRecords[] = $row;
        }
        $checkStmt->close();
        
        if (!empty($existingRecords)) {
            error_log("Found existing records: " . json_encode($existingRecords));
            throw new Exception('This subject is already in the irregular list for this semester. Found ' . count($existingRecords) . ' existing record(s).');
        }
        
        // Try to insert using INSERT IGNORE to handle duplicates gracefully
        try {
            // Debug: Print the SQL and parameters for troubleshooting
            error_log("SQL: INSERT IGNORE INTO irregular_db (student_id, course_code, course_title, lec_units, lab_units, total_units, prerequisites, year_level, semester, program, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            error_log("Params: $student_id, $course_code, $course_title, $lec_units, $lab_units, $total_units, $prerequisites, $year, $sem, $program");
            
            // Insert into irregular_db using INSERT IGNORE
            $stmt = $conn->prepare("
                INSERT IGNORE INTO irregular_db 
                (student_id, course_code, course_title, lec_units, lab_units, total_units, prerequisites, year_level, semester, program, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->bind_param(
                "sssdddsiis",
                $student_id,
                $course_code,
                $course_title,
                $lec_units,
                $lab_units,
                $total_units,
                $prerequisites,
                $year,
                $sem,
                $program
            );
            
            if ($stmt->execute()) {
                // Check if a row was actually inserted
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Subject added to irregular subjects successfully!',
                        'data' => [
                            'code' => $course_code,
                            'title' => $course_title,
                            'lec_units' => $lec_units,
                            'lab_units' => $lab_units,
                            'total_units' => $total_units,
                            'prerequisites' => $prerequisites,
                            'year_level' => $year,
                            'semester' => $sem
                        ]
                    ]);
                } else {
                    // No row was inserted, likely due to duplicate
                    throw new Exception('This subject is already in the irregular list for this semester.');
                }
            } else {
                throw new Exception('Failed to add subject to irregular subjects: ' . $stmt->error);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Check if it's a duplicate entry error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new Exception('This subject is already in the irregular list for this semester.');
            } else {
                throw new Exception('Failed to add subject to irregular subjects: ' . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle saving grades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    header('Content-Type: application/json');
    
    $student_id = trim($_POST['student_id'] ?? '');
    $course_code = trim($_POST['course_code'] ?? '');
    $course_title = trim($_POST['course_title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $sem = trim($_POST['sem'] ?? '');
    $grade = trim($_POST['grade'] ?? '');

    if ($student_id === '' || $course_code === '' || $year === '' || $sem === '' || $grade === '') {
        echo json_encode(['ok' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    if (!is_numeric($grade) || $grade < 1.0 || $grade > 5.0) {
        echo json_encode(['ok' => false, 'message' => 'Grade must be a number between 1.0 and 5.0']);
        exit;
    }

    $gradeCol = gradeColumn($conn);

    $existsStmt = $conn->prepare("SELECT 1 FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ? LIMIT 1");
    if (!$existsStmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $existsStmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
    $existsStmt->execute();
    $exists = $existsStmt->get_result()->num_rows > 0;
    $existsStmt->close();

    if ($exists) {
        $sql = "UPDATE grades_db SET {$gradeCol} = ?, course_title = ? WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['ok'=>false,'message'=>$conn->error]); exit; }
        $stmt->bind_param('ssssss', $grade, $course_title, $student_id, $course_code, $year, $sem);
        $ok = $stmt->execute();
        $stmt->close();
    } else {
        $sql = "INSERT INTO grades_db (student_id, course_code, year, sem, {$gradeCol}, course_title) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['ok'=>false,'message'=>$conn->error]); exit; }
        $stmt->bind_param('ssssss', $student_id, $course_code, $year, $sem, $grade, $course_title);
        $ok = $stmt->execute();
        $stmt->close();
    }

    if ($ok) {
        echo json_encode(['ok' => true, 'grade' => $grade]);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Failed to save grade']);
    }
    exit;
}

// AJAX: return irregular subjects for a student and semester
if (isset($_GET['action']) && $_GET['action'] === 'get_irregular') {
  header('Content-Type: application/json');
  $student_id = trim($_GET['student_id'] ?? '');
  $ys = trim($_GET['ys'] ?? ''); // format expected: '1-1'

  if ($student_id === '' || $ys === '') {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Missing parameters']);
    exit;
  }

  [$y, $s] = array_map('intval', explode('-', $ys));

  // Determine actual column names in irregular_db
  $colsRes = $conn->query("SHOW COLUMNS FROM irregular_db");
  $columns = [];
  while ($c = $colsRes->fetch_assoc()) $columns[] = $c['Field'];

  $yearCol = in_array('year_level', $columns) ? 'year_level' : (in_array('year', $columns) ? 'year' : 'year_level');
  $semCol = in_array('semester', $columns) ? 'semester' : (in_array('sem', $columns) ? 'sem' : 'semester');

  $lecCol = in_array('lec_units', $columns) ? 'lec_units' : (in_array('lecture_units', $columns) ? 'lecture_units' : null);
  $labCol = in_array('lab_units', $columns) ? 'lab_units' : null;
  $statusCol = in_array('status', $columns) ? 'status' : null;

  $selectCols = [];
  $selectCols[] = "id";
  $selectCols[] = "course_code";
  $selectCols[] = "course_title";
  if ($lecCol) $selectCols[] = "$lecCol AS lec_units"; else $selectCols[] = "0 AS lec_units";
  if ($labCol) $selectCols[] = "$labCol AS lab_units"; else $selectCols[] = "0 AS lab_units";
  $selectCols[] = "total_units";
  if ($statusCol) $selectCols[] = "$statusCol AS status";

  $sql = "SELECT " . implode(',', $selectCols) . " FROM irregular_db WHERE student_id = ? AND $yearCol = ? AND $semCol = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'DB error: ' . $conn->error]);
    exit;
  }
  $stmt->bind_param('sii', $student_id, $y, $s);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
  }
  echo json_encode(['success' => true, 'data' => $rows]);
  exit;
}

$studentId = trim($_GET['student_id'] ?? $_POST['student_id'] ?? '');
$program = trim($_GET['program'] ?? $_POST['program'] ?? '');
$fiscalYear = trim($_GET['fiscal_year'] ?? $_POST['fiscal_year'] ?? '');

$student = null;
if ($studentId !== '') {
    if (columnExists($conn, 'signin_db', 'student_id')) {
        // First, get the column names from the signin_db table
        $result = $conn->query("SHOW COLUMNS FROM signin_db");
        $columns = [];
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Build the SELECT query with only existing columns
        $selectFields = [];
        $fieldMappings = [
            'student_id' => 'student_id',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'student_name' => 'name',  // Common alternative names
            'name' => 'name',
            'full_name' => 'full_name',
            'programs' => 'program',   // Common alternative names
            'program' => 'program',
            'course' => 'course',
            'curriculum' => 'curriculum',
            'classification' => 'classification',
            'category' => 'category',
            'year_level' => 'year_level'
        ];
        
        // Always include CONCAT for full name
        $selectFields[] = "CONCAT(firstname, ' ', lastname) AS full_name";
        
        foreach ($fieldMappings as $field => $dbField) {
            if (in_array($dbField, $columns)) {
                $selectFields[] = "`$dbField` AS `$field`";
            }
        }
        
        if (!empty($selectFields)) {
            $sql = "SELECT " . implode(", ", $selectFields) . " FROM signin_db WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $studentId);
                $stmt->execute();
                $student = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }
    }
}

// Debug: Log student data for troubleshooting
error_log("Student data: " . print_r($student, true));

// Normalize program name
$program = '';

// Try to get program from different possible field names
$possibleProgramFields = ['programs', 'program', 'course', 'program_name'];
foreach ($possibleProgramFields as $field) {
    if (!empty($student[$field])) {
        $program = trim($student[$field]);
        break;
    }
}

// If still no program, get it from the signin_db table
if (empty($program) && !empty($studentId)) {
    $stmt = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $program = $row['course'];
        }
        $stmt->close();
    }
}

// Check for program in various formats
$program = strtoupper(trim($program));
if (strpos($program, 'BSIT') !== false) {
    $program = 'BSIT';
} elseif (strpos($program, 'BSCS') !== false) {
    $program = 'BSCS';
} else {
    // If we still can't determine the program, show an error but don't default
    error_log("Warning: Could not determine program for student $studentId. Program value: " . ($program ?: 'empty'));
    $program = ''; // Will trigger the program selection form
}

if ($fiscalYear === '') {
    $fy = latestFiscalYear($conn, $program);
    if ($fy) $fiscalYear = $fy;
}

// Fetch curriculum for program (optionally fiscal year)
$hasYearSem = columnExists($conn, 'curriculum', 'year_semester');
$codeCol = columnExists($conn, 'curriculum', 'course_code') ? 'course_code' : (columnExists($conn, 'curriculum', 'course_code') ? 'course_code' : 'course_code');
$titleCol = columnExists($conn, 'curriculum', 'course_title') ? 'course_title' : (columnExists($conn, 'curriculum', 'course_title') ? 'course_title' : 'course_title');
$lecCol = columnExists($conn, 'curriculum', 'lec_units') ? 'lec_units' : (columnExists($conn, 'curriculum', 'lecture_units') ? 'lecture_units' : 'lec_units');
$labCol = columnExists($conn, 'curriculum', 'lab_units') ? 'lab_units' : 'lab_units';
$totalCol = columnExists($conn, 'curriculum', 'total_units') ? 'total_units' : (columnExists($conn, 'curriculum', 'units') ? 'units' : 'units');
// Check which prerequisite column exists, if any
$preCol = '';
if (columnExists($conn, 'curriculum', 'prerequisites')) {
    $preCol = 'prerequisites';
} elseif (columnExists($conn, 'curriculum', 'pre_req')) {
    $preCol = 'prerequisites';
} elseif (columnExists($conn, 'curriculum', 'prerequisites')) {
    $preCol = 'prerequisites';
}

$curriculum = [];
// Only include prerequisite in the select if we found a valid column
$selectFields = [
    "$codeCol AS code",
    "$titleCol AS title",
    "$lecCol AS lec",
    "$labCol AS lab",
    "$totalCol AS total_units"
];

if ($preCol) {
    $selectFields[] = "$preCol AS prereq";
} else {
    // If no prerequisite column exists, include a NULL value
    $selectFields[] = "NULL AS prereq";
}

$sql = "SELECT " . implode(", ", $selectFields);
if ($hasYearSem) {
    $sql .= ", year_semester AS ys";
} else {
    $sql .= ", year, semester";
}
$sql .= " FROM curriculum WHERE 1=1";
$params = [];
$types = '';
if (columnExists($conn, 'curriculum', 'program')) {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= 's';
}
if ($fiscalYear !== '' && columnExists($conn, 'curriculum', 'fiscal_year')) {
    $sql .= " AND fiscal_year = ?";
    $params[] = $fiscalYear;
    $types .= 's';
}
$sql .= $hasYearSem ? " ORDER BY ys, code" : " ORDER BY year, semester, code";
$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rowCount = 0;
    while ($row = $res->fetch_assoc()) {
        $ys = $hasYearSem ? normalizeSemesterKey($row['ys']) : (string)($row['year'] . '-' . (int)$row['semester']);
        if (!isset($curriculum[$ys])) $curriculum[$ys] = [];
        $curriculum[$ys][] = $row;
        $rowCount++;
    }
    $stmt->close();
    
    // Debug output
    if ($rowCount === 0) {
        $debugInfo = [
            'query' => $sql,
            'params' => $params,
            'program' => $program,
            'fiscalYear' => $fiscalYear,
            'hasYearSem' => $hasYearSem
        ];
        error_log("No curriculum found. Debug info: " . print_r($debugInfo, true));
    }
}

// Fetch grades for the student with normalization and prefix-year-sem fallback
$gradesByCode = [];
$gradesByPrefixYearSem = [];
if ($studentId !== '') {
    // Get all grades for the student, ordered by year and semester
    $gstmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ? ORDER BY year, sem");
    if ($gstmt) {
        $gstmt->bind_param('s', $studentId);
        $gstmt->execute();
        $gres = $gstmt->get_result();
        while ($g = $gres->fetch_assoc()) {
            // Try both possible column names for course code
            $code = !empty($g['course_code']) ? $g['course_code'] : $g['course_code'];
            if (empty($code)) continue;
            
            // Normalize the code and store original for debugging
            $originalCode = $code;
            $norm = normalizeCourseCode($code);
            $gradeValue = !empty($g['final_grade']) ? $g['final_grade'] : (!empty($g['grade']) ? $g['grade'] : null);
            
            if (empty($gradeValue)) continue;  // Skip if no grade value
            
            // Store the grade with multiple key formats for flexible matching
            $gradesByCode[$norm] = $gradeValue;  // Normalized code (e.g., 'CCS-0001')
            $gradesByCode[$code] = $gradeValue;  // Original code as in DB
            
            // Store with year and semester for better matching
            $year = intval($g['year'] ?? 0);
            $sem = intval($g['sem'] ?? 0);
            
            if ($year && $sem) {
                // Format: CCS-0001_1_1
                $gradesByCode["{$norm}_{$year}_{$sem}"] = $gradeValue;
                // Format: CCS-1-1
                $gradesByCode["{$norm}-{$year}-{$sem}"] = $gradeValue;
                
                // For subjects with prefixes (e.g., NSTP, PE)
                if (preg_match('/^([A-Z]+)/', $norm, $matches)) {
                    $prefix = $matches[1];
                    // Format: NSTP-1-1
                    $gradesByCode["{$prefix}-{$year}-{$sem}"] = $gradeValue;
                    // Store in prefix-year-sem array
                    $gradesByPrefixYearSem["{$prefix}-{$year}-{$sem}"] = $gradeValue;
                }
            }
            
            // Enhanced debug log with more context
            error_log(sprintf(
                "[Grade Loaded] Student: %s | Course: %s (Normalized: %s) | Year: %d | Sem: %d | Grade: %s | Program: %s",
                $studentId,
                $originalCode,
                $norm,
                $year,
                $sem,
                $gradeValue,
                $program
            ));
            
            // Log all grade entries for this student
            if ($year >= 3) {  // Only log for 3rd and 4th year courses
                error_log(sprintf(
                    "[3rd/4th Year Grade] %s: %s (Year %d, Sem %d) = %s",
                    $originalCode,
                    $course_title ?? 'No title',
                    $year,
                    $sem,
                    $gradeValue
                ));
            }
        }
        $gstmt->close();
    }
}

$ysOrder = ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Evaluation</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    /* Irregular subject styling */
    .irregular-subject {
      background-color: #f8f9fa !important;
      border-left: 4px solid #28a745 !important;
    }
    .irregular-subject:hover {
      background-color: #e9ecef !important;
    }
    
    /* Dropdown styles */
    .dropdown-menu {
        min-width: 200px;
        border: 1px solid rgba(0,0,0,.15);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        border-radius: 0.375rem;
        padding: 0.5rem 0;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        color: #212529;
        transition: all 0.2s;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #0d6efd;
    }
    
    .dropdown-divider {
        margin: 0.25rem 0;
    }
    
    .btn-group .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Course row styles */
    .course-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .course-row:hover {
        background-color: #f8f9fa !important;
    }
    .course-row.selected {
        background-color: #001f3f !important;
        color: white;
    }
    .course-row.selected .text-muted {
        color: #e0e0e0 !important;
    }
  .btn-add-irregular {
    display: inline-block;
    margin-left: 10px;
  }
  .course-row.selected .btn-add-irregular {
    display: inline-block;
  }
    .course-title-cell {
        position: relative;
        padding-right: 120px !important;
    }
    @media print {
      @page {
        size: A4 landscape;
        margin: 0.5cm;
      }
      body {
        zoom: 0.7;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .no-print, .print-button {
        display: none !important;
      }
      .main-content {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
      }
    }

    body {
      font-family: 'Arial', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 0;
    }

    .sidebar {
      width: 250px;
      background: #3a7bd5;
      color: white;
      padding: 20px 0;
      height: 100vh;
      position: fixed;
      overflow-y: auto;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .main-content {
      margin-left: 250px;
      padding: 20px;
      background: #f8f9fa;
    }

    .prospectus {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      position: relative;
      margin-bottom: 30px;
    }

    .print-button {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      background: #3a7bd5;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .print-button:hover {
      background: #2c5fb3;
    }

    .nav-link {
      color: rgba(255,255,255,0.8);
      padding: 10px 20px;
      margin: 5px 10px;
      border-radius: 5px;
      transition: all 0.3s;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .nav-link:hover, .nav-link.active {
      background: rgba(255,255,255,0.1);
      color: white;
    }

    .nav-link i {
      width: 20px;
      text-align: center;
    }

    .page-number {
      position: absolute;
      bottom: 10px;
      right: 20px;
      font-size: 12px;
      color: #666;
    }

    .form-control, .form-select {
      border-radius: 5px;
      border: 1px solid #ced4da;
      padding: 10px 15px;
    }

    .form-control:focus, .form-select:focus {
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    .btn-primary {
      background-color: #3a7bd5;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
    }

    .btn-primary:hover {
      background-color: #2c5fb3;
    }

    .semester-title {
      background:  #003366;
      padding: 10px 15px;
      border-radius: 5px;
      font-weight: 600;
      color:       #f1f5f9;
      margin-bottom: 15px;
      border-left: 4px solid #3a7bd5;
    }

    .table {
      border: none;
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      margin-bottom: 1rem;
      color: #333;
    }

    .table th {
      background-color: #3a7bd5;
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.5px;
      padding: 12px 8px;
      vertical-align: middle;
      border: none;
    }

    .table td, .table th {
      padding: 0.75rem;
      vertical-align: middle;
    }

    .table-hover tbody tr:hover {
      background-color: #f8f9fa;
    }

    /* Grade row styling */
    .passed-row {
      background-color: #f0fdf4 !important;
    }

    .warning-row {
      background-color: #fffbeb !important;
      color: #b45309;
    }

    .failed-row {
      background-color: #fef2f2 !important;
      color: #b91c1c;
    }
    
    .prereq-failed {
      background-color: #f8d7da !important;
      color: #721c24 !important;
      border: 2px solid #dc3545 !important;
      font-weight: 500;
    }
    
    .prereq-failed .grade-cell {
      background-color: #dc3545 !important;
      color: white !important;
      font-weight: bold;
    }
    
    /* Grade cell styling */
    .grade-cell {
      font-weight: 600;
      color: inherit;
      transition: all 0.2s ease;
    }
    
    /* Row hover effects */
    .course-row {
      transition: background-color 0.2s ease;
    }
    
    .passed-row:hover, .failed-row:hover, .warning-row:hover {
      opacity: 0.9;
      transform: translateX(2px);
    }

    .badge-program {
      font-size: 1rem;
      padding: 0.35em 0.65em;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="d-flex flex-column align-items-center mb-4">
     
      <img src="regs.jpg" style="width: 120px; margin-top: 10px;">
    </div>
    
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a href="dashboardr.php" class="nav-link">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
     
      <li class="nav-item mb-2">
        <a href="registrar.php" class="nav-link active">
          <i class="bi bi-journal-text"></i> Student List
        </a>
      </li>
    </ul>
  </div>
  
  <!-- Main Content -->
  <div class="main-content">
    <!-- Print Button -->
    
    
    <div class="container py-3">
      <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Student Evaluation</h1>
  </div>
  <div class="container my-4">
    <form method="GET" class="row g-2 align-items-end mb-3">
      <div class="col-sm-4">
        <label class="form-label">Student ID</label>
        <input type="text" name="student_id" class="form-control" value="<?= htmlspecialchars($studentId) ?>" required>
      </div>
      <div class="col-sm-3">
        <label class="form-label">Program</label>
        <select name="program" class="form-select">
          <option value="BSCS" <?= $program==='BSCS'?'selected':'' ?>>BSCS</option>
          <option value="BSIT" <?= $program==='BSIT'?'selected':'' ?>>BSIT</option>
        </select>
      </div>
      <?php if (columnExists($conn, 'curriculum', 'fiscal_year')): ?>
      <div class="col-sm-3">
        <label class="form-label">Fiscal Year</label>
        <input type="text" name="fiscal_year" class="form-control" value="<?= htmlspecialchars($fiscalYear) ?>" placeholder="e.g. 2024-2025">
      </div>
      <?php endif; ?>
      <div class="col-sm-2">
        <button class="btn btn-primary w-100" type="submit">Evaluate</button>
      </div>
    </form>
  </div>

<?php if ($student): 
  // Calculate grand total of all units for the header
  $grandTotalUnits = 0;
  if (isset($ysOrder) && isset($curriculum)) {
      foreach ($ysOrder as $ysKey) {
          if (isset($curriculum[$ysKey])) {
              foreach ($curriculum[$ysKey] as $course) {
                  $grandTotalUnits += (float)($course['total_units'] ?? 0);
              }
          }
      }
  }
?>
  <!-- Watermark -->
  <div class="watermark" style="position: absolute; opacity: 0.1; font-size: 5em; transform: rotate(-45deg); top: 30%; left: 10%; z-index: 0; pointer-events: none;">
    FOR ADMIN USE ONLY
  </div>
  
  <div class="prospectus">
    <div class="text-center mb-4">
      <h3 class="mb-1">CITY COLLEGE OF CALAMBA</h3>
      <p class="text-muted mb-2">Office of the College Registrar</p>
      <div class="d-flex justify-content-center align-items-center gap-3">
        <span class="badge bg-primary badge-program"><?= htmlspecialchars($program) ?></span>
        <?php if ($fiscalYear): ?>
          <span class="badge bg-secondary">Curriculum <?= htmlspecialchars($fiscalYear) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="row mb-4">
  <div class="col-md-3">
    <div class="d-flex align-items-center mb-2">
      <span class="text-muted me-2"><i class="bi bi-person-fill"></i></span>
      <div>
        <div class="small text-muted">Student Name</div>
        <div class="fw-bold">
          <?php 
          if (!empty($student['firstname']) || !empty($student['lastname'])) {
              echo htmlspecialchars(trim(($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? '')));
          } else {
              echo htmlspecialchars($student['student_name'] ?? '');
          }
          ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="d-flex align-items-center mb-2">
      <span class="text-muted me-2"><i class="bi bi-person-badge"></i></span>
      <div>
        <div class="small text-muted">Student ID</div>
        <div class="fw-bold"><?= htmlspecialchars($student['student_id'] ?? '') ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="d-flex align-items-center mb-2">
      <span class="text-muted me-2"><i class="bi bi-tag"></i></span>
      <div>
        <div class="small text-muted">Classification</div>
        <div class="fw-bold">
          <?php 
          $checkYear = $GLOBALS['currentYear'] ?? date('Y');
          $checkSem = $GLOBALS['currentSem'] ?? 1;
          $isIrregular = checkIrregularStatus($conn, $studentId, $checkYear, $checkSem);
          $classificationStatus = $isIrregular ? 'irregular' : 'regular';
          $classification = ucfirst($classificationStatus);
          $badgeClass = $isIrregular ? 'badge bg-warning text-dark' : 'badge bg-primary text-white';
          $GLOBALS['studentClassificationStatus'] = $classificationStatus;
          ?>
          <span class="<?= $badgeClass ?> classification-badge" 
                role="button" 
                data-student-id="<?= htmlspecialchars($studentId) ?>"
                data-current-classification="<?= htmlspecialchars($classificationStatus) ?>"
                data-bs-toggle="tooltip" 
                title="Click to change classification"
                style="cursor: pointer; transition: all 0.3s ease;">
            <?= $classification ?> <i class="bi bi-pencil-square ms-1" style="font-size: 0.8em;"></i>
          </span>
          <button class="btn btn-sm btn-outline-warning ms-2" 
                  onclick="cleanupDuplicates('<?= htmlspecialchars($studentId) ?>')"
                  data-bs-toggle="tooltip" 
                  title="Clean up duplicate irregular subjects">
            <i class="bi bi-trash"></i> Clean Dups
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="d-flex align-items-center mb-2">
      <span class="text-muted me-2"><i class="bi bi-journal-bookmark"></i></span>
      <div>
        <div class="small text-muted">GRAND TOTAL UNITS:</div>
        <div class="fw-bold"><?= number_format($grandTotalUnits ?? 0, 1) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="d-flex align-items-center mb-2">
      <span class="text-muted me-2"><i class="bi bi-calendar-check"></i></span>
      <div>
        <div class="small text-muted">Generated</div>
        <div class="fw-bold"><?= date('F j, Y h:i A') ?></div>
      </div>
    </div>
  </div>
</div>

    <?php
    $labels = [
      '1-1' => '<i class="bi bi-1-circle me-2"></i> FIRST YEAR • FIRST SEMESTER',
      '1-2' => '<i class="bi bi-2-circle me-2"></i> FIRST YEAR • SECOND SEMESTER',
      '2-1' => '<i class="bi bi-3-circle me-2"></i> SECOND YEAR • FIRST SEMESTER',
      '2-2' => '<i class="bi bi-4-circle me-2"></i> SECOND YEAR • SECOND SEMESTER',
      '3-1' => '<i class="bi bi-5-circle me-2"></i> THIRD YEAR • FIRST SEMESTER',
      '3-2' => '<i class="bi bi-6-circle me-2"></i> THIRD YEAR • SECOND SEMESTER',
      '4-1' => '<i class="bi bi-7-circle me-2"></i> FOURTH YEAR • FIRST SEMESTER',
      '4-2' => '<i class="bi bi-8-circle me-2"></i> FOURTH YEAR • SECOND SEMESTER',
    ];
    ?>

    <?php foreach ($labels as $ysKey => $label): 
        // Get the current year and semester from the first course in the curriculum
        if (!empty($curriculum)) {
            $firstCourse = reset($curriculum);
            $currentYear = $firstCourse['year'] ?? date('Y');
            $currentSem = $firstCourse['semester'] ?? 1;
        } else {
            $currentYear = date('Y');
            $currentSem = 1;
        }

        // Ensure these variables are available globally
        $GLOBALS['currentYear'] = $currentYear;
        $GLOBALS['currentSem'] = $currentSem;

        // Get current year and semester from the first part of the key (e.g., '1-1' -> year=1, sem=1)
        $currentYearSem = explode('-', $ysKey);
        $currentYear = (int)($currentYearSem[0] ?? 1);
        $currentSem = (int)($currentYearSem[1] ?? 1);
        
        // Calculate total units for this semester, excluding courses with failed prerequisite
        $semesterUnits = 0;
        if (isset($curriculum[$ysKey])) {
            foreach ($curriculum[$ysKey] as $courseCode => $course) {
                // Get the course's prerequisite using the detected column name
                $prereqColumn = $preCol ?: 'NULL';
                $prereqQuery = "SELECT $prereqColumn FROM curriculum WHERE course_code = ?";
                $prereqStmt = $conn->prepare($prereqQuery);
                $hasPrereqToCheck = !empty($preCol);
                
                if ($prereqStmt) {
                    $prereqStmt->bind_param('s', $courseCode);
                    $prereqStmt->execute();
                    $prereqResult = $prereqStmt->get_result();
                    
                    if ($prereqResult->num_rows > 0) {
                        $prereqData = $prereqResult->fetch_assoc();
                        $hasPrereqToCheck = !empty(trim($prereqData[$prereqColumn] ?? ''));
                    }
                }
                
                // Check if the student has a failing grade for this course
                $gradeQuery = "SELECT final_grade FROM grades_db 
                              WHERE student_id = ? AND course_code = ?";
                $gradeStmt = $conn->prepare($gradeQuery);
                $hasFailingGrade = false;
                
                if ($gradeStmt) {
                    $gradeStmt->bind_param('ss', $studentId, $courseCode);
                    $gradeStmt->execute();
                    $gradeResult = $gradeStmt->get_result();
                    
                    if ($gradeRow = $gradeResult->fetch_assoc()) {
                        // Convert grade to float for proper comparison
                        $finalGrade = $gradeRow['final_grade'];
                        // Check if grade is a failing grade (null, empty, or less than 5.00)
                        $hasFailingGrade = ($finalGrade === null || 
                                         $finalGrade === '' || 
                                         (is_numeric($finalGrade) && (float)$finalGrade < 5.00));
                    }
                }
                
                // Check if the student has a failing grade for this course
                // But skip prerequisite checking for regular 3rd and 4th year students
                $studentClassificationStatus = $GLOBALS['studentClassificationStatus'] ?? strtolower(trim($student['classification'] ?? 'regular'));
                $isIrregular = ($studentClassificationStatus === 'irregular');
                $isThirdYearOrAbove = ($currentYear >= 3);
                
                $hasFailedPrereq = false;
                if ($hasPrereqToCheck && (!$isThirdYearOrAbove || $isIrregular)) {
                    $hasFailedPrereq = hasFailedPrerequisites($conn, $studentId, $courseCode, $currentYear, $currentSem);
                    if ($hasFailedPrereq) {
                        error_log("EXCLUDING course $courseCode - Has failed prerequisites");
                        continue; // Skip to next course if prerequisite is not met
                    }
                }
                
                // Then check if this course has a failing grade
                if ($hasFailingGrade) {
                    error_log("EXCLUDING course $courseCode - Has failing grade");
                    continue; // Skip to next course if it has a failing grade
                }
                
                // If we get here, include the course in the total
                $courseUnits = (float)($course['total_units'] ?? 0);
                $semesterUnits += $courseUnits;
                error_log("INCLUDING course $courseCode with $courseUnits units. New total: $semesterUnits");
            }
        }
        
        // Track regular and irregular units separately
        $regularUnits = $semesterUnits;
        $irregularUnits = 0;
        
        // Get irregular subjects for this semester
        $irregularSubjects = [];
        if (!empty($studentId)) {
            // First, get the actual column names from the table
            $columnsStmt = $conn->query("SHOW COLUMNS FROM irregular_db");
            $columnNames = [];
            while($col = $columnsStmt->fetch_assoc()) {
                $columnNames[] = $col['Field'];
            }
            
            // Log the column names for debugging
            error_log("Columns in irregular_db: " . implode(', ', $columnNames));
            
            // Use the actual column names in the query
            $yearColumn = in_array('year_level', $columnNames) ? 'year_level' : 'year';
            $semColumn = in_array('semester', $columnNames) ? 'semester' : 'sem';
            
            $irregularQuery = "SELECT id, course_code, course_title, 
                             total_units, $yearColumn as year_level, $semColumn as semester 
                             FROM irregular_db 
                             WHERE student_id = ? AND $yearColumn = ? AND $semColumn = ?";
            $irregularStmt = $conn->prepare($irregularQuery);
            if ($irregularStmt) {
                $irregularStmt->bind_param('sii', $studentId, $currentYear, $currentSem);
                $irregularStmt->execute();
                $irregularResult = $irregularStmt->get_result();
                while ($row = $irregularResult->fetch_assoc()) {
                    $irregularUnits += (float)($row['total_units'] ?? 0);
                    $irregularSubjects[] = [
                        'code' => $row['course_code'],
                        'title' => $row['course_title'],
                        'total_units' => (float)($row['total_units'] ?? 0),
                        'irregular' => true,
                        'id' => $row['id']
                    ];
                }
                $semesterUnits += $irregularUnits;
            }
        }
    ?>
  <div class="mb-4" data-ys="<?= htmlspecialchars($ysKey) ?>">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="semester-title d-flex align-items-center">
            <?= $labels[$ysKey] ?>
          </div>
            <div class="d-flex gap-2">
            <span class="badge bg-primary rounded-pill" data-bs-toggle="tooltip" title="Regular Units">
              <i class="bi bi-book me-1"></i> <?= number_format($regularUnits, 1) ?>
            </span>
            <!-- Clickable irregular units badge: opens subject selector for this semester -->
            <span class="badge bg-success rounded-pill open-select-subjects" role="button" data-ys="<?= htmlspecialchars($ysKey) ?>" data-bs-toggle="tooltip" title="View courses">
              <i class="bi bi-plus-circle me-1"></i> <?= number_format($irregularUnits, 1) ?>
            </span>
            <span class="badge bg-secondary rounded-pill">
              <i class="bi bi-calculator me-1"></i> Total: <?= number_format($irregularUnits, 1) ?>
            </span>
            <button class="btn btn-sm btn-outline-primary select-all-courses me-1" data-bs-toggle="tooltip" title="Toggle select all courses">
              <i class="bi bi-check2-square me-1"></i> Select All
            </button>
            <button class="btn btn-sm btn-primary save-semester-btn" data-ys="<?= htmlspecialchars($ysKey) ?>" data-bs-toggle="tooltip" title="Save selected courses">
              <i class="bi bi-save me-1"></i> Save Selected
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle" style="border: none; font-size: 0.9rem; border-collapse: collapse; border-spacing: 0;">
            <style>
              /* Remove all bottom borders by default */
              .table > :not(caption) > * > * {
                border-bottom-width: 0;
                box-shadow: none;
              }
              
              /* Add bottom border only to the first column (Final Grade) */
              .table > tbody > tr > td:first-child {
                border-bottom: 1px solid #dee2e6 !important;
              }
              
              /* Keep the top border for row separation */
              .table > :not(:first-child) {
                border-top: 2px solid #dee2e6;
              }
              
              /* Ensure the first column has the same border on hover */
              .table > tbody > tr:hover > td:first-child {
                border-bottom: 1px solid #dee2e6 !important;
              }
              
              /* Grade cell styling */
              .grade-cell {
                font-weight: normal;
                color: #000000;
              }
              
              .passed-grade {
                color: #198754; /* Green for passed grades */
                font-weight: bold;
              }
              
              .failed-grade {
                color: #dc3545; /* Red for failed grades */
                font-weight: bold;
              }
              
              .prereq-failed {
                color: #6c757d; /* Gray for prerequisite not met */
              }
              
              /* Failed course indicator */
              .failed-course-checkbox {
                position: relative;
              }
              
              .failed-course-checkbox::after {
                content: '✕';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #dc3545; /* Red color for the X */
                font-weight: bold;
                font-size: 1.2em;
                pointer-events: none; /* Allow clicking through the X */
              }
              
              /* Keep the checkbox visible but make it look disabled */
              .failed-course-checkbox input[type="checkbox"] {
                opacity: 0.5;
                cursor: not-allowed;
              }
            </style>
            <thead>
              <tr class="text-center" style="background-color: #3a7bd5; color: white; font-weight: bold;">
                <th style="width:5%; padding: 12px 8px;">Select</th>
                <th style="width:12%; padding: 12px 8px;"rgb(255, 255, 255); color: white; font-weight: bold;"></th>
                <th style="width:10%; padding: 12px 8px;">CODE</th>
                <th style="padding: 12px 8px; text-align: left;">COURSE TITLE</th>
                <th style="width:7%; padding: 12px 8px;">Lec</th>
                <th style="width:7%; padding: 12px 8px;">Lab</th>
                <th style="width:8%; padding: 12px 8px;">Units</th>
                <th style="width:15%; padding: 12px 8px;">Pre-Req</th>
                <th style="width:8%; padding: 12px 8px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rows = $curriculum[$ysKey] ?? [];
              $hasIrregular = !empty($irregularSubjects);
              $studentClassificationStatus = $GLOBALS['studentClassificationStatus'] ?? strtolower(trim($student['classification'] ?? 'regular'));
              
              // Debug: Log the current semester and number of rows
              error_log("Processing semester: $ysKey, Number of courses: " . count($rows));
              
              if (empty($rows) && !$hasIrregular):
              ?>
                <tr><td colspan="8" class="text-center text-muted">No curriculum data for this semester.</td></tr>
              <?php else:
                [$yr, $sm] = array_map('intval', explode('-', $ysKey));
                foreach ($rows as $subj):
                  $code = $subj['code'];
                  // Simplify grade matching logic
                  $grade = '';
                  $triedPatterns = [];
                  $matchedPattern = '';
                  
                  // 1. Try exact match first
                  if (isset($gradesByCode[$code])) {
                      $grade = $gradesByCode[$code];
                      $matchedPattern = $code;
                      $triedPatterns[] = "Exact match: $code";
                  }
                  
                  // 2. Try normalized code match (remove all non-alphanumeric characters and convert to uppercase)
                  if (empty($grade)) {
                      $normalizedCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
                      foreach ($gradesByCode as $key => $value) {
                          $normalizedKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $key));
                          if ($normalizedKey === $normalizedCode) {
                              $grade = $value;
                              $matchedPattern = $key;
                              $triedPatterns[] = "Normalized match: $key";
                              break;
                          }
                      }
                  }
                  
                  // 3. Try matching by course code prefix and number (e.g., "MATH 101" matches "MATH-101")
                  if (empty($grade) && preg_match('/^([A-Za-z]+)[\s-]*(\d+[A-Za-z]*)$/', $code, $matches)) {
                      $prefix = strtoupper($matches[1]);
                      $number = $matches[2];
                      $pattern1 = $prefix . $number;  // MATH101
                      $pattern2 = $prefix . '-' . $number;  // MATH-101
                      $pattern3 = $prefix . ' ' . $number;  // MATH 101
                      
                      foreach ([$pattern1, $pattern2, $pattern3] as $pattern) {
                          if (isset($gradesByCode[$pattern])) {
                              $grade = $gradesByCode[$pattern];
                              $matchedPattern = $pattern;
                              $triedPatterns[] = "Pattern match: $pattern";
                              break;
                          }
                      }
                  }
                  
                  // 4. Try matching by course title if available
                  if (empty($grade) && !empty($subj['title'])) {
                      $currentTitle = strtolower(trim($subj['title']));
                      
                      foreach ($gradesByCode as $key => $value) {
                          if (is_array($value) && !empty($value['course_title'])) {
                              $gradeTitle = strtolower(trim($value['course_title']));
                              if ($currentTitle === $gradeTitle) {
                                  $grade = $value;
                                  $matchedPattern = $key . ' (title match)';
                                  $triedPatterns[] = "Title match: {$value['course_title']}";
                                  break;
                              }
                          }
                      }
                  }
                  
                  // Debug logging for grade matching
                  if (!empty($grade)) {
                      $gradeValue = is_array($grade) ? ($grade['final_grade'] ?? $grade['grade'] ?? '') : $grade;
                      error_log(sprintf(
                          "[GRADE MATCH] %-10s → %-5s (via %s) | Tried: %s",
                          $code,
                          is_array($gradeValue) ? json_encode($gradeValue) : $gradeValue,
                          $matchedPattern,
                          implode(', ', $triedPatterns)
                      ));
                  } else {
                      error_log(sprintf(
                          "[GRADE NOT FOUND] %-10s | Tried: %d patterns | First 5 grades: %s",
                          $code,
                          count($triedPatterns),
                          implode(', ', array_slice(array_keys($gradesByCode), 0, 5))
                      ));
                  }
                  
                  // Initialize variables with enhanced grade handling
                  $gradeNum = null;
                  $displayGrade = '—';
                  $rowClass = '';
                  $prereqFailed = false;
                  $prereq = !empty($subj['prereq']) ? htmlspecialchars($subj['prereq']) : 'None';
                  
                  // Handle grade value extraction from array or direct value
                  $gradeValue = '';
                  $hasRecordedGrade = false;
                  $failedCourse = false;
                  if (!empty($grade)) {
                      $gradeValue = is_array($grade) ? ($grade['final_grade'] ?? $grade['grade'] ?? '') : $grade;
                      error_log("Found grade for $code: " . print_r($gradeValue, true));
                  } else {
                      error_log("No grade found for $code, tried patterns: " . implode(', ', $triedPatterns ?? []));
                  }
                  $hasRecordedGrade = ($gradeValue !== '' && $gradeValue !== null);
                  
                  // Convert grade to number if possible
                  if (is_numeric($gradeValue)) {
                      $gradeNum = floatval($gradeValue);
                      $displayGrade = number_format($gradeNum, 2);
                      
                      // Determine row class based on grade
                      if ($gradeNum <= 3.25) {
                          $rowClass = 'passed-row';
                      } elseif ($gradeNum < 5.0) {
                          $rowClass = 'warning-row';
                          $displayGrade = 'INC';
                      } else {
                          $rowClass = 'failed-row';
                          $displayGrade = '5.00';
                          $failedCourse = true;
                      }
                  } elseif (!empty($gradeValue)) {
                      // Handle non-numeric grades (INC, DRP, etc.)
                      $displayGrade = strtoupper(trim($gradeValue));
                      if (in_array($displayGrade, ['INC', 'DRP', 'UD', 'IP', ''])) {
                          $rowClass = 'warning-row';
                      } elseif (in_array($displayGrade, ['PASS', 'COMPLETE'])) {
                          $rowClass = 'passed-row';
                      } else {
                          $rowClass = 'failed-row';
                          $failedCourse = true;
                      }
                  }
                  
                  
                  // Check if student is irregular (based on displayed classification)
                  $isIrregular = ($studentClassificationStatus === 'irregular');
                  
                  // Get current year and semester from the ysKey
                  [$yearLevel, $semester] = explode('-', $ysKey);
                  $isThirdYearOrAbove = ($yearLevel >= 3);
                  
                  // Determine prerequisite status
                  $prereq = trim($prereq);
                  $normalizedPrereq = strtolower($prereq);
                  $courseHasPrereq = !empty($prereq) && $prereq !== 'None' && $normalizedPrereq !== 'none' && $normalizedPrereq !== '' && $normalizedPrereq !== '-';
                  
                  // For regular 3rd and 4th year students, don't block courses based on prerequisites
                  $shouldCheckPrereq = $isIrregular && $courseHasPrereq && !$isThirdYearOrAbove;
                  
                  // Check prerequisites only if needed
                  $prereqFailed = false;
                  if ($shouldCheckPrereq) {
                      if ($courseHasPrereq) {
                          $prereqCodes = array_map('trim', explode(',', $prereq));
                          foreach ($prereqCodes as $prereqCode) {
                              $prereqCode = trim($prereqCode);
                              $normPrereqCode = normalizeCourseCode($prereqCode);
                              $prereqGrade = trim((string)($gradesByCode[$normPrereqCode] ?? ''));
                              
                              // If prerequisite has a grade and it's failing (> 3.25), mark as failed
                              if ($prereqGrade !== '' && is_numeric($prereqGrade) && floatval($prereqGrade) > 3.25) {
                                  $prereqFailed = true;
                                  break;
                              }
                              // If prerequisite has no grade, check if it's a required course
                              elseif ($prereqGrade === '') {
                                  $prereqFailed = true;
                                  break;
                              }
                          }
                          
                          if ($prereqFailed) {
                              $rowClass = 'prereq-failed';
                          }
                      }
                  }
                  
                  $showPrereqLabel = $isIrregular && $prereqFailed && $courseHasPrereq && !$isThirdYearOrAbove;
                 
                 // Show actions when:
// 1. There's no grade recorded yet, OR
// 2. The student has passed but not with good grades (grade > 3.00 and <= 3.25)
// Hide actions for students with good grades (1.0 - 3.00)
// Hide actions for students with failed prerequisites (except regular 3rd/4th year students)
$showActions = (!$hasRecordedGrade || ($hasRecordedGrade && $gradeNum > 3.00 && $gradeNum <= 3.25)) && (!$prereqFailed || ($isThirdYearOrAbove && !$isIrregular));

              ?>
                <?php 
                // Debug: Log the display values
                error_log(sprintf(
                    "Displaying grade - Course: %s, Grade: %s, Display: %s, RowClass: %s, PrereqFailed: %s",
                    $code,
                    $gradeValue ?? 'null',
                    $displayGrade ?? 'null',
                    $rowClass ?? 'null',
                    $prereqFailed ? 'true' : 'false'
                ));
                ?>
                <tr class="course-row" style="background-color: #ffffff;" data-code="<?= htmlspecialchars($code) ?>">
                  <td class="text-center" style="padding: 12px 8px; position: relative;">
                    <?php if ($failedCourse): ?>
                    <div class="failed-course-checkbox">
                      <input type="checkbox" 
                             class="form-check-input course-checkbox" 
                             disabled
                             data-code="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-lec="<?= htmlspecialchars($subj['lec']) ?>"
                             data-lab="<?= htmlspecialchars($subj['lab']) ?>"
                             data-total-units="<?= htmlspecialchars($subj['total_units']) ?>"
                             data-prereq="<?= htmlspecialchars($prereq) ?>"
                             data-program="<?= htmlspecialchars($student['program'] ?? '') ?>">
                    </div>
                    <?php else: ?>
                    <input type="checkbox" 
                           class="form-check-input course-checkbox" 
                           data-code="<?= htmlspecialchars($code) ?>"
                           data-title="<?= htmlspecialchars($subj['title']) ?>"
                           data-lec="<?= htmlspecialchars($subj['lec']) ?>"
                           data-lab="<?= htmlspecialchars($subj['lab']) ?>"
                           data-total-units="<?= htmlspecialchars($subj['total_units']) ?>"
                           data-prereq="<?= htmlspecialchars($prereq) ?>"
                           data-program="<?= htmlspecialchars($student['program'] ?? '') ?>">
                    <?php endif; ?>
                  </td>
                  <td class="text-center grade-cell <?= $rowClass ?> <?= $prereqFailed ? 'prereq-failed' : '' ?>" 
                      style="padding: 12px 8px;" 
                      data-code="<?= htmlspecialchars(normalizeCourseCode($code)) ?>">
                    <strong>
                        <?= $showPrereqLabel ? 'PREREQ' : ($displayGrade ?? '—') ?>
                    </strong>
                  </td>
                  <td class="text-center" style="padding: 12px 8px;">
                    <span class="badge" style="background-color: #e9ecef; color: #212529; border: 1px solid #dee2e6; padding: 0.25em 0.5em; font-size: 0.9em; border-radius: 4px;"><?= htmlspecialchars($code) ?></span>
                  </td>
                  <td class="course-title-cell" style="padding: 12px 8px;">
                    <div class="fw-bold"><?= htmlspecialchars($subj['title']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($student['program'] ?? '') ?></div>
                  </td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['lec']) ?></td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['lab']) ?></td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['total_units']) ?></td>
                  <td class="text-center"><?= $prereq ?></td>
                  <td class="text-center" style="width: 120px;">
                    <?php if ($showActions): ?>
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-gear"></i> Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li>
                            <a class="dropdown-item add-subject-open" href="#" 
                               data-bs-toggle="modal" data-bs-target="#addSubjectModal"
                               data-code="<?= htmlspecialchars($code) ?>"
                               data-title="<?= htmlspecialchars($subj['title']) ?>"
                               data-units="<?= htmlspecialchars($subj['total_units'] ?? '3') ?>">
                              <i class="bi bi-plus-circle me-2"></i>Add subject
                            </a>
                          </li>
                          <?php if ($studentId !== ''): ?>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                           
                          </li>
                          <?php endif; ?>
                        </ul>
                      </div>
                    <?php else: ?>
                      <span class="text-muted" style="font-size: 0.8rem;">Not available</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              <!-- Total Units Row -->
              <tr style="background-color: #f8f9fa;">
                <td colspan="5" class="text-end fw-bold" style="padding: 12px 8px; border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;">
                  <span class="me-3">TOTAL UNITS:</span>
                </td>
                <td class="text-center fw-bold" style="padding: 12px 8px; border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;">
                 <?= number_format($irregularUnits, 1) ?>
                </td>
                <td colspan="2" style="border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
<?php endforeach; ?>


    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
      <div class="text-muted small">
        <i class="bi bi-shield-lock me-1"></i> For Official Use Only
      </div>
      <div class="text-muted small">
        Printed on <?= date('F j, Y h:i A') ?>
      </div>
    </div>
    <div class="page-number"></div>
  </div>
<?php elseif ($studentId !== ''): ?>
  <div class="container"><div class="alert alert-warning">No student found for ID: <?= htmlspecialchars($studentId) ?>.</div></div>
<?php else: ?>
  <div class="container"><div class="alert alert-info">Enter a student ID to view the evaluation.</div></div>
<?php endif; ?>

<?php if ($student): ?>
<!-- Page Number Script -->
<script>
  // Update page number for printing
  function updatePageNumber() {
    const pageNumbers = document.querySelectorAll('.page-number');
    const totalPages = pageNumbers.length;
    
    pageNumbers.forEach((el, index) => {
      el.textContent = `Page ${index + 1} of ${totalPages}`;
    });
  }
  
  // Call once on load
  document.addEventListener('DOMContentLoaded', updatePageNumber);
  
  // Update on print
  window.addEventListener('beforeprint', updatePageNumber);
</script>

<!-- Grade Edit Modal -->
<div class="modal fade"  tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        
            <label class="form-label">Course</label>
            <input type="text" class="form-control" id="eg_title" name="course_title" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Grade</label>
            <input type="number" step="0.25" min="1" max="5" class="form-control" id="eg_grade" name="grade" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="eg_save">Save</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select Subject Modal (lists semester courses for quick add) -->
<div class="modal fade" id="selectSubjectModal" tabindex="-1" aria-labelledby="selectSubjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="selectSubjectModalLabel">  Subject </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="selectSubjectContainer">
          <div class="table-responsive">
            <table class="table table-hover" id="selectSubjectTable">
              <thead>
                <tr>
                  <th style="width:10%">Code</th>
                  <th>Title</th>
                  <th style="width:10%">Lec</th>
                  <th style="width:10%">Lab</th>
                  <th style="width:10%">Units</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addSubjectModalLabel">Add Subject to Irregular</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addSubjectForm" action="" method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="add_irregular_subject">
          <input type="hidden" name="student_id" id="studentIdInput" value="<?= htmlspecialchars($studentId) ?>">
          <input type="hidden" name="course_code" id="courseCodeInput">
          
          <div class="mb-3">
            <label for="courseTitleInput" class="form-label">Course Title</label>
            <input type="text" class="form-control" id="courseTitleInput" name="course_title" readonly>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="yearSelect" class="form-label">Year</label>
              <select class="form-select" id="yearSelect" name="year" required>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="semSelect" class="form-label">Semester</label>
              <select class="form-select" id="semSelect" name="sem" required>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
              </select>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="lecUnitsInput" class="form-label">Lecture Units</label>
              <input type="number" class="form-control" id="lecUnitsInput" name="lec_units" min="0" step="0.5" value="3" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="labUnitsInput" class="form-label">Lab Units</label>
              <input type="number" class="form-control" id="labUnitsInput" name="lab_units" min="0" step="0.5" value="0" required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12 mb-3">
              <label for="totalUnitsInput" class="form-label">Total Units</label>
              <input type="number" class="form-control" id="totalUnitsInput" name="total_units" min="0.5" step="0.5" readonly>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="prerequisiteInput" class="form-label">Prerequisite</label>
            <input type="text" class="form-control" id="prerequisiteInput" name="prerequisites" placeholder="Enter prerequisites (e.g., CCS 0001, CCS 0002)">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Subject</button>|


        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add Subject button click
    const addSubjectModal = document.getElementById('addSubjectModal');
    if (addSubjectModal) {
    addSubjectModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      // Support programmatic prefill via dataset on the modal element
      const courseCode = button ? button.getAttribute('data-code') : (addSubjectModal.dataset.prefillCode || '');
      const courseTitle = button ? button.getAttribute('data-title') : (addSubjectModal.dataset.prefillTitle || '');
      const units = button ? button.getAttribute('data-units') : (addSubjectModal.dataset.prefillUnits || '3');
            
            // Get the current semester and year from the active tab or default to 1
            const activeTab = document.querySelector('.semester-title.active');
            let defaultYear = '1';
            let defaultSem = '1';
            
            if (activeTab) {
                const tabText = activeTab.textContent.trim();
                // Extract year and semester from the tab text (e.g., "FIRST YEAR • FIRST SEMESTER")
                const yearMatch = tabText.match(/FIRST|SECOND|THIRD|FOURTH/);
                if (yearMatch) {
                    const yearText = yearMatch[0];
                    defaultYear = {
                        'FIRST': '1',
                        'SECOND': '2',
                        'THIRD': '3',
                        'FOURTH': '4'
                    }[yearText] || '1';
                }
                
                if (tabText.includes('SECOND')) {
                    defaultSem = '2';
                }
            }
            
            // Update the modal's content
            const modalTitle = addSubjectModal.querySelector('.modal-title');
            const courseCodeInput = addSubjectModal.querySelector('#courseCodeInput');
            const courseTitleInput = addSubjectModal.querySelector('#courseTitleInput');
            const lecUnitsInput = addSubjectModal.querySelector('#lecUnitsInput');
            const labUnitsInput = addSubjectModal.querySelector('#labUnitsInput');
            const totalUnitsInput = addSubjectModal.querySelector('#totalUnitsInput');
            const yearSelect = addSubjectModal.querySelector('#yearSelect');
            const semSelect = addSubjectModal.querySelector('#semSelect');
            
            modalTitle.textContent = `Add ${courseCode} to Irregular`;
            courseCodeInput.value = courseCode;
            courseTitleInput.value = courseTitle;
            
            // Calculate total units when lec or lab units change
            function updateTotalUnits() {
                const lecUnits = parseFloat(lecUnitsInput.value) || 0;
                const labUnits = parseFloat(labUnitsInput.value) || 0;
                const total = lecUnits + labUnits;
                totalUnitsInput.value = total.toFixed(1);
                return total;
            }
            
            // Add event listeners for unit changes
            lecUnitsInput.addEventListener('input', updateTotalUnits);
            labUnitsInput.addEventListener('input', updateTotalUnits);
            
            // Set initial values
            if (units) {
                lecUnitsInput.value = units;
                labUnitsInput.value = '0';
                updateTotalUnits();
            }
            
      // If prefill year/sem provided on modal dataset, use them
      if (addSubjectModal.dataset.prefillYear) defaultYear = addSubjectModal.dataset.prefillYear;
      if (addSubjectModal.dataset.prefillSem) defaultSem = addSubjectModal.dataset.prefillSem;

      // Set default year and semester based on the current view
      if (yearSelect) yearSelect.value = defaultYear;
      if (semSelect) semSelect.value = defaultSem;
        });
        
        // Handle form submission
        const form = document.getElementById('addSubjectForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Create a new FormData object
                const formData = new FormData();
                
                // Explicitly add all form fields to ensure nothing is missed
                const formElements = this.elements;
                for (let i = 0; i < formElements.length; i++) {
                    const element = formElements[i];
                    if (element.name && !element.disabled && element.type !== 'file') {
                        if (element.type === 'checkbox' || element.type === 'radio') {
                            if (element.checked) {
                                formData.append(element.name, element.value);
                            }
                        } else {
                            formData.append(element.name, element.value);
                        }
                    }
                }
                
                // Debug: Log form data
                console.log('Form data being submitted:');
                const formDataObj = {};
                for (let [key, value] of formData.entries()) {
                    formDataObj[key] = value;
                }
                console.log(JSON.stringify(formDataObj, null, 2));
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
                
                fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message with Bootstrap toast
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '11';
                        toast.innerHTML = `
                            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-success text-white">
                                    <strong class="me-auto">Success</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    ${data.message || 'Subject added successfully!'}
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(addSubjectModal);
                        if (modal) modal.hide();
                        
                        // Reload the page after a short delay
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        throw new Error(data.message || 'Failed to add subject');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error message
                    const errorMessage = error.message.includes('<!DOCTYPE html>') 
                        ? 'An error occurred. Please try again.' 
                        : error.message;
                    
                    alert('Error: ' + errorMessage);
                })
                .finally(() => {
                    // Reset button state
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                });
            });
        }
    }
    
    // Handle course row selection
    document.querySelectorAll('.course-row').forEach(row => {
        // Make the entire row clickable
        row.addEventListener('click', function(e) {
      // Don't trigger if clicking on the Add buttons or dropdown add item
      if (e.target.closest('.btn-add-irregular') || e.target.closest('.add-subject-open')) {
                return;
            }
            
            const isSelected = this.classList.contains('selected');
            
            // Remove selection from all rows
            document.querySelectorAll('.course-row').forEach(r => {
                r.classList.remove('selected');
            });
            
            // Toggle selection on clicked row
            if (!isSelected) {
                this.classList.add('selected');
            }
        });
    });

  // Handle opening the semester subject selector when clicking the irregular-units badge
  document.addEventListener('click', function(e) {
    const badge = e.target.closest('.open-select-subjects');
    if (!badge) return;

    e.preventDefault();
    const ys = badge.getAttribute('data-ys');
    if (!ys) return;

    // Find the semester block
    const semBlock = document.querySelector(`div.mb-4[data-ys="${ys}"]`);
    const tbody = document.querySelector('#selectSubjectTable tbody');
    tbody.innerHTML = '';

    // Fetch irregular subjects for this student and semester
    const studentId = '<?= htmlspecialchars($studentId) ?>';
    if (!studentId) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No student selected.</td></tr>';
      const selectModalEl = document.getElementById('selectSubjectModal');
      bootstrap.Modal.getOrCreateInstance(selectModalEl).show();
      return;
    }

    fetch(`?action=get_irregular&student_id=${encodeURIComponent(studentId)}&ys=${encodeURIComponent(ys)}`)
      .then(r => r.json())
      .then(data => {
        if (!data.success) {
          tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${data.message || 'Failed to load irregular subjects'}</td></tr>`;
        } else if (!data.data || data.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No irregular subjects for this student in this semester.</td></tr>';
        } else {
          data.data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${r.course_code}</td>
              <td>${r.course_title}</td>
              <td class="text-center">${r.lec_units ?? '0'}</td>
              <td class="text-center">${r.lab_units ?? '0'}</td>
              <td class="text-center">${r.total_units ?? ''}</td>
            `;
            tbody.appendChild(tr);
          });
        }

        const selectModalEl = document.getElementById('selectSubjectModal');
        bootstrap.Modal.getOrCreateInstance(selectModalEl).show();
      })
      .catch(err => {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading irregular subjects.</td></tr>';
        const selectModalEl = document.getElementById('selectSubjectModal');
        bootstrap.Modal.getOrCreateInstance(selectModalEl).show();
      });
  });

  // Delegate click from select modal to open addSubjectModal prefilled
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.select-add');
    if (!btn) return;
    e.preventDefault();

    const code = btn.getAttribute('data-code') || '';
    const title = btn.getAttribute('data-title') || '';
    const units = btn.getAttribute('data-units') || '3';

    // Prefill the addSubjectModal via dataset then show it
    addSubjectModal.dataset.prefillCode = code;
    addSubjectModal.dataset.prefillTitle = title;
    addSubjectModal.dataset.prefillUnits = units;

    // Optionally set year/sem based on current view; leave defaults otherwise
    // Close the selector modal first
    const selectModalEl = document.getElementById('selectSubjectModal');
    const selectModal = bootstrap.Modal.getInstance(selectModalEl);
    if (selectModal) selectModal.hide();

    // Show addSubjectModal programmatically
    const modal = bootstrap.Modal.getOrCreateInstance(addSubjectModal);
    modal.show();
  });
});
</script>
<script>
(function(){
  const modalEl = document.getElementById('editGradeModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('editGradeForm');
  const btnSave = document.getElementById('eg_save');

  document.querySelectorAll('.').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('eg_student').value = btn.dataset.student;
      document.getElementById('eg_code').value = btn.dataset.code;
      document.getElementById('eg_title').value = btn.dataset.title;
      document.getElementById('eg_year').value = btn.dataset.year;
      document.getElementById('eg_sem').value = btn.dataset.sem;
      document.getElementById('eg_grade').value = btn.dataset.grade || '';
      modal.show();
    });
  });

  // Add input validation for grade field
  const gradeInput = document.getElementById('eg_grade');
  if (gradeInput) {
    gradeInput.addEventListener('input', function(e) {
      // Allow only numbers and one decimal point
      this.value = this.value.replace(/[^0-9.]/g, '')
        .replace(/(\..*)\./g, '$1')  // Only allow one decimal point
        .replace(/^(\d{1,2})(\.\d{0,2})?.*$/, '$1$2'); // Limit to 2 decimal places
      
      // Validate range (0.00 to 5.00)
      const value = parseFloat(this.value);
      if (!isNaN(value) && (value < 0 || value > 5)) {
        this.setCustomValidity('Grade must be between 0.00 and 5.00');
      } else {
        this.setCustomValidity('');
      }
    });
  }

  btnSave.addEventListener('click', async () => {
    // Get form data
    const fd = new FormData(form);
    const gradeValue = parseFloat(fd.get('grade'));
    
    // Validate grade
    if (isNaN(gradeValue) || gradeValue < 0 || gradeValue > 5) {
      alert('Please enter a valid grade between 0.00 and 5.00');
      return;
    }
    
    try {
      // Show loading state
      btnSave.disabled = true;
      btnSave.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
      
      const res = await fetch('save_grade.php', { 
        method: 'POST', 
        body: fd 
      });
      
      const data = await res.json();
      console.log('Grade save response:', data);
      
      if (data.success) {
        const code = document.getElementById('eg_code').value;
        const norm = code.replace(/[^A-Z0-9]/gi,'').toUpperCase();
        console.log('Looking for cell with code:', norm);
        
        // Try to find the cell using the course code in the data-code attribute
        let cell = document.querySelector(`.grade-cell[data-code="${CSS.escape(norm)}"]`);
        
        // If not found, try to find by course title
        if (!cell) {
          const courseTitle = document.getElementById('eg_title').value;
          console.log('Trying to find by title:', courseTitle);
          const rows = document.querySelectorAll('tr.course-row');
          for (const row of rows) {
            const titleCell = row.querySelector('.course-title-cell');
            if (titleCell && titleCell.textContent.trim() === courseTitle.trim()) {
              cell = row.querySelector('.grade-cell');
              console.log('Found cell by title');
              break;
            }
          }
        }
        
        if (cell) {
          console.log('Found cell, updating display');
          const row = cell.closest('tr');
          
          // Clear existing classes
          row.classList.remove('passed-row', 'failed-row', 'prereq-failed');
          cell.classList.remove('passed-grade', 'failed-grade');
          
          // Set grade display and styling
          if (gradeValue <= 3.25) { 
            cell.textContent = gradeValue.toFixed(2);
            cell.classList.add('passed-grade');
            row.classList.add('passed-row');
          } else if (gradeValue >= 3.25 && gradeValue <= 4.0) {
            cell.textContent = 'INC';
            cell.classList.add('failed-grade');
            row.classList.add('failed-row');
          } else if (gradeValue >= 5.0) {
            cell.textContent = 'Failed';
            cell.classList.add('failed-grade');
            row.classList.add('failed-row');
          } else {
            cell.textContent = '—';
          }
          
          // Force a reflow to ensure the UI updates
          cell.style.display = 'none';
          cell.offsetHeight; // Trigger reflow
          cell.style.display = '';
          
          console.log('Cell updated successfully');
        } else {
          console.error('Could not find grade cell for code:', norm);
        }
        
        // Close the modal
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '1100';
        toast.innerHTML = `
          <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
              <strong class="me-auto">Success</strong>
              <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
              Grade updated successfully!
            </div>
          </div>
        `;
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
          toast.remove();
        }, 3000);
        
      } else {
        alert(data.message || 'Failed to save grade');
      }
    } catch (e) {
      console.error('Error saving grade:', e);
      alert('Error saving grade. Please check the console for details.');
    }
  });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Function to show a toast notification
  function showToast(message, type = 'success') {
      const toastContainer = document.createElement('div');
      toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
      toastContainer.style.zIndex = '1100';
      
      const toast = document.createElement('div');
      toast.className = `toast show align-items-center text-white bg-${type} border-0`;
      toast.role = 'alert';
      toast.setAttribute('aria-live', 'assertive');
      toast.setAttribute('aria-atomic', 'true');
      
      toast.innerHTML = `
          <div class="d-flex">
              <div class="toast-body">
                  ${message}
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
      `;
      
      toastContainer.appendChild(toast);
      document.body.appendChild(toastContainer);
      
      // Auto-remove after 3 seconds
      setTimeout(() => {
          toastContainer.remove();
      }, 3000);
  }

  // Handle Save Semester button click
  document.addEventListener('click', async function(e) {
      const saveBtn = e.target.closest('.save-semester-btn');
      if (!saveBtn) return;
      
      console.log('Save button clicked');
      const semester = saveBtn.getAttribute('data-ys');
      const studentId = '<?= htmlspecialchars($student['student_id'] ?? '') ?>';
      
      console.log('Semester:', semester, 'Student ID:', studentId);
      
      if (!studentId) {
          console.log('No student ID found');
          showToast('Student ID is required', 'danger');
          return;
      }

      // Get the semester container where the save button was clicked
      const semesterContainer = saveBtn.closest('.mb-4');
      if (!semesterContainer) return;
      
      // Get all checked checkboxes from ALL semesters (not just this one)
      const checkboxes = document.querySelectorAll('.course-checkbox:checked');
      
      console.log('Found checkboxes:', checkboxes.length);
      checkboxes.forEach((cb, index) => {
          console.log(`Checkbox ${index}:`, cb.getAttribute('data-code'));
      });
      
      if (checkboxes.length === 0) {
          console.log('No checkboxes selected');
          showToast('Please select at least one course to save', 'warning');
          return;
      }
      
      // Check if courses are from different semesters
      const sourceSemesters = new Set();
      checkboxes.forEach(cb => {
          const row = cb.closest('tr');
          const semesterSection = row.closest('.mb-4');
          const semesterTitle = semesterSection.querySelector('.semester-title');
          if (semesterTitle) {
              sourceSemesters.add(semesterTitle.textContent.trim());
          }
      });
      
      if (sourceSemesters.size > 1) {
          const confirmMessage = `You are saving courses from multiple semesters (${Array.from(sourceSemesters).join(', ')}) to semester ${semester}. Continue?`;
          if (!confirm(confirmMessage)) {
              return;
          }
      }
      
      const courses = [];
      const [year, sem] = semester.split('-').map(Number);
      
      // Collect data from checked checkboxes
      checkboxes.forEach(checkbox => {
          courses.push({
              code: checkbox.getAttribute('data-code'),
              title: checkbox.getAttribute('data-title'),
              lec_units: parseFloat(checkbox.getAttribute('data-lec') || 0),
              lab_units: parseFloat(checkbox.getAttribute('data-lab') || 0),
              total_units: parseFloat(checkbox.getAttribute('data-total-units') || 0),
              prerequisites: checkbox.getAttribute('data-prereq') || '',
              program: checkbox.getAttribute('data-program') || '', // Get program from checkbox data
              year_level: year,
              semester: sem,
              year_semester: semester
          });
      });

      try {
          console.log('Sending save request with courses:', courses);
          
          // Show loading state
          const originalText = saveBtn.innerHTML;
          saveBtn.disabled = true;
          saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

          const response = await fetch('save_semester.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  student_id: studentId,
                  semester: semester,
                  courses: courses
              })
          });

          console.log('Response received:', response);
          const result = await response.json();
          console.log('Response JSON:', result);
          
          if (result.success) {
              showToast(`Successfully saved ${result.courses_saved} courses for semester ${semester}`, 'success');
              
              // Optional: Reload the page after a short delay
              setTimeout(() => {
                  window.location.reload();
              }, 1500);
          } else {
              throw new Error(result.message || 'Failed to save semester');
          }
      } catch (error) {
          console.error('Error saving semester:', error);
          showToast('Error saving semester: ' + error.message, 'danger');
      } finally {
          // Reset button state
          if (saveBtn) {
              saveBtn.disabled = false;
              saveBtn.innerHTML = originalText || '<i class="bi bi-save me-1"></i> Save';
          }
      }
  });
});
</script>
<script>
  // Add click handler for checkboxes to highlight selected rows
  document.addEventListener('DOMContentLoaded', function() {
      document.addEventListener('change', function(e) {
          const checkbox = e.target;
          if (checkbox.classList.contains('course-checkbox')) {
              const row = checkbox.closest('tr');
              if (checkbox.checked) {
                  row.classList.add('selected');
              } else {
                  row.classList.remove('selected');
              }
          }
      });
      
      // Add select all/none functionality
      document.addEventListener('click', function(e) {
          if (e.target.classList.contains('select-all-courses')) {
              const container = e.target.closest('.mb-4');
              if (container) {
                  const checkboxes = container.querySelectorAll('.course-checkbox');
                  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                  
                  checkboxes.forEach(cb => {
                      cb.checked = !allChecked;
                      cb.dispatchEvent(new Event('change'));
                  });
                  
                  e.target.textContent = allChecked ? 'Select All' : 'Deselect All';
              }
          }
      });
  });
</script>
<script>
// Classification Update Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle classification badge click
    const classificationBadges = document.querySelectorAll('.classification-badge');
    
    classificationBadges.forEach(badge => {
        badge.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const studentId = this.getAttribute('data-student-id');
            const currentClassification = this.getAttribute('data-current-classification');
            
            // Create a simple confirmation dialog
            const newClassification = currentClassification === 'regular' ? 'irregular' : 'regular';
            const confirmMessage = `Change classification from "${currentClassification}" to "${newClassification}"?`;
            
            if (confirm(confirmMessage)) {
                // Show loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Updating...';
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
                
                // Send AJAX request to update classification
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'action': 'update_classification',
                        'student_id': studentId,
                        'classification': newClassification
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the badge
                        const newBadgeClass = newClassification === 'irregular' ? 'badge bg-warning text-dark' : 'badge bg-primary text-white';
                        this.className = newBadgeClass + ' classification-badge';
                        this.setAttribute('data-current-classification', newClassification);
                        this.innerHTML = newClassification.charAt(0).toUpperCase() + newClassification.slice(1) + ' <i class="bi bi-pencil-square ms-1" style="font-size: 0.8em;"></i>';
                        
                        // Show success message
                        showToast(data.message, 'success');
                        
                        // Update global classification status
                        window.studentClassificationStatus = newClassification;
                        
                        // Optionally refresh the page to update all classification-dependent elements
                        setTimeout(() => {
                            if (confirm('Classification updated successfully! Would you like to refresh the page to see all changes?')) {
                                window.location.reload();
                            }
                        }, 1000);
                        
                    } else {
                        // Show error message
                        showToast(data.message || 'Failed to update classification', 'danger');
                        
                        // Restore original content
                        this.innerHTML = originalContent;
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }
                })
                .catch(error => {
                    console.error('Error updating classification:', error);
                    showToast('Error updating classification. Please try again.', 'danger');
                    
                    // Restore original content
                    this.innerHTML = originalContent;
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                })
                .finally(() => {
                    // Always restore pointer events and opacity after a delay
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            }
        });
        
        // Add hover effect
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Cleanup duplicates function
function cleanupDuplicates(studentId) {
    if (!studentId) {
        showToast('Student ID is required', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to clean up duplicate irregular subjects? This will remove duplicate entries and keep only the first occurrence.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'cleanup_duplicates');
    formData.append('student_id', studentId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Optionally refresh the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error cleaning up duplicates', 'error');
    });
}

// Toast notification function
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
    toast.style.cssText = 'margin-bottom: 10px; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}
</script>

<style>
/* Style for checkboxes */
.course-checkbox {
    cursor: pointer;
    transform: scale(1.2);
    margin: 0;
}

/* Highlight selected rows */
tr.selected {
    background-color: #e6f7ff !important;
}

/* Add hover effect for checkboxes */
.course-checkbox:hover {
    filter: brightness(1.1);
}

.save-semester-btn {
  font-size: 0.8rem;
  padding: 0.25rem 0.75rem;
  margin-left: 10px;
  white-space: nowrap;
}

.semester-title {
  font-weight: 600;
  font-size: 1.1rem;
}

.semester-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.d-flex.justify-content-between {
  align-items: center;
}

/* Toast styling */
.toast {
  opacity: 1 !important;
}

.bg-success {
  background-color: #198754 !important;
}

.bg-danger {
  background-color: #dc3545 !important;
}

.bg-warning {
  background-color: #ffc107 !important;
  color: #000 !important;
}

.bg-warning .btn-close {
  filter: invert(1) grayscale(100%) brightness(0);
}
</style>
</body>
</html>