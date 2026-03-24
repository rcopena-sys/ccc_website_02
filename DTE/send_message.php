<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $recipient_type = filter_input(INPUT_POST, 'recipient_type', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    $stmt = $conn->prepare("INSERT INTO messages_db 
        (sender_id, recipient_type, subject, message, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("isss", $sender_id, $recipient_type, $subject, $message);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Message sent successfully!";
    } else {
        $_SESSION['error'] = "Failed to send message. Please try again.";
    }
    
    $stmt->close();
}

header('Location: notification_page.php');
exit();
?>
