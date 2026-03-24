<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in and has dean privileges
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$response = ['success' => false, 'message' => ''];

// Handle different actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'assign_subject':
        // Validate required fields
        $required = ['student_id', 'course_code', 'course_title', 'year_semester', 'reason'];
        $missing = array_diff($required, array_keys(array_filter($_POST)));
        
        if (!empty($missing)) {
            $response['message'] = 'Missing required fields: ' . implode(', ', $missing);
            break;
        }
        
        $studentId = trim($_POST['student_id']);
        $courseCode = trim($_POST['course_code']);
        $courseTitle = trim($_POST['course_title']);
        $yearSemester = trim($_POST['year_semester']);
        $reason = in_array($_POST['reason'], ['failed', 'prerequisite', 'other']) ? $_POST['reason'] : 'other';
        $notes = trim($_POST['notes'] ?? '');
        $assignedBy = $_SESSION['user_id'];
        
        // Check if this assignment already exists and is active
        $checkStmt = $conn->prepare("
            SELECT id FROM student_subjects 
            WHERE student_id = ? AND course_code = ? AND year_semester = ? AND is_active = 1
            LIMIT 1
        ");
        
        if (!$checkStmt) {
            $response['message'] = 'Database error: ' . $conn->error;
            break;
        }
        
        $checkStmt->bind_param('sss', $studentId, $courseCode, $yearSemester);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();
        
        if ($exists) {
            $response['message'] = 'This subject has already been assigned to the student for the selected semester.';
            break;
        }
        
        // Insert the new assignment
        $stmt = $conn->prepare("
            INSERT INTO student_subjects 
            (student_id, course_code, course_title, year_semester, reason, notes, assigned_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            $response['message'] = 'Database error: ' . $conn->error;
            break;
        }
        
        $stmt->bind_param('sssssss', 
            $studentId, 
            $courseCode, 
            $courseTitle, 
            $yearSemester, 
            $reason,
            $notes,
            $assignedBy
        );
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Subject assigned successfully',
                'id' => $conn->insert_id
            ];
        } else {
            $response['message'] = 'Failed to assign subject: ' . $stmt->error;
        }
        
        $stmt->close();
        break;
        
    case 'remove_assignment':
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $response['message'] = 'Invalid assignment ID';
            break;
        }
        
        // Mark the assignment as inactive instead of deleting it
        $stmt = $conn->prepare("
            UPDATE student_subjects 
            SET is_active = 0 
            WHERE id = ? AND assigned_by = ?
        ");
        
        if (!$stmt) {
            $response['message'] = 'Database error: ' . $conn->error;
            break;
        }
        
        $assignedBy = $_SESSION['user_id'];
        $stmt->bind_param('is', $id, $assignedBy);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Assignment removed successfully'
            ];
        } else {
            $response['message'] = 'Failed to remove assignment: ' . $stmt->error;
        }
        
        $stmt->close();
        break;
        
    default:
        $response['message'] = 'Invalid action';
        break;
}

echo json_encode($response);
$conn->close();
?>
