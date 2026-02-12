<?php
include 'db.php';

header('Content-Type: application/json');

$response = ['hasAccount' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = $_GET['student_id'] ?? '';
    $email = $_GET['email'] ?? '';
    
    if (empty($student_id) || empty($email)) {
        $response['message'] = 'Student ID and email are required';
        echo json_encode($response);
        exit;
    }
    
    // Check if student already has an account in signin_db
    $check_query = "SELECT COUNT(*) as count FROM signin_db WHERE student_id = '$student_id' OR email = '$email'";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $row = $check_result->fetch_assoc()) {
        if ($row['count'] > 0) {
            $response['hasAccount'] = true;
            $response['message'] = 'Student already has an account';
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>
