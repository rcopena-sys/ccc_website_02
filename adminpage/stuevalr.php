<?php
require_once 'db.php';

// Check if a student is regular based on classification
function isRegularStudent($studentData) {
    if (!isset($studentData['classification'])) {
        return false;
    }
    
    $classification = trim($studentData['classification']);
    
    // Check for various regular student classifications
    $regularClassifications = [
        'Regular',
        'Regular 3rd Year',
        'Regular 4th Year'
    ];
    
    return in_array($classification, $regularClassifications);
}

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
    // First, check if the course has any prerequisites in the curriculum table
    $prereqQuery = "SELECT prerequisites FROM curriculum WHERE course_code = ?";
    $prereqStmt = $conn->prepare($prereqQuery);
    if (!$prereqStmt) return false;
    
    $prereqStmt->bind_param('s', $courseCode);
    $prereqStmt->execute();
    $prereqResult = $prereqStmt->get_result();
    
    if ($prereqResult->num_rows === 0) {
        return false; // Course not found or has no prerequisites
    }
    
    $courseData = $prereqResult->fetch_assoc();
    $prerequisites = trim($courseData['prerequisites'] ?? '');
    
    // If no prerequisites listed, return false (no prerequisites to check)
    if (empty($prerequisites)) {
        return false;
    }
    
    // Split prerequisites by comma and clean up
    $prereqCodes = array_map('trim', explode(',', $prerequisites));
    $prereqCodes = array_filter($prereqCodes); // Remove any empty entries
    
    // If no valid prerequisites found, return false
    if (empty($prereqCodes)) {
        return false;
    }
    
    // Log all prerequisites for this course
    error_log("Prerequisites for $courseCode: " . implode(', ', $prereqCodes));
    
    // Check all prerequisites
    foreach ($prereqCodes as $prereqCode) {
        // Skip empty codes
        if (empty($prereqCode)) continue;#c6282
        
        error_log("Checking prerequisite $prereqCode for course $courseCode (Student: $studentId, Year: $currentYear, Sem: $currentSem)");
        
        // Check if the student has passed this prerequisite
        $gradeQuery = "SELECT final_grade FROM grades_db 
                      WHERE student_id = ? 
                      AND course_code = ?";
        
        $gradeStmt = $conn->prepare($gradeQuery);
        if (!$gradeStmt) {
            error_log("Failed to prepare statement for prerequisite $prereqCode");
            continue;
        }
        
        $gradeStmt->bind_param('ss', $studentId, $prereqCode);
        if (!$gradeStmt->execute()) {
            error_log("Failed to execute query for prerequisite $prereqCode: " . $gradeStmt->error);
            continue;
        }
        
        $gradeResult = $gradeStmt->get_result();
        
        if ($gradeResult->num_rows === 0) {
            // No grade found for this prerequisite - check if it's a first-semester course
            $isFirstSemCourse = $conn->query("SELECT 1 FROM curriculum WHERE course_code = '$prereqCode' AND semester = 1 AND year <= $currentYear");
            if ($isFirstSemCourse && $isFirstSemCourse->num_rows > 0) {
                // It's a first-semester course that should have been taken already but has no grade
                error_log("Prerequisite check: $courseCode requires $prereqCode which has no grade");
                return true;
            } else {
                error_log("Prerequisite check: $courseCode requires $prereqCode which has no grade but it's not a first-semester course");
            }
        } else {
            // Check if the grade is a passing grade
            $gradeRow = $gradeResult->fetch_assoc();
            $grade = $gradeRow['final_grade'];
            
            if ($grade === null) {
                error_log("Prerequisite check: $courseCode requires $prereqCode which has no grade (NULL)");
                return true;
            } elseif (is_numeric($grade)) {
                if ($grade >= 5.00) {
                    error_log("Prerequisite check: $courseCode requires $prereqCode which has a failing grade: $grade");
                    return true;
                } else {
                    error_log("Prerequisite check: $courseCode requires $prereqCode which has a passing grade: $grade");
                }
            } else {
                error_log("Prerequisite check: $courseCode requires $prereqCode which has an invalid grade format: " . gettype($grade));
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

// Handle Add Subject to Irregular DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_irregular_subject') {
    header('Content-Type: application/json');
    
    try {
        // Get form data
        $student_id = trim($_POST['student_id'] ?? '');
        $course_code = trim($_POST['course_code'] ?? '');
        $course_title = trim($_POST['course_title'] ?? '');
        $year_level = intval($_POST['year_level'] ?? $_POST['year'] ?? 1);
        $semester = intval($_POST['semester'] ?? $_POST['sem'] ?? 1);
        $prerequisites = trim($_POST['prerequisites'] ?? '');
        
        // Debug: Log incoming data
        error_log("Adding irregular subject - Student: $student_id, Course: $course_code, Year: $year_level, Sem: $semester");
        
        // Get exact values from curriculum table to ensure consistency
        $currSql = "SELECT lec_units, lab_units, total_units, prerequisites FROM curriculum WHERE course_code = ? LIMIT 1";
        $currStmt = $conn->prepare($currSql);
        if ($currStmt) {
            $currStmt->bind_param('s', $course_code);
            $currStmt->execute();
            $currResult = $currStmt->get_result();
            if ($currResult && $currResult->num_rows > 0) {
                $currData = $currResult->fetch_assoc();
                $lec_units = floatval($currData['lec_units'] ?? 0);
                $lab_units = floatval($currData['lab_units'] ?? 0);
                $total_units = floatval($currData['total_units'] ?? 0);
                $prerequisites = $prerequisites ?: trim($currData['prerequisites'] ?? '');
                
                error_log("Found in curriculum - Total units: $total_units, Lec: $lec_units, Lab: $lab_units");
            } else {
                // Fallback to form values if not found in curriculum
                $lec_units = floatval($_POST['lec_units'] ?? 0);
                $lab_units = floatval($_POST['lab_units'] ?? 0);
                $total_units = floatval($_POST['total_units'] ?? 0);
                error_log("Using form values - Total units: $total_units, Lec: $lec_units, Lab: $lab_units");
            }
            $currStmt->close();
        } else {
            // Fallback to form values if query fails
            $lec_units = floatval($_POST['lec_units'] ?? 0);
            $lab_units = floatval($_POST['lab_units'] ?? 0);
            $total_units = floatval($_POST['total_units'] ?? 0);
            error_log("Query failed, using form values - Total units: $total_units");
        }
        
        // Validate required fields
        if (empty($student_id) || empty($course_code) || empty($course_title)) {
            throw new Exception('Missing required fields: student_id, course_code, or course_title');
        }
        
        if ($total_units <= 0) {
            throw new Exception('Total units must be greater than 0');
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
        $stmt->close();
        
        // Check for duplicate using dynamic column detection
        $colsRes = $conn->query("SHOW COLUMNS FROM irregular_db");
        $columns = [];
        while ($c = $colsRes->fetch_assoc()) $columns[] = $c['Field'];
        
        $yearCol = in_array('year_level', $columns) ? 'year_level' : (in_array('year', $columns) ? 'year' : 'year_level');
        $semCol = in_array('semester', $columns) ? 'semester' : (in_array('sem', $columns) ? 'sem' : 'semester');
        
        $checkStmt = $conn->prepare("
            SELECT id FROM irregular_db 
            WHERE student_id = ? AND course_code = ? AND $yearCol = ? AND $semCol = ?
        ");
        $checkStmt->bind_param('ssii', $student_id, $course_code, $year_level, $semester);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
        
        if ($exists) {
            throw new Exception('This subject is already in the irregular list for this semester.');
        }
        
        // Build dynamic INSERT statement with available columns
        $insertCols = ['student_id', 'course_code', 'course_title', 'total_units', $yearCol, $semCol];
        $insertVals = [$student_id, $course_code, $course_title, $total_units, $year_level, $semester];
        $insertTypes = 'sssdis';
        $insertParams = "sssddiss";
        
        // Add optional columns if they exist
        $lecCol = in_array('lec_units', $columns) ? 'lec_units' : (in_array('lecture_units', $columns) ? 'lecture_units' : null);
        $labCol = in_array('lab_units', $columns) ? 'lab_units' : null;
        $programCol = in_array('program', $columns) ? 'program' : null;
        $prereqCol = in_array('prerequisites', $columns) ? 'prerequisites' : (in_array('prereq', $columns) ? 'prereq' : null);
        $statusCol = in_array('status', $columns) ? 'status' : null;
        
        if ($lecCol) {
            $insertCols[] = $lecCol;
            $insertVals[] = $lec_units;
            $insertTypes .= 'd';
            $insertParams .= 'd';
        }
        if ($labCol) {
            $insertCols[] = $labCol;
            $insertVals[] = $lab_units;
            $insertTypes .= 'd';
            $insertParams .= 'd';
        }
        if ($programCol && !empty($program)) {
            $insertCols[] = $programCol;
            $insertVals[] = strtoupper($program);
            $insertTypes .= 's';
            $insertParams .= 's';
        }
        if ($prereqCol && !empty($prerequisites)) {
            $insertCols[] = $prereqCol;
            $insertVals[] = $prerequisites;
            $insertTypes .= 's';
            $insertParams .= 's';
        }
        if ($statusCol) {
            $insertCols[] = $statusCol;
            $insertVals[] = 'enrolled';
            $insertTypes .= 's';
            $insertParams .= 's';
        }
        
        // Create and execute INSERT statement
        $sql = "INSERT INTO irregular_db (" . implode(',', $insertCols) . ") VALUES (" . str_repeat('?,', count($insertCols)-1) . "?)";
        error_log("INSERT SQL: $sql");
        error_log("INSERT values: " . json_encode($insertVals));
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($insertTypes, ...$insertVals);
        
        if ($stmt->execute()) {
            // Update student classification to irregular in signin_db and students_db
            $updateSql = "UPDATE signin_db SET classification = 'irregular' WHERE student_id = ? AND (classification IS NULL OR classification != 'irregular')";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param('s', $student_id);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Also update students_db if it exists and has a classification column
                if (columnExists($conn, 'students_db', 'classification')) {
                    $updateStudentsDb = $conn->prepare("UPDATE students_db SET classification = 'irregular' WHERE student_id = ?");
                    if ($updateStudentsDb) {
                        $updateStudentsDb->bind_param('s', $student_id);
                        $updateStudentsDb->execute();
                        $updateStudentsDb->close();
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Subject added to irregular subjects successfully!',
                'data' => [
                    'course_code' => $course_code,
                    'course_title' => $course_title,
                    'total_units' => $total_units,
                    'lec_units' => $lec_units,
                    'lab_units' => $lab_units,
                    'year_level' => $year_level,
                    'semester' => $semester,
                    'program' => strtoupper($program)
                ]
            ]);
        } else {
            throw new Exception('Failed to add subject to irregular subjects: ' . $stmt->error);
        }
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error adding irregular subject: ' . $e->getMessage());
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
  
  // Add program and prerequisites if columns exist
  $programCol = in_array('program', $columns) ? 'program' : null;
  $prereqCol = in_array('prerequisites', $columns) ? 'prerequisites' : (in_array('prereq', $columns) ? 'prereq' : null);
  if ($programCol) $selectCols[] = $programCol;
  if ($prereqCol) $selectCols[] = "$prereqCol AS prerequisites"; else $selectCols[] = "'' AS prerequisites";

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

// Handle Bulk Save to Irregular DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (isset($data['action']) && $data['action'] === 'bulk_save_irregular') {
        header('Content-Type: application/json');
        
        try {
            $student_id = trim($data['student_id'] ?? '');
            $subjects = $data['subjects'] ?? [];
            
            if (empty($student_id) || empty($subjects)) {
                throw new Exception('Missing student ID or subjects');
            }
            
            // Get the program from the current curriculum (not from student record)
            // Since the subjects being displayed are from a specific curriculum, use that program
            $program = 'BSIT'; // Default to BSIT since we're working with BSIT curriculum
            // You can also get this dynamically if needed:
            // $program = strtoupper($GLOBALS['current_program'] ?? 'BSIT');
            
            // Check for existing subjects to avoid duplicates
            $existingSubjects = [];
            $checkStmt = $conn->prepare("SELECT course_code, year_level, semester FROM irregular_db WHERE student_id = ?");
            if ($checkStmt) {
                $checkStmt->bind_param('s', $student_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                while ($row = $checkResult->fetch_assoc()) {
                    $key = $row['course_code'] . '_' . $row['year_level'] . '_' . $row['semester'];
                    $existingSubjects[$key] = true;
                }
                $checkStmt->close();
            }
            
            // Determine actual column names in irregular_db
            $colsRes = $conn->query("SHOW COLUMNS FROM irregular_db");
            $columns = [];
            while ($c = $colsRes->fetch_assoc()) $columns[] = $c['Field'];
            
            // Debug: Log available columns
            error_log("Available columns in irregular_db: " . implode(', ', $columns));
            
            $yearCol = in_array('year_level', $columns) ? 'year_level' : (in_array('year', $columns) ? 'year' : 'year_level');
            $semCol = in_array('semester', $columns) ? 'semester' : (in_array('sem', $columns) ? 'sem' : 'semester');
            $lecCol = in_array('lec_units', $columns) ? 'lec_units' : (in_array('lecture_units', $columns) ? 'lecture_units' : null);
            $labCol = in_array('lab_units', $columns) ? 'lab_units' : null;
            $totalCol = in_array('total_units', $columns) ? 'total_units' : (in_array('units', $columns) ? 'units' : 'total_units');
            $statusCol = in_array('status', $columns) ? 'status' : null;
            $programCol = in_array('program', $columns) ? 'program' : null;
            $prereqCol = in_array('prerequisites', $columns) ? 'prerequisites' : (in_array('prereq', $columns) ? 'prereq' : null);
            
            // Debug: Log detected unit columns
            error_log("Unit columns detected - total_units: $totalCol, lec_units: $lecCol, lab_units: $labCol");
            
            // Insert each subject
            $successCount = 0;
            $duplicateCount = 0;
            $errorCount = 0;
            
            foreach ($subjects as $subject) {
                $course_code = trim($subject['course_code'] ?? '');
                $course_title = trim($subject['course_title'] ?? '');
                $prerequisites = trim($subject['prerequisites'] ?? '');
                $year_level = intval($subject['year_level'] ?? 1);
                $semester = intval($subject['semester'] ?? 1);
                
                // Get exact values from curriculum table to ensure consistency
                $currSql = "SELECT lec_units, lab_units, total_units FROM curriculum WHERE course_code = ? LIMIT 1";
                $currStmt = $conn->prepare($currSql);
                if ($currStmt) {
                    $currStmt->bind_param('s', $course_code);
                    $currStmt->execute();
                    $currResult = $currStmt->get_result();
                    if ($currResult && $currResult->num_rows > 0) {
                        $currData = $currResult->fetch_assoc();
                        $lec_units = floatval($currData['lec_units'] ?? 0);
                        $lab_units = floatval($currData['lab_units'] ?? 0);
                        $total_units = floatval($currData['total_units'] ?? 0);
                    } else {
                        // Fallback to frontend values if not found in curriculum
                        $lec_units = floatval($subject['lec_units'] ?? 0);
                        $lab_units = floatval($subject['lab_units'] ?? 0);
                        $total_units = floatval($subject['total_units'] ?? 0);
                    }
                    $currStmt->close();
                } else {
                    // Fallback to frontend values if query fails
                    $lec_units = floatval($subject['lec_units'] ?? 0);
                    $lab_units = floatval($subject['lab_units'] ?? 0);
                    $total_units = floatval($subject['total_units'] ?? 0);
                }
                
                // Check for duplicate
                $key = $course_code . '_' . $year_level . '_' . $semester;
                if (isset($existingSubjects[$key])) {
                    $duplicateCount++;
                    continue;
                }
                
                // Validate required fields
                if (empty($course_code) || empty($course_title) || $total_units <= 0) {
                    $errorCount++;
                    continue;
                }
                
                // Build INSERT query with available columns
                $insertCols = ['student_id', 'course_code', 'course_title', $totalCol, $yearCol, $semCol];
                $insertVals = [$student_id, $course_code, $course_title, $total_units, $year_level, $semester];
                $insertTypes = 'sssdis';
                
                // Debug: Log the units being saved
                error_log("Saving units for $course_code - Total: $total_units, Lec: $lec_units, Lab: $lab_units");
                
                if ($programCol && !empty($program)) {
                    $insertCols[] = $programCol;
                    $insertVals[] = strtoupper($program); // Convert to uppercase (e.g., BSIT)
                    $insertTypes .= 's';
                    error_log("Adding program column: $programCol with value: " . strtoupper($program));
                }
                
                if ($lecCol) {
                    $insertCols[] = $lecCol;
                    $insertVals[] = $lec_units; // Use actual lec_units from curriculum
                    $insertTypes .= 'd';
                }
                if ($labCol) {
                    $insertCols[] = $labCol;
                    $insertVals[] = $lab_units; // Use actual lab_units from curriculum
                    $insertTypes .= 'd';
                }
                if ($prereqCol && !empty($prerequisites)) {
                    $insertCols[] = $prereqCol;
                    $insertVals[] = $prerequisites;
                    $insertTypes .= 's';
                }
                if ($statusCol) {
                    $insertCols[] = $statusCol;
                    $insertVals[] = 'enrolled';
                    $insertTypes .= 's';
                }
                
                // Debug: Log the INSERT statement
                $sql = "INSERT INTO irregular_db (" . implode(',', $insertCols) . ") VALUES (" . str_repeat('?,', count($insertCols)-1) . "?)";
                error_log("INSERT SQL: " . $sql);
                error_log("INSERT values: " . json_encode($insertVals));
                error_log("INSERT types: " . $insertTypes);
                
                // Check if units column is being included
                if (strpos(implode(',', $insertCols), $totalCol) !== false) {
                    $totalIndex = array_search($totalCol, $insertCols);
                    if ($totalIndex !== false && isset($insertVals[$totalIndex])) {
                        error_log("SAVING $totalCol: " . $insertVals[$totalIndex]);
                    }
                }
                
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($insertTypes, ...$insertVals);
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                    $stmt->close();
                } else {
                    $errorCount++;
                }
            }
            
            // Update student classification to irregular in signin_db and students_db
            if ($successCount > 0) {
                $updateSql = "UPDATE signin_db SET classification = 'irregular' WHERE student_id = ? AND (classification IS NULL OR classification != 'irregular')";
                $updateStmt = $conn->prepare($updateSql);
                if ($updateStmt) {
                    $updateStmt->bind_param('s', $student_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Also update students_db if it exists and has a classification column
                    if (columnExists($conn, 'students_db', 'classification')) {
                        $updateStudentsDb = $conn->prepare("UPDATE students_db SET classification = 'irregular' WHERE student_id = ?");
                        if ($updateStudentsDb) {
                            $updateStudentsDb->bind_param('s', $student_id);
                            $updateStudentsDb->execute();
                            $updateStudentsDb->close();
                        }
                    }
                }
                
                // Also update year_semester in irregular_db for all newly added subjects
                $currentYear = $GLOBALS['currentYear'] ?? date('Y');
                $currentSem = $GLOBALS['currentSem'] ?? 1;
                
                // Update each newly added subject with correct year_semester format
                foreach ($subjects as $subject) {
                    $yearLevel = intval($subject['year_level'] ?? 1);
                    $semester = intval($subject['semester'] ?? 1);
                    $yearSem = $yearLevel . '-' . $semester; // Format: Y-S (1-1, 1-2, 2-1, etc.)
                    
                    $updateIrregularSql = "UPDATE irregular_db SET year_semester = ? WHERE student_id = ? AND course_code = ? AND year_level = ? AND semester = ?";
                    $updateIrregularStmt = $conn->prepare($updateIrregularSql);
                    if ($updateIrregularStmt) {
                        $updateIrregularStmt->bind_param('sssii', $yearSem, $student_id, $subject['course_code'], $yearLevel, $semester);
                        $updateIrregularStmt->execute();
                        $updateIrregularStmt->close();
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Successfully added $successCount subjects. " . 
                            ($duplicateCount > 0 ? "$duplicateCount duplicates skipped. " : "") .
                            ($errorCount > 0 ? "$errorCount errors occurred." : "")
            ]);
            
        } catch (Exception $e) {
            error_log("Bulk save error: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage(),
                'debug_info' => [
                    'student_id' => $student_id ?? 'missing',
                    'subjects_count' => count($subjects ?? []),
                    'program' => $program ?? 'missing'
                ]
            ]);
        }
        exit;
    }
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
$totalCol = columnExists($conn, 'curriculum', 'total_units') ? 'total_units' : (columnExists($conn, 'curriculum', 'units') ? 'units' : 'total_units');
$preCol = columnExists($conn, 'curriculum', 'prerequisites') ? 'prerequisites' : (columnExists($conn, 'curriculum', 'pre_req') ? 'pre_req' : 'prerequisites');

$curriculum = [];
$sql = "SELECT $codeCol AS code, $titleCol AS title, $lecCol AS lec, $labCol AS lab, $totalCol AS units, $preCol AS prereq";
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
    
    // Helper function to check if subject should show buttons based on inc logic
    function shouldShowButtons($code, $displayGrade, $prereq, $curriculum, $gradesByCode) {
        // First check if this subject itself has "inc" grade
        if (isset($displayGrade) && $displayGrade === 'inc') {
            error_log("DEBUG: Subject $code has inc grade, showing buttons");
            return true;
        }
        
        // Also check if this subject has prerequisites and those prerequisites are "inc" subjects
        if (!empty($prereq) && $prereq !== 'none' && $prereq !== 'n/a') {
            error_log("DEBUG: Subject $code has prereq: $prereq");
            
            // Get the prerequisite course codes
            $prereqCodes = array_map('trim', explode(',', $prereq));
            foreach ($prereqCodes as $prereqCode) {
                $normPrereqCode = normalizeCourseCode($prereqCode);
                error_log("DEBUG: Checking prerequisite code: $prereqCode (normalized: $normPrereqCode)");
                
                // Check if this prerequisite subject exists in ANY semester and has "inc" grade
                if (!empty($curriculum)) {
                    foreach ($curriculum as $semesterCourses) {
                        foreach ($semesterCourses as $course) {
                            $courseCode = normalizeCourseCode($course['code'] ?? '');
                            error_log("DEBUG: Comparing with course: $courseCode (normalized: $courseCode)");
                            
                            if ($courseCode === $normPrereqCode) {
                                error_log("DEBUG: Found matching prerequisite course: $course[code]");
                                // Check if this prerequisite subject has "inc" grade
                                $prereqGrade = $gradesByCode[$course['code']] ?? null;
                                if ($prereqGrade) {
                                    $gradeValue = is_array($prereqGrade) ? ($prereqGrade['final_grade'] ?? $prereqGrade['grade'] ?? '') : $prereqGrade;
                                    error_log("DEBUG: Prerequisite grade: $gradeValue");
                                    
                                    if (is_numeric($gradeValue)) {
                                        $gradeNum = floatval($gradeValue);
                                        if ($gradeNum >= 3.25 && $gradeNum <= 4.00) {
                                            error_log("DEBUG: Prerequisite is inc (numeric range), showing buttons");
                                            return true;
                                        }
                                    } elseif (is_string($gradeValue) && strtoupper(trim($gradeValue)) === 'INC') {
                                        error_log("DEBUG: Prerequisite is inc (text), showing buttons");
                                        return true;
                                    }
                                } else {
                                    error_log("DEBUG: No grade found for prerequisite course: $course[code]");
                                }
                            }
                        }
                    }
                }
            }
        } else {
            error_log("DEBUG: Subject $code has no prerequisites or prereq is 'none'");
        }
        
        return false;
    }
    
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
            $gradeValue = !empty($g['final_grade']) ? $g['final_grade'] : ($g['grade'] ?? '');
            
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

// Define unit limits for each semester and course
$unitLimits = [
    'BSIT' => [
        '1-1' => ['max' => 26, 'label' => 'FIRST YEAR • FIRST SEMESTER'],
        '1-2' => ['max' => 26, 'label' => 'FIRST YEAR • SECOND SEMESTER'],
        '2-1' => ['max' => 26, 'label' => 'SECOND YEAR • FIRST SEMESTER'],
        '2-2' => ['max' => 23, 'label' => 'SECOND YEAR • SECOND SEMESTER'],
        '3-1' => ['max' => 24, 'label' => 'THIRD YEAR • FIRST SEMESTER'],
        '3-2' => ['max' => 12, 'label' => 'THIRD YEAR • SECOND SEMESTER'],
        '4-1' => ['max' => 6,  'label' => 'FOURTH YEAR • FIRST SEMESTER'],
        '4-2' => ['max' => 6,  'label' => 'FOURTH YEAR • SECOND SEMESTER']
    ],
    'BSCS' => [
        '1-1' => ['max' => 26, 'label' => 'FIRST YEAR • FIRST SEMESTER'],
        '1-2' => ['max' => 26, 'label' => 'FIRST YEAR • SECOND SEMESTER'],
        '2-1' => ['max' => 26, 'label' => 'SECOND YEAR • FIRST SEMESTER'],
        '2-2' => ['max' => 26, 'label' => 'SECOND YEAR • SECOND SEMESTER'],
        '3-1' => ['max' => 24, 'label' => 'THIRD YEAR • FIRST SEMESTER'],
        '3-2' => ['max' => 13, 'label' => 'THIRD YEAR • SECOND SEMESTER'],
        '4-1' => ['max' => 6,  'label' => 'FOURTH YEAR • FIRST SEMESTER'],
        '4-2' => ['max' => 6,  'label' => 'FOURTH YEAR • SECOND SEMESTER']
    ]
];

// Use the unit limits for labels if available, otherwise fallback to default
$labels = [];
foreach ($ysOrder as $ys) {
    if (isset($unitLimits[$program][$ys])) {
        $labels[$ys] = $unitLimits[$program][$ys]['label'];
    } else {
        // Fallback labels
        $defaultLabels = [
            '1-1' => 'FIRST YEAR • FIRST SEMESTER',
            '1-2' => 'FIRST YEAR • SECOND SEMESTER', 
            '2-1' => 'SECOND YEAR • FIRST SEMESTER',
            '2-2' => 'SECOND YEAR • SECOND SEMESTER',
            '3-1' => 'THIRD YEAR • FIRST SEMESTER',
            '3-2' => 'THIRD YEAR • SECOND SEMESTER',
            '4-1' => 'FOURTH YEAR • FIRST SEMESTER',
            '4-2' => 'FOURTH YEAR • SECOND SEMESTER'
        ];
        $labels[$ys] = $defaultLabels[$ys] ?? $ys;
    }
}

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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
      color: #ffffffff;
    }
    
    .prereq-failed {
      background-color: #ffebee !important;
      color: #fff5f5ff !important;
      border: 2px solid #ffffffff !important;
      font-weight: bold;
    }
    
    .prereq-failed .grade-cell {
      background-color: #ffebee !important;
      color: #ffffff !important;
      font-weight: bold;
      border: 2px solid #ffffff !important;
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
    
    /* Unit limit warning styles */
    .unit-limit-warning {
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.7; }
      100% { opacity: 1; }
    }
    
    .unit-overlimit {
      background-color: #f8d7da !important;
      border-left: 4px solid #dc3545 !important;
    }
    
    .unit-overlimit .semester-title {
      background-color: #dc3545 !important;
      color: white !important;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="d-flex flex-column align-items-center mb-4">

      <img src="dci.png.png" alt="DCI Logo" style="width: 120px; margin-top: 10px;">
    </div>
    
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a href="dashboard2.php" class="nav-link">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
      </li>
      <li class="nav-item mb-2">
        <a href="stueval.php" class="nav-link active">
          <i class="bi bi-journal-text"></i> Student Evaluation
        </a>
      </li>
      <li class="nav-item mb-2">
        <a href="list.php" class="nav-link active">
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
                  $grandTotalUnits += (float)($course['units'] ?? 0);
              }
          }
      }
  }
?>
  <!-- Watermark -->
  <div class="watermark" style="position: absolute; opacity: 0.1; font-size: 5em; transform: rotate(-45deg); top: 30%; left: 10%; z-index: 0; pointer-events: none;">
   
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
              echo htmlspecialchars($student['student_name'] ?? 'N/A');
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
        <div class="fw-bold"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></div>
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
          $classification = $isIrregular ? 'Irregular' : 'Regular';
          $badgeClass = $isIrregular ? 'badge bg-warning text-dark' : 'badge bg-primary text-white';
          
          // Update signin_db and students_db classification based on student status
          if ($isIrregular) {
              $updateSql = "UPDATE signin_db SET classification = 'irregular' WHERE student_id = ? AND (classification IS NULL OR classification != 'irregular')";
              $updateStmt = $conn->prepare($updateSql);
              if ($updateStmt) {
                  $updateStmt->bind_param('s', $studentId);
                  $updateStmt->execute();
                  $updateStmt->close();
                  
                  // Also update students_db if it exists and has a classification column
                  if (columnExists($conn, 'students_db', 'classification')) {
                      $updateStudentsDb = $conn->prepare("UPDATE students_db SET classification = 'irregular' WHERE student_id = ?");
                      if ($updateStudentsDb) {
                          $updateStudentsDb->bind_param('s', $studentId);
                          $updateStudentsDb->execute();
                          $updateStudentsDb->close();
                      }
                  }
              }
          } else {
              // Update to Regular if student is not irregular
              $updateSql = "UPDATE signin_db SET classification = 'Regular' WHERE student_id = ? AND (classification IS NULL OR classification != 'Regular')";
              $updateStmt = $conn->prepare($updateSql);
              if ($updateStmt) {
                  $updateStmt->bind_param('s', $studentId);
                  $updateStmt->execute();
                  $updateStmt->close();
                  
                  // Also update students_db if it exists and has a classification column
                  if (columnExists($conn, 'students_db', 'classification')) {
                      $updateStudentsDb = $conn->prepare("UPDATE students_db SET classification = 'Regular' WHERE student_id = ?");
                      if ($updateStudentsDb) {
                          $updateStudentsDb->bind_param('s', $studentId);
                          $updateStudentsDb->execute();
                          $updateStudentsDb->close();
                      }
                  }
              }
          }
          ?>
          <span class="<?= $badgeClass ?>"><?= $classification ?></span>
        </div>
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
    
    // Get all irregular subjects for this student (load once for all semesters)
    $allIrregularSubjects = [];
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
                         WHERE student_id = ?";
        $irregularStmt = $conn->prepare($irregularQuery);
        if ($irregularStmt) {
            $irregularStmt->bind_param('s', $studentId);
            $irregularStmt->execute();
            $irregularResult = $irregularStmt->get_result();
            while ($row = $irregularResult->fetch_assoc()) {
                $allIrregularSubjects[] = [
                    'code' => $row['course_code'],
                    'title' => $row['course_title'],
                    'units' => (float)($row['total_units'] ?? 0),
                    'irregular' => true,
                    'id' => $row['id'],
                    'year_level' => $row['year_level'],
                    'semester' => $row['semester']
                ];
            }
        }
    }
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
        
        // Calculate total units for this semester, excluding courses with failed prerequisites
        $semesterUnits = 0;
        if (isset($curriculum[$ysKey])) {
            foreach ($curriculum[$ysKey] as $courseCode => $course) {
                // Get the course's prerequisites
                $prereqQuery = "SELECT prerequisites FROM curriculum WHERE course_code = ?";
                $prereqStmt = $conn->prepare($prereqQuery);
                $hasPrereqToCheck = false;
                
                if ($prereqStmt) {
                    $prereqStmt->bind_param('s', $courseCode);
                    $prereqStmt->execute();
                    $prereqResult = $prereqStmt->get_result();
                    
                    if ($prereqResult->num_rows > 0) {
                        $prereqData = $prereqResult->fetch_assoc();
                        $hasPrereqToCheck = !empty(trim($prereqData['prerequisites'] ?? ''));
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
                
                // Check if this course has failed prerequisites
                $hasFailedPrereq = false;
                if ($hasPrereqToCheck) {
                    error_log("Checking prerequisites for course: $courseCode, Year: $currentYear, Sem: $currentSem");
                    $hasFailedPrereq = hasFailedPrerequisites($conn, $studentId, $courseCode, $currentYear, $currentSem);
                    if ($hasFailedPrereq) {
                        error_log("Course $courseCode has failed prerequisites");
                    } else {
                        error_log("Course $courseCode has all prerequisites met");
                    }
                }
                
                // First, check if this course has any failed prerequisites
                $hasFailedPrereq = false;
                if ($hasPrereqToCheck) {
                    $hasFailedPrereq = hasFailedPrerequisites($conn, $studentId, $courseCode, $currentYear, $currentSem);
                    if ($hasFailedPrereq) {
                        error_log("EXCLUDING course $courseCode - Has failed prerequisites");
                        continue; // Skip to next course if prerequisites are not met
                    }
                }
                
                // Then check if this course has a failing grade
                if ($hasFailingGrade) {
                    error_log("EXCLUDING course $courseCode - Has failing grade");
                    continue; // Skip to next course if it has a failing grade
                }
                
                // If we get here, include the course in the total
                $courseUnits = (float)($course['units'] ?? 0);
                $semesterUnits += $courseUnits;
                error_log("INCLUDING course $courseCode with $courseUnits units. New total: $semesterUnits");
            }
        }
        
        // Track regular and irregular units separately
        $regularUnits = $semesterUnits;
        $irregularUnits = 0;
        
        // Get unit limit for this semester and program
        $maxUnits = isset($unitLimits[$program][$ysKey]) ? $unitLimits[$program][$ysKey]['max'] : 26; // Default to 26 if not set
        $isOverLimit = $semesterUnits > $maxUnits;
        
        // Get irregular subjects for this semester from the allIrregularSubjects array
        $irregularSubjects = [];
        $irregularUnits = 0;
        [$yr, $sm] = array_map('intval', explode('-', $ysKey));
        
        foreach ($allIrregularSubjects as $irregular) {
            if ($irregular['year_level'] == $yr && $irregular['semester'] == $sm) {
                $irregularSubjects[] = $irregular;
                $irregularUnits += $irregular['units'];
            }
        }
        $semesterUnits += $irregularUnits;
    ?>
  <div class="mb-4 <?= $isOverLimit ? 'unit-overlimit unit-limit-warning' : '' ?>" data-ys="<?= htmlspecialchars($ysKey) ?>">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="semester-title d-flex align-items-center">
            <?= $labels[$ysKey] ?>
          </div>
            <div class="d-flex gap-2">
<!-- Clickable irregular units badge: opens subject selector for this semester -->
            <span class="badge bg-success rounded-pill open-select-subjects me-2" role="button" data-ys="<?= htmlspecialchars($ysKey) ?>" data-bs-toggle="tooltip" title="Click to see courses">
              <i class="bi bi-plus-circle me-1"></i> <?= number_format($irregularUnits, 1) ?>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary select-semester" 
                    data-ys="<?= htmlspecialchars($ysKey) ?>"
                    data-bs-toggle="tooltip" title="Select all subjects in this semester">
              <i class="bi bi-check-all me-1"></i> Select All
            </button>
            <span class="badge <?= $isOverLimit ? 'bg-danger' : 'bg-secondary' ?> rounded-pill" data-bs-toggle="tooltip" title="Current vs Maximum Units">
              <i class="bi bi-calculator me-1"></i> <?= number_format($semesterUnits, 1) ?> / <?= $maxUnits ?>
              <?php if ($isOverLimit): ?>
                <i class="bi bi-exclamation-triangle ms-1"></i>
              <?php endif; ?>
            </span>
            <?php if ($isOverLimit): ?>
            <div class="alert alert-danger mt-2 mb-0 py-2">
              <small><i class="bi bi-exclamation-triangle me-1"></i> Unit limit exceeded by <?= number_format($semesterUnits - $maxUnits, 1) ?> units</small>
            </div>
            <?php endif; ?>
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
                color: #ffffffff; /* Red for failed grades */
                font-weight: bold;
              }
              
              .prereq-failed {
                background-color: #eb203fff !important; /* Light red background */
                color: #bf3e3eff !important; /* Dark red text */
                border: 2px solid #d74646ff !important; /* Red border */
                font-weight: bold;
              }
            </style>
            <thead>
              <tr class="text-center" style="background-color: #3a7bd5; color: white; font-weight: bold;">
                <th style="width:5%; padding: 12px 8px;">
                  
                </th>
                <th style="width:10%; padding: 12px 8px;"rgb(255, 255, 255); color: white; font-weight: bold;">GRADE</th>
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
              
              // Debug: Log the current semester and number of rows
              error_log("Processing semester: $ysKey, Number of courses: " . count($rows));
              
              if (empty($rows) && !$hasIrregular):
              ?>
                <tr><td colspan="9" class="text-center text-muted">No curriculum data for this semester.</td></tr>
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
                      $gradeValue = is_array($grade) ? ($grade['final_grade'] ?? $grade['grade'] ?? 'N/A') : $grade;
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
                  
                  // Check if this subject is a prerequisite for any other subject in the curriculum
$isPrerequisiteForOthers = false;
if (!empty($curriculum)) {
    foreach ($curriculum as $semesterCourses) {
        foreach ($semesterCourses as $course) {
            $coursePrereq = strtolower(trim($course['prereq'] ?? ''));
            if (!empty($coursePrereq) && $coursePrereq !== 'none' && $coursePrereq !== 'n/a') {
                // Check if current course code is in this course's prerequisites
                $prereqCodes = array_map('trim', explode(',', $coursePrereq));
                $normCurrentCode = normalizeCourseCode($code);
                foreach ($prereqCodes as $prereqCode) {
                    $normPrereqCode = normalizeCourseCode($prereqCode);
                    if ($normPrereqCode === $normCurrentCode) {
                        $isPrerequisiteForOthers = true;
                        break 2; // Break both loops
                    }
                }
            }
        }
    }
}

                  $gradeNum = null;
                  $displayGrade = '—';
                  $rowClass = '';
                  $prereqFailed = false;
                  $prereq = $subj['prereq'] ?? '';
                  
                  // Handle grade value extraction from array or direct value
                  $gradeValue = '';
                  if (!empty($grade)) {
                      $gradeValue = is_array($grade) ? ($grade['final_grade'] ?? $grade['grade'] ?? '') : $grade;
                      error_log("Found grade for $code: " . print_r($gradeValue, true));
                  } else {
                      error_log("No grade found for $code, tried patterns: " . implode(', ', $triedPatterns ?? []));
                  }
                  
                  // Convert grade to number if possible
                  if (is_numeric($gradeValue)) {
                      $gradeNum = floatval($gradeValue);
                      $displayGrade = number_format($gradeNum, 2);
                      
                      // Determine row class based on grade
                      if ($gradeNum <= 3.25) {
                          $rowClass = 'passed-row';
                      } elseif ($gradeNum >= 3.25 && $gradeNum <= 4.00) {
                          $displayGrade = 'inc';
                          $rowClass = 'warning-row';
                      } elseif ($gradeNum <= 5.0) {
                          $rowClass = 'failed-row';
                      }
                  } elseif (is_string($gradeValue)) {
                      $gradeValueUpper = strtoupper(trim($gradeValue));
                      if ($gradeValueUpper === 'INC') {
                          $displayGrade = 'inc';
                          $rowClass = 'warning-row';
                      } elseif (in_array($gradeValueUpper, ['PASS', 'COMPLETE'])) {
                          $rowClass = 'passed-row';
                      } else {
                          $rowClass = 'failed-row';
                      }
                  }
                  
                  // Check if this subject has prerequisites and if those prerequisites are "inc" subjects
                  // OR if this subject itself has "inc" grade
                  $showButtons = shouldShowButtons($code, $displayGrade, $prereq, $curriculum, $gradesByCode);
                  
                  error_log("DEBUG: Final showButtons result for $code: " . ($showButtons ? 'true' : 'false'));
                  
                  
                  // Check if student is regular
                  $isRegular = isRegularStudent($student);
                  
                  // Debug: Log student data
                  error_log("DEBUG: Student data: " . print_r($student, true));
                  error_log("DEBUG: isRegular result: " . ($isRegular ? 'true' : 'false'));
                  
                  // Check prerequisites for all students (not just irregular)#c6282
                  $prereq = trim($prereq);
                  $shouldCheckPrereq = !empty($prereq) && !in_array(strtolower($prereq), ['none', 'n/a', '-', '', 'none ']);
                  
                  // Check if this is a classification-based prerequisite (like "Regular 3rd Year")
                  $isClassificationPrereq = false;
                  $classificationPrereqs = ['Regular', 'Regular 3rd Year', 'Regular 4th Year'];
                  
                  if ($shouldCheckPrereq) {
                      $prereqLower = strtolower($prereq);
                      foreach ($classificationPrereqs as $classPrereq) {
                          if (strpos($prereqLower, strtolower($classPrereq)) !== false) {
                              $isClassificationPrereq = true;
                              break;
                          }
                      }
                  }
                  
                  // Only check prerequisites for irregular students, regular students should not be flagged
                  if ($shouldCheckPrereq) {
                      // Direct check for "Regular" classification from signin_db
                      $studentClassification = trim($student['classification'] ?? '');
                      $isDirectRegular = ($studentClassification === 'Regular' || $studentClassification === 'Regular 3rd Year' || $studentClassification === 'Regular 4th Year'); // Exact match for any regular classification
                      
                      // Debug logging
                      error_log("DEBUG: Student classification from signin_db: " . $studentClassification);
                      error_log("DEBUG: isDirectRegular (exact match): " . ($isDirectRegular ? 'true' : 'false'));
                      error_log("DEBUG: Prereq: " . $prereq);
                      
                      // If student is any "Regular" classification, check if they meet the specific prerequisite
                      if ($isDirectRegular) {
                          // Check if the student meets the specific year requirement
                          if ($prereqLower === 'regular') {
                              // Any regular student can take courses with just "Regular" prerequisite
                              $prereqFailed = false;
                              error_log("DEBUG: Student is Regular and prerequisite is 'Regular', setting prereqFailed = false");
                          } elseif ($prereqLower === 'regular 3rd year') {
                              // Regular students can take 3rd year courses (assuming they're in 3rd year or above)
                              if ($studentClassification === 'Regular' || $studentClassification === 'Regular 3rd Year' || $studentClassification === 'Regular 4th Year') {
                                  $prereqFailed = false;
                                  error_log("DEBUG: Student is $studentClassification and prerequisite is 'Regular 3rd Year', setting prereqFailed = false");
                              } else {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Student is $studentClassification but prerequisite is 'Regular 3rd Year', setting prereqFailed = true");
                              }
                          } elseif ($prereqLower === 'regular 4th year') {
                              // Regular students can take 4th year courses (assuming they're in 4th year)
                              // For now, allow any Regular student to take 4th year courses
                              if ($studentClassification === 'Regular' || $studentClassification === 'Regular 3rd Year' || $studentClassification === 'Regular 4th Year') {
                                  $prereqFailed = false;
                                  error_log("DEBUG: Student is $studentClassification and prerequisite is 'Regular 4th Year', setting prereqFailed = false");
                              } else {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Student is $studentClassification but prerequisite is 'Regular 4th Year', setting prereqFailed = true");
                              }
                          } else {
                              // For any other regular-based prerequisite, check if student classification contains the prerequisite
                              if (strpos(strtolower($prereq), strtolower($studentClassification)) !== false) {
                                  $prereqFailed = false;
                                  error_log("DEBUG: Student $studentClassification matches prerequisite $prereq, setting prereqFailed = false");
                              } else {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Student $studentClassification does not match prerequisite $prereq, setting prereqFailed = true");
                              }
                          }
                      } else {
                          error_log("DEBUG: Student is NOT exactly 'Regular', checking prerequisites normally");
                          // Original prerequisite checking logic for non-regular students
                          if ($isClassificationPrereq) {
                              $studentClassLower = strtolower(trim($student['classification'] ?? ''));
                              $matchedClassPrereq = '';
                              
                              // Find which classification prerequisite matched
                              foreach ($classificationPrereqs as $classPrereq) {
                                  if (strpos($prereqLower, strtolower($classPrereq)) !== false) {
                                      $matchedClassPrereq = strtolower($classPrereq);
                                      break;
                                  }
                              }
                              
                              error_log("DEBUG: Matched class prereq: " . $matchedClassPrereq);
                              error_log("DEBUG: Student class lower: " . $studentClassLower);
                              
                              // NEW LOGIC: If student is irregular and prerequisite is Regular-based, BLOCK the course
                              if (strpos($studentClassLower, 'irregular') !== false && 
                                  in_array($matchedClassPrereq, ['regular', 'regular 3rd year', 'regular 4th year'])) {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Irregular student cannot take course with Regular prerequisite: " . $matchedClassPrereq . ", prereqFailed = true");
                              } elseif (strpos($studentClassLower, $matchedClassPrereq) === false) {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Classification NOT matched, prereqFailed = true");
                              } else {
                                  $prereqFailed = false;
                                  error_log("DEBUG: Classification matched, prereqFailed = false");
                              }
                          } else {
                              // Regular course prerequisite checking
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
                          }
                      }
                      
                      if ($prereqFailed && !$isDirectRegular) {
                          $rowClass = 'prereq-failed';
                          error_log("DEBUG: Setting rowClass to prereq-failed");
                      }
                  }
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
                  <td class="text-center" style="padding: 12px 8px;">
                    <?php 
                    // Check if this subject is already in irregular_db for any semester
                    $isInIrregularDb = false;
                    $normalizedCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
                    foreach ($allIrregularSubjects as $irregular) {
                        $normalizedIrregularCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($irregular['code']));
                        if ($normalizedIrregularCode === $normalizedCode) {
                            $isInIrregularDb = true;
                            break;
                        }
                    }
                    
                    // Check subject grade status
                    $isPassed = false;
                    $isFailed = false;
                    $normalizedGradeCode = normalizeCourseCode($code);
                    if (isset($gradesByCode[$normalizedGradeCode])) {
                        $grade = $gradesByCode[$normalizedGradeCode];
                        if (is_numeric($grade)) {
                            $gradeValue = floatval($grade);
                            if ($gradeValue <= 3.25) {
                                $isPassed = true;
                            } elseif ($gradeValue >= 5.0) {
                                $isFailed = true;
                                // If failed, don't consider it as in irregular_db for display purposes
                                $isInIrregularDb = false;
                            }
                        } elseif (is_string($grade) && strtoupper(trim($grade)) === 'PASSED') {
                            $isPassed = true;
                        }
                    }
                    ?>
                    <?php if ($prereqFailed && !$isRegular): ?>
                      <span style="color: #e63e3eff; font-size: 1.2em; font-weight: bold;">✗</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             disabled
                             style="display: none;">
                    <?php elseif ($isInIrregularDb && !$isFailed): ?>
                      <span style="color: #6c757d; font-size: 1.2em; font-weight: bold;">✓</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             disabled
                             title="This subject is already in irregular subjects">
                    <?php elseif ($isFailed): ?>
                      <span style="color: #e63e3eff; font-size: 1.2em; font-weight: bold;">✗</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             title="This subject needs to be retaken">
                    <?php elseif ($isPassed): ?>
                      <span style="color: #28a745; font-size: 1.2em; font-weight: bold;">✓</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             disabled
                             title="This subject has already been passed">
                    <?php else: ?>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>">
                    <?php endif; ?>
                  </td>
                  <td class="text-center grade-cell <?= $rowClass ?> <?= ($prereqFailed && !$isRegular) ? 'prereq-failed' : '' ?>" 
                      style="padding: 12px 8px; <?= ($prereqFailed && !$isRegular) ? 'background-color: transparent !important; color: #c62828 !important;' : '' ?>" 
                      data-code="<?= htmlspecialchars(normalizeCourseCode($code)) ?>">
                    <strong style="<?= ($prereqFailed && !$isRegular) ? 'color: #c93838ff !important;' : '' ?>">
                        <?php 
                        // Show empty cell for PREREQ display
                        $showPrereq = ($prereqFailed && !$isRegular) && 
                                    !empty($prereq) && 
                                    !in_array(strtolower(trim($prereq)), ['none', 'n/a', '-', '', 'none ']);
                        echo $showPrereq ? '' : ($displayGrade ?? '—'); 
                        ?>
                    </strong>
                  </td>
                  <td class="text-center" style="padding: 12px 8px;">
                    <span class="badge" style="background-color: #e9ecef; color: #212529; border: 1px solid #ffffffff; padding: 0.25em 0.5em; font-size: 0.9em; border-radius: 4px;"><?= htmlspecialchars($code) ?></span>
                  </td>
                  <td class="course-title-cell" style="padding: 12px 8px;">
                    <?= htmlspecialchars($subj['title']) ?>
                  </td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['lec']) ?></td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['lab']) ?></td>
                  <td class="text-center" style="padding: 12px 8px;"><?= htmlspecialchars($subj['units']) ?></td>
                  <td class="text-center" style="padding: 12px 8px;">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                      <span><?= htmlspecialchars($subj['prereq'] ?: 'None') ?></span>
                      <?php if ($showButtons): ?>
                      <button class="btn btn-sm btn-outline-primary edit-prereq-btn" 
                              data-bs-toggle="modal" data-bs-target="#editPrereqModal"
                              data-code="<?= htmlspecialchars($code) ?>"
                              data-title="<?= htmlspecialchars($subj['title']) ?>"
                              data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="text-center" style="width: 120px;">
                    <?php if ($showButtons): ?>
                    <button class="btn btn-sm btn-success add-subject-open" 
                            data-bs-toggle="modal" data-bs-target="#addSubjectModal"
                            data-code="<?= htmlspecialchars($code) ?>"
                            data-title="<?= htmlspecialchars($subj['title']) ?>"
                            data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>">
                        <i class="bi bi-plus-circle me-1"></i>Add Subject
                    </button>
                    <?php else: ?>
                    <span class="text-muted small"></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              <!-- Total Units Row -->
              <tr style="background-color: <?= $isOverLimit ? '#f8d7da' : '#f8f9fa' ?>;">
                <td colspan="6" class="text-end fw-bold" style="padding: 12px 8px; border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;">
                  <span class="me-3">TOTAL UNITS:</span>
                </td>
                <td class="text-center fw-bold <?= $isOverLimit ? 'text-danger' : '' ?>" style="padding: 12px 8px; border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;">
                  <?= number_format($semesterUnits, 1) ?> / <?= $maxUnits ?>
                  <?php if ($isOverLimit): ?>
                    <div class="small text-danger mt-1">
                      <i class="bi bi-exclamation-triangle"></i> Over limit!
                    </div>
                  <?php endif; ?>
                </td>
                <td colspan="2" style="border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
<?php endforeach; ?>

<!-- Bulk Save Section -->
<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="bi bi-check-square me-2"></i>Bulk Save Selected Subjects</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <label for="bulkYearSem" class="form-label">Select Year and Semester:</label>
          <select class="form-select" id="bulkYearSem" required>
            <option value="">Choose Year-Semester...</option>
            <option value="1-1">1st Year • First Semester</option>
            <option value="1-2">1st Year • Second Semester</option>
            <option value="2-1">2nd Year • First Semester</option>
            <option value="2-2">2nd Year • Second Semester</option>
            <option value="3-1">3rd Year • First Semester</option>
            <option value="3-2">3rd Year • Second Semester</option>
            <option value="4-1">4th Year • First Semester</option>
            <option value="4-2">4th Year • Second Semester</option>
          </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <div>
            <button type="button" id="bulkSaveBtn" class="btn btn-success me-2" disabled>
              <i class="bi bi-save me-1"></i>Save Selected Subjects
            </button>
            <button type="button" id="clearSelectionBtn" class="btn btn-outline-secondary">
              <i class="bi bi-x-square me-1"></i>Clear Selection
            </button>
          </div>
        </div>
      </div>
      <div class="mt-3">
        <div id="selectedSubjectsInfo" class="alert alert-info" style="display: none;">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong>Selected Subjects:</strong> <span id="selectedCount">0</span>
            </div>
            <div class="text-end">
              <span class="badge bg-primary fs-6" id="totalUnitsBadge">0.0 units</span>
            </div>
          </div>
          <div id="selectedSubjectsList" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>


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
<div class="modal fade" id="editGradeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Grade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editGradeForm">
          <input type="hidden" name="action" value="save_grade">
          <input type="hidden" name="student_id" id="eg_student">
          <input type="hidden" name="course_code" id="eg_code">
          <input type="hidden" name="year" id="eg_year">
          <input type="hidden" name="sem" id="eg_sem">
          <div class="mb-3">
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
        <h5 class="modal-title" id="selectSubjectModalLabel"> Selected Courses </h5>
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
          <div class="mb-3">
            <label for="totalUnitsInput" class="form-label">Total Units</label>
            <input type="number" class="form-control" id="totalUnitsInput" name="total_units" min="0.5" step="0.5" readonly>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Prerequisite Modal -->
<div class="modal fade" id="editPrereqModal" tabindex="-1" aria-labelledby="editPrereqModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editPrereqModalLabel">Edit Prerequisite</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editPrereqForm">
        <div class="modal-body">
          <div class="mb-3">
            <label for="editCourseCode" class="form-label">Course Code</label>
            <input type="text" class="form-control" id="editCourseCode" readonly>
          </div>
          <div class="mb-3">
            <label for="editCourseTitle" class="form-label">Course Title</label>
            <input type="text" class="form-control" id="editCourseTitle" readonly>
          </div>
          <div class="mb-3">
            <label for="editPrereqInput" class="form-label">Prerequisite</label>
            <select class="form-select" id="editPrereqInput" name="prereq">
              <option value="">None</option>
              <option value="Regular">Regular</option>
              <option value="Regular 3rd Year">Regular 3rd Year</option>
              <option value="Regular 4th Year">Regular 4th Year</option>
            </select>
            <div class="form-text">Select the prerequisite requirement for this course.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Prerequisite</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle Select All for any semester
    document.addEventListener('click', function(e) {
        const selectAllBtn = e.target.closest('.select-semester');
        if (!selectAllBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const ysKey = selectAllBtn.getAttribute('data-ys');
        if (!ysKey) return;
        
        // Find the specific semester section using the data-ys attribute
        const semesterSection = document.querySelector(`[data-ys="${ysKey}"]`);
        if (!semesterSection) return;
        
        // Find the table within this semester section
        const table = semesterSection.querySelector('table');
        if (!table) return;
        
        // Find all checkboxes in this semester's table that are not disabled
        const checkboxes = table.querySelectorAll('input[type="checkbox"].subject-checkbox:not([disabled])');
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        
        // Toggle all checkboxes in this semester only
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
            // Trigger change event if needed
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
        
        // Update button text and icon only for the clicked button
        const icon = allChecked ? 'check-all' : 'x-circle';
        selectAllBtn.innerHTML = `<i class="bi bi-${icon} me-1"></i> ${allChecked ? 'Select All' : 'Deselect All'}`;
    });
    
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
                
                // Get program and unit limits from server-side data
                const program = '<?= htmlspecialchars($program) ?>';
                const unitLimits = <?= json_encode($unitLimits) ?>;
                
                // Get the selected year and semester
                const yearSelect = document.getElementById('yearSelect');
                const semSelect = document.getElementById('semSelect');
                const year = yearSelect.value;
                const sem = semSelect.value;
                const yearSem = `${year}-${sem}`;
                
                // Get units for the subject being added
                const totalUnitsInput = document.getElementById('totalUnitsInput');
                const newUnits = parseFloat(totalUnitsInput.value) || 0;
                
                // Get current units from irregular_db only (not including curriculum subjects)
                let currentUnits = 0;
                const semesterBlock = document.querySelector(`div.mb-4[data-ys="${yearSem}"]`);
                if (semesterBlock) {
                    // Look for irregular units badge specifically, not the total badge
                    const irregularBadge = semesterBlock.querySelector('.badge.bg-success.rounded-pill');
                    if (irregularBadge) {
                        const match = irregularBadge.textContent.match(/([\d.]+)/);
                        if (match) {
                            currentUnits = parseFloat(match[1]);
                        }
                    } else {
                        // Fallback: Look for any badge that shows irregular units
                        const badges = semesterBlock.querySelectorAll('.badge');
                        badges.forEach(badge => {
                            if (badge.textContent.includes('Irregular Units')) {
                                const match = badge.textContent.match(/([\d.]+)/);
                                if (match) {
                                    currentUnits = parseFloat(match[1]);
                                }
                            }
                        });
                    }
                }
                
                // Check unit limits
                const maxUnits = unitLimits[program] && unitLimits[program][yearSem] ? unitLimits[program][yearSem].max : 26;
                const totalUnitsAfter = currentUnits + newUnits;
                
                // Check if adding this subject would exceed the limit
                if (totalUnitsAfter > maxUnits) {
                    const overBy = (totalUnitsAfter - maxUnits).toFixed(1);
                    alert(`Cannot add subject: Unit limit would be exceeded.\n\n` +
                        `Current units: ${currentUnits.toFixed(1)}\n` +
                        `Adding: ${newUnits.toFixed(1)} units\n` +
                        `Total: ${totalUnitsAfter.toFixed(1)} units\n` +
                        `Maximum allowed: ${maxUnits} units\n` +
                        `Over limit by: ${overBy} units\n\n` +
                        `Please remove some subjects or choose a different semester.`);
                    return;
                }
                
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
                        const message = data.message || 'Subject added successfully!';
                        toast.innerHTML = `
                            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="toast-header bg-success text-white">
                                    <strong class="me-auto">Success</strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                                <div class="toast-body">
                                    ${message}
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

  document.querySelectorAll('.btn-edit-grade').forEach(btn => {
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

<!-- Bulk Save Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');
    const bulkSaveBtn = document.getElementById('bulkSaveBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const bulkYearSem = document.getElementById('bulkYearSem');
    const selectedSubjectsInfo = document.getElementById('selectedSubjectsInfo');
    const selectedCount = document.getElementById('selectedCount');
    const selectedSubjectsList = document.getElementById('selectedSubjectsList');
    const totalUnitsBadge = document.getElementById('totalUnitsBadge');
    
    // Update selected subjects display
    function updateSelectedDisplay() {
        const selectedCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        // Calculate total units
        let totalUnits = 0;
        selectedCheckboxes.forEach(checkbox => {
            const units = parseFloat(checkbox.dataset.units) || 0;
            totalUnits += units;
        });
        
        selectedCount.textContent = count + ' subject' + (count !== 1 ? 's' : '') + ' (' + totalUnits.toFixed(1) + ' units)';
        
        // Update badge with color based on total units
        totalUnitsBadge.textContent = totalUnits.toFixed(1) + ' units';
        totalUnitsBadge.className = 'badge fs-6';
        
        if (totalUnits >= 26) {
            totalUnitsBadge.classList.add('bg-danger'); // Red for high units
        } else if (totalUnits >= 24) {
            totalUnitsBadge.classList.add('bg-warning'); // Orange for medium-high units
        } else if (totalUnits >= 22) {
            totalUnitsBadge.classList.add('bg-info'); // Blue for medium units
        } else if (totalUnits >= 20) {
            totalUnitsBadge.classList.add('bg-success'); // Green for low-medium units
        } else {
            totalUnitsBadge.classList.add('bg-secondary'); // Gray for very low units
        }
        
        if (count > 0) {
            selectedSubjectsInfo.style.display = 'block';
            
            // Build list of selected subjects
            let listHTML = '<div class="row">';
            selectedCheckboxes.forEach((checkbox, index) => {
                const code = checkbox.value;
                const title = checkbox.dataset.title;
                const units = checkbox.dataset.units;
                
                listHTML += `
                    <div class="col-md-6 mb-2">
                        <div class="card card-body py-2">
                            <small><strong>${code}</strong> - ${title} (${units} units)</small>
                        </div>
                    </div>
                `;
            });
            listHTML += '</div>';
            selectedSubjectsList.innerHTML = listHTML;
            
            // Enable save button if year-semester is selected
            bulkSaveBtn.disabled = !bulkYearSem.value;
        } else {
            selectedSubjectsInfo.style.display = 'none';
            bulkSaveBtn.disabled = true;
        }
    }
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            subjectCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedDisplay();
        });
    }
    
    // Individual checkbox changes
    subjectCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedDisplay);
    });
    
    // Year-semester selection change
    bulkYearSem.addEventListener('change', function() {
        const hasSelection = document.querySelectorAll('.subject-checkbox:checked').length > 0;
        bulkSaveBtn.disabled = !this.value || !hasSelection;
    });
    
    // Clear selection
    clearSelectionBtn.addEventListener('click', function() {
        subjectCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        updateSelectedDisplay();
    });
    
    // Bulk save functionality
    bulkSaveBtn.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
        const yearSem = bulkYearSem.value;
        
        if (!yearSem) {
            alert('Please select a year and semester.');
            return;
        }
        
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one subject.');
            return;
        }
        
        // Get program and unit limits from server-side data
        const program = '<?= htmlspecialchars($program ?: 'BSIT') ?>';
        const unitLimits = <?= json_encode($unitLimits) ?>;
        
        // Debug logging
        console.log('Program:', program);
        console.log('Unit limits:', unitLimits);
        console.log('YearSem:', yearSem);
        
        // Get current units from irregular_db only (not including curriculum subjects)
        let currentUnits = 0;
        const semesterBlock = document.querySelector(`div.mb-4[data-ys="${yearSem}"]`);
        if (semesterBlock) {
            // Look for irregular units badge specifically, not the total badge
            const irregularBadge = semesterBlock.querySelector('.badge.bg-success.rounded-pill');
            if (irregularBadge) {
                const match = irregularBadge.textContent.match(/([\d.]+)/);
                if (match) {
                    currentUnits = parseFloat(match[1]);
                    console.log('Found irregular units:', currentUnits);
                }
            } else {
                // Fallback: Look for any badge that shows irregular units
                const badges = semesterBlock.querySelectorAll('.badge');
                badges.forEach(badge => {
                    if (badge.textContent.includes('Irregular Units')) {
                        const match = badge.textContent.match(/([\d.]+)/);
                        if (match) {
                            currentUnits = parseFloat(match[1]);
                            console.log('Found irregular units in badge:', currentUnits);
                        }
                    }
                });
            }
        }
        
        console.log('Current irregular units:', currentUnits);
        
        // Calculate units of selected subjects
        let selectedUnits = 0;
        selectedCheckboxes.forEach(checkbox => {
            const units = parseFloat(checkbox.dataset.units) || 0;
            selectedUnits += units;
        });
        
        const totalUnitsAfter = currentUnits + selectedUnits;
        const maxUnits = unitLimits[program] && unitLimits[program][yearSem] ? unitLimits[program][yearSem].max : 26;
        
        // Check if adding these subjects would exceed the limit
        if (totalUnitsAfter > maxUnits) {
            const overBy = (totalUnitsAfter - maxUnits).toFixed(1);
           Swal.fire({
    icon: 'error',
    title: 'Unit Limit Exceeded',
    html: `
        <div style="text-align:left;">
            <p><strong>Cannot add subjects:</strong> Unit limit would be exceeded.</p>
            <hr>
            <p><strong>Current units:</strong> ${currentUnits.toFixed(1)}</p>
            <p><strong>Adding:</strong> ${selectedUnits.toFixed(1)} units</p>
            <p><strong>Total after adding:</strong> ${totalUnitsAfter.toFixed(1)} units</p>
            <p><strong>Maximum allowed:</strong> ${maxUnits} units</p>
            <p><strong>Over limit by:</strong> ${overBy} units</p>
            <hr>
            <p>Please remove some subjects or choose a different semester.</p>
        </div>
    `,
    confirmButtonText: 'Okay',
});

            return;
        }
        
        const [year, sem] = yearSem.split('-');
        const studentId = '<?= htmlspecialchars($studentId) ?>';
        
        // Prepare data for saving
        const subjects = [];
        selectedCheckboxes.forEach(checkbox => {
            subjects.push({
                course_code: checkbox.value,
                course_title: checkbox.dataset.title,
                total_units: checkbox.dataset.units,
                lec_units: checkbox.dataset.lec,
                lab_units: checkbox.dataset.lab,
                prerequisites: checkbox.dataset.prereq,
                year_level: year,
                semester: sem
            });
        });
        
        // Send data to server
        console.log('Sending data:', {
            action: 'bulk_save_irregular',
            student_id: studentId,
            subjects: subjects,
            program: program,
            yearSem: yearSem
        });
        
        fetch('stueval.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'bulk_save_irregular',
                student_id: studentId,
                subjects: subjects
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                const message = `Successfully saved ${subjects.length} subjects to irregular database.`;
                alert(message);
                // Clear selection after successful save
                clearSelectionBtn.click();
                // Optionally refresh the page or update the display
                window.location.reload();
            } else {
                alert('Error saving subjects: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving subjects. Please try again.');
        });
    });
});

// Handle Edit Prerequisite Modal
document.addEventListener('DOMContentLoaded', function() {
    const editPrereqModal = document.getElementById('editPrereqModal');
    if (editPrereqModal) {
        editPrereqModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const courseCode = button.getAttribute('data-code');
            const courseTitle = button.getAttribute('data-title');
            const currentPrereq = button.getAttribute('data-prereq');
            
            // Fill the modal with course information
            document.getElementById('editCourseCode').value = courseCode;
            document.getElementById('editCourseTitle').value = courseTitle;
            document.getElementById('editPrereqInput').value = currentPrereq;
        });
        
        // Handle form submission
        const form = document.getElementById('editPrereqForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', 'edit_prerequisite');
                formData.append('course_code', document.getElementById('editCourseCode').value);
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                
                // Send AJAX request
                fetch('stueval.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(editPrereqModal);
                        modal.hide();
                        
                        // Show success message
                        alert('Prerequisite updated successfully!');
                        
                        // Reload page to show updated prerequisite
                        window.location.reload();
                    } else {
                        alert('Error updating prerequisite: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating prerequisite. Please try again.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
        }
    }
});
</script>
</body>
</html>