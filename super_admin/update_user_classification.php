<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin (role_id 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['user_id']) || !isset($_POST['classification'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$classification = trim($_POST['classification']);

// Validate classification
if (!in_array($classification, ['Regular', 'Irregular'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid classification']);
    exit();
}

try {
    // Update the classification in the database
    $stmt = $conn->prepare("UPDATE signin_db SET classification = ? WHERE id = ?");
    $stmt->bind_param("si", $classification, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Classification updated successfully']);
    } else {
        throw new Exception('Failed to update classification');
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log('Error updating classification: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating classification']);
}

$conn->close();
?>
