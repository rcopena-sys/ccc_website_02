<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in and has appropriate role (1=admin, 2=dean, 3=registrar)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

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
          WHERE m.id = ? AND (m.recipient_type = 'registrar' OR m.recipient_type = 'all')";

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
        
        // Get the current user's information from signin_db
        $get_sender_query = "SELECT id, student_id FROM signin_db WHERE id = ?";
        $stmt = $conn->prepare($get_sender_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $sender_row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$sender_row) {
            $error_message = "Error: Could not find your user account. Please log in again.";
        } else {
            // Use user's ID as sender_id if student_id is not available
            $sender_id = !empty($sender_row['student_id']) ? $sender_row['student_id'] : 'user_' . $sender_row['id'];
            
            // Insert reply into messages_db
            $insert_query = "INSERT INTO messages_db 
                            (sender_id, recipient_type, recipient_id, subject, message, status) 
                            VALUES (?, ?, ?, ?, ?, 'unread')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssss", 
                $sender_id, 
                $recipient_type,
                $recipient_id,
                $subject,
                $reply_message
            );
    
            if ($stmt->execute()) {
            $success_message = "Your reply has been sent successfully!";
            
            // Create a notification for the recipient
            $notification_message = "New reply from Registrar: " . substr($reply_message, 0, 50) . "...";
            $notification_link = "view_message.php?id=" . $conn->insert_id;
            
            $notification_sql = "INSERT INTO notifications_db (user_id, sender_id, title, message, link, created_at) 
                               VALUES (?, ?, 'New Reply', ?, ?, NOW())";
            
            $notif_stmt = $conn->prepare($notification_sql);
            $notif_stmt->bind_param("iiss", $recipient_id, $sender_id, $notification_message, $notification_link);
            $notif_stmt->execute();
            $notif_stmt->close();
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
    <title>View Message - Registrar's Panel</title>  <link rel="icon" type="image/x-icon" href="favicon.ico">
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
        .reply-box {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
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
                            if (!empty($message['firstname'])) {
                                echo htmlspecialchars($message['firstname'] . ' ' . $message['lastname']);
                                if (!empty($message['email'])) {
                                    echo ' &lt;' . htmlspecialchars($message['email']) . '&gt;';
                                }
                                if (!empty($message['course'])) {
                                    echo ' (' . htmlspecialchars($message['course']) . ')';
                                }
                            } else {
                                echo 'System';
                            }
                            ?>
                        </div>
                        <div class="text-muted">
                            <?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                    <?php if (!empty($message['recipient_type'])): ?>
                        <div class="mb-2">
                            <strong>To:</strong> 
                            <?php 
                            $recipient = ucfirst($message['recipient_type']);
                            if ($message['recipient_type'] === 'all') {
                                $recipient = 'All Users';
                            }
                            echo $recipient;
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
                
                <!-- Reply Form -->
                <?php if (!empty($message['sender_id']) && $message['sender_id'] != $_SESSION['user_id']): ?>
                    <div class="reply-box">
                        <h5><i class="fas fa-reply me-2"></i>Reply to this message</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="reply_message" class="form-label">Your Message</label>
                                <textarea class="form-control" id="reply_message" name="reply_message" rows="4" required></textarea>
                            </div>
                            <button type="submit" name="reply_submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Reply
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure textarea is interactive
            const textarea = document.getElementById('reply_message');
            if (textarea) {
                // First, enable the textarea in case it was disabled
                textarea.disabled = false;
                
                // Set minimum height
                textarea.style.minHeight = '100px';
                
                // Auto-resize functionality
                const adjustHeight = function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                };
                
                // Initial adjustment
                adjustHeight.call(textarea);
                
                // Add event listeners
                textarea.addEventListener('input', adjustHeight);
                textarea.addEventListener('focus', adjustHeight);
                
                // Make sure textarea is focused when the page loads
                setTimeout(() => {
                    textarea.focus();
                }, 100);
            }

            // Show success message if redirected with success parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Your message has been sent successfully!',
                    confirmButtonColor: '#0d6efd',
                }).then(() => {
                    // Remove the success parameter from URL
                    const newUrl = window.location.pathname + '?id=<?php echo $message_id; ?>';
                    window.history.replaceState({}, document.title, newUrl);
                });
            }
            
            // Debug: Log any form submission issues
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const replyText = document.getElementById('reply_message').value.trim();
                    if (!replyText) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Please enter a message before sending',
                            confirmButtonColor: '#0d6efd',
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
