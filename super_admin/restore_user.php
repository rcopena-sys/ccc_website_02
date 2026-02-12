<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is admin
if ($_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF token verification failed']);
    exit;
}

// Get user ID
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

// Prevent self-modification
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot modify your own account']);
    exit;
}

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Update user status to Active
    $updateSql = "UPDATE signin_db SET status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $new_status = 'Active';
    $updateStmt->bind_param("si", $new_status, $user_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'User restored successfully']);
} catch (Exception $e) {
    // Rollback on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($updateStmt)) {
        $updateStmt->close();
    }
    $conn->close();
}
?>
    $conn->close();
}
?>
