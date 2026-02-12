<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

header('Content-Type: application/json');

// Log the request
error_log('=== ARCHIVE REQUEST ===');
error_log('User ID received: ' . ($_POST['user_id'] ?? 'NOT SET'));
error_log('Current admin ID: ' . $_SESSION['user_id']);
error_log('Current admin role: ' . $_SESSION['role_id']);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    error_log('DENIED: Unauthorized access');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log('DENIED: Invalid CSRF token');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if (!isset($_POST['user_id'])) {
    error_log('DENIED: User ID not provided');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$user_id = intval($_POST['user_id']);
error_log('Processing archive for user ID: ' . $user_id);

// Prevent self-archive
if ($user_id === $_SESSION['user_id']) {
    error_log('DENIED: Self-archive attempt');
    echo json_encode(['success' => false, 'message' => 'You cannot archive your own account']);
    exit();
}

try {
    // Verify user exists
    $check_stmt = $conn->prepare("SELECT id, status FROM signin_db WHERE id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    $check_stmt->close();
    
    if (!$user) {
        error_log('FAILED: User not found - ID ' . $user_id);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    error_log('User found - Current status: "' . $user['status'] . '"');
    
    // Update user status to Inactive (prevents login)
    $stmt = $conn->prepare("UPDATE signin_db SET status = ? WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $new_status = 'Inactive';
    $stmt->bind_param("si", $new_status, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    error_log('UPDATE executed - Affected rows: ' . $affected_rows);
    
    // Verify the update worked
    $verify = $conn->prepare("SELECT status FROM signin_db WHERE id = ?");
    $verify->bind_param("i", $user_id);
    $verify->execute();
    $verify_result = $verify->get_result();
    $verify_user = $verify_result->fetch_assoc();
    $verify->close();
    
    error_log('Verification - New status: "' . ($verify_user['status'] ?? 'NULL') . '"');
    
    echo json_encode(['success' => true, 'message' => 'User has been set to inactive. They will no longer be able to login.']);
} catch (Exception $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
