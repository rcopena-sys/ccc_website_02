<?php
session_start();
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../db_connect.php';

// Get feedback ID from URL
$feedback_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($feedback_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
    exit();
}

try {
    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM feedback_db WHERE id = ?");
    $stmt->bind_param("i", $feedback_id);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete feedback');
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
