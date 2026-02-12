<?php
require_once '../db_connect.php';
require_once '../config/global_func.php';

session_start();

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Get and validate input
$user_id = intval($_POST['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
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
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception('User not found or already deleted');
        }
    } else {
        throw new Exception('Failed to execute query');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
