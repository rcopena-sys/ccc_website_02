<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int)$_POST['id'];

// First, get the assignment details for logging
$stmt = $conn->prepare("SELECT * FROM assign_curriculum WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    exit;
}

$assignment = $result->fetch_assoc();
$stmt->close();

// Delete the assignment
$deleteStmt = $conn->prepare("DELETE FROM assign_curriculum WHERE id = ?");
$deleteStmt->bind_param("i", $id);

if ($deleteStmt->execute()) {
    // Log the deletion (you might want to log this to a separate table)
    $logMessage = sprintf(
        "Deleted assignment: Student ID %d, Program: %s, Fiscal Year: %s",
        $assignment['student_id'],
        $assignment['program'],
        $assignment['fiscal_year']
    );
    // You can log this to a file or database table
    // error_log($logMessage);
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete assignment']);
}

$deleteStmt->close();
$conn->close();
?>
