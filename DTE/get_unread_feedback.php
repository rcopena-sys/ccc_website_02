<?php
include 'db.php';
header('Content-Type: application/json');

// Get count of unread feedback
$query = "SELECT COUNT(*) as count FROM feedback_db WHERE is_read = 0";
$result = $conn->query($query);
$count = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $count = $row['count'];
}

// Get latest unread feedback for dropdown
$latest_feedback = [];
$query = "SELECT * FROM feedback_db WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $latest_feedback[] = [
            'id' => $row['id'],
            'email' => $row['email'],
            'message' => $row['message'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => $count,
    'feedback' => $latest_feedback,
    'has_new' => $count > 0
]);
?>
