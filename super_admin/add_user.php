<?php
session_start();
require_once '../config/connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate form data
        $firstname = clean_input($conn, $_POST['firstname'] ?? '');
        $lastname = clean_input($conn, $_POST['lastname'] ?? '');
        $email = clean_input($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        // Convert empty student_id to NULL
        $student_id = !empty(trim($_POST['student_id'] ?? '')) ? clean_input($conn, $_POST['student_id']) : null;
        $course = clean_input($conn, $_POST['course'] ?? '');
        $classification = clean_input($conn, $_POST['classification'] ?? 'regular'); // Default to regular
        $role_id = intval($_POST['role_id'] ?? 6); // Default to student role

        // Determine whether the selected role represents a student role
        $isStudentRole = false;
        $roleNameStmt = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ? LIMIT 1");
        if ($roleNameStmt) {
            $roleNameStmt->bind_param('i', $role_id);
            $roleNameStmt->execute();
            $rres = $roleNameStmt->get_result();
            if ($rrow = $rres->fetch_assoc()) {
                $roleName = strtolower($rrow['role_name'] ?? '');
                if (strpos($roleName, 'student') !== false || $roleName === 'bscs' || $roleName === 'bsit') {
                    $isStudentRole = true;
                }
            }
            $roleNameStmt->close();
        }

        // If the selected role is not a student role, clear student-related fields to avoid being treated as student
        // Exception: Dean (role_id = 2) can have student fields if needed
        if (!$isStudentRole && $role_id != 2) {
            $student_id = null;
            $course = '';
            $classification = '';
        }

        // Validation
        if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }
        
        // Check dean account limit (maximum 2 dean accounts allowed)
        if ($role_id == 2) {
            $deanCountStmt = $conn->prepare("SELECT COUNT(*) as count FROM signin_db WHERE role_id = 2");
            $deanCountStmt->execute();
            $deanResult = $deanCountStmt->get_result();
            $deanCount = $deanResult->fetch_assoc()['count'];
            $deanCountStmt->close();
            
            if ($deanCount >= 2) {
                throw new Exception('Maximum of 2 dean accounts allowed. Cannot create more dean accounts.');
            }
        }

        // Clean and format names
        $firstname = trim($firstname);
        $lastname = trim($lastname);

        // Validate first name
        if (empty($firstname)) {
            throw new Exception('First name is required');
        }

        // Format first name: capitalize first letter, rest lowercase
        $firstname = ucfirst(strtolower($firstname));

        // Allow letters, spaces, hyphens, and apostrophes, with at least one capital letter
      if (!preg_match('/^(?=.*[A-ZÁÉÍÓÚÜÑ])[A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\'-]*$/u', $firstname)) {
    throw new Exception('Please enter a valid first name with at least one capital letter (e.g., "John")');
}

        // Validate last name
        if (empty($lastname)) {
            throw new Exception('Last name is required');
        }

        // Format last name: capitalize first letter, rest lowercase
        $lastname = ucfirst(strtolower($lastname));

        // Allow letters, spaces, hyphens, and apostrophes, with at least one capital letter
      if (!preg_match('/^(?=.*[A-ZÁÉÍÓÚÜÑ])[A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\'-]*$/u', $lastname)) {
    throw new Exception('Please enter a valid last name with at least one capital letter (e.g., "Doe")');
}

        // If role is student, ensure student-specific fields are present
        if ($isStudentRole) {
            if (empty($student_id) || empty($course)) {
                throw new Exception('Student ID and Course are required for student roles.');
            }
            // ensure classification has a sensible default
            if (empty($classification)) $classification = 'regular';
            
            // Validate student ID format (YYYY-#####)
            if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
                throw new Exception('Student ID must be in the format: YYYY-##### (e.g., 2022-10873)');
            }
        }

        // Basic email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }
        
        // Block Gmail addresses first (more specific check)
        if (strpos(strtolower($email), '@gmail.com') !== false) {
            throw new Exception('Gmail addresses are not accepted. Please use your @ccc.edu.ph school email.');
        }
        
        // Validate email domain (must be @ccc.edu.ph)
        if (!preg_match('/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i', $email)) {
            throw new Exception('Only @ccc.edu.ph email addresses are accepted. Please use your school email.');
        }

        // Enhanced password validation
        $password_validation = validate_password_strength($password);
        if (!$password_validation['valid']) {
            throw new Exception($password_validation['message']);
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM signin_db WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        
        // Check if trying to create a new dean when one already exists for the same dean type
        $deanRoleNames = ['DBA Dean', 'DAS Dean', 'DTE Dean', 'Dean'];
        if (in_array(strtolower($rrow['role_name'] ?? ''), array_map('strtolower', $deanRoleNames))) {
            // Check if a user with this specific dean role already exists
            $deanCheck = $conn->prepare("SELECT id FROM signin_db s JOIN roles r ON s.role_id = r.role_id WHERE r.role_name = ? LIMIT 1");
            $deanCheck->bind_param("s", $rrow['role_name']);
            $deanCheck->execute();
            $deanResult = $deanCheck->get_result();
            
            if ($deanResult->num_rows > 0) {
                throw new Exception('A ' . htmlspecialchars($rrow['role_name']) . ' already exists in the system. Only one ' . htmlspecialchars($rrow['role_name']) . ' account is allowed.');
            }
            $deanCheck->close();
        }

        // Hash password with stronger options
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        // If Argon2ID is not available, fall back to bcrypt
        if ($hashed_password === false) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO signin_db 
            (firstname, lastname, email, password, student_id, course, classification, status, role_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
        
        // Use 's' for string or 'i' for integer based on the parameter type
        // For NULL values, we need to handle them specially
        $status = 'active'; // Set default status
        $stmt->bind_param("sssssssss", 
            $firstname, 
            $lastname, 
            $email, 
            $hashed_password, 
            $student_id, 
            $course, 
            $classification, 
            $status,
            $role_id
        );
        
        // If student_id is NULL, we need to set it to NULL in the database
        if ($stmt->execute()) {
            $message = 'User added successfully!';
            // Log activity (non-fatal)
            try {
                $adminId = $_SESSION['user_id'] ?? null;
                $adminName = ($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '');
                $action = 'Add User';
                $details = 'Added user: ' . $firstname . ' ' . $lastname . ' (' . $email . ')';
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

                $logStmt = $conn->prepare("INSERT INTO activity_log_db (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                if ($logStmt) {
                    $logStmt->bind_param('isssss', $adminId, $adminName, $action, $details, $ip, $ua);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } catch (Exception $e) {
                error_log('Activity log insert failed (add_user): ' . $e->getMessage());
            }

            // Clear form
            $_POST = [];
        } else {
            throw new Exception('Failed to add user: ' . $conn->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get roles for dropdown
$roles = [];
$result = $conn->query("SELECT * FROM roles ORDER BY role_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a1929 0%, #1e3a5f 25%, #2e5490 50%, #1e3a5f 75%, #0a1929 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            position: relative;
            overflow-x: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Floating particles for ambiance */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(66, 133, 244, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(66, 133, 244, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(20px) rotate(-1deg); }
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
        
        /* Form Container */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(10, 25, 41, 0.3),
                0 10px 20px rgba(30, 58, 95, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-title {
            color: #1e3a5f;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 25px;
        }
        
        .form-group-label {
            color: #1e3a5f;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(66, 133, 244, 0.2);
            border-radius: 10px;
            padding: 12px 16px;
            color: #1e3a5f;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: white;
            border-color: #4285f4;
            color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(66, 133, 244, 0.2);
            border-radius: 10px;
            padding: 12px 16px;
            color: #1e3a5f;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            background: white;
            border-color: #4285f4;
            color: #1e3a5f;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }
        
        .invalid-feedback {
            color: #ea4335;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
        }
        
        .alert {
            border-radius: 10px;
            border: 1px solid;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(52, 168, 83, 0.1);
            border-color: #34a853;
            color: #0f5132;
        }
        
        .alert-danger {
            background: rgba(234, 67, 53, 0.1);
            border-color: #ea4335;
            color: #842029;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #adb5bd;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #4285f4;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .password-strength-text {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #4285f4, #669df6);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
            margin-top: 20px;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
            color: white;
        }
        
        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(66, 133, 244, 0.3);
        }
        
        .required-indicator {
            color: #ea4335;
            margin-left: 4px;
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
            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div style="display: flex; width: 100%; min-height: 100vh;">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile-circle">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES); ?></h3>
                <p>Administrator</p>
            </div>

            <div class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="homepage.php" class="nav-link">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                </div>
                <div class="nav-item">
                    <a href="user_role.php" class="nav-link">
                        <i class="fas fa-user-shield"></i> User Roles
                    </a>
                </div>
                <div class="nav-item">
                    <a href="calendars.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </a>
                </div>
                <div class="nav-item">
                    <a href="activity.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i> Activity Logs
                    </a>
                </div>
                <div class="nav-item">
                    <a href="view_feedback.php" class="nav-link">
                        <i class="fas fa-comment-alt"></i> Feedback
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Add New User</h1>
                <p>Create a new user account in the system</p>
            </div>

            <div class="form-card">
                <h2 class="form-title"><i class="fas fa-user-plus me-3" style="color: #4285f4;"></i>New User Account</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label for="firstname" class="form-group-label">First Name <span class="required-indicator">*</span></label>
                            <input type="text" class="form-control" id="firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" 
                                   pattern="(?=.*[A-ZÁÉÍÓÚÜÑ])[A-Za-zÁÉÍÓÚÜÑñÑáéíóúüÜ\s'-]+"
                                   title="First name must contain at least one capital letter and can include letters, spaces, or special characters only"
                                   required>
                            <div class="invalid-feedback">
                                First name must contain at least one capital letter and can include letters, spaces, or special characters only
                            </div>
                        </div>
                        <div>
                            <label for="lastname" class="form-group-label">Last Name <span class="required-indicator">*</span></label>
                            <input type="text" class="form-control" id="lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" 
                                   pattern="(?=.*[A-ZÁÉÍÓÚÜÑ])[A-Za-zÁÉÍÓÚÜÑñÑáéíóúüÜ\s'-]+"
                                   title="Last name must contain at least one capital letter and can include letters, spaces, or special characters only"
                                   required>
                            <div class="invalid-feedback">
                                Last name must contain at least one capital letter and can include letters, spaces, or special characters only
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label for="email" class="form-group-label">Email <span class="required-indicator">*</span></label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               pattern="^[a-zA-Z0-9._%+-]+@ccc\.edu\.ph$"
                               title="Email must end with @ccc.edu.ph (e.g., username@ccc.edu.ph)"
                               required>
                        <div class="invalid-feedback">
                            Email must end with @ccc.edu.ph (e.g., username@ccc.edu.ph)
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label for="password" class="form-group-label">Password <span class="required-indicator">*</span></label>
                            <div style="position: relative;">
                                <input type="password" class="form-control" id="password" name="password" required
                                       minlength="8" 
                                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                                       title="Password must be at least 8 characters long with uppercase, lowercase, number, and special character"
                                       autocomplete="new-password">
                                <span class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long with uppercase, lowercase, number, and special character
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="password-strength"></div>
                                </div>
                                <span class="password-strength-text" id="password-strength-text">Enter a password to see strength</span>
                            </div>
                        <div>
                            <label for="confirm_password" class="form-group-label">Confirm Password <span class="required-indicator">*</span></label>
                            <div style="position: relative;">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </span>
                                <div class="invalid-feedback">
                                    Passwords do not match
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="student-fields">
                            <label for="student_id" class="form-group-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" 
                                   value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                                   pattern="\d{4}-\d{5}"
                                   title="Student ID must be in format: YYYY-##### (e.g., 2022-10873)"
                                   placeholder="2022-10873"
                                   maxlength="10">
                            <div class="invalid-feedback">
                                        Student ID must be in format: YYYY-##### (e.g., 2022-10873)
                            </div>
                        </div>
                        
                        <div class="student-fields">
                            <label for="course" class="form-group-label">Course</label>
                            <input type="text" class="form-control" id="course" name="course" 
                                   value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>">
                            <div class="invalid-feedback">
                                Please enter a valid course
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="student-fields">
                            <label for="classification" class="form-group-label">Classification</label>
                            <select class="form-select" id="classification" name="classification" required>
                                <option value="regular" <?php echo (!isset($_POST['classification']) || $_POST['classification'] === 'regular') ? 'selected' : ''; ?>>Regular</option>
                                <option value="irregular" <?php echo (isset($_POST['classification']) && $_POST['classification'] === 'irregular') ? 'selected' : ''; ?>>Irregular</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a classification
                            </div>
                        </div>
                        
                        <div>
                            <label for="role_id" class="form-group-label">Role <span class="required-indicator">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>" data-role-name="<?php echo htmlspecialchars($role['role_name']); ?>"
                                        <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select a role
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-top: 30px;">
                        <button type="submit" class="btn-submit" style="flex: 1;">
                            <i class="fas fa-user-plus me-2"></i> Add User
                        </button>
                        <a href="homepage.php" style="flex: 1; padding: 12px 32px; background: rgba(100, 116, 139, 0.1); border: 1px solid rgba(100, 116, 139, 0.3); border-radius: 10px; color: #64748b; text-decoration: none; display: flex; align-items: center; justify-content: center; font-weight: 600; transition: all 0.3s ease;">
                            <i class="fas fa-arrow-left me-2"></i> Back to Users
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    // Check passwords match
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const emailInput = document.getElementById('email');
                    const firstNameInput = document.getElementById('firstname');
                    const lastNameInput = document.getElementById('lastname');
                    
                    // Name validation - require capital letters
                    function validateNameField(input, fieldName) {
                        if (input.value.length > 0 && !/(?=.*[A-ZÁÉÍÓÚÜÑ])/.test(input.value)) {
                            input.setCustomValidity(fieldName + ' must contain at least one capital letter (e.g., "John" not "john")');
                        } else {
                            input.setCustomValidity('');
                        }
                    }
                    
                    validateNameField(firstNameInput, 'First name');
                    validateNameField(lastNameInput, 'Last name');
                    
                    // Email validation - block Gmail and only allow @ccc.edu.ph
                    if (emailInput.value.toLowerCase().includes('@gmail.com')) {
                        emailInput.setCustomValidity('Gmail addresses are not accepted. Please use your @ccc.edu.ph school email.');
                    } else if (!/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i.test(emailInput.value)) {
                        emailInput.setCustomValidity('Only @ccc.edu.ph email addresses are accepted.');
                    } else {
                        emailInput.setCustomValidity('');
                    }
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = [];
            
            // Length checks
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (password.length >= 16) score++;
            
            // Character variety
            if (/[a-z]/.test(password)) {
                score++;
                feedback.push('lowercase');
            }
            if (/[A-Z]/.test(password)) {
                score++;
                feedback.push('uppercase');
            }
            if (/[0-9]/.test(password)) {
                score++;
                feedback.push('numbers');
            }
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                score++;
                feedback.push('special characters');
            }
            
            // Calculate percentage
            let percentage = Math.min((score / 8) * 100, 100);
            
            // Determine strength level and color
            let strength, color, text;
            if (score < 3) {
                strength = 'weak';
                color = 'danger';
                text = 'Weak password - add more character types';
            } else if (score < 5) {
                strength = 'fair';
                color = 'warning';
                text = 'Fair password - could be stronger';
            } else if (score < 7) {
                strength = 'good';
                color = 'info';
                text = 'Good password';
            } else {
                strength = 'strong';
                color = 'success';
                text = 'Strong password!';
            }
            
            return {
                score: score,
                percentage: percentage,
                strength: strength,
                color: color,
                text: text,
                feedback: feedback
            };
        }
        
        // Real-time password strength checking
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-strength-text');
            
            if (password.length > 0) {
                const strength = checkPasswordStrength(password);
                strengthBar.style.width = strength.percentage + '%';
                strengthBar.className = 'progress-bar bg-' + strength.color;
                strengthText.textContent = strength.text;
                strengthText.className = 'form-text text-' + strength.color;
            } else {
                strengthBar.style.width = '0%';
                strengthBar.className = 'progress-bar';
                strengthText.textContent = 'Enter a password to see strength';
                strengthText.className = 'form-text';
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Real-time email validation
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('email');
            const firstNameInput = document.getElementById('firstname');
            const lastNameInput = document.getElementById('lastname');
            
            // Real-time name validation
            function validateName(input) {
                const name = input.value;
                input.setCustomValidity('');
                
                if (name.length > 0 && !/(?=.*[A-ZÁÉÍÓÚÜÑ])/.test(name)) {
                    input.setCustomValidity('Name must contain at least one capital letter (e.g., "John" not "john")');
                }
            }
            
            if (firstNameInput) {
                firstNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }
            
            if (lastNameInput) {
                lastNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }
            
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value;
                    
                    // Clear previous validation
                    this.setCustomValidity('');
                    
                    // Check if email contains Gmail
                    if (email.toLowerCase().includes('@gmail.com')) {
                        this.setCustomValidity('Gmail addresses are not accepted. Please use your @ccc.edu.ph school email.');
                    } 
                    // Check if email matches @ccc.edu.ph pattern
                    else if (email.length > 0 && !/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i.test(email)) {
                        this.setCustomValidity('Only @ccc.edu.ph email addresses are accepted.');
                    }
                });
            }

            const roleSelect = document.getElementById('role_id');
            const studentFields = document.querySelectorAll('.student-fields');

            function updateFieldVisibility() {
                if (!roleSelect) return;
                const opt = roleSelect.selectedOptions && roleSelect.selectedOptions[0];
                const roleName = opt ? (opt.dataset.roleName || '').toLowerCase() : '';
                // Treat specific roles as student roles
                const isStudent = roleName.includes('student') || 
                                roleName === 'bscs' || 
                                roleName === 'bsit';

                studentFields.forEach(function (el) {
                    // show for student, hide otherwise
                    el.style.display = isStudent ? '' : 'none';
                    // toggle required on inputs inside
                    el.querySelectorAll('input, select, textarea').forEach(function (inp) {
                        if (isStudent) inp.setAttribute('required', 'required');
                        else inp.removeAttribute('required');
                    });
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', updateFieldVisibility);
                updateFieldVisibility();
            }

            // Auto-format student ID as YYYY-#####
            const studentIdInput = document.getElementById('student_id');
            if (studentIdInput) {
                studentIdInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove all non-digits
                    
                    // Add hyphen after 4 digits when there are more than 4 digits
                    if (value.length > 4) {
                        value = value.slice(0, 4) + '-' + value.slice(4, 9);
                    }
                    
                    e.target.value = value;
                    
                    // Set cursor position to end of input
                    setTimeout(() => {
                        e.target.setSelectionRange(e.target.value.length, e.target.value.length);
                    }, 0);
                });

                // Also handle paste events
                studentIdInput.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length > 4) {
                            value = value.slice(0, 4) + '-' + value.slice(4, 9);
                        }
                        e.target.value = value;
                    }, 10);
                });
            }
        });
    </script>
</body>
</html>