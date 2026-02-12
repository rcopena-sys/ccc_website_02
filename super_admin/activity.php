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
    
    // Create CSV content
    $csv_content = "User,Action,Description,IP Address,Time\n";
    
    if ($result && $result->num_rows > 0) {
        while($log = $result->fetch_assoc()) {
            $fullname = trim(($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? ''));
            $username = $fullname ?: ($log['username'] ?? 'Unknown User');
            $email = $log['email'] ?? 'N/A';
            // Combine name and email to match table display
            $user_display = $username . ' (' . $email . ')';
            $action = ucfirst($log['action'] ?? '');
            $description = $log['action_description'] ?? 'No description provided';
            $ip_address = $log['ip_address'] ?? 'N/A';
            $time = date('Y-m-d H:i:s', strtotime($log['created_at']));
            
            // Escape fields with commas
            $csv_content .= '"' . str_replace('"', '""', $user_display) . '",';
            $csv_content .= '"' . str_replace('"', '""', $action) . '",';
            $csv_content .= '"' . str_replace('"', '""', $description) . '",';
            $csv_content .= '"' . str_replace('"', '""', $ip_address) . '",';
            $csv_content .= '"' . str_replace('"', '""', $time) . "\"\n";
        }
    }
    
    // Set headers for CSV download
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --bg-1: #0a1929;
            --bg-2: #1e3a5f;
            --bg-3: #2e5490;
            --accent-1: #4285f4;
            --accent-2: #669df6;
            --card-bg: rgba(255,255,255,0.95);
            --muted: #6b7280;
            --text: #1f2937;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--bg-1) 0%, var(--bg-2) 25%, var(--bg-3) 50%, var(--bg-2) 75%, var(--bg-1) 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            color: var(--text);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .card {
            border: none;
            border-radius: 12px;
            background: var(--card-bg);
            box-shadow: 0 20px 40px rgba(10,25,41,0.2);
            margin-top: 30px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(66,133,244,0.08);
        }

        .card-header {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-teal {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-teal:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(66,133,244,0.18); }

        .btn-success { background: linear-gradient(135deg, #28a745, #20c997); color: #fff; border: none; border-radius: 8px; }
        .btn-success:hover { transform: translateY(-2px); }

        .badge-login { background-color: #34a853; }
        .badge-logout { background-color: #ef4444; }
        .badge-other { background-color: #6c757d; }

        table.dataTable tbody tr:hover { background-color: rgba(34, 197, 94, 0.04) !important; }

        .table thead th {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: white;
            border: none;
        }

        .back-btn {
            background: #fff;
            color: var(--accent-1);
            border: 1px solid rgba(66,133,244,0.2);
            border-radius: 8px;
        }

        .back-btn:hover { background: linear-gradient(90deg, var(--accent-1), var(--accent-2)); color: #fff; }

        /* Small responsive tweaks */
        @media (max-width: 768px) {
            .card { margin: 15px; }
            .card-header { padding: 0.75rem 1rem; }
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
                <a href="../super_admin/homepage.php" class="btn back-btn me-2">
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
                            <th>Description</th>
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
                                    <td><?= e($log['action_description'] ?? 'No description provided') ?></td>
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