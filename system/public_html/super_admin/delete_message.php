<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if message ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit();
}

$message_id = (int)$_POST['id'];

// Update message status to 'deleted' instead of actually deleting it
$query = "UPDATE messages_db SET status = 'deleted', updated_at = NOW() WHERE id = ? AND recipient_type = 'admin'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);

if ($stmt->execute()) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        // No rows affected - message doesn't exist or already deleted
        echo json_encode(['success' => false, 'message' => 'Message not found or already deleted']);
    }
} else {
    // Error executing the query
    error_log("Error deleting message: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
}

$stmt->close();
$conn->close();
?>
