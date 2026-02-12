<?php
header('Content-Type: application/json');

// Database connection
require_once '../config/db_connect.php';

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['semester'], $input['courses'], $input['student_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$semester = $input['semester'];
$courses = $input['courses'];
$student_id = $input['student_id'];

if (!is_array($courses) || empty($courses)) {
    echo json_encode(['success' => false, 'message' => 'No courses selected']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE irregular_db SET year_semester = ? WHERE student_id = ? AND course_code = ?");
    
    $updated = 0;
    
    foreach ($courses as $course_code) {
        $stmt->bind_param('sss', $semester, $student_id, $course_code);
        if ($stmt->execute()) {
            $updated++;
        }
    }
    
    // Commit the transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Updated $updated course(s) to semester $semester"
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
