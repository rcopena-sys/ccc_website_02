<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get and validate input
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

try {
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception('You cannot delete your own account');
    }

    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM signin_db WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('User not found or already deleted');
        }
    } else {
        throw new Exception('Failed to execute query');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
