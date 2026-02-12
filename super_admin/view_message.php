<?php
session_start();
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// Check if message ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: notification.php?error=invalid_message_id");
    exit();
}

$message_id = (int)$_GET['id'];

// Get message details
$query = "SELECT m.*, s.firstname, s.lastname, s.email, s.course 
          FROM messages_db m
          JOIN signin_db s ON m.sender_id = s.student_id
          WHERE m.id = ? AND m.recipient_type = 'admin'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$message) {
    header("Location: notification.php?error=message_not_found");
    exit();
}

// Mark message as read if status column exists
$check_columns = $conn->query("SHOW COLUMNS FROM messages_db");
$has_status = false;
$has_updated_at = false;

while ($column = $check_columns->fetch_assoc()) {
    if ($column['Field'] === 'status') $has_status = true;
    if ($column['Field'] === 'updated_at') $has_updated_at = true;
}

if ($has_status) {
    // Build the update query based on available columns
    $update_sql = "UPDATE messages_db SET status = 'read', is_read = 1";
    if ($has_updated_at) {
        $update_sql .= ", updated_at = NOW()";
    }
    $update_sql .= " WHERE id = ?";
    
    $update = $conn->prepare($update_sql);
    $update->bind_param("i", $message_id);
    $update->execute();
    $update->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Super Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .message-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .message-body {
            line-height: 1.8;
            font-size: 1.05rem;
        }
        .back-btn {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Back Button -->
        <a href="notification.php" class="btn btn-outline-secondary back-btn">
            <i class="fas fa-arrow-left me-1"></i> Back to Notifications
        </a>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo htmlspecialchars($message['subject']); ?></h4>
            </div>
            <div class="card-body">
                <div class="message-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>From:</strong> 
                            <?php 
                            echo htmlspecialchars($message['firstname'] . ' ' . $message['lastname']);
                            echo ' (' . htmlspecialchars($message['email'] . ' - ' . $message['course'] . ')'); 
                            ?>
                        </div>
                        <div class="text-muted">
                            <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                    <?php
                    // Check if status column exists
                    $status_column_exists = isset($message['status']);
                    if ($status_column_exists): 
                    ?>
                    <div class="mb-2">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $message['status'] === 'read' ? 'success' : 'warning text-dark';
                        ?>">
                            <?php echo ucfirst($message['status']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="notification.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Notifications
                    </a>
                    <div>
                        <a href="reply_message.php?reply_to=<?php echo $message_id; ?>" class="btn btn-primary">
                            <i class="fas fa-reply me-1"></i> Reply
                        </a>
                        <a href="#" class="btn btn-outline-danger" id="deleteMessage" data-id="<?php echo $message_id; ?>">
                            <i class="fas fa-trash-alt me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Handle delete button click
        document.getElementById('deleteMessage').addEventListener('click', function(e) {
            e.preventDefault();
            const messageId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to delete the message
                    fetch('delete_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + messageId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Deleted!',
                                'The message has been deleted.',
                                'success'
                            ).then(() => {
                                window.location.href = 'notification.php';
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                data.message || 'Failed to delete the message.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the message.',
                            'error'
                        );
                    });
                }
            });
        });
    </script>
</body>
</html>
