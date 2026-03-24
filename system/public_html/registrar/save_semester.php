<?php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }
    
    $student_id = trim($data['student_id'] ?? '');
    $semester = trim($data['semester'] ?? '');
    $courses = $data['courses'] ?? [];
    
    if (empty($student_id) || empty($semester)) {
        throw new Exception('Student ID and semester are required');
    }
    
    if (empty($courses)) {
        throw new Exception('No courses to save');
    }
    
    $conn->begin_transaction();
    $savedCount = 0;
    
    foreach ($courses as $course) {
        $course_code = trim($course['code'] ?? '');
        $course_title = trim($course['title'] ?? '');
        $lec_units = floatval($course['lec'] ?? 0);
        $lab_units = floatval($course['lab'] ?? 0);
        $total_units = floatval($course['total_units'] ?? 0);
        $grade = trim($course['grade'] ?? '');
        
        if (empty($course_code) || empty($course_title)) {
            continue; // Skip invalid courses
        }
        
        // Parse year and semester from semester string (e.g., "1-1" -> year=1, sem=1)
        $semester_parts = explode('-', $semester);
        $year = intval($semester_parts[0] ?? 0);
        $sem = intval($semester_parts[1] ?? 0);
        
        if ($year === 0 || $sem === 0) {
            continue; // Skip invalid semester format
        }
        
        // Check if grade already exists
        $gradeCol = columnExists($conn, 'grades_db', 'final_grade') ? 'final_grade' : 'grade';
        $checkStmt = $conn->prepare("SELECT id FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
        $checkStmt->bind_param('sssi', $student_id, $course_code, $year, $sem);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
        
        if ($exists) {
            // Update existing grade
            $sql = "UPDATE grades_db SET {$gradeCol} = ?, course_title = ? WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssi', $grade, $course_title, $student_id, $course_code, $year, $sem);
        } else {
            // Insert new grade
            $sql = "INSERT INTO grades_db (student_id, course_code, course_title, year, sem, {$gradeCol}) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssis', $student_id, $course_code, $course_title, $year, $sem, $grade);
        }
        
        if ($stmt->execute()) {
            $savedCount++;
        }
        $stmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully saved {$savedCount} courses for semester {$semester}",
        'saved_count' => $savedCount
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to check if column exists
function columnExists(mysqli $conn, string $table, string $column): bool {
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}
?>
