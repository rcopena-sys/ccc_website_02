<?php
session_start();
// Check if user is logged in and is a registrar (role_id = 2) or admin (role_id = 3)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// Initialize user data
$user = [
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'profile_image' => 'default-avatar.png'
];

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, profile_image FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user = array_merge($user, $row);
}
$stmt->close();

// Count BSIT students
$bsit_query = "SELECT COUNT(*) as count FROM students_db WHERE programs = 'BSIT'";
$bsit_result = $conn->query($bsit_query);
$bsit_count = $bsit_result ? (int)$bsit_result->fetch_assoc()['count'] : 0;

// Count BSCS students
$bscs_query = "SELECT COUNT(*) as count FROM students_db WHERE programs = 'BSCS'";
$bscs_result = $conn->query($bscs_query);
$bscs_count = $bscs_result ? (int)$bscs_result->fetch_assoc()['count'] : 0;

// Count total students
$total_query = "SELECT COUNT(*) as count FROM students_db";
$total_result = $conn->query($total_query);
$total_count = $total_result ? (int)$total_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            background: white;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            width: 220px;
            height: 100vh;
            color: #fff;
            padding: 30px 16px 16px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border-top-right-radius: 32px;
            border-bottom-right-radius: 32px;
            box-shadow: 2px 0 16px rgba(31,38,135,0.10);
        }
        .profile-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #fff;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(31,38,135,0.10);
            border: 4px solid #fff;
        }
        .profile-circle img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar .role {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .sidebar .label {
            font-size: 0.98rem;
            color: #e0e0e0;
            font-weight: 400;
            margin-bottom: 18px;
        }
        .sidebar .nav-link {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin: 8px 0;
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-size: 1.08rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #fff;
            color: #2563eb;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }
        .logout-btn {
            margin-top: auto;
            background: linear-gradient(90deg, #dc2626 0%, #f87171 100%);
            color: #fff;
            padding: 10px 0;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.08rem;
            letter-spacing: 0.5px;
            border: none;
            width: 100%;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: #b91c1c; color: #fff; }
        @media (max-width: 700px) {
            .sidebar { width: 100px; padding: 10px 4px; }
            .sidebar .nav-link, .logout-btn { font-size: 0.9rem; padding: 8px 0; }
            .profile-circle { width: 60px; height: 60px; }
            .profile-circle img { width: 52px; height: 52px; }
        }
        .content {
            flex: 1;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f3f4f6;
            width: 100%;
        }
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin: 24px 0;
        }
        .chart-area {
            width: 100%;
            max-width: 900px;
            height: 500px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
        <div class="profile-circle"><img src="regs.jpg" alt="My Small Photo"></div>
            <div class="role">Registrar</div>
            <div class="label">Registrar</div>
            <a href="dashboardr.php" class="nav-link active">Dashboard</a>
            <a href="registrar.php" class="nav-link">Student List</a>
            <a href="student_evaluation.php" class="nav-link"><i class="fas fa-clipboard-check"></i> Student Evaluation</a>
            <a href="studentgrade.php" class="nav-link">Student Grade</a>
            <a href="fiscal_year.php" class="nav-link"><i class="fas fa-calendar"></i> Fiscal Year</a>
            <a href="feedbackr.php" class="nav-link">Feedback</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin-bottom: 20px;">
                <h2 style="color:#374151; margin: 0;">Registrar Dashboard</h2>
                <div class="notification-container" style="position: relative;">
                    <a href="notification_page.php" style="color: #4b5563; text-decoration: none; position: relative;">
                        <i class="fas fa-bell" style="font-size: 24px;"></i>
                        <?php
                        // Count unread notifications
                        $unread_query = "SELECT COUNT(*) as unread_count FROM notifications_db 
                                      WHERE user_id = ? AND is_read = 0";
                        $stmt = $conn->prepare($unread_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $unread_result = $stmt->get_result();
                        $unread_count = $unread_result->fetch_assoc()['unread_count'];
                        $stmt->close();
                        
                        if ($unread_count > 0): ?>
                            <span class="notification-badge" style="
                                position: absolute;
                                top: -8px;
                                right: -8px;
                                background-color: #ef4444;
                                color: white;
                                border-radius: 50%;
                                width: 20px;
                                height: 20px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 12px;
                                font-weight: bold;
                            "><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-area">
                    <canvas id="deptBarChart"></canvas>
                </div>
            </div>
        </div>
    </>
    <script>
        const ctx = document.getElementById('deptBarChart').getContext('2d');
        const deptBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['BSIT', 'BSCS', 'Total Students'],
                datasets: [{
                    label: 'No. of Students',
                    data: [<?php echo $bsit_count; ?>, <?php echo $bscs_count; ?>, <?php echo $total_count; ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)', // BSIT
                        'rgba(16, 185, 129, 0.8)', // BSCS
                        'rgba(139, 92, 246, 0.8)'  // Total
                    ],
                    borderRadius: 8,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Student Population by Department',
                        font: { size: 18 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>
