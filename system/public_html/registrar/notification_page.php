<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/**
 * Convert a timestamp to a human-readable time difference
 * 
 * @param string $datetime MySQL datetime string
 * @param bool $full Whether to show all time components or just the most significant one
 * @return string Human-readable time difference
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    // Calculate weeks from days
    $weeks = floor($diff->d / 7);
    $remaining_days = $diff->d % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    // Create a new stdClass to hold the time differences
    $diff = new stdClass();
    
    // Add weeks and remaining days to the diff object
    $diff->w = $weeks;
    $diff->d = $remaining_days;
    
    foreach ($string as $k => &$v) {
        if (property_exists($diff, $k) && $diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Check if user is logged in and has appropriate role (1=admin, 2=dean, 3=registrar)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$user_role = ($role_id == 3) ? 'registrar' : 'dean';

// Get user details
$stmt = $conn->prepare("SELECT id, firstname, lastname, email FROM signin_db WHERE id = ?");
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
            $stmt->bind_param("isssss", $user_id, $recipient_type, $student_id, $subject, $message);
            $message_sent = $stmt->execute();
            $message_id = $conn->insert_id;
            $stmt->close();
            
            if ($message_sent) {
                // Create a notification for the recipient
                $notification_message = "New message from " . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
                $notification_link = "view_message.php?id=" . $message_id;
                
                // Determine recipient user ID based on recipient type
                $recipient_id = 1; // Default to admin
                if ($recipient_type === 'bsit' || $recipient_type === 'bscs') {
                    // For student messages, we'll need to handle this differently
                    // For now, we'll just notify the admin
                    $recipient_id = 1;
                }
                
                $notification_sql = "INSERT INTO notifications_db (user_id, sender_id, title, message, link, created_at) 
                                   VALUES (?, ?, 'New Message', ?, ?, NOW())";
                
                $stmt = $conn->prepare($notification_sql);
                $stmt->bind_param("iiss", $recipient_id, $user_id, $notification_message, $notification_link);
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

// Get notifications and messages
// Get notifications
$notifications = [];
$query_notifications = "
    SELECT 
        n.id,
        n.title,
        n.message,
        n.link,
        IF(n.is_read = 1, 1, 0) AS is_read,
        n.created_at,
        n.user_id,
        n.role_id,
        COALESCE(s.firstname, 'System') AS first_name,
        COALESCE(s.lastname, '') AS last_name,
        COALESCE(s.email, 'system@ccc.edu.ph') AS email,
        COALESCE(r.role_name, 'System') AS role_name,
        'notification' AS item_type
    FROM notifications_db n
    LEFT JOIN signin_db s ON n.sender_id = s.id
    LEFT JOIN roles r ON n.role_id = r.role_id
    WHERE n.role_id = 3 OR n.user_id = ?
    ORDER BY created_at DESC
    LIMIT 50";

$stmt = $conn->prepare($query_notifications);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Get notifications and messages for the registrar
$query = "
    -- Get notifications from notifications_db for the logged-in registrar
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
    WHERE n.role_id = 2 OR n.user_id = ?

    UNION ALL

    -- Get messages sent to the registrar or all users
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
            WHEN s.role_id = 1 THEN 'Admin'
            WHEN s.role_id = 3 THEN 'Dean'
            WHEN s.role_id = 4 THEN 'Student'
            ELSE 'System'
        END AS role_name,
        'message' AS item_type
    FROM messages_db m
    LEFT JOIN signin_db s ON m.sender_id = s.id
    WHERE (m.recipient_type = 'registrar' OR m.recipient_type = 'all')
      AND (m.status IS NULL OR m.status != 'deleted')

    ORDER BY created_at DESC
    LIMIT 50";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Sort all notifications by created_at
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 50 most recent
$notifications = array_slice($notifications, 0, 50);

// Get messages for the current user based on their role
$received_messages = [];
$sent_messages = [];

// Get messages where user is the recipient (either specifically to registrar or to all)
$stmt = $conn->prepare("SELECT m.*, 
                      CONCAT(s.firstname, ' ', s.lastname) as sender_name,
                      s.role_id as sender_role
                      FROM messages_db m 
                      LEFT JOIN signin_db s ON m.sender_id = s.id
                      WHERE (m.recipient_type = 'registrar' OR m.recipient_type = 'all')
                      AND (m.status IS NULL OR m.status != 'deleted')
                      ORDER BY m.created_at DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $received_messages[] = $row;
    }
    $stmt->close();
}

// Get messages sent by the current user
$stmt = $conn->prepare("SELECT m.*, 
                      'You' as recipient_name
                      FROM messages_db m 
                      WHERE m.sender_id = ? 
                      ORDER BY m.created_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
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
    <title>Notification - Messages</title>  <link rel="icon" type="image/x-icon" href="favicon.ico">
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

   


    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="d-flex align-items-center">
                    <i class="bi bi-chat-square-text-fill me-2"></i>
                 Notifications
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php 
                            $dashboard = 'dashboard2.php'; // Default to dean dashboard
                            if (isset($_SESSION['role_id'])) {
                                $dashboard = ($_SESSION['role_id'] == 3) ? 'dashboardr.php' : 'dashboard2.php';
                            }
                            echo $dashboard;
                        ?>">Dashboard</a></li>
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
            <!-- Left Column - Notifications and Messages -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="notificationsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                    <i class="bi bi-bell-fill me-1"></i> All
                                    <?php if ($unread_count > 0): ?>
                                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                                    <?php endif; ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab">
                                    <i class="bi bi-envelope-fill me-1"></i> Messages
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                                    <i class="bi bi-bell-fill me-1"></i> Alerts
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content" id="notificationsTabContent">
                            <!-- All Tab -->
                            <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                                    <?php if (empty($notifications)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-bell-slash display-4 d-block mb-2"></i>
                                            No notifications yet.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $item): ?>
                                            <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                                               class="list-group-item list-group-item-action <?php echo $item['is_read'] ? '' : 'bg-light'; ?>"
                                               <?php if (!$item['is_read'] && $item['item_type'] === 'notification'): ?>
                                                   onclick="markAsRead(<?php echo $item['id']; ?>, this)"
                                               <?php endif; ?>>
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">
                                                        <?php if ($item['item_type'] === 'message'): ?>
                                                            <i class="bi bi-envelope<?php echo $item['is_read'] ? '' : '-fill'; ?> me-1"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-bell<?php echo $item['is_read'] ? '' : '-fill'; ?> me-1"></i>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo time_elapsed_string($item['created_at']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($item['message'], 0, 100) . (strlen($item['message']) > 100 ? '...' : ''))); ?></p>
                                                <small class="text-muted">
                                                    From: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name'] . ' (' . $item['role_name'] . ')'); ?>
                                                </small>
                                                <?php if (!$item['is_read']): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                                        <span class="visually-hidden">New</span>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Messages Tab -->
                            <div class="tab-pane fade" id="messages" role="tabpanel" aria-labelledby="messages-tab">
                                <?php 
                                $messages = array_filter($notifications, function($item) {
                                    return $item['item_type'] === 'message';
                                });
                                ?>
                                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                                    <?php if (empty($messages)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-envelope-slash display-4 d-block mb-2"></i>
                                            No messages yet.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $item): ?>
                                            <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                                               class="list-group-item list-group-item-action <?php echo $item['is_read'] ? '' : 'bg-light'; ?>">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">
                                                        <i class="bi bi-envelope<?php echo $item['is_read'] ? '' : '-fill'; ?> me-1"></i>
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo time_elapsed_string($item['created_at']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($item['message'], 0, 100) . (strlen($item['message']) > 100 ? '...' : ''))); ?></p>
                                                <small class="text-muted">
                                                    From: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name'] . ' (' . $item['role_name'] . ')'); ?>
                                                </small>
                                                <?php if (!$item['is_read']): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                                        <span class="visually-hidden">New</span>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Alerts Tab -->
                            <div class="tab-pane fade" id="alerts" role="tabpanel" aria-labelledby="alerts-tab">
                                <?php 
                                $alerts = array_filter($notifications, function($item) {
                                    return $item['item_type'] === 'notification';
                                });
                                ?>
                                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                                    <?php if (empty($alerts)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-bell-slash display-4 d-block mb-2"></i>
                                            No alerts yet.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($alerts as $item): ?>
                                            <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                                               class="list-group-item list-group-item-action <?php echo $item['is_read'] ? '' : 'bg-light'; ?>"
                                               onclick="markAsRead(<?php echo $item['id']; ?>, this)">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">
                                                        <i class="bi bi-bell<?php echo $item['is_read'] ? '' : '-fill'; ?> me-1"></i>
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo time_elapsed_string($item['created_at']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($item['message'])); ?></p>
                                                <small class="text-muted">
                                                    From: <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                                </small>
                                                <?php if (!$item['is_read']): ?>
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                                        <span class="visually-hidden">New alert</span>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
                                        <label for="recipient_type" class="form-label">Recipient</label>
                                        <select class="form-select" id="recipient_type" name="recipient_type" required onchange="toggleStudentIdField()">
                                            <option value="">Select Recipient</option>
                                            <option value="admin">Administrator</option>
                                            <option value="bsit">BSIT Students</option>
                                            <option value="bscs">BSCS Students</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="studentIdField" style="display: none;">
                                        <label for="student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter Student ID (leave blank to send to all students in the program)">
                                        <small class="text-muted">Leave blank to send to all students in the selected program</small>
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
                                                            $status_class = [
                                                                'sent' => 'info',
                                                                'delivered' => 'primary',
                                                                'read' => 'success'
                                                            ];
                                                            echo $status_class[$message['status']] ?? 'secondary';
                                                        ?>">
                                                            <?php echo ucfirst($message['status']); ?>
                                                        </span>
                                                    </small>
                                                    <?php if ($message['status'] === 'read'): ?>
                                                        <small class="text-muted">
                                                            Read: <?php echo date('M d, Y h:i A', strtotime($message['updated_at'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
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
        function toggleStudentIdField() {
            const recipientType = document.getElementById('recipient_type').value;
            const studentIdField = document.getElementById('studentIdField');
            
            if (recipientType === 'bsit' || recipientType === 'bscs') {
                studentIdField.style.display = 'block';
            } else {
                studentIdField.style.display = 'none';
                document.getElementById('student_id').value = ''; // Clear the field when hiding
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleStudentIdField(); // Set initial state
        });
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