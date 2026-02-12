<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Check if user is logged in and has appropriate role (1=admin, 2=registrar, 3=dean)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$user_role = ($role_id == 2) ? 'registrar' : 'dean';

// Get user details with role
$stmt = $conn->prepare("SELECT s.id, s.firstname, s.lastname, s.email, s.role_id, r.role_name 
                       FROM signin_db s 
                       LEFT JOIN roles r ON s.role_id = r.role_id 
                       WHERE s.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: ../index.php?error=user_not_found');
    exit();
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $recipient_type = filter_input(INPUT_POST, 'recipient_type', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    
    if (!empty($subject) && !empty($message) && !empty($recipient_type)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert the message
            $stmt = $conn->prepare("INSERT INTO messages_db (sender_id, recipient_type, student_id, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'unread', NOW())");
            $stmt->bind_param("issss", $user_id, $recipient_type, $student_id, $subject, $message);
            $message_sent = $stmt->execute();
            $message_id = $conn->insert_id;
            $stmt->close();
            
            if ($message_sent) {
                // Create a notification for the recipient
                $notification_message = "New message from " . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
                $notification_link = "view_message.php?id=" . $message_id;
                
                // Determine recipient user ID based on recipient type
                $recipient_role_id = null;
                if ($recipient_type === 'all') {
                    // For all users
                    $recipient_role_id = null; // Will be handled by the notification system
                } elseif ($recipient_type === 'admin') {
                    $recipient_role_id = 1; // Admin
                } elseif ($recipient_type === 'registrar') {
                    $recipient_role_id = 2; // Registrar
                } elseif ($recipient_type === 'dean') {
                    $recipient_role_id = 3; // Dean
                } elseif (in_array($recipient_type, ['bsit', 'bscs'])) {
                    // For student messages, we'll need to handle this differently
                    // For now, we'll just notify the admin
                    $recipient_role_id = 1; // Admin
                }
                
                $notification_sql = "INSERT INTO notifications_db (user_id, role_id, sender_id, title, message, link, created_at) 
                                   VALUES (?, ?, ?, 'New Message', ?, ?, NOW())";
                
                $stmt = $conn->prepare($notification_sql);
                $stmt->bind_param("iiiss", $user_id, $recipient_role_id, $user_id, $notification_message, $notification_link);
                $notification_sent = $stmt->execute();
                $stmt->close();
                
                if ($notification_sent) {
                    $conn->commit();
                    // Set success message in session
                    $_SESSION['success'] = 'Message sent successfully!';
                    // Redirect to clear POST data
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    throw new Exception('Failed to send notification');
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
    $stmt = $conn->prepare("UPDATE notifications_db SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("is", $notification_id, $user['id']);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: notification.php');
    exit();
}

// Get unread notifications count
$unread_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications_db WHERE user_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("s", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result->fetch_assoc()['count'];
    $stmt->close();
}

// Also include unread messages addressed to this role (e.g., 'dean' or 'registrar')
try {
    $msg_unread = 0;
    $mstmt = $conn->prepare("SELECT COUNT(*) as cnt FROM messages_db WHERE recipient_type = ? AND (status IS NULL OR status != 'read')");
    if ($mstmt) {
        $mstmt->bind_param('s', $user_role);
        $mstmt->execute();
        $mres = $mstmt->get_result();
        $row = $mres->fetch_assoc();
        $msg_unread = isset($row['cnt']) ? (int)$row['cnt'] : 0;
        $mstmt->close();
    }
    $unread_count += $msg_unread;
} catch (Exception $e) {
    error_log('Error counting unread messages: ' . $e->getMessage());
}

// Get notifications and messages for the dean
$query = "
    -- Get notifications from notifications_db for the logged-in dean
    SELECT 
        n.id,
        n.title,
        n.message,
        n.link,
        n.is_read,
        n.created_at,
        n.user_id,
        n.role_id,
        COALESCE(u.firstname, 'System') AS first_name,
        COALESCE(u.lastname, '') AS last_name,
        COALESCE(u.email, 'system@ccc.edu.ph') AS email,
        COALESCE(r.role_name, 'System') AS role_name,
        'notification' AS item_type
    FROM notifications_db n
    LEFT JOIN signin_db u ON n.sender_id = u.id
    LEFT JOIN roles r ON n.role_id = r.role_id
    WHERE n.role_id = 3 OR n.user_id = ?

    UNION ALL

    -- Get messages sent to the dean or all users
    SELECT 
        m.id,
        m.subject AS title,
        m.message,
        CONCAT('view_message.php?id=', m.id) AS link,
        IF(m.status = 'read', 1, 0) AS is_read,
        m.created_at,
        m.sender_id AS user_id,
        COALESCE(s.role_id, 0) AS role_id,
        COALESCE(s.firstname, 'Unknown') AS first_name,
        COALESCE(s.lastname, '') AS last_name,
        COALESCE(s.email, '') AS email,
        CASE 
            WHEN s.role_id = 2 THEN 'Registrar'
            WHEN s.role_id = 4 THEN 'Student'
            WHEN s.role_id = 3 THEN 'Dean'
            WHEN s.role_id = 1 THEN 'Admin'
            ELSE 'System'
        END AS role_name,
        'message' AS item_type
    FROM messages_db m
    LEFT JOIN signin_db s ON m.sender_id = s.id
    WHERE (m.recipient_type = 'dean' OR m.recipient_type = 'all')
      AND (m.status IS NULL OR m.status != 'deleted')

    ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
} else {
    error_log('Error preparing query: ' . $conn->error);
}

$notifications = [];
if ($notifications_result) {
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    // Debug output
    error_log('Number of notifications/messages found: ' . count($notifications));
    error_log('Sample notification data: ' . print_r(!empty($notifications) ? $notifications[0] : 'No data', true));
} else {
    error_log('Query failed: ' . $conn->error);
}

// Get messages for the current user based on their role
$received_messages = [];
$sent_messages = [];

// Get messages where user is the recipient (dean)
$query = "SELECT 
            m.*,
            CASE 
                WHEN s.id IS NOT NULL THEN CONCAT(s.firstname, ' ', s.lastname)
                WHEN m.sender_id = 'system' THEN 'System'
                ELSE CONCAT('Student ID: ', m.sender_id)
            END as sender_name,
            COALESCE(s.role_id, 
                CASE 
                    WHEN m.sender_id = 'system' THEN 0 
                    ELSE 4  -- Default to Student role if not found
                END
            ) as sender_role,
            'message' as item_type
          FROM messages_db m 
          LEFT JOIN signin_db s ON m.sender_id = s.id
          WHERE (m.recipient_type = 'dean' OR m.recipient_type = 'all')
          AND (m.status IS NULL OR m.status != 'deleted')
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $received_messages = $result->fetch_all(MYSQLI_ASSOC);
    
    // Log the first message for debugging
    if (!empty($received_messages)) {
        error_log('First message data: ' . print_r($received_messages[0], true));
    }
    
    $stmt->close();
} else {
    error_log('Error preparing messages query: ' . $conn->error);
    $received_messages = [];
}

// Get messages where user is the sender
$stmt = $conn->prepare("SELECT m.*, 
                      CASE 
                          WHEN m.recipient_type = 'all' THEN 'All Users'
                          WHEN m.recipient_type = 'admin' THEN 'Administrator'
                          WHEN m.recipient_type = 'registrar' THEN 'Registrar'
                          WHEN m.recipient_type = 'dean' THEN 'Dean'
                          WHEN m.recipient_type = 'student' THEN 'Student'
                          ELSE 'Unknown Recipient'
                      END as recipient_name
                      FROM messages_db m 
                      WHERE m.sender_id = ? 
                      ORDER BY m.created_at DESC");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$sent_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> notification- Messages</title>  <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
   
       


    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="d-flex align-items-center">
                    <i class="bi bi-chat-square-text-fill me-2"></i>
                 Notifications
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard2.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Notifications</li>
                    </ol>
                </nav>
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
                        <?php if (!empty($notifications)): ?>
                            <!-- Show notifications if any exist -->
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="<?php echo $notification['link'] ?? '#'; ?>" 
                                       class="list-group-item list-group-item-action notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php if (isset($notification['first_name']) || isset($notification['last_name'])): ?>
                                            <small class="text-muted">
                                                From: <?php echo htmlspecialchars(trim(($notification['first_name'] ?? '') . ' ' . ($notification['last_name'] ?? ''))); ?>
                                                <?php if (!empty($notification['role_name'])): ?>
                                                    <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($notification['role_name']); ?></span>
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">System Notification</small>
                                        <?php endif; ?>
                                        <?php if (isset($notification['is_read']) && $notification['is_read'] == 0): ?>
                                            <span class="badge bg-primary">New</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($received_messages)): ?>
                            <!-- Show received messages if no notifications but messages exist -->
                            <div class="list-group list-group-flush">
                                <?php foreach ($received_messages as $message): 
                                    // Get sender role name
                                    $sender_role = '';
                                    if (isset($message['sender_role'])) {
                                        switch($message['sender_role']) {
                                            case 1: $sender_role = 'Admin'; break;
                                            case 2: $sender_role = 'Registrar'; break;
                                            case 3: $sender_role = 'Dean'; break;
                                            case 4: $sender_role = 'Student'; break;
                                            default: $sender_role = 'User';
                                        }
                                    } else {
                                        $sender_role = 'System';
                                    }
                                ?>
                                    <a href="view_message.php?id=<?php echo $message['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1 text-muted">
                                            From: <?php echo htmlspecialchars($message['sender_name'] ?? 'System'); ?>
                                            <span class="badge bg-secondary ms-2"><?php echo $sender_role; ?></span>
                                            <?php if (isset($message['is_read']) && $message['is_read'] == 0): ?>
                                                <span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-0 text-truncate">
                                            <?php echo htmlspecialchars(substr($message['message'] ?? '', 0, 100)); ?>
                                            <?php echo (isset($message['message']) && strlen($message['message']) > 100) ? '...' : ''; ?>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Show message when no notifications or messages -->
                            <div class="p-3 text-center text-muted">
                                No notifications or messages yet.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="notifications.php" class="btn btn-sm btn-outline-primary">View All Notifications</a>
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
                            <!-- Sent Messages nav removed; sent messages will be shown in Recent Notifications when there are no notifications -->
                            <li class="nav-item position-relative" role="presentation">
                                <button class="nav-link" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab">
                                    Inbox
                                    <?php if (count($received_messages) > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger unread-count">
                                            <?php echo count($received_messages); ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="messagesTabContent">
                            <!-- New Message Tab -->
                            <div class="tab-pane fade show active" id="new-message" role="tabpanel">
                                <form method="POST" action="" id="messageForm">
                                    <div class="mb-3">
                                        <label for="recipient_type" class="form-label">Send To</label>
                                        <select class="form-select" id="recipient_type" name="recipient_type" required>
                                            <option value="">Select recipient</option>
                                            <option value="admin">Administrator</option>
                                            <option value="registrar">Registrar</option>
                                            <option value="student">Student</option>
                                            <option value="all">All Users</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="student_id_group" style="display: none;">
                                        <label for="student_id" class="form-label">Student ID (for student messages)</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter student ID">
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
                            <!-- Sent Messages tab removed; sent messages are displayed in Recent Notifications when there are no notifications -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display success message if set -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']); // Clear the message after displaying
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Auto-hide success message after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show/hide student ID field based on recipient type
        document.getElementById('recipient_type').addEventListener('change', function() {
            const studentIdGroup = document.getElementById('student_id_group');
            if (this.value === 'student') {
                studentIdGroup.style.display = 'block';
                document.getElementById('student_id').setAttribute('required', 'required');
            } else {
                studentIdGroup.style.display = 'none';
                document.getElementById('student_id').removeAttribute('required');
            }
        });

        // Mark notification as read when viewing
        function markAsRead(notificationId) {
            fetch(`mark_notification_read.php?id=${notificationId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
            });
        }
    </script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#inboxTable, #sentTable').DataTable({
                pageLength: 10,
                order: [[2, 'desc']], // Sort by date column (index 2) in descending order
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<''tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });

            // Auto-resize textarea
            $('#message').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Mark message as read when viewing
            $('tr[onclick]').on('click', function() {
                const url = $(this).attr('onclick').match(/window\.location='([^']+)'/)[1];
                window.location = url;
            });
        });
    </script>
</body>
</html>