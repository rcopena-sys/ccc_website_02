<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in and has appropriate role (1=admin, 2=registrar, 3=dean)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if message ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit();
}

$message_id = (int)$_POST['id'];
$user_id = $_SESSION['user_id'];

// Check if the message exists and the current user is the recipient
$check_query = "SELECT m.*, s.id as sender_id 
               FROM messages_db m
               INNER JOIN signin_db s ON m.sender_id = s.id
               WHERE m.id = ? AND (m.recipient_type = 'registrar' OR m.recipient_type = 'all')";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Message not found or access denied']);
    exit();
}

// Update the message status to 'read' and is_read to 1
$update_query = "UPDATE messages_db SET status = 'read', is_read = 1 WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $message_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    // Update the unread count in the session
    if (isset($_SESSION['unread_message_count']) && $_SESSION['unread_message_count'] > 0) {
        $_SESSION['unread_message_count']--;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update message status']);
}

$conn->close();
?>
