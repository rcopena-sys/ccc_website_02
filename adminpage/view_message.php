<?php
session_start();

// Debug: Dump session data
// error_log('Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header("Location: ../index.php");
    exit();
}

// For now, allow any logged-in user to test the page
// In production, uncomment the following lines:
/*
if ($_SESSION['role_id'] != 3) {
    // If not dean, redirect to appropriate dashboard
    if ($_SESSION['role_id'] == 1) {
        header("Location: ../super_admin/dashboard.php");
    } else if ($_SESSION['role_id'] == 2) {
        header("Location: dashboardr.php");
    } else if ($_SESSION['role_id'] == 4) {
        header("Location: ../student/dashboard.php");
    } else {
        header("Location: dashboard2.php");
    }
    exit();
}
*/

require_once '../db_connect.php';

// Check if message ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: notification_page.php?error=invalid_message_id");
    exit();
}

$message_id = (int)$_GET['id'];

// Get message details
$query = "SELECT m.*, s.firstname, s.lastname, s.email, s.course 
          FROM messages_db m
          LEFT JOIN signin_db s ON m.sender_id = s.id
          WHERE m.id = ? AND (m.recipient_type = 'dean' OR m.recipient_type = 'all')";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $reply_message = trim($_POST['reply_message'] ?? '');
    $subject = "Re: " . $message['subject'];
    $recipient_id = $message['sender_id'];
    
    if (!empty($reply_message)) {
        // Determine recipient type based on sender's role
        $recipient_type = 'student'; // default
        $get_role_query = "SELECT role_id FROM signin_db WHERE id = ?";
        $stmt = $conn->prepare($get_role_query);
        $stmt->bind_param("i", $recipient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            switch($row['role_id']) {
                case 1: $recipient_type = 'admin'; break;
                case 2: $recipient_type = 'registrar'; break;
                case 3: $recipient_type = 'dean'; break;
                case 4: $recipient_type = 'student'; break;
            }
        }
        $stmt->close();
        
        // Get the current user's student_id from signin_db
        $get_sender_query = "SELECT student_id FROM signin_db WHERE id = ?";
        $stmt = $conn->prepare($get_sender_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $sender_row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$sender_row || !isset($sender_row['student_id'])) {
            $error_message = "Error: Could not find your user account information.";
            $stmt->close();
        } else {
            // Insert reply into messages_db using student_id as sender_id
            $insert_query = "INSERT INTO messages_db 
                            (sender_id, recipient_type, recipient_id, subject, message, status) 
                            VALUES (?, ?, ?, ?, ?, 'unread')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssss", 
                $sender_row['student_id'], 
                $recipient_type,
                $recipient_id,
                $subject,
                $reply_message
            );
        
            if ($stmt->execute()) {
                $success_message = "Your reply has been sent successfully!";
            } else {
                $error_message = "Failed to send reply. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error_message = "Please enter a message.";
    }
}

if (!$message) {
    header("Location: notification_page.php?error=message_not_found");
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
    $update_sql .= " WHERE id = ? AND (status IS NULL OR status != 'read')";
    
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
    <title>View Message - Dean's Panel</title>  <link rel="icon" type="image/x-icon" href="favicon.ico">
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
            white-space: pre-wrap;
        }
        .back-btn {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Back Button -->
        <a href="notification_page.php" class="btn btn-outline-secondary back-btn">
            <i class="fas fa-arrow-left me-1"></i> Back to Notifications
        </a>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
                            if (!empty($message['email'])) {
                                echo ' (' . htmlspecialchars($message['email']);
                                if (!empty($message['course'])) {
                                    echo ' - ' . htmlspecialchars($message['course']);
                                }
                                echo ')';
                            }
                            ?>
                        </div>
                        <div class="text-muted">
                            <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                    <?php if ($has_status): ?>
                    <div class="mb-2">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo (isset($message['status']) && $message['status'] === 'read') ? 'success' : 'warning text-dark';
                        ?>">
                            <?php echo isset($message['status']) ? ucfirst($message['status']) : 'Unread'; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            
            <!-- Reply Form -->
            <div class="card-footer bg-light">
                <h5 class="mb-3">Reply to this Message</h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="reply_message" class="form-label">Your Reply</label>
                        <textarea class="form-control" id="reply_message" name="reply_message" rows="4" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" name="reply_submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="notification_page.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Notifications
                    </a>
                    <div>
                        <a href="compose_message.php?reply_to=<?php echo $message_id; ?>" class="btn btn-primary">
                            <i class="fas fa-reply me-1"></i> Reply
                        </a>
                        <button class="btn btn-outline-danger" id="deleteMessage" data-id="<?php echo $message_id; ?>">
                            <i class="fas fa-trash-alt me-1"></i> Delete
                        </button>
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
                                window.location.href = 'notification_page.php';
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
