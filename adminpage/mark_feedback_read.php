<?php
include 'db.php';
header('Content-Type: application/json');

if (isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $query = "UPDATE feedback_db SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $feedback_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'No feedback ID provided']);
}
?>
