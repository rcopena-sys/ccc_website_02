<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$response = [];
$user_id = $_SESSION['user_id'];

// Query to check messages for registrar
$query = "SELECT m.*, 
          CONCAT(s.firstname, ' ', s.lastname) as sender_name,
          s.role_id as sender_role
          FROM messages_db m 
          LEFT JOIN signin_db s ON m.sender_id = s.id
          WHERE (m.recipient_type = 'registrar' OR m.recipient_type = 'all' OR m.recipient_id = ?)
          AND (m.status IS NULL OR m.status != 'deleted')
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    $response['message_count'] = count($messages);
    $response['messages'] = $messages;
} else {
    $response['error'] = "Error preparing query: " . $conn->error;
}

// Also check the notifications
$query = "SELECT * FROM notifications_db WHERE role_id = 3 OR user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
    $response['notification_count'] = count($notifications);
    $response['notifications'] = $notifications;
}

// Check user info
$query = "SELECT * FROM signin_db WHERE id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    $response['user'] = $user;
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
