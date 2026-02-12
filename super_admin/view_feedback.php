<?php
session_start();
// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// Mark all feedback as read when viewing the page
$conn->query("UPDATE feedback_db SET is_read = 1");

// Get search and filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';

// Build the query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(message LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($status === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($status === 'read') {
    $where_conditions[] = "is_read = 1";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

// Prepare the final query
$query = "SELECT * FROM feedback_db";
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}
$query .= " ORDER BY created_at DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$feedback_result = $stmt->get_result();

// Get counts for status badges
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback_db")->fetch_assoc()['count'];
$unread_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback_db WHERE is_read = 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Super Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .feedback-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #3b82f6;
        }
        .feedback-card:hover {
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
                            <a class="nav-link active" href="view_feedback.php">
                                <i class="fas fa-comment-alt me-1"></i> Feedback
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
                    <i class="fas fa-comment-alt text-primary me-2"></i>Feedback Management
                </h2>
                <div>
                    <span class="badge bg-primary me-2">Total: <?php echo $total_feedback; ?></span>
                    <span class="badge bg-warning">Unread: <?php echo $unread_feedback; ?></span>
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
                                       placeholder="Search feedback..." value="<?php echo htmlspecialchars($search); ?>">
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

            <!-- Feedback List -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if ($feedback_result->num_rows > 0): ?>
                        <?php while ($feedback = $feedback_result->fetch_assoc()): 
                            // Parse the message to extract individual fields
                            $message = $feedback['message'];
                            $lines = explode("\n", $message);
                            $feedback_data = [];
                            
                            foreach ($lines as $line) {
                                $parts = explode(": ", $line, 2);
                                if (count($parts) === 2) {
                                    $key = trim($parts[0]);
                                    $value = trim($parts[1]);
                                    $feedback_data[$key] = $value;
                                }
                            }
                            
                            $rating = isset($feedback_data['Rating']) ? $feedback_data['Rating'] : 'N/A';
                            $email = $feedback['email'];
                            $created_at = new DateTime($feedback['created_at']);
                            $formatted_date = $created_at->format('M j, Y g:i A');
                            $is_unread = $feedback['is_read'] == 0;
                        ?>
                        <div class="feedback-card border-bottom p-4 <?php echo $is_unread ? 'unread' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h5 class="mb-0 me-2">
                                            <?php echo htmlspecialchars($feedback_data['Name'] ?? 'Anonymous'); ?>
                                        </h5>
                                        <span class="text-muted small">
                                            &lt;<?php echo htmlspecialchars($email); ?>&gt;
                                        </span>
                                        <span class="ms-2 status-badge <?php echo $is_unread ? 'badge-unread' : 'badge-read'; ?>">
                                            <?php echo $is_unread ? 'New' : 'Read'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <?php if (preg_match('/(\d+)\/5/', $rating, $matches)): 
                                            $stars = (int)$matches[1];
                                        ?>
                                            <div class="rating-stars me-2">
                                                <?php echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars); ?>
                                                <span class="text-muted ms-1">(<?php echo $rating; ?>)</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small">No rating</div>
                                        <?php endif; ?>
                                        
                                        <span class="text-muted small ms-3">
                                            <i class="far fa-clock me-1"></i> <?php echo $formatted_date; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($feedback_data['Feedback'])): ?>
                                        <div class="bg-light p-3 rounded mt-2">
                                            <?php echo nl2br(htmlspecialchars($feedback_data['Feedback'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3">
                                    <div class="btn-group">
                                        <a href="mailto:<?php echo htmlspecialchars($email); ?>" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           title="Reply via Email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteFeedback(<?php echo $feedback['id']; ?>)"
                                                title="Delete Feedback">
                                            <i class="far fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <div class="mb-3">
                                <i class="fas fa-inbox fa-4x text-muted"></i>
                            </div>
                            <h5 class="text-muted mb-2">No feedback found</h5>
                            <p class="text-muted small">
                                <?php 
                                if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)) {
                                    echo 'Try adjusting your search or filter criteria.';
                                } else {
                                    echo 'No feedback submissions yet. Check back later.';
                                }
                                ?>
                            </p>
                            <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                                <a href="view_feedback.php" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-sync-alt me-1"></i> Reset Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($feedback_result->num_rows > 0): ?>
                <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing <?php echo $feedback_result->num_rows; ?> of <?php echo $total_feedback; ?> feedback items
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
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
        // Delete feedback function
        function deleteFeedback(id) {
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
                    // Send AJAX request to delete the feedback
                    fetch(`delete_feedback.php?id=${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the feedback item from the DOM
                            const feedbackItem = document.querySelector(`[data-feedback-id="${id}"]`);
                            if (feedbackItem) {
                                feedbackItem.remove();
                                
                                // Show success message
                                Swal.fire(
                                    'Deleted!',
                                    'The feedback has been deleted.',
                                    'success'
                                );
                                
                                // Reload the page after 1.5 seconds
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            throw new Error(data.error || 'Failed to delete feedback');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error!',
                            'There was an error deleting the feedback.',
                            'error'
                        );
                    });
                }
            });
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-hide alerts after 5 seconds
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>
