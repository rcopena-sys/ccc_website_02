<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}

// Get student information
$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT id, firstname, lastname, student_id, course FROM signin_db WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header('Location: index.php?error=user_not_found');
    exit();
}

// Check whether the notifications table exists in this database to avoid fatal errors
$notifications_table_exists = false;
$check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check) {
    if ($check->num_rows > 0) {
        $notifications_table_exists = true;
    } else {
        error_log("notifications table not found; notification features will be disabled for this page.");
    }
    $check->free();
} else {
    error_log('Failed to check for notifications table: ' . $conn->error);
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_type = filter_input(INPUT_POST, 'recipient_type', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    if (in_array($recipient_type, ['dean', 'registrar', 'admin']) && !empty($subject) && !empty($message)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert the message
            $stmt = $conn->prepare("INSERT INTO messages_db (sender_id, recipient_type, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $student['student_id'], $recipient_type, $subject, $message);
            $message_sent = $stmt->execute();
            $message_id = $conn->insert_id;
            $stmt->close();
            
            if ($message_sent) {
                // Create a notification for the admin if notifications table exists
                if ($notifications_table_exists) {
                    $admin_notification = "New message from " . htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) . " (" . htmlspecialchars($student['course']) . ")";
                    $notification_sql = "INSERT INTO notifications (user_id, sender_id, title, message, link) 
                                       SELECT admin_id, ?, 'New Message', ?, ? 
                                       FROM admin_recipients 
                                       WHERE recipient_type = ?";

                    $notification_link = "view_message.php?id=" . $message_id;
                    $stmt = $conn->prepare($notification_sql);
                    if ($stmt) {
                        $stmt->bind_param("ssss", $student['student_id'], $admin_notification, $notification_link, $recipient_type);
                        $notification_sent = $stmt->execute();
                        $stmt->close();

                        if ($notification_sent) {
                            $conn->commit();
                            $success = 'Your message has been sent successfully.';
                        } else {
                            throw new Exception('Failed to send notification: ' . $conn->error);
                        }
                    } else {
                        throw new Exception('Failed to prepare notification statement: ' . $conn->error);
                    }
                } else {
                    // Notifications table missing — commit the message without creating notifications
                    $conn->commit();
                    $success = 'Your message has been sent successfully.';
                }
            } else {
                throw new Exception('Failed to send message');
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'An error occurred while sending your message. Please try again.';
            error_log('Message sending error: ' . $e->getMessage());
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    if ($notifications_table_exists) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("is", $notification_id, $student['student_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log('Failed to prepare mark-read statement: ' . $conn->error);
        }
    } else {
        // notifications table missing — nothing to mark
        error_log('Attempted to mark notification read but notifications table is missing.');
    }
    header('Location: notification.php');
    exit();
}

// Get unread notifications count
$unread_count = 0;
if ($notifications_table_exists) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("s", $student['student_id']);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $unread_count = isset($row['count']) ? (int)$row['count'] : 0;
        } else {
            error_log('Failed to execute unread count: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log('Failed to prepare unread count statement: ' . $conn->error);
    }
} else {
    // notifications table missing — default to 0
    $unread_count = 0;
}

// Get all notifications
$notifications = [];
if ($notifications_table_exists) {
    $stmt = $conn->prepare("SELECT n.*, s.firstname, s.lastname 
                      FROM notifications n 
                      LEFT JOIN signin_db s ON n.sender_id COLLATE utf8mb4_unicode_ci = s.student_id COLLATE utf8mb4_unicode_ci
                      WHERE n.user_id = ? 
                      ORDER BY n.created_at DESC");
    if ($stmt) {
        $stmt->bind_param("s", $student['student_id']);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }
        } else {
            error_log('Failed to execute notifications select: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log('Failed to prepare notifications select: ' . $conn->error);
    }
} else {
    // notifications table missing — leave notifications empty
    $notifications = [];
}

// Get sent messages
$sent_messages = [];
// First check if updated_at column exists
$check_updated_at = $conn->query("SHOW COLUMNS FROM messages_db LIKE 'updated_at'");
$has_updated_at = $check_updated_at->num_rows > 0;

// Build the query with explicit column selection
$query = "SELECT 
    m.id, m.sender_id, m.recipient_type, m.subject, m.message, m.status, m.created_at,"
    . ($has_updated_at ? " m.updated_at," : " NULL as updated_at,")
    . " CASE 
        WHEN m.recipient_type = 'dean' THEN 'Dean''s Office'
        WHEN m.recipient_type = 'registrar' THEN 'Registrar''s Office'
        WHEN m.recipient_type = 'admin' THEN 'MISD Office'
        ELSE 'Unknown Office'
    END as recipient_name
    FROM messages_db m 
    WHERE m.sender_id = ? 
    ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $student['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sent_messages[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications & Messages</title>  <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .notification-item {
            border-left: 4px solid #6c757d;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .notification-item:hover {
            background-color: #f1f1f1;
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
        }
        .message-card {
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .unread-count {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <div class="container pt-3">
        <a href="<?php 
            echo (strtoupper($student['course']) === 'BSIT') ? 'dci_page.php' : 'cs_studash.php'; 
        ?>" class="btn btn-outline-primary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="container py-4">
        <div class="row">
            <div class="col-12 mb-4">
                <h2><i class="bi bi-bell-fill me-2"></i>Notifications & Messages</h2>
                <hr>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column - Notifications -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Notifications</h5>
                        <span class="badge bg-primary"><?php echo $unread_count; ?> unread</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <?php if (empty($sent_messages)): ?>
                                <div class="p-3 text-center text-muted">
                                    No notifications yet.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($sent_messages as $message): ?>
                                        <div class="list-group-item message-card">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">To: <?php echo htmlspecialchars($message['recipient_name']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?></small>
                                            </div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted">Status: 
                                                    <span class="badge bg-<?php 
                                                        $status = $message['status'] ?? 'sent';
                                                        $status_class = [
                                                            'sent' => 'info',
                                                            'delivered' => 'primary',
                                                            'read' => 'success'
                                                        ];
                                                        echo $status_class[$status] ?? 'secondary';
                                                    ?>">
                                                        <?php echo ucfirst($status); ?>
                                                    </span>
                                                </small>
                                                <?php if (isset($message['status']) && $message['status'] === 'read' && !empty($message['updated_at'])): 
                                                    $read_time = strtotime($message['updated_at']);
                                                    if ($read_time !== false): ?>
                                                        <small class="text-muted">
                                                            Read: <?php echo date('M d, Y h:i A', $read_time); ?>
                                                        </small>
                                                    <?php endif; 
                                                endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo $notification['link'] ?? '#'; ?>" 
                                       class="list-group-item list-group-item-action notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php if ($notification['sender_id']): ?>
                                            <small class="text-muted">From: <?php echo htmlspecialchars($notification['firstname'] . ' ' . $notification['lastname']); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">System Notification</small>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="all_notifications.php" class="btn btn-sm btn-outline-primary">View All Notifications</a>
                    </div>
                </div>
            </div>

            <!-- Right Column - Messages -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="messagesTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="new-message-tab" data-bs-toggle="tab" data-bs-target="#new-message" type="button" role="tab">New Message</button>
                            </li>
                            <!-- Sent Messages tab removed; recent sent messages shown in Notifications when there are no notifications -->
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="messagesTabContent">
                            <!-- New Message Tab -->
                            <div class="tab-pane fade show active" id="new-message" role="tabpanel">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="recipient_type" class="form-label">Send To</label>
                                        <select class="form-select" id="recipient_type" name="recipient_type" required>
                                            <option value="">Select recipient...</option>
                                            <option value="dean">Dean's Office</option>
                                            <option value="registrar">Registrar's Office</option>
                                            <option value="admin">MISD Office</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Sent Messages Tab -->
                            <div class="tab-pane fade" id="sent-messages" role="tabpanel">
                                <?php if (empty($sent_messages)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-envelope-open display-4 d-block mb-2"></i>
                                        No sent messages yet.
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($sent_messages as $message): ?>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="list-group-item message-card text-decoration-none text-dark">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">To: <?php echo htmlspecialchars($message['recipient_name']); ?></h6>
                                                    <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?></small>
                                                </div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <small class="text-muted">Status: 
                                                        <span class="badge bg-<?php 
                                                            $status = $message['status'] ?? 'sent';
                                                            $status_class = [
                                                                'sent' => 'info',
                                                                'delivered' => 'primary',
                                                                'read' => 'success'
                                                            ];
                                                            echo $status_class[$status] ?? 'secondary';
                                                        ?>
                                                            <?php echo ucfirst($status); ?>
                                                        </span>
                                                    </small>
                                                    <?php 
                                                    if (isset($message['status']) && $message['status'] === 'read' && !empty($message['updated_at'])): 
                                                        $read_time = strtotime($message['updated_at']);
                                                        if ($read_time !== false): ?>
                                                            <small class="text-muted">
                                                                Read: <?php echo date('M d, Y h:i A', $read_time); ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <small class="text-muted">
                                                                Read
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sent Messages moved to Recent Notifications when there are no notifications; modal removed -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Wait for the document to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Mark notification as read when clicked
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.addEventListener('click', function() {
                    const notificationId = this.dataset.notificationId;
                    if (notificationId) {
                        fetch(`mark_notification_read.php?id=${notificationId}`, { method: 'POST' });
                    }
                });
            });

            // Auto-resize textarea
            const messageTextarea = document.getElementById('message');
            if (messageTextarea) {
                messageTextarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Sent Messages modal removed — sent messages are shown in Recent Notifications when applicable
        });
    </script>
</body>
</html>