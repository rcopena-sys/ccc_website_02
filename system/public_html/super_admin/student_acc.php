<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Student Accounts</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', sans-serif;
        }
        
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            position: relative;
            color: #f8fafc;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.03"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.02"/><circle cx="20" cy="60" r="0.5" fill="white" opacity="0.02"/><circle cx="80" cy="40" r="0.5" fill="white" opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }
        
        .container { 
            display: flex; 
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }
        
        .sidebar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            width: 280px;
            min-height: 100vh;
            color: #e2e8f0;
            padding: 30px 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 4px 0 20px rgba(0,0,0,0.2);
            border-right: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .profile-circle { 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            margin: 20px 0 30px; 
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 50px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
            border: 4px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .profile-circle:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.4);
        }
        
        .sidebar > div:first-of-type {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        
        .sidebar nav {
            width: 100%;
            flex-grow: 1;
        }
        
        .sidebar nav a {
            display: block; 
            padding: 14px 20px; 
            color: #e2e8f0; 
            text-decoration: none; 
            border-radius: 10px; 
            margin-bottom: 8px;
            background: rgba(255,255,255,0.05);
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.05);
            text-align: left;
            backdrop-filter: blur(10px);
        }
        
        .sidebar nav a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(59, 130, 246, 0.8);
            color: white;
            transform: translateX(8px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            border-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .logout-btn {
            margin-top: auto;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        
        .content {
            flex-grow: 1;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            margin: 20px;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
        }
        
        .content:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #1e293b;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-container input[type="text"],
        .search-container select {
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            color: #1e293b;
        }
        
        .search-container input[type="text"]:focus,
        .search-container select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .search-container button {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-container button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            color: #000000; /* Black text for better visibility */
        }
        
        th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f8fafc;
            transform: translateX(2px);
        }
        
        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
        }
        
        .status.active {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        
        .status.inactive {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            font-size: 16px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-top: 20px;
            border: 1px dashed #e2e8f0;
        }
        
        .no-results i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
            display: block;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 8px;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: white;
            color: #4b5563;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .pagination a:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-edit {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        
        .btn-edit:hover {
            background: #bae6fd;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
        }
        
        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15);
        }
        
        .btn-view {
            background: #f0f9ff;
            color: #0369a1;
            border: 1px solid #e0f2fe;
        }
        
        .btn-view:hover {
            background: #e0f2fe;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.15);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="profile-circle">
            <i class="fas fa-user-shield"></i>
        </div>
        <div>Super Admin</div>
        
        <nav>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="homepage.php"><i class="fas fa-users"></i> User Management</a>
            <a href="list_students.php"><i class="fas fa-user-graduate"></i> Student List</a>
            <a href="student_acc.php" class="active"><i class="fas fa-user-check"></i> Student Accounts</a>
            <a href="curriculum.php"><i class="fas fa-book"></i> Curriculum</a>
            <a href="calendars.php"><i class="fas fa-calendar-alt"></i> Calendar</a>
            <a href="view_feedback.php"><i class="fas fa-comments"></i> Feedback</a>
            <a href="activity.php"><i class="fas fa-chart-line"></i> Activity Logs</a>
            <a href="notification.php"><i class="fas fa-bell"></i> Notifications</a>
        </nav>
        
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i> Logout
        </a>
    </div>
    
    <div class="content">
        <div class="header">
            <h1>Student Accounts</h1>
        </div>
        
        <form method="GET" action="" class="search-container">
            <input type="text" name="search" placeholder="Search by name, ID or email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <select name="course">
                <option value="">All Courses</option>
                <option value="BSIT" <?php echo (isset($_GET['course']) && $_GET['course'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                <option value="BSCS" <?php echo (isset($_GET['course']) && $_GET['course'] == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
            </select>
            <button type="submit"><i class="fas fa-search" style="margin-right: 8px;"></i> Search</button>
            <a href="student_acc.php" class="btn" style="background: #e2e8f0; color: #4b5563; text-decoration: none; padding: 12px 20px; border-radius: 10px;">
                <i class="fas fa-sync-alt" style="margin-right: 8px;"></i> Reset
            </a>
        </form>
        
        <?php
        // Handle search and filter
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $course = isset($_GET['course']) ? trim($_GET['course']) : '';
        
        // Base query for student accounts (role_id 4 or 5)
        $where = ["s.role_id IN (4, 5)"];
        $params = [];
        $types = '';
        
        // Add search condition
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $where[] = "(s.student_id LIKE ? OR s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }
        
        // Add course filter
        if (!empty($course)) {
            $where[] = "s.course = ?";
            $params[] = $course;
            $types .= 's';
        }
        
        // Build the query
        $sql = "SELECT s.*, r.role_name 
                FROM signin_db s 
                JOIN roles r ON s.role_id = r.role_id 
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY s.lastname, s.firstname";
        
        // Prepare and execute the query
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Student ID</th>';
            echo '<th>Name</th>';
            echo '<th>Email</th>';
            echo '<th>Course</th>';
            echo '<th>Role</th>';
            echo '<th>Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['student_id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars($row['course'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['role_name']) . '</td>';
                $isActive = $row['is_active'] ?? 1; // Default to active (1) if not set
                echo '<td><span class="status ' . ($isActive ? 'active' : 'inactive') . '">' . ($isActive ? 'Active' : 'Inactive') . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<div class="no-results">';
            echo '<i class="fas fa-user-slash" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>';
            echo '<p>No student accounts found.</p>';
            echo '</div>';
        }
        
        $stmt->close();
        $conn->close();
        ?>
    </div>
</div>

<script>
    // Add animation to table rows
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            row.style.transition = `all 0.3s ease ${index * 0.05}s`;
            
            // Trigger reflow
            void row.offsetWidth;
            
            // Animate in
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        });
    });
</script>

</body>
</html>