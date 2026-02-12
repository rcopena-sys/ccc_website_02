<?php
header('Content-Type: application/json');

// Database connection
require_once 'db.php';

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);

// Check action type
if (isset($input['action']) && $input['action'] === 'update_irregular_status') {
    handleIrregularStatusUpdate($conn, $input);
    exit;
}

// Original semester update handling
if (!isset($input['semester'], $input['courses'], $input['student_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$semester = $input['semester'];
$courses = $input['courses'];
$student_id = $input['student_id'];

/**
 * Check if a course checkbox should be disabled due to failed prerequisites
 */
function shouldDisableCheckbox($prereq, $gradesByCode) {
    // If no prerequisites, don't disable
    $prereq = trim($prereq);
    if (empty($prereq) || $prereq === 'None' || $prereq === 'none' || $prereq === 'N/A' || $prereq === '-' || $prereq === 'None') {
        return false;
    }
    
    // Check each prerequisite
    $prereqCodes = array_map('trim', explode(',', $prereq));
    foreach ($prereqCodes as $prereqCode) {
        $prereqCode = trim($prereqCode);
        if (empty($prereqCode)) continue; // Skip empty codes
        
        $prereqGrade = trim((string)($gradesByCode[$prereqCode] ?? ''));
        
        // If prerequisite has no grade, disable the checkbox
        if ($prereqGrade === '') {
            return true;
        }
        // If prerequisite has a grade and it's failing (>= 3.0), disable the checkbox
        elseif (is_numeric($prereqGrade) && floatval($prereqGrade) >= 3.0) {
            return true;
        }
    }
    
    return false;
}

/**
 * Handle updating irregular status for a course
 */
function handleIrregularStatusUpdate($conn, $data) {
    if (!isset($data['course_code'], $data['student_id'], $data['semester'], $data['is_irregular'], $data['course_details'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }

    $courseCode = $data['course_code'];
    $studentId = $data['student_id'];
    $isIrregular = (bool)$data['is_irregular'];
    $courseDetails = $data['course_details'];
    list($year, $sem) = explode('-', $data['semester']);
    
    // If trying to mark as irregular, check prerequisites first
    if ($isIrregular) {
        // Get student's grades
        $gradesByCode = [];
        $gradeStmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ?");
        $gradeStmt->bind_param('s', $studentId);
        $gradeStmt->execute();
        $gradeResult = $gradeStmt->get_result();
        
        while ($gradeRow = $gradeResult->fetch_assoc()) {
            $gradesByCode[$gradeRow['course_code']] = $gradeRow;
        }
        
        // Check if prerequisites are failed
        if (shouldDisableCheckbox($courseDetails['prereq'], $gradesByCode)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot mark course as irregular. Failed prerequisites detected.'
            ]);
            return;
        }
    }
    
    try {
        if ($isIrregular) {
            // Check if the course already exists in irregular_db
            $checkStmt = $conn->prepare("SELECT id FROM irregular_db WHERE student_id = ? AND course_code = ?");
            $checkStmt->bind_param('ss', $studentId, $courseCode);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();
            
            if (!$exists) {
                // Insert into irregular_db with all course details
                $semesterStr = "$year-$sem";
                $lecUnits = (float)$courseDetails['lec'];
                $labUnits = (float)$courseDetails['lab'];
                $totalUnits = (float)$courseDetails['units'];
                $program = $courseDetails['program'] ?? '';
                
                $insertStmt = $conn->prepare("
                    INSERT INTO irregular_db 
                    (student_id, course_code, course_title, lec_units, lab_units, total_units, 
                     prerequisites, year_semester, year_level, semester, status, program)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                ");
                
                $insertStmt->bind_param(
                    'sssdddsssss',
                    $studentId,
                    $courseCode,
                    $courseDetails['title'],  // Course title from the row
                    $lecUnits,               // Lecture units
                    $labUnits,               // Lab units
                    $totalUnits,             // Total units
                    $courseDetails['prereq'], // Prerequisites
                    $semesterStr,            // Year-semester (e.g., "1-1")
                    $year,                   // Year level
                    $sem,                    // Semester
                    $program                 // Program
                );
                $insertStmt->execute();
            } else {
                // Update existing record with new details
                $semesterStr = "$year-$sem";
                $lecUnits = (float)$courseDetails['lec'];
                $labUnits = (float)$courseDetails['lab'];
                $totalUnits = (float)$courseDetails['units'];
                $program = $courseDetails['program'] ?? '';
                
                $updateStmt = $conn->prepare("
                    UPDATE irregular_db SET 
                    course_title = ?, 
                    lec_units = ?, 
                    lab_units = ?, 
                    total_units = ?, 
                    prerequisites = ?,
                    year_semester = ?,
                    year_level = ?,
                    semester = ?,
                    program = ?
                    WHERE student_id = ? AND course_code = ?
                ");
                
                $updateStmt->bind_param(
                    'sdddsssssss',
                    $courseDetails['title'],  // Course title
                    $lecUnits,               // Lecture units
                    $labUnits,               // Lab units
                    $totalUnits,             // Total units
                    $courseDetails['prereq'], // Prerequisites
                    $semesterStr,            // Year-semester
                    $year,                   // Year level
                    $sem,                    // Semester
                    $program,                // Program
                    $studentId,              // Student ID
                    $courseCode              // Course code
                );
                $updateStmt->execute();
            }
        } else {
            // Remove from irregular_db if exists
            $deleteStmt = $conn->prepare("DELETE FROM irregular_db WHERE student_id = ? AND course_code = ?");
            $deleteStmt->bind_param('ss', $studentId, $courseCode);
            $deleteStmt->execute();
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Split semester into year and semester (format: "1-1" or "1-2")
list($year, $sem) = explode('-', $semester);

if (!is_array($courses) || empty($courses)) {
    echo json_encode(['success' => false, 'message' => 'No courses selected']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // First, check if the courses exist in irregular_db
    $placeholders = str_repeat('?,', count($courses) - 1) . '?';
    $stmt = $conn->prepare("SELECT course_code FROM irregular_db WHERE student_id = ? AND course_code IN ($placeholders)");
    $types = str_repeat('s', count($courses) + 1);
    $params = array_merge([$student_id], $courses);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $existingCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $existingCourseCodes = array_column($existingCourses, 'course_code');
    
    // Prepare the update statement for existing records
    $updateStmt = $conn->prepare("UPDATE irregular_db SET year_semester = ?, year_level = ?, semester = ? WHERE student_id = ? AND course_code = ?");
    
    // Prepare the insert statement for new records
    $insertStmt = $conn->prepare("INSERT INTO irregular_db (student_id, course_code, year_semester, year_level, semester, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    
    $updated = 0;
    $inserted = 0;
    
    foreach ($courses as $course_code) {
        if (in_array($course_code, $existingCourseCodes)) {
            // Update existing record
            $updateStmt->bind_param('sssss', $semester, $year, $sem, $student_id, $course_code);
            if ($updateStmt->execute()) {
                $updated++;
            }
        } else {
            // Insert new record
            $insertStmt->bind_param('sssss', $student_id, $course_code, $semester, $year, $sem);
            if ($insertStmt->execute()) {
                $inserted++;
            }
        }
    }
    
    // Commit the transaction
    $conn->commit();
    
    $message = [];
    if ($updated > 0) $message[] = "Updated $updated course(s)";
    if ($inserted > 0) $message[] = "Added $inserted new course(s)";
    
    echo json_encode([
        'success' => true,
        'message' => implode(' and ', $message) . " to semester $semester"
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
