<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if required fields are provided
if (empty($_POST['student_id']) || empty($_POST['course_code'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$studentId = trim($_POST['student_id']);
$courseCode = trim($_POST['course_code']);
$courseTitle = trim($_POST['course_title'] ?? '');
$units = intval($_POST['units'] ?? 3); // Default to 3 units if not provided

try {
    // Check if the student exists
    $stmt = $conn->prepare("SELECT student_id FROM students_db WHERE student_id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Check if the course is already in the irregular schedule
    $stmt = $conn->prepare("SELECT * FROM irregular_db WHERE student_id = ? AND course_code = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param('ss', $studentId, $courseCode);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This course is already in the irregular schedule']);
        exit;
    }
    
    // Get year and semester from POST data
    $year = intval($_POST['year'] ?? 1);
    $sem = intval($_POST['sem'] ?? 1);
    
    // Add the course to the irregular schedule
    $stmt = $conn->prepare("INSERT INTO irregular_db (student_id, course_code, course_title, year, sem, units, date_added) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param('sssiid', $studentId, $courseCode, $courseTitle, $year, $sem, $units);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to add subject to irregular schedule");
    }
} catch (Exception $e) {
    error_log('Error in add_irregular_subject.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
