<?php
require_once 'db.php';

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
$year = (int)($_POST['year'] ?? date('Y'));
$semester = (int)($_POST['semester'] ?? 1);

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
    // Check if grade already exists for this student and course
    $checkStmt = $conn->prepare("SELECT id FROM grades_db WHERE student_id = ? AND course_code = ?");
    $checkStmt->bind_param('ss', $student_id, $course_code);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        // Update existing grade
        $stmt = $conn->prepare("UPDATE grades_db SET grade = ?, updated_at = NOW() WHERE student_id = ? AND course_code = ?");
        $stmt->bind_param('dss', $grade, $student_id, $course_code);
    } else {
        // Insert new grade
        $stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, course_title, grade, year, semester, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param('sssdii', $student_id, $course_code, $course_title, $grade, $year, $semester);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Grade saved successfully',
            'grade' => $grade,
            'course_code' => $course_code
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

$conn->close();
?>
