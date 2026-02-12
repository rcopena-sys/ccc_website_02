<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Get Dean users only (Dean, DBA Dean, DTE Dean, DAS Dean)
$dean_list = [];
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT s.*, r.role_name FROM signin_db s 
        JOIN roles r ON s.role_id = r.role_id 
        WHERE r.role_name IN ('Dean', 'DBA Dean', 'DTE Dean', 'DAS Dean')";

$params = [];

// Add search condition if search term exists
if (!empty($search)) {
    $sql .= " AND (s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ? OR s.student_id LIKE ?)";
    $search_param = "%" . $search . "%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$sql .= " ORDER BY 
          CASE 
            WHEN r.role_name = 'Dean' THEN 1
            WHEN r.role_name = 'DBA Dean' THEN 2
            WHEN r.role_name = 'DAS Dean' THEN 3
            WHEN r.role_name = 'DTE Dean' THEN 4
          END,
          s.lastname, s.firstname";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $dean_list[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Dean Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 25%, #334155 50%, #1e293b 75%, #0f172a 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Ambient background effects */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(147, 197, 253, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(30, 64, 175, 0.08) 0%, transparent 50%);
            animation: float 25s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(0.5deg); }
            50% { transform: translateY(10px) rotate(-0.5deg); }
            75% { transform: translateY(-5px) rotate(0.3deg); }
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
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
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            position: relative;
        }
        
        .sidebar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, transparent 70%);
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
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
            animation: pulse 4s ease-in-out infinite;
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
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: white;
            border-left-color: #3b82f6;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            color: white;
            border-left-color: #3b82f6;
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
            padding: 30px;
            position: relative;
            z-index: 10;
        }
        
        .page-header {
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-header h1 {
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .page-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Search and Filter Section */
        .controls-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(15, 23, 42, 0.3),
                0 10px 20px rgba(30, 41, 59, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            max-width: 400px;
            padding: 16px 20px 16px 50px;
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
        }
        
        /* Dean Cards Grid */
        .dean-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .dean-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(15, 23, 42, 0.3),
                0 10px 20px rgba(30, 41, 59, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out both;
            position: relative;
            overflow: hidden;
        }
        
        .dean-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 60px rgba(15, 23, 42, 0.4),
                0 15px 30px rgba(30, 41, 59, 0.3);
        }
        
        .dean-card.dean::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #7c3aed, #8b5cf6, #a78bfa);
        }
        
        .dean-card.dba-dean::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #fbbf24, #fcd34d);
        }
        
        .dean-card.das-dean::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
        }
        
        .dean-card.dte-dean::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444, #f87171, #fca5a5);
        }
        
        .dean-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dean-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
            position: relative;
        }
        
        .dean-avatar::after {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 50%;
            z-index: -1;
            opacity: 0.3;
            animation: pulse 3s ease-in-out infinite;
        }
        
        .dean-card.dean .dean-avatar {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
        }
        
        .dean-card.dean .dean-avatar::after {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
        }
        
        .dean-card.dba-dean .dean-avatar {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }
        
        .dean-card.dba-dean .dean-avatar::after {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }
        
        .dean-card.das-dean .dean-avatar {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .dean-card.das-dean .dean-avatar::after {
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        
        .dean-card.dte-dean .dean-avatar {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }
        
        .dean-card.dte-dean .dean-avatar::after {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }
        
        .dean-avatar i {
            color: white;
            font-size: 28px;
        }
        
        .dean-info h3 {
            color: #0f172a;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        
        .dean-role {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .dean-card.dean .dean-role {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .dean-card.dba-dean .dean-role {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .dean-card.das-dean .dean-role {
            background: linear-gradient(135deg, #10b981, #34d399);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .dean-card.dte-dean .dean-role {
            background: linear-gradient(135deg, #ef4444, #f87171);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .dean-role i {
            margin-right: 8px;
        }
        
        .dean-details {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.8;
        }
        
        .dean-detail {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: rgba(59, 130, 246, 0.05);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .dean-detail:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(5px);
        }
        
        .dean-detail i {
            width: 24px;
            margin-right: 12px;
            color: #3b82f6;
            font-size: 16px;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(15, 23, 42, 0.3),
                0 10px 20px rgba(30, 41, 59, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            text-align: center;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out both;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
            color: white;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 25px;
            color: rgba(255, 255, 255, 0.3);
        }
        
        .empty-state h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        /* Clock */
        #clock {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.95);
            color: #0f172a;
            padding: 12px 20px;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
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
            .dean-grid {
                grid-template-columns: 1fr;
            }
            .stats-container {
                grid-template-columns: 1fr;
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
            <h3>Super Admin</h3>
            <p>Administrator</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="admin.php" class="nav-link">
                    <i class="fas fa-user-shield"></i>
                    Administrators
                </a>
            </div>
            <div class="nav-item">
                <a href="registrar.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    Registrars
                </a>
            </div>
            <div class="nav-item">
                <a href="deans.php" class="nav-link active">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Deans
                </a>
            </div>
            <div class="nav-item">
                <a href="staff.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Staff Management
                </a>
            </div>
            <div class="nav-item">
                <a href="list_students.php" class="nav-link">
                    <i class="fas fa-graduation-cap"></i>
                    Student List
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>Dean Management</h1>
            <p>View and manage dean staff members</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card" style="animation-delay: 0.1s;">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo count($dean_list); ?></div>
                <div class="stat-label">Total Deans</div>
            </div>
        </div>
        
        <!-- Search Section -->
        <div class="controls-section">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <form method="GET" action="" style="display: flex; align-items: center;">
                    <input type="text" name="search" class="search-input" placeholder="Search deans by name, email, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
        </div>
        
        <!-- Dean Grid -->
        <?php if (!empty($dean_list)): ?>
            <div class="dean-grid">
                <?php foreach ($dean_list as $index => $dean): ?>
                    <?php 
                    $roleClass = '';
                    $iconClass = 'fas fa-chalkboard-teacher';
                    if (strtolower($dean['role_name']) === 'dean') {
                        $roleClass = 'dean';
                        $iconClass = 'fas fa-user-graduate';
                    } elseif (strtolower($dean['role_name']) === 'dba dean') {
                        $roleClass = 'dba-dean';
                        $iconClass = 'fas fa-briefcase';
                    } elseif (strtolower($dean['role_name']) === 'das dean') {
                        $roleClass = 'das-dean';
                        $iconClass = 'fas fa-flask';
                    } elseif (strtolower($dean['role_name']) === 'dte dean') {
                        $roleClass = 'dte-dean';
                        $iconClass = 'fas fa-graduation-cap';
                    }
                    ?>
                    <div class="dean-card <?php echo $roleClass; ?>" style="animation-delay: <?php echo ($index + 1) * 0.1; ?>s;">
                        <div class="dean-header">
                            <div class="dean-avatar">
                                <i class="<?php echo $iconClass; ?>"></i>
                            </div>
                            <div class="dean-info">
                                <h3><?php echo htmlspecialchars($dean['firstname'] . ' ' . $dean['lastname']); ?></h3>
                                <div class="dean-role">
                                    <i class="<?php echo $iconClass; ?>"></i>
                                    <?php echo htmlspecialchars($dean['role_name']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dean-details">
                            <div class="dean-detail">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($dean['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="dean-detail">
                                <i class="fas fa-id-card"></i>
                                <span><?php echo htmlspecialchars($dean['student_id'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="dean-detail">
                                <i class="fas fa-book"></i>
                                <span><?php echo htmlspecialchars($dean['course'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="dean-detail">
                                <i class="fas fa-layer-group"></i>
                                <span><?php echo htmlspecialchars($dean['classification'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="controls-section">
                <div class="empty-state">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>No Deans Found</h3>
                    <p><?php echo !empty($search) ? 'No deans match your search criteria.' : 'No deans found in the system.'; ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div id="clock"></div>
    
    <script>
        // Clock functionality
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('clock');
            clock.textContent = now.toLocaleTimeString();
        }

        // Update clock every second
        setInterval(updateClock, 1000);

        // Initial clock update
        document.addEventListener('DOMContentLoaded', updateClock);
        
        // Auto-submit search on input (optional enhancement)
        const searchInput = document.querySelector('.search-input');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>