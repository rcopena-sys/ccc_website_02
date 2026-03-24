<?php
require_once '../student/db_connect.php';

// Handle event operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $date = $_POST['date'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    header('Content-Type: application/json');

    try {
        switch ($action) {
            case 'add':
                $stmt = $conn->prepare("INSERT INTO events (date, title, description) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $date, $title, $description);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Event added successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error adding event: ' . $conn->error]);
                }
                break;

            case 'edit':
                $stmt = $conn->prepare("UPDATE events SET title = ?, description = ? WHERE date = ?");
                $stmt->bind_param("sss", $title, $description, $date);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating event: ' . $conn->error]);
                }
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM events WHERE date = ?");
                $stmt->bind_param("s", $date);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting event: ' . $conn->error]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
