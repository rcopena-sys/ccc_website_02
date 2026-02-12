<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Get staff members (Super Admin, Dean, Registrar)
$staff_list = [];
$roles = ['Super Admin', 'Dean', 'Registrar'];
$search = isset($_GET['search']) ? $_GET['search'] : '';

foreach ($roles as $role) {
    $sql = "SELECT s.*, r.role_name FROM signin_db s 
            JOIN roles r ON s.role_id = r.role_id 
            WHERE r.role_name = ?";
    
    $params = [$role];
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $sql .= " AND (s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ? OR s.student_id LIKE ?)";
        $search_param = "%" . $search . "%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    $sql .= " ORDER BY s.lastname, s.firstname";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $staff_list[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Staff Management</title>
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
        
        /* Staff Cards Grid */
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .staff-card {
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
        
        .staff-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e40af, #3b82f6, #60a5fa);
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 60px rgba(15, 23, 42, 0.4),
                0 15px 30px rgba(30, 41, 59, 0.3);
        }
        
        .staff-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .staff-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .staff-avatar i {
            color: white;
            font-size: 24px;
        }
        
        .staff-info h3 {
            color: #0f172a;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .staff-role {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .role-super-admin {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
        }
        
        .role-dean {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
        }
        
        .role-registrar {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
        }
        
        .staff-details {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .staff-detail {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .staff-detail i {
            width: 20px;
            margin-right: 10px;
            color: #3b82f6;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 25px;
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
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
            color: white;
        }
        
        .stat-icon.super-admin {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }
        
        .stat-icon.dean {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
        }
        
        .stat-icon.registrar {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.3);
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
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
            .staff-grid {
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
                <a href="staff.php" class="nav-link active">
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
            <h1>Staff Management</h1>
            <p>View and manage administrative staff members</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <?php
            $super_admin_count = 0;
            $dean_count = 0;
            $registrar_count = 0;
            
            foreach ($staff_list as $staff) {
                switch ($staff['role_name']) {
                    case 'Super Admin':
                        $super_admin_count++;
                        break;
                    case 'Dean':
                        $dean_count++;
                        break;
                    case 'Registrar':
                        $registrar_count++;
                        break;
                }
            }
            ?>
            
            <div class="stat-card" style="animation-delay: 0.1s;">
                <div class="stat-icon super-admin">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-value"><?php echo $super_admin_count; ?></div>
                <div class="stat-label">Super Admins</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.2s;">
                <div class="stat-icon dean">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?php echo $dean_count; ?></div>
                <div class="stat-label">Deans</div>
            </div>
            
            <div class="stat-card" style="animation-delay: 0.3s;">
                <div class="stat-icon registrar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-value"><?php echo $registrar_count; ?></div>
                <div class="stat-label">Registrars</div>
            </div>
        </div>
        
        <!-- Search Section -->
        <div class="controls-section">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <form method="GET" action="" style="display: flex; align-items: center;">
                    <input type="text" name="search" class="search-input" placeholder="Search staff by name, email, or ID..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
        </div>
        
        <!-- Staff Grid -->
        <?php if (!empty($staff_list)): ?>
            <div class="staff-grid">
                <?php foreach ($staff_list as $index => $staff): ?>
                    <div class="staff-card" style="animation-delay: <?php echo ($index + 1) * 0.1; ?>s;">
                        <div class="staff-header">
                            <div class="staff-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="staff-info">
                                <h3><?php echo htmlspecialchars($staff['firstname'] . ' ' . $staff['lastname']); ?></h3>
                                <span class="staff-role role-<?php echo strtolower(str_replace(' ', '-', $staff['role_name'])); ?>">
                                    <?php echo htmlspecialchars($staff['role_name']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="staff-details">
                            <div class="staff-detail">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($staff['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="staff-detail">
                                <i class="fas fa-id-card"></i>
                                <span><?php echo htmlspecialchars($staff['student_id'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="staff-detail">
                                <i class="fas fa-book"></i>
                                <span><?php echo htmlspecialchars($staff['course'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="staff-detail">
                                <i class="fas fa-layer-group"></i>
                                <span><?php echo htmlspecialchars($staff['classification'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="controls-section">
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Staff Found</h3>
                    <p><?php echo !empty($search) ? 'No staff members match your search criteria.' : 'No staff members found in the system.'; ?></p>
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