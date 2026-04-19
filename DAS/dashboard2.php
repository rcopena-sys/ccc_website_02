<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Keep students_db.classification in sync with signin_db.classification
// for all students sharing the same student_id.
$syncClassificationSql = "UPDATE students_db s
    INNER JOIN signin_db si ON s.student_id = si.student_id
    SET s.classification = si.classification
    WHERE si.classification IS NOT NULL
      AND (s.classification IS NULL OR s.classification <> si.classification)";
$conn->query($syncClassificationSql);
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

  function isIrregularClassification(string $classification): bool {
    return preg_match('/\birregular\b/i', trim($classification)) === 1;
  }

  function canOverloadSubjects(string $classification): bool {
    $classification = trim($classification);
    return isIrregularClassification($classification) || preg_match('/\bprobationary\b/i', $classification) === 1;
  }

  function normalizeDasProgram(string $program): string {
    $program = strtoupper(trim($program));

    if ($program === '') {
      return '';
    }

    if (strpos($program, 'BSCS') !== false || strpos($program, 'BSAIS') !== false) {
      return 'BSCS';
    }

    if (strpos($program, 'BSIT') !== false || strpos($program, 'BSA') !== false) {
      return 'BSIT';
    }

    return $program;
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

  function isSemesterLocked(mysqli $conn, int $semester): bool {
    if (!in_array($semester, [1, 2], true)) {
      return false;
    }

    try {
      $tblRes = $conn->query("SHOW TABLES LIKE 'semester_locks'");
      if (!$tblRes || $tblRes->num_rows === 0) {
        return false;
      }

      $stmt = $conn->prepare("SELECT is_locked FROM semester_locks WHERE semester = ? LIMIT 1");
      if (!$stmt) {
        return false;
      }

      $stmt->bind_param('i', $semester);
      if (!$stmt->execute()) {
        $stmt->close();
        return false;
      }

      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $stmt->close();

      if (!$row) {
        return false;
      }

      return (int)$row['is_locked'] === 1;
    } catch (Throwable $e) {
      error_log('Semester lock check failed in stueval.php: ' . $e->getMessage());
      return false;
    }
  }

  function semesterLabel(int $semester): string {
    if ($semester === 1) return '1st Semester';
    if ($semester === 2) return '2nd Semester';
    return 'Selected semester';
  }

function normalizeSemesterKey(string $raw): string {
    $raw = strtolower(trim($raw));
  $raw = str_replace(['first','second','third','fourth','fifth','1st','2nd','3rd','4th','5th'], ['1','2','3','4','5','1','2','3','4','5'], $raw);
    $raw = str_replace(['year','yr','semester','sem',' ', '-'], '', $raw);
  if (preg_match('/^([1-5])([1-2])$/', $raw, $m)) {
        return $m[1] . '-' . $m[2];
    }
  if (preg_match('/^[1-5]-[1-2]$/', $raw)) return $raw;
    return $raw;
}

  function parseYearLevelNumber(string $raw): ?int {
    $val = strtolower(trim($raw));
    if ($val === '') {
      return null;
    }

    if (preg_match('/\b([1-5])\b/', $val, $m)) {
      return (int)$m[1];
    }

    if (preg_match('/\b([1-5])(st|nd|rd|th)\b/', $val, $m)) {
      return (int)$m[1];
    }

    if (strpos($val, 'first') !== false) return 1;
    if (strpos($val, 'second') !== false) return 2;
    if (strpos($val, 'third') !== false) return 3;
    if (strpos($val, 'fourth') !== false) return 4;
    if (strpos($val, 'fifth') !== false) return 5;

    return null;
  }

  function parseSemesterNumber(string $raw): ?int {
    $val = strtolower(trim($raw));
    if ($val === '') {
      return null;
    }

    if (preg_match('/\b([1-2])\b/', $val, $m)) {
      return (int)$m[1];
    }

    if (preg_match('/\b([1-2])(st|nd)\b/', $val, $m)) {
      return (int)$m[1];
    }

    if (strpos($val, 'first') !== false) return 1;
    if (strpos($val, 'second') !== false) return 2;

    return null;
  }

function normalizeCourseCode(string $code): string {
    // Uppercase and strip all non-alphanumeric to match 'ALG 101' with 'ALG101' etc.
    $upper = strtoupper(trim($code));
    return preg_replace('/[^A-Z0-9]/', '', $upper);
}

function deletedAutoloadBlockKey(string $courseCode, int $year, int $sem): string {
  return normalizeCourseCode($courseCode) . '|' . $year . '-' . $sem;
}

function markDeletedAutoloadBlock(string $studentId, string $courseCode, int $year, int $sem): void {
  if ($studentId === '' || $year <= 0 || $sem <= 0) {
    return;
  }
  if (!isset($_SESSION['deleted_irregular_autoload_blocks']) || !is_array($_SESSION['deleted_irregular_autoload_blocks'])) {
    $_SESSION['deleted_irregular_autoload_blocks'] = [];
  }
  if (!isset($_SESSION['deleted_irregular_autoload_blocks'][$studentId]) || !is_array($_SESSION['deleted_irregular_autoload_blocks'][$studentId])) {
    $_SESSION['deleted_irregular_autoload_blocks'][$studentId] = [];
  }
  $_SESSION['deleted_irregular_autoload_blocks'][$studentId][deletedAutoloadBlockKey($courseCode, $year, $sem)] = true;
}

function isDeletedAutoloadBlocked(string $studentId, string $courseCode, int $year, int $sem): bool {
  if ($studentId === '' || $year <= 0 || $sem <= 0) {
    return false;
  }
  $key = deletedAutoloadBlockKey($courseCode, $year, $sem);
  return !empty($_SESSION['deleted_irregular_autoload_blocks'][$studentId][$key]);
}

function clearDeletedAutoloadBlocks(string $studentId): void {
  if ($studentId === '') {
    return;
  }
  if (isset($_SESSION['deleted_irregular_autoload_blocks'][$studentId])) {
    unset($_SESSION['deleted_irregular_autoload_blocks'][$studentId]);
  }
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

function isFailedGradeForClassification($gradeValue): bool {
  if ($gradeValue === null || $gradeValue === '') {
    return false;
  }

  if (is_numeric($gradeValue)) {
    return (float)$gradeValue >= 5.00;
  }

  $txt = strtoupper(trim((string)$gradeValue));
  return in_array($txt, ['FAILED', 'FAIL'], true);
}

function calculateFailedUnitsForStudent(mysqli $conn, string $studentId): float {
  if ($studentId === '') {
    return 0.0;
  }

  $codeCol = columnExists($conn, 'curriculum', 'course_code') ? 'course_code' : 'course_code';
  $totalCol = columnExists($conn, 'curriculum', 'total_units')
    ? 'total_units'
    : (columnExists($conn, 'curriculum', 'units') ? 'units' : null);
  $lecCol = columnExists($conn, 'curriculum', 'lec_units')
    ? 'lec_units'
    : (columnExists($conn, 'curriculum', 'lecture_units') ? 'lecture_units' : null);
  $labCol = columnExists($conn, 'curriculum', 'lab_units') ? 'lab_units' : null;

  $unitsByCode = [];
  $currSql = "SELECT {$codeCol} AS course_code";
  if ($totalCol !== null) {
    $currSql .= ", {$totalCol} AS total_units";
  } else {
    $currSql .= ", NULL AS total_units";
  }
  if ($lecCol !== null) {
    $currSql .= ", {$lecCol} AS lec_units";
  } else {
    $currSql .= ", 0 AS lec_units";
  }
  if ($labCol !== null) {
    $currSql .= ", {$labCol} AS lab_units";
  } else {
    $currSql .= ", 0 AS lab_units";
  }
  $currSql .= " FROM curriculum";

  $currRes = $conn->query($currSql);
  if ($currRes instanceof mysqli_result) {
    while ($row = $currRes->fetch_assoc()) {
      $code = trim((string)($row['course_code'] ?? ''));
      if ($code === '') {
        continue;
      }
      $norm = normalizeCourseCode($code);
      $units = (float)($row['total_units'] ?? 0);
      if ($units <= 0) {
        $units = (float)($row['lec_units'] ?? 0) + (float)($row['lab_units'] ?? 0);
      }
      if (!isset($unitsByCode[$norm]) || $unitsByCode[$norm] <= 0) {
        $unitsByCode[$norm] = $units;
      }
    }
  }

  $gradeCol = gradeColumn($conn);
  $sql = "SELECT course_code, {$gradeCol} AS grade_value, year, sem FROM grades_db WHERE student_id = ? ORDER BY year DESC, sem DESC";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return 0.0;
  }

  $stmt->bind_param('s', $studentId);
  $stmt->execute();
  $res = $stmt->get_result();

  $latestByCourse = [];
  while ($row = $res->fetch_assoc()) {
    $code = trim((string)($row['course_code'] ?? ''));
    if ($code === '') {
      continue;
    }
    $norm = normalizeCourseCode($code);
    if (!array_key_exists($norm, $latestByCourse)) {
      $latestByCourse[$norm] = $row['grade_value'] ?? null;
    }
  }
  $stmt->close();

  $failedUnits = 0.0;
  foreach ($latestByCourse as $norm => $gradeValue) {
    if (!isFailedGradeForClassification($gradeValue)) {
      continue;
    }
    $failedUnits += (float)($unitsByCode[$norm] ?? 0.0);
  }

  return round($failedUnits, 2);
}

function classificationByFailedUnits(float $failedUnits): ?string {
  if ($failedUnits > 6.00) {
    return 'Dismissal';
  }
  if ($failedUnits >= 6.00) {
    return 'Probationary';
  }
  return null;
}

function resolveStudentClassification(mysqli $conn, string $studentId): string {
  $failedUnits = calculateFailedUnitsForStudent($conn, $studentId);
  $ruleClassification = classificationByFailedUnits($failedUnits);

  if ($ruleClassification !== null) {
    return $ruleClassification;
  }

  $currentYear = (int)($GLOBALS['currentYear'] ?? date('Y'));
  $currentSem = (int)($GLOBALS['currentSem'] ?? 1);

  return checkIrregularStatus($conn, $studentId, $currentYear, $currentSem) ? 'Irregular' : 'Regular';
}

function syncStudentClassification(mysqli $conn, string $studentId, string $classification): void {
  if ($studentId === '' || $classification === '') {
    return;
  }

  $updateSigninStmt = $conn->prepare("UPDATE signin_db SET classification = ? WHERE student_id = ? AND (classification IS NULL OR classification <> ?)");
  if ($updateSigninStmt) {
    $updateSigninStmt->bind_param('sss', $classification, $studentId, $classification);
    $updateSigninStmt->execute();
    $updateSigninStmt->close();
  }

  if (columnExists($conn, 'students_db', 'classification')) {
    $updateStudentsStmt = $conn->prepare("UPDATE students_db SET classification = ? WHERE student_id = ? AND (classification IS NULL OR classification <> ?)");
    if ($updateStudentsStmt) {
      $updateStudentsStmt->bind_param('sss', $classification, $studentId, $classification);
      $updateStudentsStmt->execute();
      $updateStudentsStmt->close();
    }
  }
}

function isGradePassingForPromotion($gradeValue): bool {
  if ($gradeValue === null || $gradeValue === '') {
    return false;
  }

  if (is_numeric($gradeValue)) {
    $gv = (float)$gradeValue;
    // In this system, numeric grades <= 3.25 are treated as passed.
    return $gv <= 3.25;
  }

  if (is_string($gradeValue)) {
    $gUpper = strtoupper(trim($gradeValue));
    return $gUpper === 'PASSED';
  }

  return false;
}

function isSubjectPassedForYearPromotion(string $courseCode, array $gradesByCode): bool {
  $norm = normalizeCourseCode($courseCode);

  // Prefer normalized code key; fall back to original code key if present
  $gradeValue = $gradesByCode[$norm] ?? ($gradesByCode[$courseCode] ?? null);

  return isGradePassingForPromotion($gradeValue);
}

function mapIrregularStatusFromGrade($gradeValue): string {
  if ($gradeValue === null || $gradeValue === '') {
    return 'enrolled';
  }

  if (is_numeric($gradeValue)) {
    $gv = (float)$gradeValue;
    if ($gv <= 3.25) {
      return 'completed';
    }
    if ($gv >= 5.00) {
      return 'failed';
    }
    return 'enrolled';
  }

  $txt = strtoupper(trim((string)$gradeValue));
  if (in_array($txt, ['PASSED', 'PASS', 'COMPLETE', 'COMPLETED'], true)) {
    return 'completed';
  }
  if (in_array($txt, ['FAILED', 'FAIL'], true)) {
    return 'failed';
  }

  return 'enrolled';
}

function ensureGradedSubjectInIrregularDb(mysqli $conn, string $studentId, string $courseCode, string $courseTitle, int $year, int $sem, $gradeValue = null): bool {
  $tbl = $conn->query("SHOW TABLES LIKE 'irregular_db'");
  if (!$tbl || $tbl->num_rows === 0) {
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

  $statusValue = mapIrregularStatusFromGrade($gradeValue);

  $dup = $conn->prepare("SELECT id FROM irregular_db WHERE student_id = ? AND course_code = ? AND {$yearCol} = ? AND {$semCol} = ? LIMIT 1");
  if ($dup) {
    $dup->bind_param('ssii', $studentId, $courseCode, $year, $sem);
    $dup->execute();
    $dupRes = $dup->get_result();
    $dupRow = $dupRes ? $dupRes->fetch_assoc() : null;
    $exists = !empty($dupRow);
    $dup->close();
    if ($exists) {
      if (in_array('status', $columns, true) && in_array($statusValue, ['enrolled', 'completed', 'failed', 'pending'], true)) {
        $updateDup = $conn->prepare("UPDATE irregular_db SET status = ? WHERE student_id = ? AND course_code = ? AND {$yearCol} = ? AND {$semCol} = ?");
        if ($updateDup) {
          $updateDup->bind_param('sssii', $statusValue, $studentId, $courseCode, $year, $sem);
          $updateDup->execute();
          $updateDup->close();
        }
      }
      return true;
    }
  }

  $lecUnits = 0.0;
  $labUnits = 0.0;
  $totalUnits = 0.0;
  $prereq = '';
  $cur = $conn->prepare("SELECT * FROM curriculum WHERE course_code = ? LIMIT 1");
  if ($cur) {
    $cur->bind_param('s', $courseCode);
    $cur->execute();
    $res = $cur->get_result();
    if ($res && ($row = $res->fetch_assoc())) {
      $courseTitle = trim((string)($row['course_title'] ?? $courseTitle));
      $lecUnits = (float)($row['lec_units'] ?? $row['lecture_units'] ?? 0);
      $labUnits = (float)($row['lab_units'] ?? 0);
      $totalUnits = (float)($row['total_units'] ?? $row['units'] ?? 0);
      if ($totalUnits <= 0) $totalUnits = $lecUnits + $labUnits;
      $prereq = trim((string)($row['prerequisites'] ?? $row['pre_req'] ?? ''));
    }
    $cur->close();
  }

  $program = '';
  if (in_array('program', $columns, true)) {
    $p = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ? LIMIT 1");
    if ($p) {
      $p->bind_param('s', $studentId);
      $p->execute();
      $r = $p->get_result();
      if ($r && ($pr = $r->fetch_assoc())) {
        $program = strtoupper(trim((string)($pr['course'] ?? '')));
      }
      $p->close();
    }
  }

  $insertCols = ['student_id', 'course_code', 'course_title', $yearCol, $semCol];
  $insertVals = [$studentId, $courseCode, $courseTitle, $year, $sem];
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
    $insertVals[] = $statusValue;
    $insertTypes .= 's';
  }
  if (in_array('year_semester', $columns, true)) {
    $insertCols[] = 'year_semester';
    $insertVals[] = $year . '-' . $sem;
    $insertTypes .= 's';
  }

  $sql = "INSERT INTO irregular_db (" . implode(',', $insertCols) . ") VALUES (" . str_repeat('?,', count($insertCols) - 1) . "?)";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return false;
  }
  $stmt->bind_param($insertTypes, ...$insertVals);
  $ok = $stmt->execute();
  if (!$ok) {
    error_log('ensureGradedSubjectInIrregularDb insert failed: ' . $stmt->error);
  }
  $stmt->close();
  return (bool)$ok;
}

function ensurePassedSubjectInIrregularDb(mysqli $conn, string $studentId, string $courseCode, string $courseTitle, int $year, int $sem): bool {
  return ensureGradedSubjectInIrregularDb($conn, $studentId, $courseCode, $courseTitle, $year, $sem, 2.00);
}

/**
 * Compute effective year level based on passed/failed subjects per year:
 * - If all subjects in 1-1 and 1-2 are passed -> at least 2nd year
 * - If any subject in a year (both sems) is failed/missing -> stays in that year and is irregular
 * - Repeats for 2nd, 3rd, 4th year (2-1/2-2, 3-1/3-2, 4-1/4-2)
 */
function computeYearPromotion(array $curriculum, array $gradesByCode): array {
  $effectiveYear = 1;
  $isIrregular = false;
  $yearStatuses = [];

  for ($year = 1; $year <= 4; $year++) {
    $ysKeys = [$year . '-1', $year . '-2'];
    $hasSubjects = false;
    $allPassed = true;

    foreach ($ysKeys as $ys) {
      if (!isset($curriculum[$ys]) || !is_array($curriculum[$ys])) {
        continue;
      }
      foreach ($curriculum[$ys] as $course) {
        $code = $course['code'] ?? '';
        if ($code === '') {
          continue;
        }
        $hasSubjects = true;
        if (!isSubjectPassedForYearPromotion($code, $gradesByCode)) {
          $allPassed = false;
          break 2; // This year is not fully passed; stop checking further
        }
      }
    }

    $yearStatuses[$year] = [
      'has_subjects' => $hasSubjects,
      'all_passed'   => $hasSubjects && $allPassed,
    ];

    if (!$hasSubjects) {
      // Program may not use this year level; skip it
      continue;
    }

    if ($allPassed) {
      // Fully passed this academic year -> promote to next (capped at 4th year)
      $effectiveYear = min(4, $year + 1);
      continue;
    }

    // Has at least one failed/missing subject in this year -> stay on this year and mark irregular
    $effectiveYear = $year;
    $isIrregular = true;
    break;
  }

  return [
    'effective_year' => $effectiveYear,
    'is_irregular'   => $isIrregular,
    'year_statuses'  => $yearStatuses,
  ];
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

        // Enforce 5th-year insertion for irregular students only.
        if ($year_level >= 5) {
          $classStmt = $conn->prepare("SELECT classification FROM signin_db WHERE student_id = ? LIMIT 1");
          $isIrregularStudent = false;
          if ($classStmt) {
            $classStmt->bind_param('s', $student_id);
            $classStmt->execute();
            $classRes = $classStmt->get_result();
            $classRow = $classRes ? $classRes->fetch_assoc() : null;
            $classStmt->close();
            $classificationValue = (string)($classRow['classification'] ?? '');
            $isIrregularStudent = isIrregularClassification($classificationValue);
          }
          if (!$isIrregularStudent) {
            throw new Exception('5th year is available for irregular students only.');
          }
        }

        if (in_array($semester, [1, 2], true) && isSemesterLocked($conn, $semester)) {
          $label = semesterLabel($semester);
          echo json_encode([
            'success' => false,
            'message' => $label . ' is currently locked. Adding irregular subjects for this semester is not allowed.'
          ]);
          exit;
        }
        
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
        
        // Derive total units when curriculum has no explicit total_units value.
        if ($total_units <= 0) {
          $total_units = $lec_units + $lab_units;
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
        $totalCol = in_array('total_units', $columns) ? 'total_units' : (in_array('units', $columns) ? 'units' : 'total_units');
        $insertCols = ['student_id', 'course_code', 'course_title', $totalCol, $yearCol, $semCol];
        $insertVals = [$student_id, $course_code, $course_title, $total_units, $year_level, $semester];
        $insertTypes = 'sssdii';
        
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
        }
        if ($labCol) {
            $insertCols[] = $labCol;
            $insertVals[] = $lab_units;
            $insertTypes .= 'd';
        }
        if ($programCol && !empty($program)) {
            $insertCols[] = $programCol;
            $insertVals[] = strtoupper($program);
            $insertTypes .= 's';
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
        
        // Create and execute INSERT statement
        $sql = "INSERT INTO irregular_db (" . implode(',', $insertCols) . ") VALUES (" . str_repeat('?,', count($insertCols)-1) . "?)";
        error_log("INSERT SQL: $sql");
        error_log("INSERT values: " . json_encode($insertVals));
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($insertTypes, ...$insertVals);
        
        if ($stmt->execute()) {
          syncStudentClassification($conn, $student_id, resolveStudentClassification($conn, $student_id));
            
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

    $semNumber = (int)$sem;
    if (in_array($semNumber, [1, 2], true) && isSemesterLocked($conn, $semNumber)) {
      $label = semesterLabel($semNumber);
      echo json_encode(['ok' => false, 'message' => $label . ' is currently locked. Saving grades for this semester is not allowed.']);
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
      // Auto-sync any subject that already has a grade into irregular_db.
      ensureGradedSubjectInIrregularDb($conn, $student_id, $course_code, $course_title, (int)$year, (int)$sem, $grade);
      echo json_encode(['ok' => true, 'success' => true, 'grade' => $grade]);
    } else {
      echo json_encode(['ok' => false, 'success' => false, 'message' => 'Failed to save grade']);
    }
    exit;
}

// AJAX: return subjects for a different program (used for irregular students cross-enrolling)
if (isset($_GET['action']) && $_GET['action'] === 'get_other_program_subjects') {
  header('Content-Type: application/json');

  // Get the student ID from the request
  $reqStudentId = trim($_GET['student_id'] ?? '');
  if (!$reqStudentId) {
    $reqStudentId = $studentId; // Use the page-level student ID if not in request
  }

  // Get the student's enrolled program from signin_db
  $studentProgram = '';
  if ($reqStudentId) {
    $progStmt = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ? LIMIT 1");
    if ($progStmt) {
      $progStmt->bind_param('s', $reqStudentId);
      $progStmt->execute();
      $progRes = $progStmt->get_result();
      if ($progRes && $progRes->num_rows > 0) {
        $progRow = $progRes->fetch_assoc();
        $studentProgram = strtoupper(trim($progRow['course'] ?? ''));
      }
      $progStmt->close();
    }
  }

  $otherProgram = strtoupper(trim($_GET['program'] ?? ''));
  $year = (int)($_GET['year'] ?? 0);
  $sem  = (int)($_GET['sem'] ?? 0);

  // VALIDATION: Only allow loading courses for the student's enrolled program
  if ($otherProgram === '' || $year <= 0 || $sem <= 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Missing or invalid program / year / semester',
      'data'    => []
    ]);
    exit;
  }

  // SECURITY: Ensure requested program matches student's enrolled program
  if (!$studentProgram || $otherProgram !== $studentProgram) {
    echo json_encode([
      'success' => false,
      'message' => 'Access denied. Can only load courses for your enrolled program (' . htmlspecialchars($studentProgram) . ').',
      'data'    => []
    ]);
    exit;
  }

  try {
    $hasYearSemLocal = columnExists($conn, 'curriculum', 'year_semester');

    $codeColLocal  = columnExists($conn, 'curriculum', 'course_code')   ? 'course_code'   : 'course_code';
    $titleColLocal = columnExists($conn, 'curriculum', 'course_title')  ? 'course_title'  : 'course_title';
    $lecColLocal   = columnExists($conn, 'curriculum', 'lec_units')     ? 'lec_units'     : (columnExists($conn, 'curriculum', 'lecture_units') ? 'lecture_units' : 'lec_units');
    $labColLocal   = columnExists($conn, 'curriculum', 'lab_units')     ? 'lab_units'     : 'lab_units';
    $totalColLocal = columnExists($conn, 'curriculum', 'total_units')   ? 'total_units'   : (columnExists($conn, 'curriculum', 'units') ? 'units' : 'total_units');
    $preColLocal   = columnExists($conn, 'curriculum', 'prerequisites') ? 'prerequisites' : (columnExists($conn, 'curriculum', 'pre_req') ? 'pre_req' : 'prerequisites');

    $sql = "SELECT $codeColLocal AS code, $titleColLocal AS title, $lecColLocal AS lec, $labColLocal AS lab, $totalColLocal AS units, $preColLocal AS prereq";
    if ($hasYearSemLocal) {
      $sql .= ", year_semester AS ys";
    } else {
      $sql .= ", year, semester";
    }
    $sql .= " FROM curriculum WHERE 1=1";

    $params = [];
    $types  = '';

    if (columnExists($conn, 'curriculum', 'program')) {
      $sql    .= " AND program = ?";
      $params[] = $otherProgram;
      $types   .= 's';
    }

    if ($hasYearSemLocal) {
      $ys = $year . '-' . $sem;
      $sql    .= " AND year_semester = ?";
      $params[] = $ys;
      $types   .= 's';
    } else {
      $yearColLocal = columnExists($conn, 'curriculum', 'year')      ? 'year'      : null;
      $semColLocal  = columnExists($conn, 'curriculum', 'semester')  ? 'semester'  : null;
      if ($yearColLocal && $semColLocal) {
        $sql    .= " AND $yearColLocal = ? AND $semColLocal = ?";
        $params[] = $year;
        $params[] = $sem;
        $types   .= 'ii';
      }
    }

    $sql .= $hasYearSemLocal ? " ORDER BY ys, code" : " ORDER BY $codeColLocal";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      echo json_encode([
        'success' => false,
        'message' => 'DB error: ' . $conn->error,
        'data'    => []
      ]);
      exit;
    }

    if ($types !== '') {
      $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
      $rows[] = $r;
    }
    $stmt->close();

    echo json_encode([
      'success' => true,
      'data'    => $rows
    ]);
  } catch (Exception $e) {
    error_log('get_other_program_subjects error: ' . $e->getMessage());
    echo json_encode([
      'success' => false,
      'message' => 'Unexpected error while loading subjects',
      'data'    => []
    ]);
  }
  exit;
}

// AJAX: return irregular subjects for a student and semester
if (isset($_GET['action']) && $_GET['action'] === 'get_irregular') {
  header('Content-Type: application/json');
  $student_id = trim($_GET['student_id'] ?? '');
  $ys = trim($_GET['ys'] ?? ''); // format expected: '1-1'

  // Basic parameter validation
  if ($student_id === '' || $ys === '') {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Missing parameters']);
    exit;
  }

  [$y, $s] = array_map('intval', explode('-', $ys));

  // Determine actual column names in irregular_db (handles year_level/year and semester/sem)
  $colsRes = $conn->query('SHOW COLUMNS FROM irregular_db');
  $columns = [];
  while ($c = $colsRes->fetch_assoc()) {
    $columns[] = $c['Field'];
  }

  $lecCol    = in_array('lec_units', $columns, true) ? 'lec_units' : (in_array('lecture_units', $columns, true) ? 'lecture_units' : null);
  $labCol    = in_array('lab_units', $columns, true) ? 'lab_units' : null;
  $totalCol  = in_array('total_units', $columns, true) ? 'total_units' : (in_array('units', $columns, true) ? 'units' : null);
  $statusCol = in_array('status', $columns, true) ? 'status' : null;

  // Build a safe SELECT list using only existing columns.
  // We intentionally select multiple year/sem variants then normalize in PHP.
  $selectCols = ['id', 'course_code', 'course_title'];
  $selectCols[] = $lecCol ? "$lecCol AS lec_units" : '0 AS lec_units';
  $selectCols[] = $labCol ? "$labCol AS lab_units" : '0 AS lab_units';
  $selectCols[] = $totalCol ? "$totalCol AS total_units" : '0 AS total_units';
  $selectCols[] = in_array('year_level', $columns, true) ? 'year_level AS row_year_level' : 'NULL AS row_year_level';
  $selectCols[] = in_array('year', $columns, true) ? 'year AS row_year' : 'NULL AS row_year';
  $selectCols[] = in_array('semester', $columns, true) ? 'semester AS row_semester' : 'NULL AS row_semester';
  $selectCols[] = in_array('sem', $columns, true) ? 'sem AS row_sem' : 'NULL AS row_sem';
  $selectCols[] = in_array('year_semester', $columns, true) ? 'year_semester AS row_year_semester' : 'NULL AS row_year_semester';
  if ($statusCol) {
    $selectCols[] = "$statusCol AS status";
  }

  $sql = 'SELECT ' . implode(',', $selectCols) . ' FROM irregular_db WHERE student_id = ?';
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'DB error: ' . $conn->error]);
    exit;
  }

  $stmt->bind_param('s', $student_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) {
    $rowYear = null;
    $rowSem = null;

    if (isset($r['row_year_level']) && $r['row_year_level'] !== null && $r['row_year_level'] !== '') {
      $rowYear = parseYearLevelNumber((string)$r['row_year_level']);
    }
    if ($rowYear === null && isset($r['row_year']) && $r['row_year'] !== null && $r['row_year'] !== '') {
      $rowYear = parseYearLevelNumber((string)$r['row_year']);
    }

    if (isset($r['row_semester']) && $r['row_semester'] !== null && $r['row_semester'] !== '') {
      $rowSem = parseSemesterNumber((string)$r['row_semester']);
    }
    if ($rowSem === null && isset($r['row_sem']) && $r['row_sem'] !== null && $r['row_sem'] !== '') {
      $rowSem = parseSemesterNumber((string)$r['row_sem']);
    }

    if (($rowYear === null || $rowSem === null) && !empty($r['row_year_semester'])) {
      $normYs = normalizeSemesterKey((string)$r['row_year_semester']);
      if (preg_match('/^([1-5])-([1-2])$/', $normYs, $m)) {
        if ($rowYear === null) {
          $rowYear = (int)$m[1];
        }
        if ($rowSem === null) {
          $rowSem = (int)$m[2];
        }
      }
    }

    if ($rowYear !== null && $rowSem !== null && isDeletedAutoloadBlocked($student_id, (string)($r['course_code'] ?? ''), (int)$rowYear, (int)$rowSem)) {
      continue;
    }

    if ($rowYear === $y && $rowSem === $s) {
      $rows[] = [
        'id' => $r['id'],
        'course_code' => $r['course_code'],
        'course_title' => $r['course_title'],
        'lec_units' => $r['lec_units'] ?? 0,
        'lab_units' => $r['lab_units'] ?? 0,
        'total_units' => $r['total_units'] ?? 0,
        'status' => $r['status'] ?? null,
      ];
    }
  }

  echo json_encode(['success' => true, 'data' => $rows]);
  exit;
}

// Handle delete irregular subject
if ((isset($_POST['action']) && $_POST['action'] === 'delete_irregular') || 
    (isset($_GET['action']) && $_GET['action'] === 'delete_irregular')) {
    
    // Clear any output buffers
    while (ob_get_level()) ob_end_clean();
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Get ID from either POST or GET
    $id = $_POST['id'] ?? $_GET['id'] ?? '';
    
    // Validate ID
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Error: Subject ID is missing.', 'debug' => ['method' => $_SERVER['REQUEST_METHOD'], 'post_data' => $_POST, 'get_data' => $_GET]]);
        exit;
    }
    
    // Convert ID to integer
    $id = intval($id);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Error: Invalid subject ID.', 'debug' => ['id_received' => $id]]);
        exit;
    }

    // Before deletion, check the semester of this irregular subject and enforce locks
    $subjectSem = null;
    $subjectStudentId = '';
    $subjectCourseCode = '';
    $subjectYear = null;
    try {
      $colsRes = $conn->query('SHOW COLUMNS FROM irregular_db');
      if ($colsRes) {
        $columns = [];
        while ($c = $colsRes->fetch_assoc()) {
          $columns[] = $c['Field'];
        }
        $yearCol = in_array('year_level', $columns, true) ? 'year_level' : (in_array('year', $columns, true) ? 'year' : null);
        $semCol = in_array('semester', $columns, true) ? 'semester' : (in_array('sem', $columns, true) ? 'sem' : null);
        if ($semCol) {
          $selectParts = ['student_id', 'course_code', "{$semCol} AS sem_val"];
          if ($yearCol) {
            $selectParts[] = "{$yearCol} AS year_val";
          }
          $semRes = $conn->query("SELECT " . implode(', ', $selectParts) . " FROM irregular_db WHERE id = {$id} LIMIT 1");
          if ($semRes && $semRow = $semRes->fetch_assoc()) {
            $subjectSem = (int)$semRow['sem_val'];
            $subjectStudentId = trim((string)($semRow['student_id'] ?? ''));
            $subjectCourseCode = trim((string)($semRow['course_code'] ?? ''));
            if (isset($semRow['year_val'])) {
              $subjectYear = (int)$semRow['year_val'];
            }
          }
        }
      }
    } catch (Throwable $e) {
      error_log('Error checking semester for irregular delete: ' . $e->getMessage());
    }

    if ($subjectSem !== null && in_array($subjectSem, [1, 2], true) && isSemesterLocked($conn, $subjectSem)) {
      $label = semesterLabel($subjectSem);
      echo json_encode(['success' => false, 'message' => $label . ' is currently locked. Deleting irregular subjects for this semester is not allowed.']);
      exit;
    }

    // Perform actual deletion from irregular_db table
    $result = $conn->query("DELETE FROM irregular_db WHERE id = $id");
    
    if ($result) {
        if ($conn->affected_rows > 0) {
        if ($subjectStudentId !== '' && $subjectCourseCode !== '' && $subjectYear !== null && $subjectSem !== null) {
          markDeletedAutoloadBlock($subjectStudentId, $subjectCourseCode, (int)$subjectYear, (int)$subjectSem);
        }
            echo json_encode(['success' => true, 'message' => 'Subject deleted successfully.', 'debug' => ['id_deleted' => $id, 'affected_rows' => $conn->affected_rows]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No subject found with the provided ID.', 'debug' => ['id_attempted' => $id]]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database delete failed: ' . $conn->error, 'debug' => ['id_attempted' => $id]]);
    }
    exit;
}

// Handle Bulk Save to Irregular DB
// Be tolerant of content-type variations like "application/json; charset=UTF-8"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
  if (stripos($contentType, 'application/json') !== 0) {
    // Not a JSON bulk-save request; let normal form POSTs continue below
        
  } else {
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

            // Enforce 5th-year insertion for irregular students only.
            $classificationValue = '';
            $isIrregularStudent = false;
            $classStmt = $conn->prepare("SELECT classification FROM signin_db WHERE student_id = ? LIMIT 1");
            if ($classStmt) {
              $classStmt->bind_param('s', $student_id);
              $classStmt->execute();
              $classRes = $classStmt->get_result();
              $classRow = $classRes ? $classRes->fetch_assoc() : null;
              $classStmt->close();
              $classificationValue = (string)($classRow['classification'] ?? '');
              $isIrregularStudent = isIrregularClassification($classificationValue);
            }
            if (!$isIrregularStudent) {
              foreach ($subjects as $subjectCheck) {
                $yearLevelCheck = intval($subjectCheck['year_level'] ?? 0);
                if ($yearLevelCheck >= 5) {
                  throw new Exception('5th year is available for irregular students only.');
                }
              }
            }

            // Check semestral lock based on the first subject's semester
            $firstSubject = $subjects[0] ?? null;
            if ($firstSubject !== null) {
              $semNumber = intval($firstSubject['semester'] ?? 0);
              if (in_array($semNumber, [1, 2], true) && isSemesterLocked($conn, $semNumber)) {
                $label = semesterLabel($semNumber);
                echo json_encode([
                  'success' => false,
                  'message' => $label . ' is currently locked. Bulk saving irregular subjects for this semester is not allowed.'
                ]);
                exit;
              }
            }
            
          // Determine which program label to store with these irregular subjects.
          // Prefer an explicit program from the request; otherwise fall back to a safe default.
          $effectiveProgram = '';
          if (!empty($data['program'])) {
            $effectiveProgram = normalizeDasProgram((string)$data['program']);
          }
          if ($effectiveProgram === '') {
            $effectiveProgram = 'BSCS'; // Fallback when not specified
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

            // Check for existing subjects to avoid duplicates (using detected year/sem columns)
            $existingSubjects = [];
            $checkStmt = $conn->prepare("SELECT course_code, {$yearCol} AS yv, {$semCol} AS sv FROM irregular_db WHERE student_id = ?");
            if ($checkStmt) {
              $checkStmt->bind_param('s', $student_id);
              $checkStmt->execute();
              $checkResult = $checkStmt->get_result();
              while ($row = $checkResult->fetch_assoc()) {
                $key = $row['course_code'] . '_' . (int)$row['yv'] . '_' . (int)$row['sv'];
                $existingSubjects[$key] = true;
              }
              $checkStmt->close();
            }
            
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

                  // Some curriculum rows may not have explicit total_units; derive from lec/lab.
                  if ($total_units <= 0) {
                    $total_units = $lec_units + $lab_units;
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
                $insertTypes = 'sssdii';
                
                // Debug: Log the units being saved
                error_log("Saving units for $course_code - Total: $total_units, Lec: $lec_units, Lab: $lab_units");
                
                if ($programCol && !empty($effectiveProgram)) {
                  $insertCols[] = $programCol;
                  $insertVals[] = $effectiveProgram; // Already uppercased above
                  $insertTypes .= 's';
                  error_log("Adding program column: $programCol with value: " . $effectiveProgram);
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
                    error_log('Bulk irregular insert failed for ' . $course_code . ': ' . $stmt->error);
                        $errorCount++;
                    }
                    $stmt->close();
                } else {
                  error_log('Bulk irregular prepare failed for ' . $course_code . ': ' . $conn->error);
                    $errorCount++;
                }
            }
            
            if ($successCount > 0) {
              syncStudentClassification($conn, $student_id, resolveStudentClassification($conn, $student_id));
                
                // Also update year_semester in irregular_db for all newly added subjects
                $currentYear = $GLOBALS['currentYear'] ?? date('Y');
                $currentSem = $GLOBALS['currentSem'] ?? 1;
                
                // Update each newly added subject with correct year_semester format
                if (in_array('year_semester', $columns, true)) {
                  foreach ($subjects as $subject) {
                    $yearLevel = intval($subject['year_level'] ?? 1);
                    $semester = intval($subject['semester'] ?? 1);
                    $yearSem = $yearLevel . '-' . $semester; // Format: Y-S (1-1, 1-2, 2-1, etc.)

                    $updateIrregularSql = "UPDATE irregular_db SET year_semester = ? WHERE student_id = ? AND course_code = ? AND {$yearCol} = ? AND {$semCol} = ?";
                    $updateIrregularStmt = $conn->prepare($updateIrregularSql);
                    if ($updateIrregularStmt) {
                      $updateIrregularStmt->bind_param('sssii', $yearSem, $student_id, $subject['course_code'], $yearLevel, $semester);
                      $updateIrregularStmt->execute();
                      $updateIrregularStmt->close();
                    }
                  }
                }
            }

              $responseSuccess = $successCount > 0;
              $responseMessage = "Successfully added $successCount subjects. " .
                         ($duplicateCount > 0 ? "$duplicateCount duplicates skipped. " : "") .
                         ($errorCount > 0 ? "$errorCount errors occurred." : "");
              if (!$responseSuccess) {
                $responseMessage = "No subjects were inserted. " . $responseMessage;
              }

              echo json_encode([
                'success' => $responseSuccess,
                'message' => trim($responseMessage)
              ]);
            
        } catch (Exception $e) {
            error_log("Bulk save error: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage(),
                'debug_info' => [
                    'student_id' => $student_id ?? 'missing',
                    'subjects_count' => count($subjects ?? []),
                'program' => $effectiveProgram ?? 'missing'
                ]
            ]);
        }
        exit;
      }
      }
    }

$studentId = trim($_GET['student_id'] ?? $_POST['student_id'] ?? '');
$program = trim($_GET['program'] ?? $_POST['program'] ?? '');
// Preserve any program chosen in the form (e.g. cross-program evaluation)
$formProgram = strtoupper(trim($program));
$fiscalYear = trim($_GET['fiscal_year'] ?? $_POST['fiscal_year'] ?? '');

// Classification policy based on total failed units:
// - >= 6.00 units: Probationary
// - >= 6.25 units: Dismissal
// If neither threshold is met, fall back to irregular vs regular.
if ($studentId !== '') {
  try {
    $resolvedClassification = resolveStudentClassification($conn, $studentId);
    syncStudentClassification($conn, $studentId, $resolvedClassification);
  } catch (Throwable $e) {
    error_log('Failed-unit classification update failed in stueval.php: ' . $e->getMessage());
  }
}

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
          'year_level' => 'year_level',
          'academic_year' => 'academic_year',
          'semester' => 'semester',
          'sem' => 'sem'
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

// Determine student classification (if available)
$studentClassification = strtolower(trim($student['classification'] ?? ''));

// Normalize program name
// If the student is irregular and a valid program was selected in the form,
// prefer that selection for the curriculum program.
$program = '';
if (strpos($studentClassification, 'irregular') !== false && in_array(normalizeDasProgram($formProgram), ['BSCS', 'BSIT'], true)) {
  $program = normalizeDasProgram($formProgram);
}

// If we still don't have a program, try to get it from different possible
// field names on the student record.
if ($program === '') {
  $possibleProgramFields = ['programs', 'program', 'course', 'program_name'];
  foreach ($possibleProgramFields as $field) {
    if (!empty($student[$field])) {
      $program = trim($student[$field]);
      break;
    }
  }
}

function loadSemesterLockStates(mysqli $conn): array {
  $locks = [
    1 => false,
    2 => false,
  ];

  try {
    $tblRes = $conn->query("SHOW TABLES LIKE 'semester_locks'");
    if (!$tblRes || $tblRes->num_rows === 0) {
      return $locks;
    }

    $res = $conn->query("SELECT semester, is_locked FROM semester_locks WHERE semester IN (1, 2)");
    if ($res) {
      while ($row = $res->fetch_assoc()) {
        $semester = (int)($row['semester'] ?? 0);
        if ($semester === 1 || $semester === 2) {
          $locks[$semester] = (int)($row['is_locked'] ?? 0) === 1;
        }
      }
    }
  } catch (Throwable $e) {
    error_log('Semester lock state load failed in stueval.php: ' . $e->getMessage());
  }

  return $locks;
}

// If still no program, check assign_curriculum table
if (empty($program) && !empty($studentId)) {
    try {
        // First, check if assign_curriculum table exists and get its column structure
        $tableCheck = $conn->query("SHOW TABLES LIKE 'assign_curriculum'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Get column names from assign_curriculum table
            $columnsStmt = $conn->query("SHOW COLUMNS FROM assign_curriculum");
            $assignColumns = [];
            while($col = $columnsStmt->fetch_assoc()) {
                $assignColumns[] = $col['Field'];
            }
            
            // Determine the student column name (could be student_id, user_id, etc.)
            $studentColumn = null;
            $possibleStudentColumns = ['student_id', 'user_id', 'student', 'user'];
            foreach ($possibleStudentColumns as $col) {
                if (in_array($col, $assignColumns)) {
                    $studentColumn = $col;
                    break;
                }
            }
            
            // Check if program_id column exists
            $hasProgramId = in_array('program_id', $assignColumns);
            
            if ($studentColumn && $hasProgramId) {
                // Check if student has been assigned a curriculum in assign_curriculum table
                $assignStmt = $conn->prepare("SELECT program_id FROM assign_curriculum WHERE $studentColumn = ? LIMIT 1");
                if ($assignStmt) {
                    $assignStmt->bind_param('s', $studentId);
                    $assignStmt->execute();
                    $assignResult = $assignStmt->get_result();
                    if ($assignRow = $assignResult->fetch_assoc()) {
                        // Student has program_id in assign_curriculum, get the program name
                        $programId = $assignRow['program_id'];
                        $progStmt = $conn->prepare("SELECT program_name FROM programs WHERE id = ? LIMIT 1");
                        if ($progStmt) {
                            $progStmt->bind_param('i', $programId);
                            $progStmt->execute();
                            $progResult = $progStmt->get_result();
                            if ($progRow = $progResult->fetch_assoc()) {
                                $program = $progRow['program_name'];
                                error_log("Found program from assign_curriculum: $program for student $studentId");
                            }
                            $progStmt->close();
                        }
                    } else {
                        // No assignment found in assign_curriculum table
                        error_log("No curriculum assignment found for student $studentId in assign_curriculum table");
                    }
                    $assignStmt->close();
                }
            } else {
                error_log("assign_curriculum table missing required columns. Found: " . implode(', ', $assignColumns));
            }
        } else {
            error_log("assign_curriculum table does not exist");
        }
    } catch (Exception $e) {
        error_log("Error checking assign_curriculum: " . $e->getMessage());
        // Continue with fallback logic
    }
}

// If still no program, get it from the signin_db table as fallback
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
$program = normalizeDasProgram($program);
if ($program !== 'BSCS' && $program !== 'BSIT') {
    // If we still can't determine the program, show an error but don't default
    error_log("Warning: Could not determine program for student $studentId. Program value: " . ($program ?: 'empty'));
    $program = ''; // Will trigger the program selection form
}

// Debug: Log the detected program
error_log("Final program detected for student $studentId: '$program'");

// Determine default fiscal year
if ($fiscalYear === '') {
  // 1) Try to get fiscal year from assign_curriculum for this program
  try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'assign_curriculum'");
    if ($tableCheck && $tableCheck->num_rows > 0 && !empty($program)) {
      // Prefer most recent assignment (by created_at / program_id) for this program
      $fySql = "SELECT fiscal_year FROM assign_curriculum 
            WHERE program = ? AND fiscal_year IS NOT NULL AND fiscal_year != ''
            ORDER BY created_at DESC, program_id DESC LIMIT 1";
      $fyStmt = $conn->prepare($fySql);
      if ($fyStmt) {
        $fyStmt->bind_param('s', $program);
        $fyStmt->execute();
        $fyRes = $fyStmt->get_result();
        if ($fyRow = $fyRes->fetch_assoc()) {
          $assignedFy = trim((string)($fyRow['fiscal_year'] ?? ''));
          if ($assignedFy !== '') {
            $fiscalYear = $assignedFy;
            error_log("Using fiscal year from assign_curriculum for program $program: '$fiscalYear'");
          }
        }
        $fyStmt->close();
      }
    }
  } catch (Exception $e) {
    error_log("Error getting fiscal year from assign_curriculum: " . $e->getMessage());
  }

  // 2) If still empty, fall back to latest fiscal year from curriculum table
  if ($fiscalYear === '') {
    $fy = latestFiscalYear($conn, $program);
    if ($fy) $fiscalYear = $fy;
  }
}

// Load available fiscal years from the actual curriculum rows first.
// This prevents showing stale/ghost years passed via URL/query params.
$availableFiscalYears = [];
try {
  if (columnExists($conn, 'curriculum', 'fiscal_year')) {
    $fySql = "SELECT DISTINCT fiscal_year FROM curriculum WHERE fiscal_year IS NOT NULL AND fiscal_year != ''";
    $fyTypes = '';
    $fyParams = [];

    if ($program !== '' && columnExists($conn, 'curriculum', 'program')) {
      $fySql .= " AND program = ?";
      $fyTypes .= 's';
      $fyParams[] = $program;
    }

    $fySql .= " ORDER BY fiscal_year DESC";
    $fyStmt = $conn->prepare($fySql);
    if ($fyStmt) {
      if ($fyTypes !== '') {
        $fyStmt->bind_param($fyTypes, ...$fyParams);
      }
      $fyStmt->execute();
      $fyRes = $fyStmt->get_result();
      while ($fyRes && ($fyRow = $fyRes->fetch_assoc())) {
        $fyLabel = trim((string)($fyRow['fiscal_year'] ?? ''));
        if ($fyLabel !== '') {
          $availableFiscalYears[] = $fyLabel;
        }
      }
      $fyStmt->close();
    }
  }

  // Fallback to fiscal_years table only when curriculum-based list is empty.
  if (empty($availableFiscalYears)) {
    $fyTableCheck = $conn->query("SHOW TABLES LIKE 'fiscal_years'");
    if ($fyTableCheck instanceof mysqli_result && $fyTableCheck->num_rows > 0) {
      $fyRes = $conn->query("SELECT label FROM fiscal_years ORDER BY start_date DESC, id DESC");
      if ($fyRes instanceof mysqli_result) {
        while ($fyRow = $fyRes->fetch_assoc()) {
          $fyLabel = trim((string)($fyRow['label'] ?? ''));
          if ($fyLabel !== '') {
            $availableFiscalYears[] = $fyLabel;
          }
        }
      }
    }
  }
} catch (Exception $e) {
  // If lookup fails, dropdown stays with the placeholder option only.
}

// Never keep an unknown fiscal year selected.
if ($fiscalYear !== '' && !in_array($fiscalYear, $availableFiscalYears, true)) {
  error_log("Ignoring unknown fiscal_year '$fiscalYear' for student $studentId / program $program");
  $fiscalYear = '';
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
  $rowCount = 0;
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ys = $hasYearSem ? normalizeSemesterKey($row['ys']) : (string)($row['year'] . '-' . (int)$row['semester']);
        if (!isset($curriculum[$ys])) $curriculum[$ys] = [];
        $curriculum[$ys][] = $row;
        $rowCount++;
    }
    $stmt->close();

  // Fallback: if no curriculum rows were found with fiscal_year, retry using only program.
  // This prevents an empty prospectus when student.fiscal_year does not exist in curriculum.
  if ($rowCount === 0 && $program !== '' && $fiscalYear !== '' && columnExists($conn, 'curriculum', 'fiscal_year')) {
    $fallbackCurriculum = [];
    $fallbackSql = "SELECT $codeCol AS code, $titleCol AS title, $lecCol AS lec, $labCol AS lab, $totalCol AS units, $preCol AS prereq";
    if ($hasYearSem) {
      $fallbackSql .= ", year_semester AS ys";
    } else {
      $fallbackSql .= ", year, semester";
    }
    $fallbackSql .= " FROM curriculum WHERE 1=1";

    $fallbackParams = [];
    $fallbackTypes = '';
    if (columnExists($conn, 'curriculum', 'program')) {
      $fallbackSql .= " AND program = ?";
      $fallbackParams[] = $program;
      $fallbackTypes .= 's';
    }
    $fallbackSql .= $hasYearSem ? " ORDER BY ys, code" : " ORDER BY year, semester, code";

    $fallbackStmt = $conn->prepare($fallbackSql);
    if ($fallbackStmt) {
      if ($fallbackTypes !== '') {
        $fallbackStmt->bind_param($fallbackTypes, ...$fallbackParams);
      }
      $fallbackStmt->execute();
      $fallbackRes = $fallbackStmt->get_result();
      $fallbackCount = 0;

      while ($fallbackRow = $fallbackRes->fetch_assoc()) {
        $ys = $hasYearSem
          ? normalizeSemesterKey($fallbackRow['ys'])
          : (string)($fallbackRow['year'] . '-' . (int)$fallbackRow['semester']);

        if (!isset($fallbackCurriculum[$ys])) {
          $fallbackCurriculum[$ys] = [];
        }

        $fallbackCurriculum[$ys][] = $fallbackRow;
        $fallbackCount++;
      }
      $fallbackStmt->close();

      if ($fallbackCount > 0) {
        $curriculum = $fallbackCurriculum;
        $rowCount = $fallbackCount;
        error_log("Fallback curriculum load succeeded for student '$studentId': program='$program', ignored fiscal_year='$fiscalYear', rows=$fallbackCount");
      }
    }
  }
    
    // Debug: Show what semesters were loaded
    error_log("Curriculum loaded for program '$program': " . json_encode(array_keys($curriculum)));
    foreach ($curriculum as $ys => $courses) {
        error_log("Semester $ys: " . count($courses) . " courses");
    }
    
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
// Track passed subjects (by normalized code) for client-side prerequisite checks
$passedSubjects = [];
// Track whether the student has at least one passed subject per semester (Y-S)
$passedBySemester = [];
// Track whether the student has any subjects in 1st Year • 1st Semester
$hasFirstYearFirstSemGrade = false;
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

            // Determine if this grade is passing for prerequisite purposes
            $isPassed = false;
            if (is_numeric($gradeValue)) {
              $gv = (float)$gradeValue;
              if ($gv <= 3.25) {
                $isPassed = true;
              }
            } elseif (is_string($gradeValue) && strtoupper(trim($gradeValue)) === 'PASSED') {
              $isPassed = true;
            }

            if ($isPassed) {
              // Store only by normalized code; JS will use this for prerequisite checks
              $passedSubjects[$norm] = true;
            }
            
            // Store the grade with multiple key formats for flexible matching
            $gradesByCode[$norm] = $gradeValue;  // Normalized code (e.g., 'CCS-0001')
            $gradesByCode[$code] = $gradeValue;  // Original code as in DB
            
            // Store with year and semester for better matching
            $year = intval($g['year'] ?? 0);
            $sem = intval($g['sem'] ?? 0);

            // Keep irregular_db aligned for already-evaluated subjects.
            // This ensures grades that existed before opening this page are backfilled.
            if ($year > 0 && $sem > 0) {
              $syncCourseTitle = trim((string)($g['course_title'] ?? ''));
              if ($syncCourseTitle === '') {
                $syncCourseTitle = $code;
              }
              if (!isDeletedAutoloadBlocked($studentId, $code, $year, $sem)) {
                ensureGradedSubjectInIrregularDb($conn, $studentId, $code, $syncCourseTitle, $year, $sem, $gradeValue);
              }
            }
            
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

                  // Track at least one passed subject per semester (e.g., '1-1')
                  if ($isPassed) {
                    $ysKey = $year . '-' . $sem;
                    $passedBySemester[$ysKey] = true;
                  }

                  // Remember if the student has any 1st Year • 1st Semester subjects
                  if ($year === 1 && $sem === 1) {
                    $hasFirstYearFirstSemGrade = true;
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

        // If still no 1st Year • 1st Semester record found in grades_db,

        // also check irregular_db so enrollment there can unlock higher-year
        // subjects even without a grade yet.
        if (!$hasFirstYearFirstSemGrade) {
          try {
            $colsRes = $conn->query("SHOW COLUMNS FROM irregular_db");
            if ($colsRes) {
              $columns = [];
              while ($c = $colsRes->fetch_assoc()) {
                $columns[] = $c['Field'];
              }
              $yearCol = in_array('year_level', $columns) ? 'year_level' : (in_array('year', $columns) ? 'year' : null);
              $semCol = in_array('semester', $columns) ? 'semester' : (in_array('sem', $columns) ? 'sem' : null);

              if ($yearCol && $semCol) {
                $sql = "SELECT 1 FROM irregular_db WHERE student_id = ? AND {$yearCol} = 1 AND {$semCol} = 1 LIMIT 1";
                $istmt = $conn->prepare($sql);
                if ($istmt) {
                  $istmt->bind_param('s', $studentId);
                  $istmt->execute();
                  $ires = $istmt->get_result();
                  if ($ires && $ires->num_rows > 0) {
                    $hasFirstYearFirstSemGrade = true;
                  }
                  $istmt->close();
                }
              }
            }
          } catch (Exception $e) {
            error_log('Error checking irregular_db for 1-1 subjects: ' . $e->getMessage());
          }
        }
}

      // Global rule for Capstone Project 1 eligibility:
      // Student must have PASSED all subjects ("major" subjects) that themselves
      // have a prerequisite, but only for semesters BEFORE Capstone Project 1.
      // Subjects whose own prereq is NONE/N/A/- are treated as minor and may be
      // failed without blocking Capstone 1.
      $allMajorWithPrereqPassed = true;
      if (!empty($curriculum) && $studentId !== '') {
        $capstoneYs = [];

        // 1) Find the semester key(s) (ys like "3-2") where Capstone Project 1 exists.
        foreach ($curriculum as $ysKey => $semesterCourses) {
          foreach ($semesterCourses as $course) {
            $courseTitle = $course['title'] ?? '';
            if (!empty($courseTitle) && stripos($courseTitle, 'capstone project 1') !== false) {
              $capstoneYs[] = $ysKey;
            }
          }
        }

        if (!empty($capstoneYs)) {
          // Use the earliest Capstone 1 semester (e.g., 3-2) as the cutoff.
          sort($capstoneYs);
          $capYs = $capstoneYs[0];
          [$capYear, $capSem] = array_map('intval', explode('-', $capYs));

          // 2) For all curriculum subjects BEFORE Capstone 1's semester, enforce
          // that any subject which itself has a prereq must be passed.
          foreach ($curriculum as $ysKey => $semesterCourses) {
            // Parse current semester key like "1-1", "2-2".
            [$curYear, $curSem] = array_map('intval', explode('-', $ysKey));

            // Only enforce rule for semesters strictly before Capstone 1.
            if ($curYear > $capYear || ($curYear === $capYear && $curSem >= $capSem)) {
              continue;
            }

            foreach ($semesterCourses as $course) {
              $courseTitle = $course['title'] ?? '';
              $courseCode  = $course['code']  ?? '';
              $coursePrereqRaw = strtolower(trim($course['prereq'] ?? ''));

              // Skip subjects whose own prerequisite is effectively "none";
              // these are treated as minor and may be failed without blocking Capstone.
              if ($coursePrereqRaw === '' || in_array($coursePrereqRaw, ['none','n/a','-','none '], true)) {
                continue;
              }

              // Skip Capstone Project 1 itself when computing the global rule.
              if (!empty($courseTitle) && stripos($courseTitle, 'capstone project 1') !== false) {
                continue;
              }

              $normCourseCode = normalizeCourseCode($courseCode);
              if ($normCourseCode === '') {
                continue;
              }

              // Look up the student's grade for this subject.
              $gradeValue = $gradesByCode[$normCourseCode] ?? null;

              // No grade at all for a major subject with a prerequisite → blocks Capstone 1.
              if ($gradeValue === null || $gradeValue === '') {
                $allMajorWithPrereqPassed = false;
                error_log("Capstone rule: missing grade for major subject $courseCode (prereq=$coursePrereqRaw) before Capstone 1 (ys=$capYs) → blocking Capstone 1");
                break 2;
              }

              // Normalize and check if the grade is passing.
              if (is_numeric($gradeValue)) {
                $gv = (float)$gradeValue;
                // In this system, numeric grades <= 3.25 are treated as passed.
                if ($gv > 3.25) {
                  $allMajorWithPrereqPassed = false;
                  error_log("Capstone rule: failing/INC grade $gv for major subject $courseCode before Capstone 1 (ys=$capYs) → blocking Capstone 1");
                  break 2;
                }
              } elseif (is_string($gradeValue)) {
                $gUpper = strtoupper(trim($gradeValue));
                // Text grades like INC / FAILED also block Capstone 1.
                if (in_array($gUpper, ['INC','FAILED','FAIL'], true)) {
                  $allMajorWithPrereqPassed = false;
                  error_log("Capstone rule: non-passing text grade '$gUpper' for major subject $courseCode before Capstone 1 (ys=$capYs) → blocking Capstone 1");
                  break 2;
                }
              }
            }
          }
        }
      }

$ysOrder = ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];

// Dynamically compute the maximum units per semester from the curriculum table
// instead of using hardcoded values. This sums all subject units in each
// semester, so the denominator (e.g. 24.0 / 26) always matches the actual
// curriculum in the database.
$maxUnitsPerSemester = [];
foreach ($ysOrder as $ys) {
  $total = 0.0;
  if (isset($curriculum[$ys])) {
    foreach ($curriculum[$ys] as $course) {
      // Prefer explicit total units; fall back to lec+lab if needed
      $units = isset($course['units']) && $course['units'] !== ''
        ? (float)$course['units']
        : ((float)($course['lec'] ?? 0) + (float)($course['lab'] ?? 0));
      $total += $units;
    }
  }
  $maxUnitsPerSemester[$ys] = $total;
}

// Build a unit limit configuration per program and semester
// Used by the frontend to prevent exceeding the curriculum load.
$unitLimits = [
  'BSCS' => [],
  'BSIT' => [],
];

foreach ($ysOrder as $ys) {
  $limit = $maxUnitsPerSemester[$ys] ?? 26; // Fallback to 26 if not found
  $unitLimits['BSCS'][$ys] = ['max' => $limit];
  $unitLimits['BSIT'][$ys] = ['max' => $limit];
}

// Compute effective academic year based on passed subjects per year/semester
$yearPromotion = computeYearPromotion($curriculum, $gradesByCode);
$effectiveYearLevel = (int)($yearPromotion['effective_year'] ?? 1);
$promotionIsIrregular = !empty($yearPromotion['is_irregular']);

// Human-readable label for display
$yearLevelLabels = [
  1 => '1st Year',
  2 => '2nd Year',
  3 => '3rd Year',
  4 => '4th Year',
  5 => '5th Year',
];
$effectiveYearLabel = $yearLevelLabels[$effectiveYearLevel] ?? ($effectiveYearLevel . 'th Year');

// Connect displayed Year Level to student academic_year (if present) for clearer UX.
$studentAcademicYearRaw = (string)($student['academic_year'] ?? $student['year_level'] ?? '');
$academicYearLevel = parseYearLevelNumber($studentAcademicYearRaw);

// Resolve visible year level:
// - keep the highest known standing between stored academic year and computed progress
// - this avoids dropping to 1st year just because of back subjects
if ($academicYearLevel !== null) {
  $displayYearLevel = max($academicYearLevel, $effectiveYearLevel);
} else {
  $displayYearLevel = $effectiveYearLevel;
}

$displayYearLevelLabel = $yearLevelLabels[$displayYearLevel] ?? ($displayYearLevel . 'th Year');

$studentSemesterRaw = (string)($student['semester'] ?? $student['sem'] ?? '');
$displaySemesterNumber = parseSemesterNumber($studentSemesterRaw);
$displayYearLevelText = $displayYearLevelLabel;
if ($displaySemesterNumber !== null) {
  $displayYearLevelText .= ' • ' . semesterLabel($displaySemesterNumber);
}

// Keep signin_db.academic_year in sync with the resolved year level shown in UI.
if (!empty($studentId) && columnExists($conn, 'signin_db', 'academic_year')) {
  $dbAcademicYearTarget = $displayYearLevelLabel;
  $dbAcademicYearCurrent = trim((string)($student['academic_year'] ?? ''));

  if ($dbAcademicYearCurrent !== $dbAcademicYearTarget) {
    $syncAcademicYearStmt = $conn->prepare("UPDATE signin_db SET academic_year = ? WHERE student_id = ?");
    if ($syncAcademicYearStmt) {
      $syncAcademicYearStmt->bind_param('ss', $dbAcademicYearTarget, $studentId);
      $syncAcademicYearStmt->execute();
      $syncAcademicYearStmt->close();

      // Reflect sync immediately in current request context.
      $student['academic_year'] = $dbAcademicYearTarget;
    }
  }
}

// Keep students_db academic year/year level aligned too, when available.
if (!empty($studentId)) {
  $studentsDbYearCol = null;
  if (columnExists($conn, 'students_db', 'academic_year')) {
    $studentsDbYearCol = 'academic_year';
  } elseif (columnExists($conn, 'students_db', 'year_level')) {
    $studentsDbYearCol = 'year_level';
  }

  if ($studentsDbYearCol !== null) {
    $dbAcademicYearTarget = $displayYearLevelLabel;
    $studentsDbCurrentStmt = $conn->prepare("SELECT {$studentsDbYearCol} AS yr FROM students_db WHERE student_id = ? LIMIT 1");
    if ($studentsDbCurrentStmt) {
      $studentsDbCurrentStmt->bind_param('s', $studentId);
      $studentsDbCurrentStmt->execute();
      $studentsDbCurrentRes = $studentsDbCurrentStmt->get_result();
      $studentsDbCurrentRow = $studentsDbCurrentRes ? $studentsDbCurrentRes->fetch_assoc() : null;
      $studentsDbCurrentStmt->close();

      $studentsDbCurrent = trim((string)($studentsDbCurrentRow['yr'] ?? ''));
      if ($studentsDbCurrent !== $dbAcademicYearTarget) {
        $studentsDbSyncStmt = $conn->prepare("UPDATE students_db SET {$studentsDbYearCol} = ? WHERE student_id = ?");
        if ($studentsDbSyncStmt) {
          $studentsDbSyncStmt->bind_param('ss', $dbAcademicYearTarget, $studentId);
          $studentsDbSyncStmt->execute();
          $studentsDbSyncStmt->close();
        }
      }
    }
  }
}

// Special rule: for IRREGULAR and PROBATIONARY students in 4-1 and 4-2,
// allow an additional 6 units above the base limit.
$studentClassification = strtolower(trim($student['classification'] ?? ''));
$isIrregularForUi = isIrregularClassification((string)($student['classification'] ?? ''));
if (canOverloadSubjects((string)($student['classification'] ?? '')) && !empty($program)) {
  foreach (['4-1', '4-2'] as $ys) {
    if (isset($unitLimits[$program][$ys])) {
      $unitLimits[$program][$ys]['max'] += 6.0;
    }
  }
}

// Build a list of available programs (for cross-program irregular evaluation)
$availablePrograms = [];
try {
  $progTable = $conn->query("SHOW TABLES LIKE 'programs'");
  if ($progTable && $progTable->num_rows > 0) {
    $progRes = $conn->query("SELECT program_name FROM programs ORDER BY program_name");
    if ($progRes instanceof mysqli_result) {
      while ($pRow = $progRes->fetch_assoc()) {
        if (!empty($pRow['program_name'])) {
          $availablePrograms[] = $pRow['program_name'];
        }
      }
    }
  }
} catch (Exception $e) {
  error_log('Error loading programs list in stueval.php: ' . $e->getMessage());
}

// Enrollment period / evaluation window for this page
$evaluationOpen = true;
$enrollmentPeriodMessage = '';

$deletedAutoloadBlockKeysForUi = [];
if (!empty($studentId) && isset($_SESSION['deleted_irregular_autoload_blocks'][$studentId]) && is_array($_SESSION['deleted_irregular_autoload_blocks'][$studentId])) {
  $deletedAutoloadBlockKeysForUi = array_keys($_SESSION['deleted_irregular_autoload_blocks'][$studentId]);
}

// Render a compact layout (no sidebar) when opened inside iframe modal.
// Keep modal mode on subsequent Evaluate submits by honoring both GET and POST.
$fromModal = trim((string)($_GET['from_modal'] ?? $_POST['from_modal'] ?? ''));
$isModalView = ($fromModal === '1');

// Current semester lock states used to disable semester checkboxes in the UI
$semesterLockStates = loadSemesterLockStates($conn);

try {
  $tblRes = $conn->query("SHOW TABLES LIKE 'enrollment_periods'");
  if ($tblRes && $tblRes->num_rows > 0) {
    $pageKey = 'stueval';
    $stmt = $conn->prepare("SELECT start_datetime, end_datetime FROM enrollment_periods WHERE page = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('s', $pageKey);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          $tz = new DateTimeZone('Asia/Manila');
          $now   = new DateTime('now', $tz);
          $start = new DateTime($row['start_datetime'], $tz);
          $end   = new DateTime($row['end_datetime'], $tz);

          if ($now < $start || $now > $end) {
            $evaluationOpen = false;
            $fmt = 'M d, Y h:i A';
            $enrollmentPeriodMessage = 'Evaluation is allowed only from ' . $start->format($fmt) . ' to ' . $end->format($fmt) . '.';
          }
        }
      }
      $stmt->close();
    }
  }
} catch (Throwable $e) {
  // Fail open on errors but log for diagnostics
  error_log('Enrollment period check failed in stueval.php: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DAS Evaluation</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Expose passed subject codes (normalized) for client-side prerequisite validation
    window.passedSubjects = <?php echo json_encode(array_keys($passedSubjects ?? [])); ?>;
    // Flag: does the student have any subjects in 1st Year • 1st Semester?
    window.hasFirstYearFirstSem = <?php echo $hasFirstYearFirstSemGrade ? 'true' : 'false'; ?>;
    // Expose student classification for client-side rules (e.g., irregular caps)
    window.studentClassification = <?php echo json_encode($student['classification'] ?? ''); ?>;
    // Source of truth for UI gating of irregular-only controls.
    window.isIrregularForUi = <?php echo !empty($isIrregularForUi) ? 'true' : 'false'; ?>;
    window.deletedAutoloadBlockKeys = <?php echo json_encode($deletedAutoloadBlockKeysForUi ?? []); ?>;
    // Effective academic year (promotion based on 1-1,1-2,2-1,2-2,3-1,3-2,4-1,4-2)
    window.effectiveYearLevel = <?php echo (int)$effectiveYearLevel; ?>;
    window.promotionIsIrregular = <?php echo $promotionIsIrregular ? 'true' : 'false'; ?>;
    // Enrollment / evaluation period flags
    window.evaluationOpen = <?php echo $evaluationOpen ? 'true' : 'false'; ?>;
    window.enrollmentPeriodMessage = <?php echo json_encode($enrollmentPeriodMessage); ?>;
  </script>

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

    /* Iframe modal view: hide sidebar and remove left offset */
    .modal-view .sidebar {
      display: none !important;
    }

    .modal-view .main-content {
      margin-left: 0 !important;
      padding: 12px !important;
    }

    .modal-view .prospectus {
      margin-bottom: 0;
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
<body class="<?php echo $isModalView ? 'modal-view' : ''; ?>">
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
          <i class="bi bi-journal-text"></i> DAS Evaluation
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
    <h1 class="h3 mb-0">DAS Evaluation</h1>
  </div>
  <div class="container my-4">
    <form method="GET" class="row g-2 align-items-end mb-3">
      <?php if ($isModalView): ?>
      <input type="hidden" name="from_modal" value="1">
      <?php endif; ?>
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
        <label class="form-label">Curriculum</label>
        <select name="fiscal_year" class="form-select">
          <option value="">Select Curriculum...</option>
          <?php foreach ($availableFiscalYears as $fyLabel): ?>
            <option value="<?= htmlspecialchars($fyLabel) ?>" <?= $fiscalYear === $fyLabel ? 'selected' : '' ?>>
              <?= htmlspecialchars($fyLabel) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-sm-2">
        <button class="btn btn-primary w-100" type="submit" name="evaluate" value="1">Evaluate</button>
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
          $classification = resolveStudentClassification($conn, $studentId);
          syncStudentClassification($conn, $studentId, $classification);
          switch ($classification) {
            case 'Dismissal':
              $badgeClass = 'badge bg-danger text-white';
              break;
            case 'Probationary':
              $badgeClass = 'badge bg-warning text-dark';
              break;
            case 'Irregular':
              $badgeClass = 'badge bg-secondary text-white';
              break;
            default:
              $badgeClass = 'badge bg-primary text-white';
              break;
          }
          ?>
          <span class="<?= $badgeClass ?>"><?= $classification ?></span>
          <div class="mt-1 small text-muted">
            Year Level :
            <?php $yearBadgeClass = $promotionIsIrregular ? 'badge bg-danger' : 'badge bg-success'; ?>
            <span class="<?= $yearBadgeClass ?> ms-1\"><?= htmlspecialchars($displayYearLevelLabel) ?></span>
            <?php if ($displayYearLevel !== $effectiveYearLevel): ?>
              <div class="small text-muted mt-1">Computed progress: <?= htmlspecialchars($effectiveYearLabel) ?></div>
            <?php endif; ?>
          </div>
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
        $semesterIsLocked = !empty($semesterLockStates[$currentSem]);
        $semesterCheckboxDisabled = $semesterIsLocked ? 'disabled aria-disabled="true"' : '';
        $semesterSelectAllDisabled = $semesterIsLocked ? 'disabled' : '';
        
        // Calculate total units for this semester from subjects with grades only
        $semesterUnits = 0;
        $irregularUnits = 0;
        
        // Get irregular subjects for this semester from the allIrregularSubjects array (for badge - count all)
        $irregularSubjects = [];
        $irregularUnitsForBadge = 0; // Count all irregular units for badge
        $irregularUnitsForTotal = 0; // Count only graded irregular units for total
        [$yr, $sm] = array_map('intval', explode('-', $ysKey));
        
        foreach ($allIrregularSubjects as $irregular) {
            if ($irregular['year_level'] == $yr && $irregular['semester'] == $sm) {
                $irregularSubjects[] = $irregular;
                $irregularUnitsForBadge += $irregular['units']; // Count all for badge
                
                // Check if this irregular subject has a grade (for total calculation)
                $grade = '';
                
                // 1. Try exact match
                if (isset($gradesByCode[$irregular['code']])) {
                    $grade = $gradesByCode[$irregular['code']];
                }
                
                // 2. Try normalized code match
                if (empty($grade)) {
                    $normalizedCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $irregular['code']));
                    foreach ($gradesByCode as $key => $value) {
                        $normalizedKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $key));
                        if ($normalizedKey === $normalizedCode) {
                            $grade = $value;
                            break;
                        }
                    }
                }
                
                // 3. Try pattern matching
                if (empty($grade) && preg_match('/^([A-Za-z]+)[\s-]*(\d+[A-Za-z]*)$/', $irregular['code'], $matches)) {
                  $prefix = strtoupper($matches[1]);
                  $number = $matches[2];
                  $pattern1 = $prefix . $number;
                  $pattern2 = $prefix . '-' . $number;
                  $pattern3 = $prefix . ' ' . $number;
                    
                  foreach ([$pattern1, $pattern2, $pattern3] as $pattern) {
                    if (isset($gradesByCode[$pattern])) {
                      $grade = $gradesByCode[$pattern];
                      break;
                    }
                  }
                }

                // 4. Try matching by course title (keep logic consistent with display rows)
                if (empty($grade) && !empty($irregular['title'])) {
                  $currentTitle = strtolower(trim($irregular['title']));
                  foreach ($gradesByCode as $key => $value) {
                    if (is_array($value) && !empty($value['course_title'])) {
                      $gradeTitle = strtolower(trim($value['course_title']));
                      if ($currentTitle === $gradeTitle) {
                        $grade = $value;
                        break;
                      }
                    }
                  }
                }
                
                // Only count for total if grade exists and is a passing grade
                if (!empty($grade)) {
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
                        $irregularUnitsForTotal += $irregular['units'];
                    }
                }
            }
        }
        
        // Calculate units from curriculum subjects that have grades
        $gradedUnits = 0;
        if (isset($curriculum[$ysKey])) {
            foreach ($curriculum[$ysKey] as $courseCode => $course) {
                // Check if this course has a grade
                $grade = '';
                
                // Try to find grade using the same logic as in the display
                // 1. Try exact match
                if (isset($gradesByCode[$courseCode])) {
                    $grade = $gradesByCode[$courseCode];
                }
                
                // 2. Try normalized code match
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
                
                // 3. Try pattern matching
                if (empty($grade) && preg_match('/^([A-Za-z]+)[\s-]*(\d+[A-Za-z]*)$/', $courseCode, $matches)) {
                  $prefix = strtoupper($matches[1]);
                  $number = $matches[2];
                  $pattern1 = $prefix . $number;
                  $pattern2 = $prefix . '-' . $number;
                  $pattern3 = $prefix . ' ' . $number;
                    
                  foreach ([$pattern1, $pattern2, $pattern3] as $pattern) {
                    if (isset($gradesByCode[$pattern])) {
                      $grade = $gradesByCode[$pattern];
                      break;
                    }
                  }
                }

                // 4. Try matching by course title (same as row display)
                if (empty($grade) && !empty($course['title'])) {
                  $currentTitle = strtolower(trim($course['title']));
                  foreach ($gradesByCode as $key => $value) {
                    if (is_array($value) && !empty($value['course_title'])) {
                      $gradeTitle = strtolower(trim($value['course_title']));
                      if ($currentTitle === $gradeTitle) {
                        $grade = $value;
                        break;
                      }
                    }
                  }
                }
                
                // Only count units if grade exists and is a passing grade
                if (!empty($grade)) {
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
                        $courseUnits = floatval($course['units'] ?? 0);
                        $gradedUnits += $courseUnits;
                    }
                }
            }
        }
        
        // Total units passed this semester = graded curriculum units + graded irregular units
        $semesterUnits = $gradedUnits + $irregularUnitsForTotal;
        
        // Max units for this semester come directly from the curriculum
        // (sum of all subject units in that semester), not hardcoded.
        $maxUnits = isset($maxUnitsPerSemester[$ysKey]) ? $maxUnitsPerSemester[$ysKey] : 0;
        $isOverLimit = $semesterUnits > $maxUnits;
    ?>
  <div class="mb-4 <?= $isOverLimit ? 'unit-overlimit unit-limit-warning' : '' ?>" data-ys="<?= htmlspecialchars($ysKey) ?>" data-current-units="<?= htmlspecialchars(number_format($semesterUnits, 1, '.', '')) ?>" data-max-units="<?= htmlspecialchars(number_format($maxUnits, 1, '.', '')) ?>">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="semester-title d-flex align-items-center">
            <?= $labels[$ysKey] ?>
            <span class="badge ms-2 <?= $semesterIsLocked ? 'bg-danger' : 'bg-success' ?>">
              <?= $semesterIsLocked ? 'Locked' : 'Unlocked' ?>
            </span>
          </div>
            <div class="d-flex gap-2">
<!-- Clickable irregular units badge: opens subject selector for this semester -->
            <span class="badge bg-success rounded-pill open-select-subjects me-2" role="button" data-ys="<?= htmlspecialchars($ysKey) ?>" data-bs-toggle="tooltip" title="Click to see courses">
              <i class="bi bi-plus-circle me-1"></i> <?= number_format($irregularUnitsForBadge, 1) ?>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary select-semester" 
                    data-ys="<?= htmlspecialchars($ysKey) ?>"
                    <?= $semesterSelectAllDisabled ?>
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
                <th style="width:5%; padding: 12px 8px;"></th>
                <th style="width:10%; padding: 12px 8px;">GRADE</th>
                <th style="width:10%; padding: 12px 8px;">CODE</th>
                <th style="padding: 12px 8px; text-align: left;">COURSE TITLE</th>
                <th style="width:7%; padding: 12px 8px;">Lec</th>
                <th style="width:7%; padding: 12px 8px;">Lab</th>
                <th style="width:8%; padding: 12px 8px;">Units</th>
                <th style="width:15%; padding: 12px 8px;">Pre-Req</th>
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
                  // For classification-based prerequisites (e.g., "Regular"),
                  // this flag controls whether the subject should be blocked
                  // for clicking even if the student is classified as Regular.
                  $regularPrereqBlocked = false;
                  // Special flag: block Capstone Project 1 when not all
                  // major (with-prerequisite) subjects are passed.
                  $isCapstone1 = !empty($subj['title']) && stripos($subj['title'], 'capstone project 1') !== false;
                  $capstoneBlockedByMajors = false;
                  
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

                    // Apply the global Capstone 1 rule to this specific row
                    if ($isCapstone1 && !$allMajorWithPrereqPassed) {
                      $capstoneBlockedByMajors = true;
                      error_log("Capstone Project 1 row: blocking selection because not all major (with-prerequisite) subjects are passed for student $studentId.");
                    }
                  
                  
                  // Check if student is regular
                  $isRegular = isRegularStudent($student);
                  
                  // Debug: Log student data
                  error_log("DEBUG: Student data: " . print_r($student, true));
                  error_log("DEBUG: isRegular result: " . ($isRegular ? 'true' : 'false'));
                  
                    // Check prerequisites for all students (not just irregular)#c6282
                    $prereq = trim($prereq);
                    $shouldCheckPrereq = !empty($prereq) && !in_array(strtolower($prereq), ['none', 'n/a', '-', '', 'none ']);

                    // Special case: for Capstone Project 1 rows where the PRE-REQ
                    // label is an abstract string like "ALL PRE-REQ", do NOT
                    // treat that text as a concrete course code prerequisite.
                    // The real blocking for Capstone 1 is handled by the
                    // $capstoneBlockedByMajors flag based on all earlier
                    // major-with-prereq subjects. This prevents a passed
                    // Capstone 1 grade from being styled as failed/red.
                    if ($isCapstone1 && $shouldCheckPrereq) {
                      $label = strtolower(preg_replace('/\s+/', ' ', $prereq));
                      if (strpos($label, 'all pre-req') !== false || strpos($label, 'all prereq') !== false) {
                        $shouldCheckPrereq = false;
                        error_log("Capstone 1: ignoring textual PRE-REQ label '$prereq' for per-subject prerequisite check.");
                      }
                    }
                  
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
                      
                      // If student is any "Regular" classification, they should NOT be blocked
                      if ($isDirectRegular) {
                          // For classification prerequisites (Regular, Regular 3rd Year, Regular 4th Year)
                          // we now treat the prerequisite as satisfied for Regular students
                          // and do NOT block them based on internal semester checks. This
                          // ensures graduating/regular students (e.g. in 3-2, 4-1, 4-2) can
                          // freely take subjects whose PRE-REQ is "Regular".
                          if ($isClassificationPrereq) {
                              $prereqFailed = false;
                              $regularPrereqBlocked = false;
                              error_log("DEBUG: Regular student automatically satisfies classification prerequisite; no blocking.");
                          } else {
                              // For regular course prerequisites, check normally
                              $prereqCodes = array_map('trim', explode(',', $prereq));
                              foreach ($prereqCodes as $prereqCode) {
                                  $prereqCode = trim($prereqCode);
                                  $normPrereqCode = normalizeCourseCode($prereqCode);
                                  $prereqGrade = trim((string)($gradesByCode[$normPrereqCode] ?? ''));
                                  $prereqGradeUpper = strtoupper($prereqGrade);

                                  // Treat INC as not satisfied for prerequisite purposes
                                  if ($prereqGradeUpper === 'INC') {
                                    $prereqFailed = true;
                                    error_log("DEBUG: Regular student has INC grade in prerequisite $prereqCode, setting prereqFailed = true");
                                    break;
                                  }

                                  // If prerequisite has a grade and it's failing (> 3.25), mark as failed
                                  if ($prereqGrade !== '' && is_numeric($prereqGrade) && floatval($prereqGrade) > 3.25) {
                                    $prereqFailed = true;
                                    error_log("DEBUG: Regular student failed prerequisite $prereqCode with grade $prereqGrade, setting prereqFailed = true");
                                    break;
                                  }
                                  // If prerequisite has no grade, check if it's a required course
                                  elseif ($prereqGrade === '') {
                                    $prereqFailed = true;
                                    error_log("DEBUG: Regular student missing prerequisite $prereqCode, setting prereqFailed = true");
                                    break;
                                  }
                              }
                              if (!$prereqFailed) {
                                  error_log("DEBUG: Regular student passed all prerequisites, setting prereqFailed = false");
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
                                $prereqGradeUpper = strtoupper($prereqGrade);

                                // Treat INC as not satisfied for prerequisite purposes
                                if ($prereqGradeUpper === 'INC') {
                                  $prereqFailed = true;
                                  error_log("DEBUG: Non-regular student has INC grade in prerequisite $prereqCode, setting prereqFailed = true");
                                  break;
                                }

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
                      } elseif (is_string($grade)) {
                        $gradeUpper = strtoupper(trim($grade));
                        if ($gradeUpper === 'PASSED') {
                          $isPassed = true;
                        } elseif (in_array($gradeUpper, ['FAILED', 'FAIL'], true)) {
                          $isFailed = true;
                          // Treat textual FAILED the same as numeric 5.0 for selection rules
                          $isInIrregularDb = false;
                        }
                      }
                    }
                    // Extra rule: if the student has no subjects in 1st Year • 1st Semester
                    // and this subject is in specified later semesters with PRE-REQ of 'None',
                    // make the checkbox not clickable from the start.
                    $normalizedPrereq = strtolower(trim($prereq ?? ''));
                    $ysKeyCurrent = $yr . '-' . $sm;
                    $needsFirstYearFirstSemBlock = (
                      in_array($ysKeyCurrent, ['2-2','3-1','3-2','4-1','4-2'], true) &&
                      in_array($normalizedPrereq, ['none','n/a','-','', 'none '], true) &&
                      !$hasFirstYearFirstSemGrade
                    );
                    ?>
                    <?php if ($isPassed): ?>
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
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             title="This subject has already been passed">
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
                             <?= $semesterCheckboxDisabled ?>
                             title="This subject needs to be retaken">
                    <?php elseif ($isCapstone1 && $capstoneBlockedByMajors): ?>
                      <span style="color: #e63e3eff; font-size: 1.2em; font-weight: bold;" title="Cannot select Capstone Project 1: you must pass all subjects that have prerequisites.">✗</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             style="display: none;">
                    <?php elseif ($needsFirstYearFirstSemBlock): ?>
                      <span style="color: #e63e3eff; font-size: 1.2em; font-weight: bold;" title="Cannot select: no subjects in First Year • First Semester.">✗</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             title="Cannot select: no subjects in First Year • First Semester."
                             style="display: none;">
                    <?php elseif ($regularPrereqBlocked): ?>
                      <span style="color: #e63e3eff; font-size: 1.2em; font-weight: bold;" title="Cannot select: Regular prerequisite not yet satisfied.">✗</span>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             style="display: none;">
                    <?php elseif ($prereqFailed && !$isRegular): ?>
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
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             style="display: none;">
                    <?php elseif ($isInIrregularDb): ?>
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
                             <?= $semesterCheckboxDisabled ?>
                             disabled
                             title="This subject is already in irregular subjects">
                    <?php else: ?>
                      <input type="checkbox" class="form-check-input subject-checkbox" 
                             value="<?= htmlspecialchars($code) ?>"
                             data-title="<?= htmlspecialchars($subj['title']) ?>"
                             data-units="<?= htmlspecialchars($subj['units'] ?? '3') ?>"
                             data-lec="<?= htmlspecialchars($subj['lec'] ?? '0') ?>"
                             data-lab="<?= htmlspecialchars($subj['lab'] ?? '0') ?>"
                             data-prereq="<?= htmlspecialchars($subj['prereq'] ?? '') ?>"
                             data-year="<?= $yr ?>"
                             data-sem="<?= $sm ?>"
                             <?= $semesterCheckboxDisabled ?>>
                    <?php endif; ?>
                  </td>
                  <td class="text-center grade-cell <?= $rowClass ?> <?= ($prereqFailed && !$isRegular) ? 'prereq-failed' : '' ?>" 
                      style="padding: 12px 8px; <?= ($prereqFailed && !$isRegular) ? 'background-color: transparent !important; color: #c62828 !important;' : '' ?>" 
                      data-code="<?= htmlspecialchars(normalizeCourseCode($code)) ?>">
                    <strong style="<?= ($prereqFailed && !$isRegular) ? 'color: #c93838ff !important;' : '' ?>">
                        <?= $displayGrade ?? '—'; ?>
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
                    </div>
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
                <td style="border-top: 2px solid #dee2e6 !important; border-bottom: 1px solid #dee2e6 !important;"></td>
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
      <?php
        $studentClassForUi = strtolower(trim($student['classification'] ?? ''));
        $isIrregularForUi = strpos($studentClassForUi, 'irregular') !== false;
      ?>
      <div class="row">
        <div class="col-md-6">
          <label for="bulkYearSem" class="form-label">Target Year and Semester (optional):</label>
          <select class="form-select" id="bulkYearSem">
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
      <?php if ($isIrregularForUi): ?>
      <div class="row mt-3">
        <div class="col-md-12 d-flex justify-content-start">
          <button type="button" id="openOtherProgramModal" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left-right me-1"></i>
            Add Subject from Other DAS Program
          </button>
        </div>
      </div>
      <?php endif; ?>
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
        <div class="alert alert-light border d-flex justify-content-between align-items-center" id="selectSubjectSummary">
          <div><strong>Total Courses:</strong> <span id="selectSubjectCount">0</span></div>
          <div><strong>Total Units:</strong> <span id="selectSubjectUnits">0.0</span></div>
        </div>
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
                  <th style="width:10%">Action</th>
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
                <?php if (!empty($isIrregularForUi)): ?>
                <option value="5">5th Year</option>
                <?php endif; ?>
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

<!-- Other DAS Program Subjects Modal (for irregular students) -->
<div class="modal fade" id="otherProgramModal" tabindex="-1" aria-labelledby="otherProgramModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otherProgramModalLabel">Add Subject from Other DAS Program</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Program</label>
            <input type="hidden" id="otherProgramSelect" value="<?= htmlspecialchars($program) ?>">
            <div class="form-control bg-light">
              <strong><?= htmlspecialchars($program) ?></strong>
              <span class="text-muted small d-block">(Student's enrolled DAS program)</span>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Year Level</label>
            <select class="form-select" id="otherYearSelect">
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
              <?php if (!empty($isIrregularForUi)): ?>
              <option value="5">5th Year</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Semester</label>
            <select class="form-select" id="otherSemSelect">
              <option value="1">1st Semester</option>
              <option value="2">2nd Semester</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-primary w-100" id="loadOtherProgramSubjects">
              <i class="bi bi-search me-1"></i>Load
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover" id="otherProgramSubjectsTable">
            <thead>
              <tr>
                <th style="width:10%">Code</th>
                <th>Title</th>
                <th style="width:10%">Lec</th>
                <th style="width:10%">Lab</th>
                <th style="width:10%">Units</th>
                <th style="width:10%">Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
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

      // Extra rule: if the student has no subjects in 1st Year • 1st Semester,
      // block "Select All" for specific higher semesters when their subjects
      // are effectively PRE-REQ "None". This mirrors the per-subject rule in
      // checkPrerequisites but shows only one SweetAlert instead of many.
      try {
        const hasFirstYearFirstSem = !!window.hasFirstYearFirstSem;
        const parts = ysKey.split('-');
        const year = parseInt(parts[0] || '0', 10);
        const sem = parseInt(parts[1] || '0', 10);

        const needsFirstYearFirstSem =
          (year === 2 && (sem === 1 || sem === 2)) ||
          (year === 3 && (sem === 1 || sem === 2)) ||
          (year === 4 && (sem === 1 || sem === 2));

        if (needsFirstYearFirstSem && !hasFirstYearFirstSem) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'First Year First Semester Required',
              html: `
                <p>You cannot select all subjects in this semester yet.</p>
                <p>Please fill the <strong>First Year 
                          
                          
                First Semester</strong> subjects first.</p>
              `,
              confirmButtonText: 'OK'
            });
          } else {
            alert('Please fill the First Year First Semester subjects first.');
          }
          return;
        }
      } catch (err) {
        console.error('Error applying Select All first-year check:', err);
      }
        
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
                
                // Check unit limits (already adjusted server-side for irregular students)
                let maxUnits = unitLimits[program] && unitLimits[program][yearSem] ? unitLimits[program][yearSem].max : 26;

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
                        
                        // Reload page to show updated curriculum and irregular subjects
                        window.location.reload();
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

    const setSelectSubjectSummary = (count, totalUnits) => {
      const countEl = document.getElementById('selectSubjectCount');
      const unitsEl = document.getElementById('selectSubjectUnits');
      if (countEl) countEl.textContent = String(count);
      if (unitsEl) unitsEl.textContent = Number(totalUnits || 0).toFixed(1);
    };

    e.preventDefault();
    const ys = badge.getAttribute('data-ys');
    if (!ys) return;
    const selectModalEl = document.getElementById('selectSubjectModal');
    if (selectModalEl) {
      selectModalEl.dataset.currentYs = ys;
    }

    // Find the semester block
    const semBlock = document.querySelector(`div.mb-4[data-ys="${ys}"]`);
    const tbody = document.querySelector('#selectSubjectTable tbody');
    tbody.innerHTML = '';

    // Fetch irregular subjects for this student and semester
    const studentId = '<?= htmlspecialchars($studentId) ?>';
    if (!studentId) {
      setSelectSubjectSummary(0, 0);
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No student selected.</td></tr>';
      const selectModalEl = document.getElementById('selectSubjectModal');
      bootstrap.Modal.getOrCreateInstance(selectModalEl).show();
      return;
    }

    const url = `./stueval.php?action=get_irregular&student_id=${encodeURIComponent(studentId)}&ys=${encodeURIComponent(ys)}`;
    console.log('Fetching irregular subjects from:', url);
    
    fetch(url)
      .then(r => {
        console.log('Get irregular response status:', r.status);
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        return r.json();
      })
      .then(data => {
        console.log('Get irregular response data:', data);
        
        // Debug: Log the raw response text if parsing fails
        if (!data) {
          console.error('Empty or invalid response from server');
          setSelectSubjectSummary(0, 0);
          tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Invalid response from server. Check console for details.</td></tr>';
          return;
        }
        
        if (!data.success) {
          console.error('Server returned error:', data.message || 'No error message');
          setSelectSubjectSummary(0, 0);
          tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.message || 'Failed to load irregular subjects. Check console for details.'}</td></tr>`;
        } else if (!data.data || data.data.length === 0) {
          console.log('No data returned for student', studentId, 'and semester', ys);
          setSelectSubjectSummary(0, 0);
          tbody.innerHTML = `
            <tr>
              <td colspan="6" class="text-center text-muted">
                No irregular subjects found for this student in semester ${ys}.
                <div class="small mt-1">Student ID: ${studentId}</div>
              </td>
            </tr>`;
        } else {
          const totalUnits = data.data.reduce((sum, row) => {
            return sum + (parseFloat(row.total_units) || 0);
          }, 0);
          setSelectSubjectSummary(data.data.length, totalUnits);

          data.data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${r.course_code}</td>
              <td>${r.course_title}</td>
              <td class="text-center">${r.lec_units ?? '0'}</td>
              <td class="text-center">${r.lab_units ?? '0'}</td>
              <td class="text-center">${r.total_units ?? ''}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-danger delete-irregular" data-id="${r.id}" data-code="${r.course_code}">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </td>
            `;
            tbody.appendChild(tr);
          });
        }

        const selectModalEl = document.getElementById('selectSubjectModal');
        bootstrap.Modal.getOrCreateInstance(selectModalEl).show();
      })
      .catch(err => {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading irregular subjects.</td></tr>';
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

    const selectModalEl = document.getElementById('selectSubjectModal');
    const currentYs = (selectModalEl && selectModalEl.dataset.currentYs) ? selectModalEl.dataset.currentYs : '';
    if (currentYs && currentYs.includes('-')) {
      const [prefillYear, prefillSem] = currentYs.split('-');
      addSubjectModal.dataset.prefillYear = prefillYear;
      addSubjectModal.dataset.prefillSem = prefillSem;
    }

    // Optionally set year/sem based on current view; leave defaults otherwise
    // Close the selector modal first
    const selectModal = bootstrap.Modal.getInstance(selectModalEl);
    if (selectModal) selectModal.hide();

    // Show addSubjectModal programmatically
    const modal = bootstrap.Modal.getOrCreateInstance(addSubjectModal);
    modal.show();
  });

  // Handle delete irregular subject - more specific targeting
  document.addEventListener('click', function(e) {
    // Check if click is on delete button or its icon
    const btn = e.target.closest('.delete-irregular');
    if (!btn && !e.target.closest('.bi-trash')) return;
    
    console.log('Delete button detected!', e.target); // Debug log
    e.preventDefault();
    e.stopPropagation();
    
    // Get the button element (might be clicked on icon)
    const deleteBtn = btn || e.target.closest('.delete-irregular');
    const id = deleteBtn.getAttribute('data-id');
    const code = deleteBtn.getAttribute('data-code');
    
    console.log('Delete button clicked!', { id, code, buttonElement: deleteBtn }); // Enhanced debug log
    
    if (!id) {
      alert('Error: Subject ID not found');
      return;
    }
    
    // Confirm deletion
    if (!confirm(`Are you sure you want to delete ${code}? This action cannot be undone.`)) {
      return;
    }
    
    // Send delete request - try GET instead of POST
    fetch(`./stueval.php?action=delete_irregular&id=${encodeURIComponent(id)}`, {
      method: 'GET'
    })
    .then(response => {
      console.log('Delete response status:', response.status);
      console.log('Delete response headers:', response.headers);
      
      // Check if response is OK
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      // Try to parse as JSON, but handle cases where response might be HTML
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return response.json();
      } else {
        return response.text().then(text => {
          console.log('Non-JSON response:', text);
          throw new Error('Server returned non-JSON response. Check server logs for details.');
        });
      }
    })
    .then(data => {
      console.log('Delete response data:', data);
      if (data.success) {
        alert(`${code} has been deleted successfully.`);
        // Update the modal table in place so the page does not reload.
        const row = deleteBtn.closest('tr');
        if (row) {
          row.remove();
        }

        const tbody = document.querySelector('#selectSubjectTable tbody');
        const remainingRows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
        if (tbody && remainingRows.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No irregular subjects remain in this semester.</td></tr>';
        }

        const countEl = document.getElementById('selectSubjectCount');
        const unitsEl = document.getElementById('selectSubjectUnits');
        if (tbody && countEl && unitsEl) {
          const rows = Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.querySelector('.delete-irregular'));
          const totalUnits = rows.reduce((sum, tr) => {
            const unitsCell = tr.querySelectorAll('td')[4];
            return sum + (parseFloat(unitsCell?.textContent || '0') || 0);
          }, 0);
          countEl.textContent = String(rows.length);
          unitsEl.textContent = totalUnits.toFixed(1);
        }
      } else {
        alert(`Error deleting ${code}: ${data.message || 'Unknown error'}`);
      }
    })
    .catch(error => {
      console.error('Delete error:', error);
      const errorMessage = error.message || 'Unknown error occurred';
      alert(`Error deleting ${code}: ${errorMessage}. Please try again.`);
    });
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
      
      const res = await fetch('stueval.php', { 
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
    const selectedProgram = '<?= htmlspecialchars($program ?: 'BSCS') ?>';
    const unitLimits = <?= json_encode($unitLimits) ?>;
    const studentClassification = String(window.studentClassification || '').toLowerCase();
    const isIrregularStudent = studentClassification.includes('irregular');

    // Floating quick summary (always visible while scrolling)
    const floatingCounter = document.createElement('div');
    floatingCounter.id = 'floatingSelectionCounter';
    floatingCounter.style.position = 'fixed';
    floatingCounter.style.right = '16px';
    floatingCounter.style.bottom = '16px';
    floatingCounter.style.zIndex = '1080';
    floatingCounter.style.minWidth = '260px';
    floatingCounter.style.maxWidth = '360px';
    floatingCounter.style.background = '#0d6efd';
    floatingCounter.style.color = '#fff';
    floatingCounter.style.borderRadius = '12px';
    floatingCounter.style.boxShadow = '0 8px 24px rgba(0,0,0,0.2)';
    floatingCounter.style.padding = '10px 12px';
    floatingCounter.style.fontSize = '13px';
    floatingCounter.style.display = 'none';
    floatingCounter.innerHTML = '<div><strong>Selected:</strong> 0 subjects</div><div><strong>Units:</strong> 0.0</div>';
    document.body.appendChild(floatingCounter);
    
    // Normalize a course code similar to PHP's normalizeCourseCode
    function normalizeCode(code) {
      return String(code || '')
        .toUpperCase()
        .replace(/[^A-Z0-9]/g, '');
    }

    // Build a Set of passed subject codes for fast lookup
    const passedSubjectCodes = Array.isArray(window.passedSubjects) ? window.passedSubjects : [];
    const passedSubjectsSet = new Set(passedSubjectCodes.map(normalizeCode));

    function parseUnits(text) {
      const match = String(text || '').match(/([\d.]+)/);
      return match ? (parseFloat(match[1]) || 0) : 0;
    }

    function getCheckboxYearSem(checkbox) {
      const y = String(checkbox?.dataset?.year || '').trim();
      const s = String(checkbox?.dataset?.sem || '').trim();
      return `${y}-${s}`;
    }

    function formatSelectedYearSemLabel(ys) {
      return formatSemesterLabel(ys || '');
    }

    function findMismatchedSelections(selectedYearSem) {
      const checked = Array.from(document.querySelectorAll('.subject-checkbox:checked'));
      if (!selectedYearSem) {
        return checked;
      }
      return checked.filter(cb => getCheckboxYearSem(cb) !== selectedYearSem);
    }

    function showSwalWarning(title, html) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title,
          html,
          confirmButtonText: 'OK'
        });
      } else {
        alert(title);
      }
    }

    function getCurrentSemesterUnitsBySemester() {
      const unitsBySemester = {};
      document.querySelectorAll('div.mb-4[data-ys]').forEach(block => {
        const ys = block.getAttribute('data-ys');
        if (!ys) return;
        const currentUnitsAttr = parseFloat(block.getAttribute('data-current-units') || '0');
        if (!Number.isNaN(currentUnitsAttr)) {
          unitsBySemester[ys] = currentUnitsAttr;
          return;
        }

        // Fallback: parse the calculator badge text "X / Y".
        const calcBadge = block.querySelector('.badge.bg-secondary.rounded-pill, .badge.bg-danger.rounded-pill');
        if (calcBadge) {
          const first = String(calcBadge.textContent || '').split('/')[0] || '';
          unitsBySemester[ys] = parseUnits(first);
        } else {
          unitsBySemester[ys] = 0;
        }
      });
      return unitsBySemester;
    }

    function getMaxUnitsForSemester(ys) {
      if (unitLimits[selectedProgram] && unitLimits[selectedProgram][ys]) {
        return parseFloat(unitLimits[selectedProgram][ys].max) || 26;
      }
      return 26;
    }

    function summarizeCheckedSubjects() {
      const checked = Array.from(document.querySelectorAll('.subject-checkbox:checked'));
      const selectedUnitsBySemester = {};
      let totalSelectedUnits = 0;

      checked.forEach(cb => {
        const y = cb.dataset.year || '';
        const s = cb.dataset.sem || '';
        const ys = `${y}-${s}`;
        const units = parseFloat(cb.dataset.units) || 0;

        totalSelectedUnits += units;
        selectedUnitsBySemester[ys] = (selectedUnitsBySemester[ys] || 0) + units;
      });

      return {
        checked,
        totalSelectedUnits,
        selectedUnitsBySemester
      };
    }

    function buildOverloadWarnings(summary) {
      const warnings = [];
      const currentUnitsBySemester = getCurrentSemesterUnitsBySemester();

      Object.keys(summary.selectedUnitsBySemester).forEach(ys => {
        const currentSemesterUnits = currentUnitsBySemester[ys] || 0;
        const selectedUnits = summary.selectedUnitsBySemester[ys] || 0;
        const maxUnits = getMaxUnitsForSemester(ys);
        const afterTotal = currentSemesterUnits + selectedUnits;

        if (afterTotal > maxUnits) {
          warnings.push({
            ys,
            currentSemesterUnits,
            selectedUnits,
            afterTotal,
            maxUnits,
            overBy: afterTotal - maxUnits
          });
        }
      });

      return warnings;
    }

    function formatSemesterLabel(ys) {
      const map = {
        '1-1': 'First Year - First Semester',
        '1-2': 'First Year - Second Semester',
        '2-1': 'Second Year - First Semester',
        '2-2': 'Second Year - Second Semester',
        '3-1': 'Third Year - First Semester',
        '3-2': 'Third Year - Second Semester',
        '4-1': 'Fourth Year - First Semester',
        '4-2': 'Fourth Year - Second Semester'
      };
      return map[ys] || ys;
    }

    function createSemesterLiveBadges() {
      document.querySelectorAll('div.mb-4[data-ys]').forEach(block => {
        const ys = block.getAttribute('data-ys');
        if (!ys) return;

        const badgeContainer = block.querySelector('.d-flex.gap-2');
        if (!badgeContainer) return;
        if (badgeContainer.querySelector('.selected-live-units')) return;

        const selectedBadge = document.createElement('span');
        selectedBadge.className = 'badge bg-info rounded-pill selected-live-units';
        selectedBadge.setAttribute('data-selected-ys', ys);
        selectedBadge.setAttribute('title', 'Selected units in this semester');
        selectedBadge.innerHTML = '<i class="bi bi-check2-square me-1"></i> Selected: 0.0';

        // Place before Select All button for better visibility
        const selectBtn = badgeContainer.querySelector('.select-semester');
        if (selectBtn) {
          badgeContainer.insertBefore(selectedBadge, selectBtn);
        } else {
          badgeContainer.appendChild(selectedBadge);
        }
      });
    }

    function updateSemesterLiveBadges(summary, warnings) {
      document.querySelectorAll('.selected-live-units').forEach(badge => {
        const ys = badge.getAttribute('data-selected-ys') || '';
        const selectedUnits = summary.selectedUnitsBySemester[ys] || 0;
        const maxUnits = getMaxUnitsForSemester(ys);
        const isOver = warnings.some(w => w.ys === ys);

        badge.classList.remove('bg-info', 'bg-danger');
        badge.classList.add(isOver ? 'bg-danger' : 'bg-info');
        badge.innerHTML = `<i class="bi bi-check2-square me-1"></i> Selected: ${selectedUnits.toFixed(1)} / ${maxUnits.toFixed(1)}`;
      });
    }

    function updateFloatingCounter(summary, warnings) {
      const count = summary.checked.length;
      const totalUnits = summary.totalSelectedUnits;

      if (count === 0) {
        floatingCounter.style.display = 'none';
        return;
      }

      floatingCounter.style.display = 'block';
      floatingCounter.style.background = warnings.length ? '#dc3545' : '#0d6efd';
      floatingCounter.innerHTML = `
        <div><strong>Selected:</strong> ${count} subject${count !== 1 ? 's' : ''}</div>
        <div><strong>Units:</strong> ${totalUnits.toFixed(1)}</div>
        <div><strong>Status:</strong> ${warnings.length ? 'Over limit' : 'Within limit'}</div>
      `;
    }

    function renderUnitWarnings(warnings) {
      let warningContainer = document.getElementById('selectedUnitWarnings');
      if (!warningContainer) {
        warningContainer = document.createElement('div');
        warningContainer.id = 'selectedUnitWarnings';
        warningContainer.className = 'mt-2';
        selectedSubjectsInfo.appendChild(warningContainer);
      }

      if (!warnings.length) {
        warningContainer.innerHTML = '';
        return;
      }

      warningContainer.innerHTML = warnings.map(w => `
        <div class="alert alert-warning py-2 mb-2">
          <strong>${formatSemesterLabel(w.ys)}:</strong>
          ${w.afterTotal.toFixed(1)} / ${w.maxUnits.toFixed(1)} units
          <span class="ms-2">(Over by ${w.overBy.toFixed(1)} units)</span>
        </div>
      `).join('');
    }

    // Validate prerequisites for a selected subject-checkbox using its data-prereq
    function checkPrerequisites(checkbox) {
      const year = parseInt(checkbox.dataset.year || '0', 10);
      const sem = parseInt(checkbox.dataset.sem || '0', 10);
      const raw = (checkbox.dataset.prereq || '').trim();

      // Extra rule: if the student has no subjects in 1st Year • 1st Semester,
      // block selection in specific higher semesters even when PRE-REQ is "None".
      // This applies when clicking subjects whose PRE-REQ is shown as "None".
      const needsFirstYearFirstSem =
        (year === 2 && (sem === 1 || sem === 2)) || // SECOND YEAR • FIRST/SECOND SEMESTER
        (year === 3 && (sem === 1 || sem === 2)) || // THIRD YEAR • FIRST/SECOND SEMESTER
        (year === 4 && (sem === 1 || sem === 2));   // FOURTH YEAR • FIRST/SECOND SEMESTER

      const hasFirstYearFirstSem = !!window.hasFirstYearFirstSem;
      const rawLower = raw.toLowerCase();

      if (needsFirstYearFirstSem && !hasFirstYearFirstSem && (!raw || ['none', 'n/a', '-', 'none '].includes(rawLower))) {
        const subjectCode = checkbox.value || checkbox.dataset.code || '';
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'First Year First Semester Required',
            html: `
              <p>You cannot select <strong>${subjectCode}</strong> yet.</p>
              <p>Please fill the <strong>First Year • First Semester</strong> subjects first.</p>
            `,
            confirmButtonText: 'OK'
          });
        } else {
          alert('Please fill the First Year • First Semester subjects first.');
        }
        return false;
      }

      if (!raw) return true;

      const lower = raw.toLowerCase();
      if (['none', 'n/a', '-', ''].includes(lower)) return true;

      const parts = raw.split(',').map(p => p.trim()).filter(Boolean);
      const missing = [];

      parts.forEach(p => {
        const lowerP = p.toLowerCase();
        // Skip classification-based prerequisites like "Regular 3rd Year"
        if (lowerP.includes('regular')) return;
        // Ignore entries without any digits (likely classification text)
        if (!/[0-9]/.test(p)) return;

        const norm = normalizeCode(p);
        if (!passedSubjectsSet.has(norm)) {
          missing.push(p);
        }
      });

      if (missing.length === 0) return true;

      if (typeof Swal !== 'undefined') {
        const subjectCode = checkbox.value || checkbox.dataset.code || '';
        const plural = missing.length > 1;
        Swal.fire({
          icon: 'error',
          title: 'Pre-requisite Not Met',
          html: `
            <p>You cannot select <strong>${subjectCode}</strong> because the following prerequisite${plural ? 's are' : ' is'} not passed:</p>
            <ul><li>${missing.join('</li><li>')}</li></ul>
            <p style="margin-top:8px;">Please complete and pass ${plural ? 'these subjects' : 'this subject'} first.</p>
          `,
          confirmButtonText: 'OK'
        });
      } else {
        alert('Pre-requisite not met. Missing: ' + missing.join(', '));
      }

      return false;
    }
    
    // Update selected subjects display
    function updateSelectedDisplay() {
      const summary = summarizeCheckedSubjects();
      const selectedCheckboxes = summary.checked;
      const count = selectedCheckboxes.length;
      const totalUnits = summary.totalSelectedUnits;
      const overloadWarnings = buildOverloadWarnings(summary);
      const currentUnitsBySemester = getCurrentSemesterUnitsBySemester();

      updateSemesterLiveBadges(summary, overloadWarnings);
      updateFloatingCounter(summary, overloadWarnings);
        
        selectedCount.textContent = count + ' subject' + (count !== 1 ? 's' : '') + ' (' + totalUnits.toFixed(1) + ' units)';
        
      // Update badge using curriculum-based per-semester limits (not fixed thresholds)
      const selectedYearSem = (bulkYearSem && bulkYearSem.value) ? bulkYearSem.value : '';
      if (selectedYearSem) {
        const selectedInSem = summary.selectedUnitsBySemester[selectedYearSem] || 0;
        const currentInSem = currentUnitsBySemester[selectedYearSem] || 0;
        const maxInSem = getMaxUnitsForSemester(selectedYearSem);
        const totalAfter = currentInSem + selectedInSem;
        totalUnitsBadge.textContent = `${selectedInSem.toFixed(1)} sel | ${totalAfter.toFixed(1)} / ${maxInSem.toFixed(1)}`;
      } else {
        totalUnitsBadge.textContent = totalUnits.toFixed(1) + ' units';
      }

        totalUnitsBadge.className = 'badge fs-6';

      if (selectedYearSem) {
        const selectedInSem = summary.selectedUnitsBySemester[selectedYearSem] || 0;
        const currentInSem = currentUnitsBySemester[selectedYearSem] || 0;
        const maxInSem = getMaxUnitsForSemester(selectedYearSem);
        const totalAfter = currentInSem + selectedInSem;
        totalUnitsBadge.classList.add(totalAfter > maxInSem ? 'bg-danger' : 'bg-success');
        } else {
        totalUnitsBadge.classList.add(overloadWarnings.length > 0 ? 'bg-danger' : 'bg-primary');
        }
        
        if (count > 0) {
            selectedSubjectsInfo.style.display = 'block';
          renderUnitWarnings(overloadWarnings);
            
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
            
            bulkSaveBtn.disabled = count === 0;
        } else {
            renderUnitWarnings([]);
            selectedSubjectsInfo.style.display = 'none';
            bulkSaveBtn.disabled = true;
        }

          return overloadWarnings;
    }

            // Initialize live badges/counters once page is ready
            createSemesterLiveBadges();
            updateSelectedDisplay();
    
    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            subjectCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedDisplay();
        });
    }
    
    // Individual checkbox changes with prerequisite validation
    subjectCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        // Only validate when trying to select the subject
        if (this.checked) {
          const ok = checkPrerequisites(this);
          if (!ok) {
            this.checked = false;
            return;
          }
        }
        const overloadWarnings = updateSelectedDisplay();

        // Show a reminder immediately when the clicked subject pushes units over limit.
        if (this.checked) {
          const ys = `${this.dataset.year || ''}-${this.dataset.sem || ''}`;
          const warning = overloadWarnings.find(w => w.ys === ys);
          if (warning) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: 'Unit Reminder',
                html: `
                  <p><strong>${formatSemesterLabel(warning.ys)}</strong> is over the unit limit.</p>
                  <p>Current units: ${warning.currentSemesterUnits.toFixed(1)} units</p>
                  <p>Selected now: ${warning.selectedUnits.toFixed(1)} units</p>
                  <p>Total: <strong>${warning.afterTotal.toFixed(1)}</strong> / ${warning.maxUnits.toFixed(1)} units</p>
                  <p>Over by: <strong>${warning.overBy.toFixed(1)}</strong> units</p>
                `,
                confirmButtonText: 'OK'
              });
            } else {
              alert(`${formatSemesterLabel(warning.ys)} is over the unit limit by ${warning.overBy.toFixed(1)} units.`);
            }
          }
        }
      });
    });
    
    // Year-semester selection change
    bulkYearSem.addEventListener('change', function() {
        updateSelectedDisplay();
        const hasSelection = document.querySelectorAll('.subject-checkbox:checked').length > 0;
        bulkSaveBtn.disabled = !hasSelection;
    });
    
    // Clear selection
    clearSelectionBtn.addEventListener('click', function() {
        subjectCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
      if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
      }
        updateSelectedDisplay();
    });
    
    // Bulk save functionality
    bulkSaveBtn.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.subject-checkbox:checked');
        const yearSem = bulkYearSem.value;
        const summary = summarizeCheckedSubjects();
        const overloadWarnings = buildOverloadWarnings(summary);
        
        if (selectedCheckboxes.length === 0) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: 'No Subjects Selected',
                text: 'Please select at least one subject.',
                confirmButtonText: 'OK'
              });
            } else {
              alert('Please select at least one subject.');
            }
            return;
        }

        // Block save if any affected semester exceeds max units.
        if (overloadWarnings.length > 0) {
          const lines = overloadWarnings.map(w =>
            `<li><strong>${formatSemesterLabel(w.ys)}</strong>: ${w.afterTotal.toFixed(1)} / ${w.maxUnits.toFixed(1)} (over by ${w.overBy.toFixed(1)})</li>`
          ).join('');
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Unit Limit Exceeded',
              html: `<p>Please fix overload before saving:</p><ul style="text-align:left;">${lines}</ul>`,
              confirmButtonText: 'Okay'
            });
          } else {
            alert('Unit limit exceeded in selected semesters. Please reduce selection.');
          }
          return;
        }
        
        // Get program and unit limits from server-side data
        const program = selectedProgram;
        
        // Debug logging
        console.log('Program:', program);
        console.log('Unit limits:', unitLimits);
        console.log('YearSem:', yearSem);
        
        const [year, sem] = yearSem ? yearSem.split('-') : ['', ''];
        const studentId = '<?= htmlspecialchars($studentId) ?>';
        
        // Prepare data for saving
        const subjects = [];
        selectedCheckboxes.forEach(checkbox => {
          const subjectYear = checkbox.dataset.year || year;
          const subjectSem = checkbox.dataset.sem || sem;
            subjects.push({
                course_code: checkbox.value,
                course_title: checkbox.dataset.title,
                total_units: checkbox.dataset.units,
                lec_units: checkbox.dataset.lec,
                lab_units: checkbox.dataset.lab,
                prerequisites: checkbox.dataset.prereq,
            year_level: subjectYear,
            semester: subjectSem
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
            subjects: subjects,
            program: program
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
            const message = data.message || `Successfully saved ${subjects.length} subjects to irregular database.`;
            Swal.fire({
              icon: 'success',
              title: 'Saved!',
              text: message,
              confirmButtonText: 'OK'
            }).then(() => {
              // Clear selection after successful save and refresh to show updated data
              clearSelectionBtn.click();
              window.location.reload();
            });
            } else {
            Swal.fire({
              icon: 'error',
              title: 'Save Failed',
              text: data.message || 'Error saving subjects. Please try again.',
              confirmButtonText: 'OK'
            });
            }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error saving subjects. Please try again.',
          confirmButtonText: 'OK'
          });
        });
      });
    
    // Cross-program subject loading for irregular students
    const otherProgramBtn = document.getElementById('openOtherProgramModal');
    const otherProgramModalEl = document.getElementById('otherProgramModal');
    const otherProgramSelect = document.getElementById('otherProgramSelect');
    const otherYearSelect = document.getElementById('otherYearSelect');
    const otherSemSelect = document.getElementById('otherSemSelect');
    const loadOtherBtn = document.getElementById('loadOtherProgramSubjects');
    const otherTableBody = document.querySelector('#otherProgramSubjectsTable tbody');

    const studentIdForOther = '<?= htmlspecialchars($studentId) ?>';
    const limitProgramKey = '<?= htmlspecialchars($program ?: 'BSCS') ?>';

    if (otherProgramBtn && otherProgramModalEl && otherProgramSelect && otherYearSelect && otherSemSelect && loadOtherBtn && otherTableBody && studentIdForOther) {
      const otherProgramModal = new bootstrap.Modal(otherProgramModalEl);

      otherProgramBtn.addEventListener('click', function() {
        otherTableBody.innerHTML = '';
        // Auto-load courses for the student's program with default year/semester
        const defaultYear = otherYearSelect.value || '1';
        const defaultSem = otherSemSelect.value || '1';
        const prog = otherProgramSelect.value;
        
        if (prog) {
          // Auto-load courses after showing modal
          setTimeout(() => {
            otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading subjects for your program...</td></tr>';
            
            const url = `stueval.php?action=get_other_program_subjects&student_id=${encodeURIComponent(studentIdForOther)}&program=${encodeURIComponent(prog)}&year=${encodeURIComponent(defaultYear)}&sem=${encodeURIComponent(defaultSem)}`;
            
            fetch(url)
              .then(r => r.json())
              .then(data => {
                if (!data.success) {
                  otherTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.message || 'Failed to load subjects.'}</td></tr>`;
                  return;
                }

                const rows = data.data || [];
                if (!rows.length) {
                  otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No subjects found for this program and semester.</td></tr>';
                  return;
                }

                otherTableBody.innerHTML = '';
                rows.forEach(r => {
                  const tr = document.createElement('tr');
                  const units = parseFloat(r.units || 0) || 0;
                  tr.innerHTML = `
                    <td>${r.code}</td>
                    <td>${r.title}</td>
                    <td class="text-center">${r.lec ?? '0'}</td>
                    <td class="text-center">${r.lab ?? '0'}</td>
                    <td class="text-center">${units.toFixed(1)}</td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-primary add-other-subject"
                              data-code="${r.code}"
                              data-title="${r.title}"
                              data-lec="${r.lec ?? '0'}"
                              data-lab="${r.lab ?? '0'}"
                              data-units="${units.toFixed(1)}"
                              data-year="${defaultYear}"
                              data-sem="${defaultSem}"
                              data-program="${prog}">
                        <i class="bi bi-plus-circle"></i> Add
                      </button>
                    </td>
                  `;
                  otherTableBody.appendChild(tr);
                });
              })
              .catch(err => {
                console.error('Error loading other program subjects:', err);
                otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading subjects.</td></tr>';
              });
          }, 500);
        }
        
        otherProgramModal.show();
      });

      loadOtherBtn.addEventListener('click', function() {
        const prog = otherProgramSelect.value;
        const year = otherYearSelect.value;
        const sem = otherSemSelect.value;

        if (!prog || !year || !sem) {
          Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please select year level and semester.',
            confirmButtonText: 'OK'
          });
          return;
        }

        otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Loading subjects...</td></tr>';

        const url = `stueval.php?action=get_other_program_subjects&student_id=${encodeURIComponent(studentIdForOther)}&program=${encodeURIComponent(prog)}&year=${encodeURIComponent(year)}&sem=${encodeURIComponent(sem)}`;

        fetch(url)
          .then(r => r.json())
          .then(data => {
            if (!data.success) {
              otherTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.message || 'Failed to load subjects.'}</td></tr>`;
              return;
            }

            const rows = data.data || [];
            if (!rows.length) {
              otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No subjects found for this program and semester.</td></tr>';
              return;
            }

            otherTableBody.innerHTML = '';
            rows.forEach(r => {
              const tr = document.createElement('tr');
              const units = parseFloat(r.units || 0) || 0;
              tr.innerHTML = `
                <td>${r.code}</td>
                <td>${r.title}</td>
                <td class="text-center">${r.lec ?? '0'}</td>
                <td class="text-center">${r.lab ?? '0'}</td>
                <td class="text-center">${units.toFixed(1)}</td>
                <td class="text-center">
                  <button class="btn btn-sm btn-primary add-other-subject"
                          data-code="${r.code}"
                          data-title="${r.title}"
                          data-lec="${r.lec ?? '0'}"
                          data-lab="${r.lab ?? '0'}"
                          data-units="${units.toFixed(1)}"
                          data-year="${year}"
                          data-sem="${sem}"
                          data-program="${prog}">
                    <i class="bi bi-plus-circle"></i> Add
                  </button>
                </td>
              `;
              otherTableBody.appendChild(tr);
            });
          })
          .catch(err => {
            console.error('Error loading other program subjects:', err);
            otherTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading subjects.</td></tr>';
          });
      });

      // Handle clicking "Add" for other-program subjects
      otherTableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-other-subject');
        if (!btn) return;
        e.preventDefault();

        const code = btn.getAttribute('data-code') || '';
        const title = btn.getAttribute('data-title') || '';
        const lec = parseFloat(btn.getAttribute('data-lec') || '0') || 0;
        const lab = parseFloat(btn.getAttribute('data-lab') || '0') || 0;
        const units = parseFloat(btn.getAttribute('data-units') || '0') || 0;
        const year = btn.getAttribute('data-year') || '1';
        const sem = btn.getAttribute('data-sem') || '1';
        const prog = btn.getAttribute('data-program') || '';

        const yearSem = `${year}-${sem}`;

        // Unit limit check using existing unitLimits for the student's main program
        let currentUnits = 0;
        const semesterBlock = document.querySelector(`div.mb-4[data-ys="${yearSem}"]`);
        if (semesterBlock) {
          const irregularBadge = semesterBlock.querySelector('.badge.bg-success.rounded-pill');
          if (irregularBadge) {
            const match = irregularBadge.textContent.match(/([\d.]+)/);
            if (match) {
              currentUnits = parseFloat(match[1]);
            }
          }
        }

        const totalUnitsAfter = currentUnits + units;
        const limits = <?= json_encode($unitLimits) ?>;
        let maxUnits = limits[limitProgramKey] && limits[limitProgramKey][yearSem]
          ? limits[limitProgramKey][yearSem].max
          : 26;

        if (totalUnitsAfter > maxUnits) {
          const overBy = (totalUnitsAfter - maxUnits).toFixed(1);
          Swal.fire({
            icon: 'error',
            title: 'Unit Limit Exceeded',
            html: `
              <div style="text-align:left;">
                <p><strong>Cannot add subject:</strong> Unit limit would be exceeded.</p>
                <hr>
                <p><strong>Current irregular units:</strong> ${currentUnits.toFixed(1)}</p>
                <p><strong>Adding:</strong> ${units.toFixed(1)} units</p>
                <p><strong>Total after adding:</strong> ${totalUnitsAfter.toFixed(1)} units</p>
                <p><strong>Maximum allowed:</strong> ${maxUnits} units</p>
                <p><strong>Over limit by:</strong> ${overBy} units</p>
              </div>
            `,
            confirmButtonText: 'OK'
          });
          return;
        }

        // Save as irregular via existing bulk_save_irregular endpoint (single subject)
        const payload = {
          action: 'bulk_save_irregular',
          student_id: studentIdForOther,
          program: prog,
          subjects: [
            {
              course_code: code,
              course_title: title,
              total_units: units,
              lec_units: lec,
              lab_units: lab,
              prerequisites: '',
              year_level: year,
              semester: sem
            }
          ]
        };

        fetch('stueval.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(payload)
        })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Subject Added',
                text: data.message || 'Subject added to irregular subjects successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                otherProgramModal.hide();
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Add Failed',
                text: data.message || 'Failed to add subject.',
                confirmButtonText: 'OK'
              });
            }
          })
          .catch(err => {
            console.error('Error saving other program subject:', err);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Error saving subject. Please try again.',
              confirmButtonText: 'OK'
            });
          });
      });
    }
    });

    // Global guard: block evaluation UI when enrollment period is closed
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.evaluationOpen !== 'undefined' && !window.evaluationOpen) {
        const msg = (window.enrollmentPeriodMessage || '').trim() ||
          'The enrollment period for evaluation is currently closed.';

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: 'Evaluation Closed',
            text: msg,
            confirmButtonText: 'OK'
          });
        } else {
          alert(msg);
        }

        const selectors = [
          'button',
          'input[type="submit"]',
          'input[type="button"]',
          'select',
          'input[type="checkbox"]',
          'input[type="radio"]'
        ];

        document.querySelectorAll(selectors.join(',')).forEach(el => {
          el.disabled = true;
        });
      }
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
                        
                        // Close modal - page will update naturally
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