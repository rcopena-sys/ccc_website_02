<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_connect.php';

header('Content-Type: application/json');

// Get POST data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data || !isset($data['student_id']) || !isset($data['grades'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit();
}

$student_id = $data['student_id'];
$grades = $data['grades'];

// Validate student ID matches session
if ($student_id !== $_SESSION['student_id']) {
    echo json_encode(['success' => false, 'message' => 'Student ID mismatch']);
    exit();
}

$conn->begin_transaction();

try {
    foreach ($grades as $grade_data) {
        $course_code = $grade_data['course_code'];
        $final_grade = $grade_data['grade'];
        
        // Validate grade range
        if ($final_grade < 1 || $final_grade > 5) {
            throw new Exception("Invalid grade range for course $course_code");
        }
        
        // Check if grade already exists
        $check_stmt = $conn->prepare("SELECT id FROM grades_db WHERE student_id = ? AND course_code = ?");
        $check_stmt->bind_param("ss", $student_id, $course_code);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($existing) {
            // Update existing grade
            $update_stmt = $conn->prepare("UPDATE grades_db SET final_grade = ?, updated_at = NOW() WHERE student_id = ? AND course_code = ?");
            $update_stmt->bind_param("dss", $final_grade, $student_id, $course_code);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new grade
            // Get course title from curriculum table
            $course_stmt = $conn->prepare("SELECT course_title, year_semester FROM curriculum WHERE course_code = ? LIMIT 1");
            $course_stmt->bind_param("s", $course_code);
            $course_stmt->execute();
            $course_result = $course_stmt->get_result();
            $course_title = '';
            $year_semester = '';
            if ($row = $course_result->fetch_assoc()) {
                $course_title = $row['course_title'];
                $year_semester = $row['year_semester'];
            }
            $course_stmt->close();
            
            // Determine year and semester from year_semester
            $year = 1;
            $sem = 1;
            if (!empty($year_semester)) {
                $parts = explode('-', $year_semester);
                if (count($parts) == 2) {
                    $year = (int)$parts[0];
                    $sem = (int)$parts[1];
                }
            }
            
            $insert_stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, course_title, final_grade, year, sem, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $insert_stmt->bind_param("sssdii", $student_id, $course_code, $course_title, $final_grade, $year, $sem);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Grades saved successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
