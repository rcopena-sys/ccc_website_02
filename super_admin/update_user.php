<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

header('Content-Type: application/json');

try {
    $user_id = intval($_POST['user_id'] ?? 0);
    $classification = clean_input($conn, $_POST['classification'] ?? 'Regular');
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    // Update the classification
    $stmt = $conn->prepare("UPDATE signin_db SET classification = ? WHERE id = ?");
    $stmt->bind_param("si", $classification, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Classification updated successfully',
            'classification' => $classification
        ]);
    } else {
        throw new Exception('Failed to update classification');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
