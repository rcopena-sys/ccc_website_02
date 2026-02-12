<?php
session_start();
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';
require_once '../includes/time_functions.php';

// Mark all notifications as read when viewing the page
$conn->query("UPDATE notifications_db SET is_read = 1 WHERE (user_id = {$_SESSION['user_id']} OR role_id = {$_SESSION['role_id']} OR role_id IS NULL) AND is_read = 0");

// Get search and filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';

// Set up pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Debug: Check if there are any notifications in the database
$debug_query = "SELECT COUNT(*) as count FROM notifications_db";
$debug_result = $conn->query($debug_query);
$notification_count = $debug_result->fetch_assoc()['count'];

// Get notifications and messages in one query
$query = "
    -- Get notifications
    SELECT 
        n.id, 
        n.title, 
        n.message, 
        n.link, 
        n.is_read, 
        n.created_at,
        n.user_id,
        n.role_id,
        COALESCE(u.firstname, 'System') as first_name, 
        COALESCE(u.lastname, '') as last_name,
        COALESCE(u.email, 'system@ccc.edu.ph') as email,
        COALESCE(r.role_name, 'System') as role_name,
        'notification' as item_type
    FROM notifications_db n
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN roles r ON n.role_id = r.role_id
    WHERE (n.user_id = ? OR n.role_id = ? OR n.role_id IS NULL)
    
    UNION ALL
    
    -- Get messages
    SELECT 
        m.id,
        m.subject as title,
        m.message,
        CONCAT('view_message.php?id=', m.id) as link,
        IF(COALESCE(m.status, 'unread') = 'read', 1, 0) as is_read,
        m.created_at,
        m.sender_id as user_id,
        NULL as role_id,
        s.firstname as first_name,
        s.lastname as last_name,
        s.email,
        'Student' as role_name,
        'message' as item_type
    FROM messages_db m
    JOIN signin_db s ON m.sender_id = s.student_id
    WHERE m.recipient_type = 'admin' 
    AND (m.status IS NULL OR m.status != 'deleted')
    
    ORDER BY created_at DESC
    LIMIT 50
";

try {
    // Execute the combined query
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ii', $_SESSION['user_id'], $_SESSION['role_id']);
        $stmt->execute();
        $notifications_result = $stmt->get_result();
    } else {
        // Fallback if prepare fails
        $notifications_result = $conn->query("
            SELECT *, 'System' as first_name, '' as last_name, 'system@ccc.edu.ph' as email, 'System' as role_name, 'notification' as item_type 
            FROM notifications_db 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
    }
} catch (Exception $e) {
    // Final fallback
    $notifications_result = $conn->query("
        SELECT *, 'System' as first_name, '' as last_name, 'system@ccc.edu.ph' as email, 'System' as role_name, 'notification' as item_type 
        FROM notifications_db 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
}

// Get total count (simplified for now)
$count_result = $conn->query("
    SELECT COUNT(*) as total FROM (
        SELECT id FROM notifications_db 
        UNION ALL
        SELECT id FROM messages_db WHERE recipient_type = 'admin' AND (status IS NULL OR status != 'deleted')
    ) as combined
");
$total_notifications = $count_result ? $count_result->fetch_assoc()['total'] : 0;

// Debug: Check messages in the database
$debug_messages = $conn->query("SELECT * FROM messages_db WHERE recipient_type = 'admin' ORDER BY created_at DESC LIMIT 5");
$has_messages = $debug_messages && $debug_messages->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - Super Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .notification-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .notification-card.message-type {
            border-left-color: #10b981;
        }
        
        .notification-card.unread {
            background-color: #f8f9fa;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .unread {
            background-color: #f0f9ff;
            border-left-color: #3b82f6;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 1.1rem;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50rem;
        }
        .badge-unread {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-read {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .search-input {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="homepage.php">
                    <i class="fas fa-home me-2"></i>Admin Panel
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="homepage.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-users me-1"></i> Users
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="add_user.php"><i class="fas fa-user-plus me-1"></i> Add User</a></li>
                                <li><a class="dropdown-item" href="bulk_user.php"><i class="fas fa-users-cog me-1"></i> Bulk Import</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="notification.php">
                                <i class="fas fa-bell me-1"></i> Notifications
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">
                    <i class="fas fa-bell text-primary me-2"></i>Notifications
                </h2>
                <div>
                    <button id="markAllRead" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fas fa-check-double me-1"></i> Mark All as Read
                    </button>
                    <button id="clearAll" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash-alt me-1"></i> Clear All
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" class="form-control search-input" 
                                       placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From date">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To date">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($has_messages === false): ?>
                <div class="alert alert-warning">
                    <h5>Debug: No messages found in database</h5>
                    <p>Query: SELECT * FROM messages_db WHERE recipient_type = 'admin'</p>
                    <p>Error: <?php echo $conn->error ?? 'None'; ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Notification List -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if ($notifications_result && $notifications_result->num_rows > 0): ?>
                        <?php 
                        // Reset the result set pointer
                        $notifications_result->data_seek(0);
                        while ($item = $notifications_result->fetch_assoc()): 
                            $is_unread = $item['is_read'] == 0;
                            $time_ago = time_elapsed_string($item['created_at']);
                            $is_message = ($item['item_type'] ?? '') === 'message';
                            
                            // Set icon and colors based on item type
                            if ($is_message) {
                                $icon_class = 'fa-envelope';
                                $icon_color = $is_unread ? 'text-primary' : 'text-muted';
                            } else {
                                $icon_class = 'fa-bell';
                                $icon_color = 'text-warning';
                                
                                // For notifications, set color based on content
                                if (stripos($item['message'] ?? '', 'success') !== false) {
                                    $icon_color = 'text-success';
                                } elseif (stripos($item['message'] ?? '', 'warning') !== false) {
                                    $icon_color = 'text-warning';
                                } elseif (stripos($item['message'] ?? '', 'error') !== false) {
                                    $icon_color = 'text-danger';
                                }
                            }
                        ?>
                        <div class="notification-card <?php echo $is_unread ? 'unread' : ''; ?> <?php echo $is_message ? 'message' : 'notification'; ?>" 
                             data-id="<?php echo $item['id']; ?>" 
                             data-type="<?php echo $is_message ? 'message' : 'notification'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <i class="fas <?php echo $icon_class; ?> me-2 <?php echo $icon_color; ?>"></i>
                                        <?php echo htmlspecialchars($item['title'] ?? 'No Title'); ?>
                                        <?php if ($is_unread): ?>
                                            <span class="badge bg-warning text-dark ms-2">New</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php 
                                        $preview = strip_tags($item['message']);
                                        echo strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php 
                                            echo htmlspecialchars(($item['first_name'] ?? 'System') . ' ' . ($item['last_name'] ?? ''));
                                            if (!empty($item['role_name'])) {
                                                echo ' (' . htmlspecialchars($item['role_name']) . ')';
                                            }
                                            ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo $time_ago; ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <a href="<?php echo htmlspecialchars($item['link'] ?? '#'); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-<?php echo $is_message ? 'envelope-open-text' : 'eye'; ?> me-1"></i>
                                        <?php echo $is_message ? 'View Message' : 'View'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <div class="mb-3">
                                <i class="fas fa-bell-slash fa-4x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No notifications</h5>
                            <p class="text-muted small">
                                <?php 
                                if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)) {
                                    echo 'No notifications match your current filters.';
                                } else {
                                    echo 'You have no notifications at this time.';
                                }
                                ?>
                            </p>
                            <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                                <a href="notification.php" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-sync-alt me-1"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($notifications_result->num_rows > 0): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing <?php echo $notifications_result->num_rows; ?> of <?php echo $total_notifications; ?> notifications
                    </div>
                    
                 
                </div>
                <?php endif; ?>
            </div>

        </div> <!-- End of container -->
    </div> <!-- End of wrapper -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Mark all notifications as read
        document.getElementById('markAllRead').addEventListener('click', function() {
            fetch('mark_all_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove 'New' badges and update UI
                    document.querySelectorAll('.notification-card').forEach(card => {
                        card.classList.remove('bg-light');
                        const badge = card.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                    });
                    showAlert('All notifications marked as read', 'success');
                }
            });
        });

        // Clear all notifications
        document.getElementById('clearAll').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
                fetch('clear_all_notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove all notification cards
                        document.querySelectorAll('.notification-card').forEach(card => card.remove());
                        showAlert('All notifications cleared', 'success');
                        
                        // Show empty state if no notifications left
                        const notificationContainer = document.querySelector('.card-body');
                        if (notificationContainer.children.length === 0) {
                            const emptyState = `
                                <div class="text-center p-5">
                                    <div class="mb-3">
                                        <i class="fas fa-bell-slash fa-4x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No notifications</h5>
                                    <p class="text-muted small">You have no notifications at this time.</p>
                                </div>
                            `;
                            notificationContainer.innerHTML = emptyState;
                        }
                    }
                });
            }
        });

        // Mark notification as read when clicked
        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                if (notificationId) {
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + notificationId
                    });
                    
                    // Update UI immediately for better UX
                    this.classList.remove('bg-light');
                    const badge = this.querySelector('.badge');
                    if (badge) {
                        badge.remove();
                    }
                }
                
                // If there's a link in the notification, follow it
                const link = this.querySelector('a');
                if (link) {
                    window.location.href = link.href;
                }
            });
        });

        // Show alert message
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>            
           
</script>
</body>
</html>
