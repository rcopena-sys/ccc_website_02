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

// Fetch students data
$students_query = "SELECT * FROM students_db ORDER BY student_name ASC";
$students_result = $conn->query($students_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            background: white;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
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
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
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
            margin-left: 220px;
            height: 100vh;
            overflow-y: auto;
        }
        .students-container {
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
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .students-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .students-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        .students-table tr:hover {
            background: #f9fafb;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="profile-circle"><img src="regs.jpg" alt="My Small Photo"></div>
            <div class="role">Registrar</div>
            <div class="label">Registrar</div>
            <a href="dashboardr.php" class="nav-link">Dashboard</a>
            <a href="registrar.php" class="nav-link active">Student List</a>
            <a href="student_evaluation.php" class="nav-link"><i class="fas fa-clipboard-check"></i> Student Evaluation</a>
            <a href="studentgrade.php" class="nav-link">Student Grade</a>
            <a href="fiscal_year.php" class="nav-link"><i class="fas fa-calendar"></i> Fiscal Year</a>
            <a href="feedbackr.php" class="nav-link">Feedback</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
        </div>
        <div class="content">
            <div class="header-actions">
                <h2 style="color:#374151; margin: 0;">Student List</h2>
                <div>
                    <a href="addsturegs.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Student
                    </a>
                    <a href="upload_csv.php" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload CSV
                    </a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="header-actions" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; align-items: center; width: 100%;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by Student ID, Name, Email, or Program..." style="flex: 1; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <button type="button" class="btn btn-primary" onclick="searchStudents()" style="padding: 10px 20px;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="students-container">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Gender</th>
                            <th>Fiscal Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($students_result && $students_result->num_rows > 0) {
                            while ($student = $students_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['student_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['programs']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['academic_year']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['semester']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['status']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['gender']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['fiscal_year']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' style='text-align: center; padding: 40px; color: #6b7280;'>No students found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function searchStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.students-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm) || searchTerm === '';
                row.style.display = shouldShow ? '' : 'none';
            });
        }
        
        // Add search on Enter key
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchStudents();
            }
        });
    </script>
</body>
</html> 
 