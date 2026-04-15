<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$status  = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowed_statuses = ['Active', 'Inactive', 'Archived'];

if (!$user_id || !in_array($status, $allowed_statuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or status']);
    exit();
}

try {
    $stmt = $conn->prepare('UPDATE signin_db SET status = ? WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('si', $status, $user_id);

    if ($stmt->execute()) {
        // Treat a successful execute as success even if affected_rows is 0,
        // to avoid false errors when the status is already set to the same value.
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        throw new Exception('Failed to execute query');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
