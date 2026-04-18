<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Get statistics
$stats = [];

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Get statistics
$stats = [];

// Get user counts by role
$user_stats = [];
$roles = ['Super Admin', 'Admin', 'Registrar', 'Dean', 'Staff', 'Student'];
foreach ($roles as $role) {
    $sql = "SELECT COUNT(*) as count FROM signin_db s JOIN roles r ON s.role_id = r.role_id WHERE r.role_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $user_stats[$role] = $result->fetch_assoc()['count'];
    } else {
        $user_stats[$role] = 0;
    }
    $stmt->close();
}

// Get curriculum statistics and total number of curriculum programs (like curriculum.php)
// Per-fiscal-year stats (not currently shown but kept for possible future use)
$curriculum_stats = [];
$sql = "SELECT fiscal_year, COUNT(*) as count 
        FROM curriculum 
        WHERE fiscal_year IS NOT NULL AND fiscal_year != '' 
        GROUP BY fiscal_year 
        ORDER BY fiscal_year";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $curriculum_stats[] = $row;
    }
}

// Total distinct curriculum programs (same logic as Programs card in curriculum.php)
$total_curriculum_programs = 0;
$sql_programs = "SELECT COUNT(DISTINCT program) as program_count 
                 FROM curriculum 
                 WHERE program IS NOT NULL AND program != ''";
$result_programs = $conn->query($sql_programs);
if ($result_programs) {
    $row_programs = $result_programs->fetch_assoc();
    $total_curriculum_programs = (int)($row_programs['program_count'] ?? 0);
}

// Get total students from students_db only
$sql = "SELECT COUNT(*) as count FROM students_db";
$result = $conn->query($sql);
$total_students = $result ? (int)$result->fetch_assoc()['count'] : 0;

// Get recent activity
$recent_activity = [];
$sql = "SELECT al.*, s.firstname, s.lastname FROM activity_log_db al 
        LEFT JOIN signin_db s ON al.user_id = s.id 
        ORDER BY al.created_at DESC LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
}

// Get student classification data for pie chart
$classification_stats = [];
$regular_count = 0;
$irregular_count = 0;

// Get classifications from signin_db
$sql_signin = "SELECT classification, COUNT(*) as count 
               FROM signin_db s 
               JOIN roles r ON s.role_id = r.role_id 
               WHERE r.role_name IN ('BSIT', 'BSCS') 
               AND classification IN ('Regular', 'Irregular')
               GROUP BY classification";
$result_signin = $conn->query($sql_signin);
if ($result_signin) {
    while ($row = $result_signin->fetch_assoc()) {
        if (strtolower($row['classification']) == 'regular') {
            $regular_count += $row['count'];
        } elseif (strtolower($row['classification']) == 'irregular') {
            $irregular_count += $row['count'];
        }
    }
}

// Get classifications from students_db (all students)
$sql_students = "SELECT classification, COUNT(*) as count 
                 FROM students_db st 
                 WHERE classification IN ('Regular', 'Irregular')
                 GROUP BY classification";
$result_students = $conn->query($sql_students);
if ($result_students) {
    while ($row = $result_students->fetch_assoc()) {
        if (strtolower($row['classification']) == 'regular') {
            $regular_count += $row['count'];
        } elseif (strtolower($row['classification']) == 'irregular') {
            $irregular_count += $row['count'];
        }
    }
}

$classification_stats = [
    'regular' => $regular_count,
    'irregular' => $irregular_count,
    'total' => $regular_count + $irregular_count
];

// Get count of student accounts with role_id 4 or 5
$sql_student_accounts = "SELECT COUNT(*) as count FROM signin_db WHERE role_id IN (4, 5)";
$result_student_accounts = $conn->query($sql_student_accounts);
$student_accounts_count = $result_student_accounts ? $result_student_accounts->fetch_assoc()['count'] : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #eef3fb;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            position: relative;
            overflow-x: hidden;
            color: #111827;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 24%);
            z-index: 1;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(30, 58, 95, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(66, 133, 244, 0.2);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .sidebar-header {
            padding: 30px 25px;
            text-align: center;
            border-bottom: 1px solid rgba(66, 133, 244, 0.2);
            position: relative;
        }
        
        .sidebar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(66, 133, 244, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .profile-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4285f4, #669df6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .profile-circle i {
            color: white;
            font-size: 32px;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 5px 0 0;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(66, 133, 244, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            background: rgba(66, 133, 244, 0.1);
            color: white;
            border-left-color: #4285f4;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(66, 133, 244, 0.2);
            color: white;
            border-left-color: #4285f4;
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 16px;
            position: relative;
            z-index: 10;
        }

        .dashboard-shell {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
        }
        
        .page-header {
            margin-bottom: 0;
            animation: none;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(229, 231, 235, 0.95);
            border-radius: 18px;
            padding: 16px 20px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        
        .page-header h1 {
            color: #111827;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        
        .page-header p {
            color: #9ca3af;
            font-size: 0.92rem;
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .header-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(229, 231, 235, 0.95);
            color: #6b7280;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            text-decoration: none;
            white-space: nowrap;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(229, 231, 235, 0.95);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            animation: none;
            cursor: pointer;
            text-decoration: none;
            display: block;
            min-height: 112px;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #4285f4, #669df6);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 18px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .stat-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .stat-icon.students { background: linear-gradient(135deg, #4285f4, #669df6); }
        .stat-icon.staff { background: linear-gradient(135deg, #34a853, #5bb974); }
        .stat-icon.admin { background: linear-gradient(135deg, #fbbc04, #fdd663); }
        .stat-icon.registrar { background: linear-gradient(135deg, #ea4335, #f56565); }
        .stat-icon.dean { background: linear-gradient(135deg, #9333ea, #a855f7); }
        .stat-icon.curriculum { background: linear-gradient(135deg, #06b6d4, #22d3ee); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .stat-label {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        /* Chart Container */
        .chart-container {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(229, 231, 235, 0.95);
            margin-bottom: 0;
            animation: none;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        
        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }
        
        .chart-subtitle {
            color: #6b7280;
            font-size: 0.84rem;
            margin: 5px 0 0;
        }

        .chart-layout {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 16px;
            align-items: start;
        }

        .chart-panel,
        .activity-feed {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(229, 231, 235, 0.95);
        }

        .chart-panel {
            min-height: 470px;
        }

        .activity-feed {
            min-height: 470px;
        }

        .classification-wrap {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            align-items: center;
        }

        .classification-stats {
            display: grid;
            gap: 14px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .metric-row .label {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .metric-row .count {
            font-weight: 700;
            font-size: 1.2rem;
            color: #111827;
        }

        .progress-track {
            height: 8px;
            border-radius: 999px;
            overflow: hidden;
            background: #e5e7eb;
        }

        .progress-bar.regular {
            background: #34a853;
        }

        .progress-bar.irregular {
            background: #ea4335;
        }

        .summary-box {
            padding-top: 14px;
            border-top: 1px solid rgba(229, 231, 235, 0.95);
        }
        
        /* Activity Feed */
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid rgba(229, 231, 235, 0.95);
            transition: all 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: rgba(37, 99, 235, 0.04);
            margin: 0 -10px;
            padding: 15px 10px;
            border-radius: 10px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }
        
        .activity-icon.login { background: linear-gradient(135deg, #4285f4, #669df6); }
        .activity-icon.logout { background: linear-gradient(135deg, #ea4335, #f56565); }
        .activity-icon.create { background: linear-gradient(135deg, #34a853, #5bb974); }
        .activity-icon.update { background: linear-gradient(135deg, #fbbc04, #fdd663); }
        .activity-icon.delete { background: linear-gradient(135deg, #ea4335, #f56565); }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            color: #111827;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
            .stats-grid,
            .chart-layout,
            .classification-wrap {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="profile-circle">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></h3>
            <p>Super Admin</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="homepage.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    User Management
                </a>
            </div>
            <div class="nav-item">
                <a href="calendars.php" class="nav-link">
                    <i class="fas fa-calendar-alt"></i>
                    Calendar
                </a>
            </div>
            <div class="nav-item">
                <a href="view_feedback.php" class="nav-link">
                    <i class="fas fa-comments"></i>
                    Feedback
                </a>
            </div>
            <div class="nav-item">
                <a href="activity.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    Activity Logs
                </a>
            </div>
            <div class="nav-item">
                <a href="notification.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    Notifications
                </a>
            </div>
              <div class="nav-item">
                <a href="about_us.php" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    Abouts Us
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-shell">
        <div class="page-header">
            <div>
                <h1>Dashboard Overview</h1>
                <p>Welcome back! Here's what's happening in your system today.</p>
            </div>
            <div class="header-actions">
                <div class="header-pill">
                    <i class="fas fa-sync-alt"></i>
                    <span>Data refreshed at <?php echo date('M d, Y h:i A'); ?></span>
                </div>
                <a href="notification.php" class="header-pill">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </div>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <a href="list_students.php?filter=students" class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-value"><?php echo number_format($total_students); ?></div>
                <div class="stat-label">Total Students</div>
            </a>
            
            <a href="admin.php?filter=admin" class="stat-card">
                <div class="stat-icon admin">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['Super Admin'] ?? 0); ?></div>
                <div class="stat-label">Admins</div>
            </a>
            
            <a href="registrar.php?filter=registrar" class="stat-card">
                <div class="stat-icon registrar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['Registrar'] ?? 0); ?></div>
                <div class="stat-label">Registrars</div>
            </a>
            
            <a href="deans.php?filter=dean" class="stat-card">
                <div class="stat-icon dean">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo number_format($user_stats['Dean'] ?? 0); ?></div>
                <div class="stat-label">Deans</div>
            </a>
            
            <a href="curriculum.php" class="stat-card">
                <div class="stat-icon curriculum">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-value"><?php echo number_format($total_curriculum_programs); ?></div>
                <div class="stat-label">curriculum</div>
            </a>
            
            <a href="student_acc.php" class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-value"><?php echo number_format($student_accounts_count); ?></div>
                <div class="stat-label">Student Accounts</div>
            </a>
        </div>
        
        <!-- Student Classification Chart -->
        <div class="chart-layout">
        <div class="chart-panel">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Student Classification Distribution</h3>
                    <p class="chart-subtitle">Regular versus irregular student count.</p>
                </div>
            </div>
            <div class="classification-wrap">
                <div>
                    <canvas id="classificationChart"></canvas>
                </div>
                <div class="classification-stats">
                    <div>
                        <div class="metric-row">
                            <span class="label" style="color:#34a853;">Regular Students</span>
                            <span class="count"><?php echo number_format($classification_stats['regular']); ?></span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar regular" style="height:100%; width: <?php echo $classification_stats['total'] > 0 ? ($classification_stats['regular'] / $classification_stats['total']) * 100 : 0; ?>%;"></div>
                        </div>
                        <div style="text-align:right; margin-top:5px; color:#6b7280; font-size:0.88rem;">
                            <?php echo $classification_stats['total'] > 0 ? round(($classification_stats['regular'] / $classification_stats['total']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    <div>
                        <div class="metric-row">
                            <span class="label" style="color:#ea4335;">Irregular Students</span>
                            <span class="count"><?php echo number_format($classification_stats['irregular']); ?></span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-bar irregular" style="height:100%; width: <?php echo $classification_stats['total'] > 0 ? ($classification_stats['irregular'] / $classification_stats['total']) * 100 : 0; ?>%;"></div>
                        </div>
                        <div style="text-align:right; margin-top:5px; color:#6b7280; font-size:0.88rem;">
                            <?php echo $classification_stats['total'] > 0 ? round(($classification_stats['irregular'] / $classification_stats['total']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    <div class="summary-box">
                        <div class="metric-row" style="margin-bottom:0;">
                            <span class="label" style="color:#111827;">Total Students</span>
                            <span class="count" style="font-size:1.3rem;"><?php echo number_format($classification_stats['total']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-feed">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">User Logs</h3>
                    <p class="chart-subtitle">Latest system activities</p>
                </div>
            </div>
            
            <?php if (!empty($recent_activity)): ?>
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo strtolower($activity['action'] ?? 'login'); ?>">
                            <i class="fas fa-<?php echo getActivityIcon($activity['action'] ?? 'login'); ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <?php 
                                $userName = !empty($activity['firstname']) ? $activity['firstname'] . ' ' . $activity['lastname'] : 'Unknown User';
                                echo htmlspecialchars($userName) . ' - ' . htmlspecialchars($activity['action'] ?? 'Unknown action');
                                ?>
                            </div>
                            <div class="activity-time">
                                <?php echo formatTimeAgo($activity['created_at'] ?? ''); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No recent activity found.</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeInUp 0.8s ease-out forwards';
                    }
                });
            });
            
            document.querySelectorAll('.stat-card').forEach(card => {
                observer.observe(card);
            });
            
            // Create Student Classification Pie Chart
            const ctx = document.getElementById('classificationChart').getContext('2d');
            const classificationData = {
                labels: ['Regular Students', 'Irregular Students'],
                datasets: [{
                    data: [
                        <?php echo $classification_stats['regular']; ?>,
                        <?php echo $classification_stats['irregular']; ?>
                    ],
                    backgroundColor: [
                        '#34a853', // Green for Regular
                        '#ea4335'  // Red for Irregular
                    ],
                    borderColor: [
                        '#ffffff',
                        '#ffffff'
                    ],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            };
            
            const classificationChart = new Chart(ctx, {
                type: 'pie',
                data: classificationData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 14,
                                    weight: '500',
                                    family: 'Inter, sans-serif'
                                },
                                color: '#1e3a5f'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                }
                            },
                            backgroundColor: 'rgba(30, 58, 95, 0.9)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(66, 133, 244, 0.3)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            titleFont: {
                                size: 14,
                                weight: '600',
                                family: 'Inter, sans-serif'
                            },
                            bodyFont: {
                                size: 13,
                                family: 'Inter, sans-serif'
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: false,
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
// Helper functions
function getActivityIcon($action) {
    $icons = [
        'Login' => 'sign-in-alt',
        'Logout' => 'sign-out-alt',
        'Create' => 'plus',
        'Update' => 'edit',
        'Delete' => 'trash'
    ];
    return $icons[$action] ?? 'circle';
}

function formatTimeAgo($datetime) {
    if (empty($datetime)) return 'Unknown time';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $time);
}
?>