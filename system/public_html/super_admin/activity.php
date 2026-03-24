<?php
session_start();
require_once '../db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Only allow admin (role_id == 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Handle Excel download
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Fetch activity logs for export
    $query = "
        SELECT 
            al.*, 
            s.firstname, 
            s.lastname, 
            s.email
        FROM activity_log_db al
        LEFT JOIN signin_db s ON al.user_id = s.id
        ORDER BY al.created_at DESC
    ";
    $result = $conn->query($query);
    
    // Create CSV content (Date + Time separated)
    $csv_content = "User,Action,Description,IP Address,Date,Time\n";
    
    if ($result && $result->num_rows > 0) {
        while ($log = $result->fetch_assoc()) {
            $fullname = trim(($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? ''));
            $username = $fullname ?: ($log['username'] ?? 'Unknown User');
            $email = $log['email'] ?? 'N/A';

            // Match display format
            $user_display = $username . ' (' . $email . ')';

            $action = ucfirst($log['action'] ?? '');
            $description = $log['action_description'] ?? 'No description provided';
            $ip_address = $log['ip_address'] ?? 'N/A';

            // Separate Date & Time for better Excel formatting
            $date_value = date('Y-m-d', strtotime($log['created_at']));
            $time_value = date('H:i:s', strtotime($log['created_at']));

            // Escape commas & quotes
            $csv_content .= '"' . str_replace('"', '""', $user_display) . '",';
            $csv_content .= '"' . str_replace('"', '""', $action) . '",';
            $csv_content .= '"' . str_replace('"', '""', $description) . '",';
            $csv_content .= '"' . str_replace('"', '""', $ip_address) . '",';
           $csv_content .= "'$date_value',";
            $csv_content .= '"' . $time_value . "\"\n";
        }
    }
    
    // Set headers for CSV download (FIXED)
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    echo $csv_content;
    exit();
}

// Fetch activity logs joined with signin_db
$query = "
    SELECT 
        al.*, 
        s.firstname, 
        s.lastname, 
        s.email
    FROM activity_log_db al
    LEFT JOIN signin_db s ON al.user_id = s.id
    ORDER BY al.created_at DESC
";
$result = $conn->query($query);

// Helper function
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Activity Logs - Admin</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #008B8B;
            --secondary-color: #006d6d;
            --background-color: #f5f7f8;
            --text-color: #333;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
            padding: 1rem 1.5rem;
        }

        .btn-teal {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        .btn-teal:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
            color: #fff;
        }

        .badge-login { background-color: #28a745; }
        .badge-logout { background-color: #dc3545; }
        .badge-other { background-color: #6c757d; }

        table.dataTable tbody tr:hover {
            background-color: #f0f9f9 !important;
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .back-btn {
            background: #fff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0"><i class="bi bi-activity me-2"></i>Activity Logs</h5>
            <div>
                <a href="?export=excel" class="btn btn-success me-2">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
                <a href="../super_admin/dashboard.php" class="btn back-btn me-2">
                    <i class="bi bi-arrow-left-circle"></i> Back
                </a>
                <button id="refreshBtn" class="btn btn-teal">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="activity" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                          <!--  <th>Description</th> -->
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($log = $result->fetch_assoc()): ?>
                                <?php
                                    $fullname = trim(($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? ''));
                                    $action = strtolower($log['action']);
                                    $badge = 'badge-other';
                                    if (strpos($action, 'login') !== false) $badge = 'badge-login';
                                    if (strpos($action, 'logout') !== false) $badge = 'badge-logout';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($fullname ?: $log['username'] ?? 'Unknown User') ?></strong><br>
                                        <small class="text-muted"><?= e($log['email'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><span class="badge <?= $badge ?>"><?= e(ucfirst($log['action'])) ?></span></td>
                                  <!--  <td><?= e($log['action_description'] ?? 'No description provided') ?></td> -->
                                    <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 mb-2"></i><br>No activity logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    const table = $('#activity').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Time column index updated (0,1,2,3)
        pageLength: 25,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search logs..."
        }
    });

    $('#refreshBtn').on('click', function() {
        location.reload();
    });
});
</script>
</body>

</html>