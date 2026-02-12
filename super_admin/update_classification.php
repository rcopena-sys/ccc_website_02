<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate input
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$classification = isset($_POST['classification']) ? trim($_POST['classification']) : '';

// Validate input
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
