<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Handle form submission for adding/editing user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set content type to JSON for AJAX responses
    header('Content-Type: application/json');
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
        exit();
    }
    // Debug log the POST data
    error_log('POST data: ' . print_r($_POST, true));
    
    $firstname = clean_input($conn, $_POST['firstname'] ?? '');
    $lastname = clean_input($conn, $_POST['lastname'] ?? '');
    $email = clean_input($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $student_id = clean_input($conn, $_POST['student_id'] ?? '');
    $course = clean_input($conn, $_POST['course'] ?? '');
    $classification = isset($_POST['classification']) ? clean_input($conn, $_POST['classification']) : '';
    $role_id = intval($_POST['role_id'] ?? 6);
    $user_id = intval($_POST['user_id'] ?? 0);
    
    // Debug log the processed data
    error_log('Processed data - Classification: ' . $classification);

    // Debug log the received data
    error_log('Processing form submission - User ID: ' . $user_id . ', Email: ' . $email);
    
    // Validate input
    if (empty($firstname) || empty($lastname) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit();
    } elseif ($user_id === 0 && empty($password)) {
        // Only require password for new users
        echo json_encode(['success' => false, 'message' => 'Password is required for new users']);
        exit();
    } else {
        $conn->begin_transaction();
        
        try {
            if ($user_id > 0) {
                // Update existing user
                if (!empty($password)) {
                    // Only update password if a new one is provided
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE signin_db SET firstname=?, lastname=?, email=?, password=?, student_id=?, course=?, role_id=?, classification=?";
                    $types = "ssssssss"; // All strings
                    $params = [&$firstname, &$lastname, &$email, &$hashed_password, &$student_id, &$course, &$role_id, &$classification];
                    error_log('With password - SQL: ' . $sql . ', Types: ' . $types);
                    
                    $sql .= " WHERE id=?";
                    $types .= "i";
                    $params[] = &$user_id;
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception('Prepare failed: ' . $conn->error);
                    }
                    
                    // Dynamically bind parameters
                    $bind_params = array_merge([$types], $params);
                    $bind_result = call_user_func_array([$stmt, 'bind_param'], $bind_params);
                    if ($bind_result === false) {
                        throw new Exception('Bind failed: ' . $stmt->error);
                    }
                } else {
                    // Don't update password if no new one is provided
                    $sql = "UPDATE signin_db SET firstname=?, lastname=?, email=?, student_id=?, course=?, role_id=?, classification=?";
                    $types = "sssssss"; // All strings
                    $params = [&$firstname, &$lastname, &$email, &$student_id, &$course, &$role_id, &$classification];
                    
                    // Add WHERE clause for update
                    $sql .= " WHERE id=?";
                    $types .= "i";
                    $params[] = &$user_id;
                    
                    error_log('Update user (no password change) - SQL: ' . $sql);
                    error_log('Parameters: ' . print_r([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'student_id' => $student_id,
                        'course' => $course,
                        'role_id' => $role_id,
                        'classification' => $classification,
                        'user_id' => $user_id
                    ], true));
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception('Prepare failed: ' . $conn->error);
                    }
                    
                    // Dynamically bind parameters
                    $bind_params = array_merge([$types], $params);
                    $bind_result = call_user_func_array([$stmt, 'bind_param'], $bind_params);
                    if ($bind_result === false) {
                        throw new Exception('Bind failed: ' . $stmt->error);
                    }
                }
                $executed = $stmt->execute();
                if ($executed) {
                    $conn->commit();
                    error_log('Update successful - Rows affected: ' . $stmt->affected_rows);
                    echo json_encode([
                        'success' => true, 
                        'message' => 'User updated successfully',
                        'classification' => $classification,
                        'debug' => [
                            'sql' => $sql,
                            'types' => $types,
                            'params' => $params
                        ]
                    ]);
                } else {
                    throw new Exception('No changes were made');
                }
            } else {
                // Add new user
                if (empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Password is required for new users']);
                    exit();
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO signin_db (firstname, lastname, email, password, student_id, course, role_id, classification) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssis", $firstname, $lastname, $email, $hashed_password, $student_id, $course, $role_id, $classification);
                    $stmt->execute();
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'User added successfully',
                        'classification' => $classification
                    ]);
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errorMsg = $conn->errno == 1062 ? 'Email or Student ID already exists' : 'An error occurred: ' . $conn->error;
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit();
    }
}

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    if ($user_id !== $_SESSION['user_id']) { // Prevent self-deletion
        try {
            $stmt = $conn->prepare("DELETE FROM signin_db WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $message = 'User deleted successfully!';
        } catch (Exception $e) {
            $error = 'Error deleting user: ' . $conn->error;
        }
    } else {
        $error = 'You cannot delete your own account';
    }
}

// Get all users with role names
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$query = "SELECT SQL_CALC_FOUND_ROWS s.*, r.role_name 
          FROM signin_db s
          LEFT JOIN roles r ON s.role_id = r.role_id
          ORDER BY s.id DESC 
          LIMIT $offset, $per_page";
$result = $conn->query($query);
$total_users = $conn->query("SELECT FOUND_ROWS() as total")->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get user data for editing
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

## table
$table_array = array();
$select = "SELECT id, firstname, lastname, email, student_id, course, classification, role_id FROM signin_db ORDER BY id DESC";
if ($result = $conn->query($select)) {
    if ($result->num_rows > 0) {
        while ($data = $result->fetch_assoc()) {
            $formatted_data = array();
            
            // Set general ID (using student_id if available, otherwise using regular id)
            // Format: YYYY-XXXXX (e.g., 2022-10873)
            $currentYear = date('Y');
            $formatted_data['general_id'] = !empty($data['student_id']) ? 
                $currentYear . '-' . ltrim($data['student_id'], '0') : 
                $currentYear . '-' . $data['id'];
            
            // Format complete name
            $formatted_data['complete_name'] = strtoupper($data['lastname'] . ', ' . $data['firstname']);
            
            // Set username and email (using email for both since that's what's used for login)
            $formatted_data['username'] = $data['email'];
            $formatted_data['email_address'] = $data['email'];
            
            // Set position based on role
            switch($data['role_id']) {
                case 1: 
                    $formatted_data['position'] = 'Non-Teaching Personnel';
                    $formatted_data['user_role'] = "['ADMIN']";
                    break;
                case 2: 
                    $formatted_data['position'] = 'Teaching Personnel';
                    $formatted_data['user_role'] = "['DEAN']";
                    break;
                case 3: 
                    $formatted_data['position'] = 'Non-Teaching Personnel';
                    $formatted_data['user_role'] = "['REGISTRAR']";
                    break;
                case 4: 
                    $formatted_data['position'] = 'Student';
                    $formatted_data['user_role'] = "['BSIT']";
                    break;
                case 5: 
                    $formatted_data['position'] = 'Student';
                    $formatted_data['user_role'] = "['BSCS']";
                    break;
                default: 
                    $formatted_data['position'] = 'Unknown';
                    $formatted_data['user_role'] = "['USER']";
            }
            
            // Set status
            $formatted_data['status'] = 'Active';
            
            array_push($table_array, $data);
        }
    }
}

$json_table = json_encode($table_array);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CCC Curriculum Evaluation</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta name="description" content="Admin dashboard for managing users and system settings">
    <meta name="author" content="CCC">
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Tabulator -->
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
    <!-- Sidebar Toggle -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(10, 25, 41, 0.3),
                0 10px 20px rgba(30, 58, 95, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out both;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }
        
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
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #4285f4, #669df6);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 25px 50px rgba(10, 25, 41, 0.4),
                0 15px 30px rgba(30, 58, 95, 0.3);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
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
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: #64748b;
            font-weight: 500;
            font-size: 1rem;
        }
        
        /* Chart Container */
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(10, 25, 41, 0.3),
                0 10px 20px rgba(30, 58, 95, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1e3a5f;
            margin: 0;
        }
        
        .chart-subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin: 5px 0 0;
        }
        
        /* Activity Feed */
        .activity-feed {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(10, 25, 41, 0.3),
                0 10px 20px rgba(30, 58, 95, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid rgba(66, 133, 244, 0.1);
            transition: all 0.3s ease;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: rgba(66, 133, 244, 0.05);
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
            color: #1e3a5f;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #64748b;
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
        }

        /* Additional table styling */
        .tabulator {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 20px !important;
            border: 1px solid rgba(66, 133, 244, 0.2) !important;
            box-shadow: 0 20px 40px rgba(10, 25, 41, 0.3) !important;
        }

        .tabulator .tabulator-header {
            background: linear-gradient(135deg, #4285f4, #669df6) !important;
            border: none !important;
        }

        .tabulator .tabulator-header .tabulator-col {
            color: white !important;
            font-weight: 600 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .tabulator .tabulator-row {
            border-bottom: 1px solid rgba(66, 133, 244, 0.1) !important;
            transition: all 0.3s ease !important;
        }

        .tabulator .tabulator-row:hover {
            background-color: rgba(66, 133, 244, 0.05) !important;
        }

        .tabulator .tabulator-cell {
            color: #1e3a5f !important;
            padding: 15px 12px !important;
        }

        /* Action buttons styling */
        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
            align-items: center;
            flex-wrap: nowrap;
            min-width: 120px;
        }

        .btn-action {
            padding: 6px 8px !important;
            min-width: 32px;
            height: 32px;
            border: none !important;
            border-radius: 6px !important;
            font-size: 12px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease !important;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-edit {
            background: linear-gradient(135deg, #4285f4, #669df6) !important;
            color: white !important;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #3367d6, #4285f4) !important;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
            color: white !important;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #dc3545) !important;
        }

        .btn-view {
            background: linear-gradient(135deg, #6c757d, #5a6268) !important;
            color: white !important;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d) !important;
        }

        .btn-archive {
            background: linear-gradient(135deg, #ff9800, #f57c00) !important;
            color: white !important;
        }

        .btn-archive:hover {
            background: linear-gradient(135deg, #f57c00, #ff9800) !important;
        }

        .btn-restore {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            color: white !important;
        }

        .btn-restore:hover {
            background: linear-gradient(135deg, #20c997, #28a745) !important;
        }

        /* Responsive action buttons */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 2px;
                min-width: 40px;
            }
            
            .btn-action {
                width: 32px;
                height: 28px;
                padding: 4px !important;
                font-size: 10px !important;
            }
            
            .btn-action i {
                font-size: 10px !important;
            }
        }

        @media (max-width: 1200px) {
            .action-buttons {
                gap: 2px;
            }
            
            .btn-action {
                padding: 5px 6px !important;
                min-width: 28px;
                height: 28px;
                font-size: 11px !important;
            }
        }

        /* Table action column responsive */
        @media (max-width: 768px) {
            #userTable td:last-child {
                padding: 8px 4px !important;
            }
        }

        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.8;
        }
        .btn-loading:after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }
        @keyframes button-loading-spinner {
            from { transform: rotate(0turn); }
            to { transform: rotate(1turn); }
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
                    <a href="dashboard.php" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? ' active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'homepage.php') !== false ? ' active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </div>
                <div class="nav-item">
                    <a href="add_user.php" class="nav-link">
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
                <h1>User Management</h1>
                <p>Manage all system users and their permissions</p>
            </div>

            <div style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(10, 25, 41, 0.3), 0 10px 20px rgba(30, 58, 95, 0.2); backdrop-filter: blur(20px); border: 1px solid rgba(66, 133, 244, 0.2); margin-bottom: 30px; animation: fadeInUp 0.8s ease-out 0.3s both;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h2 style="color: #1e3a5f; font-weight: 700; margin-bottom: 5px;">User Accounts</h2>
                        <p style="color: #64748b; margin: 0;">View and manage all system users</p>
                    </div>
                    <div style="position: relative; width: 300px;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 12px; color: #adb5bd;"></i>
                        <input type="text" id="search-input" class="form-control" placeholder="Search users..." style="padding-left: 40px; border-radius: 8px; border: 1px solid rgba(66, 133, 244, 0.2); height: 42px; color: #1e3a5f;">
                    </div>
                </div>
                
                <div style="background: white; border-radius: 15px; overflow: hidden;">
                    <div class="table-responsive">
                        <table id="userTable" class="table table-striped" style="width:100%; margin: 0;">
                            <thead style="background: linear-gradient(135deg, #4285f4, #669df6); color: white;">
                                <tr>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">ID</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Name</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Email</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Student ID</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Role</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Status</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; border-right: 1px solid rgba(255, 255, 255, 0.1);">Classification</th>
                                    <th style="color: white; font-weight: 600; padding: 15px 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody style="background: white;">
                                <?php
                                $query = "SELECT s.*, r.role_name 
                                          FROM signin_db s
                                          LEFT JOIN roles r ON s.role_id = r.role_id
                                          ORDER BY s.id DESC";
                                $result = $conn->query($query);
                                
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        // Default status to 'Active' if empty
                                        $status = !empty($row['status']) ? trim($row['status']) : 'Active';
                                        
                                        // Set status class based on status value
                                        if ($status == 'Active') {
                                            $statusClass = 'text-success';
                                        } elseif ($status == 'Archived') {
                                            $statusClass = 'text-warning';
                                        } elseif ($status == 'Inactive') {
                                            $statusClass = 'text-danger';
                                        } else {
                                            $statusClass = 'text-secondary'; // For any other status
                                        }
                                        $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                                        $userId = htmlspecialchars($row['id'], ENT_QUOTES);
                                        $fullName = htmlspecialchars($row['firstname'] . ' ' . $row['lastname'], ENT_QUOTES);
                                        $email = htmlspecialchars($row['email'], ENT_QUOTES);
                                        $roleName = htmlspecialchars($row['role_name'], ENT_QUOTES);
                                        $statusDisplay = htmlspecialchars($status, ENT_QUOTES);
                                        $classification = htmlspecialchars($row['classification'], ENT_QUOTES);
                                                
                                                // Show classification dropdown only for student roles (BSIT, BSCS)
$classificationDropdown = "";
if (in_array(strtolower($roleName), ['bsit', 'bscs'])) {
    $classificationDropdown = "
        <td>
            <div class='dropdown'>
                <button class='btn btn-sm dropdown-toggle status-toggle " . ($classification === 'Irregular' ? 'btn-warning' : 'btn-success') . "' 
                        type='button' 
                        data-bs-toggle='dropdown' 
                        aria-expanded='false'
                        data-user-id='" . $userId . "'
                        data-current-classification='" . $classification . "'>
                    " . $classification . "
                </button>
                <ul class='dropdown-menu'>
                    <li><a class='dropdown-item classification-option' href='#' data-value='Regular'>Regular</a></li>
                    <li><a class='dropdown-item classification-option' href='#' data-value='Irregular'>Irregular</a></li>
                </ul>
            </div>
        </td>";
} else {
    $classificationDropdown = "<td><span class='text-muted'></span></td>";
}

echo "<tr class='user-row' data-id='" . $userId . "' style='border-bottom: 1px solid rgba(66, 133, 244, 0.1); transition: all 0.3s ease;'>
    <td style='padding: 15px 12px; color: #1e3a5f;'>" . $userId . "</td>
    <td style='padding: 15px 12px; color: #1e3a5f;'>" . $fullName . "</td>
    <td style='padding: 15px 12px; color: #1e3a5f;'>" . $email . "</td>
    <td style='padding: 15px 12px; color: #1e3a5f;'>" . $student_id . "</td>
    <td style='padding: 15px 12px; color: #1e3a5f;'>" . $roleName . "</td>
    <td style='padding: 15px 12px; color: #1e3a5f;'><span class='" . $statusClass . "'>" . $statusDisplay . "</span></td>
    " . $classificationDropdown . "
    <td style='padding: 15px 12px; text-align: center;'>
        <div class='action-buttons'>
            <button type='button' class='btn btn-action btn-edit' title='Edit User' data-id='" . $userId . "' data-name='" . $fullName . "' data-email='" . $email . "' data-status='" . $statusDisplay . "' onclick='openSimpleEditModal(" . $userId . ", \"" . $fullName . "\", \"" . $email . "\", \"" . $statusDisplay . "\")'>
                <i class='fas fa-edit'></i>
            </button>" . 
            (($status === 'Archived' || $status === 'Inactive') ? 
                "<button type='button' class='btn btn-action btn-restore' title='Restore User' data-id='" . $userId . "' onclick='quickRestoreUser(" . $userId . ")'>
                    <i class='fas fa-undo'></i>
                </button>" 
                : 
                ""
            ) . "
        </div>
    </td>
</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' style='text-align: center; padding: 20px; color: #64748b;'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simple Edit Modal -->
    <div class="modal fade" id="simpleEditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="simpleEditForm">
                        <input type="hidden" id="simpleUserId">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="simpleUserName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="simpleUserEmail" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="simpleUserStatus">
                                <option value="Active">Active</option>
                                <option value="Inactive">archive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-danger w-100" onclick="deleteUserFromModal()">
                                <i class="fas fa-trash me-2"></i> Delete User 
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSimpleEdit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Simple Edit Modal JavaScript -->
    <script>
    // Test function
    window.testModal = function() {
        console.log('=== TEST MODAL CALLED ===');
        openSimpleEditModal(1, 'Test User', 'test@example.com', 'Active');
    };
    
    // Make function global
    window.openSimpleEditModal = function(userId, userName, userEmail, currentStatus) {
        console.log('=== openSimpleEditModal called ===');
        console.log('userId:', userId);
        console.log('userName:', userName);
        console.log('userEmail:', userEmail);
        console.log('currentStatus:', currentStatus);
        
        try {
            // Check if elements exist
            const userIdField = document.getElementById('simpleUserId');
            const userNameField = document.getElementById('simpleUserName');
            const userEmailField = document.getElementById('simpleUserEmail');
            const statusField = document.getElementById('simpleUserStatus');
            const modalElement = document.getElementById('simpleEditModal');
            
            console.log('Elements found:', {
                userIdField: !!userIdField,
                userNameField: !!userNameField,
                userEmailField: !!userEmailField,
                statusField: !!statusField,
                modalElement: !!modalElement
            });
            
            if (!modalElement) {
                console.error('Modal element not found!');
                alert('Modal element not found!');
                return;
            }
            
            // Set form values
            if (userIdField) userIdField.value = userId;
            if (userNameField) userNameField.value = userName;
            if (userEmailField) userEmailField.value = userEmail;
            if (statusField) statusField.value = currentStatus;
            
            console.log('Form values set');
            
            // Try Bootstrap first
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                console.log('Using Bootstrap modal');
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('Bootstrap modal shown');
            } else {
                console.log('Bootstrap not available, using manual modal');
                // Manual modal show
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                modalElement.classList.remove('fade');
                document.body.classList.add('modal-open');
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop show';
                backdrop.id = 'modal-backdrop-simple';
                document.body.appendChild(backdrop);
                console.log('Manual modal shown');
            }
        } catch (error) {
            console.error('Error in openSimpleEditModal:', error);
            alert('Error: ' + error.message);
        }
    };
    
    // Make save function global
    window.saveSimpleEdit = function() {
        console.log('=== saveSimpleEdit called ===');
        const userId = document.getElementById('simpleUserId').value;
        const status = document.getElementById('simpleUserStatus').value;
        
        console.log('Saving userId:', userId, 'status:', status);
        
        fetch('update_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&status=${status}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'User status updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update user status'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred: ' + error.message
            });
        });
    };
    
    // Add close handler for manual modal
    document.addEventListener('click', function(e) {
        if (e.target.getAttribute('data-bs-dismiss') === 'modal') {
            const modal = document.getElementById('simpleEditModal');
            const backdrop = document.getElementById('modal-backdrop-simple');
            
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                modal.classList.add('fade');
            }
            
            if (backdrop) {
                backdrop.remove();
            }
            
            document.body.classList.remove('modal-open');
        }
    });
    
    // Auto-test when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded, checking modal availability...');
        const modalElement = document.getElementById('simpleEditModal');
        console.log('Modal element on page load:', !!modalElement);
    });
    
    // Test functions for debugging
    window.testDelete = function() {
        console.log('=== TEST DELETE CALLED ===');
        if (typeof quickDeleteUser === 'function') {
            console.log('quickDeleteUser function found, calling with ID 1');
            quickDeleteUser(1);
        } else {
            console.error('quickDeleteUser function NOT found');
            alert('quickDeleteUser function not found');
        }
    };
    
    window.testRestore = function() {
        console.log('=== TEST RESTORE CALLED ===');
        if (typeof quickRestoreUser === 'function') {
            console.log('quickRestoreUser function found, calling with ID 1');
            quickRestoreUser(1);
        } else {
            console.error('quickRestoreUser function NOT found');
            alert('quickRestoreUser function not found');
        }
    };
    
    // Function availability check
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Checking function availability...');
        console.log('quickDeleteUser available:', typeof quickDeleteUser);
        console.log('quickRestoreUser available:', typeof quickRestoreUser);
        console.log('openSimpleEditModal available:', typeof openSimpleEditModal);
    });
    
    // Delete user from modal function
    window.deleteUserFromModal = function() {
        const userId = document.getElementById('simpleUserId').value;
        const userName = document.getElementById('simpleUserName').value;
        
        if (!userId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No user selected for deletion'
            });
            return;
        }
        
        Swal.fire({
            title: 'Delete User?',
            text: `Are you sure you want to permanently delete ${userName}? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'User Deleted',
                            text: 'User has been deleted successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Close modal and reload page
                            const modal = bootstrap.Modal.getInstance(document.getElementById('simpleEditModal'));
                            if (modal) {
                                modal.hide();
                            }
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete user'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred: ' + error.message
                    });
                });
            }
        });
    };
    </script>

    <!-- Form submission handler -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editForm = document.getElementById('editUserForm');
        const saveBtn = document.getElementById('saveChangesBtn');
        const spinner = saveBtn.querySelector('.spinner-border');
        
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            saveBtn.disabled = true;
            spinner.classList.remove('d-none');
            
            // Get form data
            const formData = new FormData(editForm);
            const userId = formData.get('user_id');
            
            // Send AJAX request
            fetch('update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('editUserMessage');
                
                if (data.error) {
                    // Show error message
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger">
                            ${data.error}
                        </div>
                    `;
                } else if (data.success) {
                    // Show success message
                    messageDiv.innerHTML = `
                        <div class="alert alert-success">
                            User updated successfully!
                        </div>
                    `;
                    
                    // Update the table
                    const table = Tabulator.findTable("#user-table")[0];
                    if (table) {
                        // Get the current status
                        const status = formData.get('status') || 'Active';
                        
                        // Update the row data
                        table.updateData([{
                            id: parseInt(userId),
                            firstname: formData.get('firstname'),
                            lastname: formData.get('lastname'),
                            email: formData.get('email'),
                            student_id: formData.get('student_id'),
                            role_id: parseInt(formData.get('role_id')),
                            status: status
                        }]);
                    }
                    
                    // Close modal after 1.5 seconds
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                        if (modal) {
                            modal.hide();
                        }
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.getElementById('editUserMessage');
                messageDiv.innerHTML = `
                    <div class="alert alert-danger">
                        An error occurred. Please try again later.
                    </div>
                `;
            })
            .finally(() => {
                // Reset button state
                saveBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
        
        // Clear messages when modal is hidden
        document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('editUserMessage').innerHTML = '';
            editForm.reset();
        });
    });
    </script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar, #content').toggleClass('active');
                $('.collapse.in').toggleClass('in');
                $('a[aria-expanded=true]').attr('aria-expanded', 'false');
            });
        });
    </script>

<script>
    // Define global functions BEFORE Tabulator initialization
    window.openEditModal = function(userId) {
        fetch(`get_user.php?id=${userId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(user => {
                // Check if the response contains an error
                if (user.success === false) {
                    throw new Error(user.message || 'Failed to load user data');
                }
                
                // Safely set form values with null checking
                const setFieldValue = (fieldId, value) => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.value = value || '';
                    } else {
                        console.warn(`Field not found: ${fieldId}`);
                    }
                };
                
                setFieldValue('editUserId', user.id);
                setFieldValue('editFirstName', user.firstname);
                setFieldValue('editLastName', user.lastname);
                setFieldValue('editEmail', user.email);
                setFieldValue('editStudentId', user.student_id);
                setFieldValue('editRole', user.role_id);
                setFieldValue('editCourse', user.course);
                setFieldValue('editClassification', user.classification || 'Regular');
                
                // Handle checkbox and password fields
                const changePasswordToggle = document.getElementById('changePasswordToggle');
                if (changePasswordToggle) {
                    changePasswordToggle.checked = false;
                }
                
                const passwordFields = document.getElementById('passwordFields');
                if (passwordFields) {
                    passwordFields.style.display = 'none';
                }
                
                setFieldValue('editPassword', '');
                setFieldValue('editConfirmPassword', '');
                
                // Clear any previous messages
                const editMessage = document.getElementById('editMessage');
                if (editMessage) {
                    editMessage.innerHTML = '';
                }
                
                // Show the modal - ensure Bootstrap is ready
                const showModal = () => {
                    const modalElement = document.getElementById('editUserModal');
                    
                    if (!modalElement) {
                        console.error('Modal element not found - waiting for DOM');
                        setTimeout(showModal, 500);
                        return;
                    }
                    
                    if (typeof bootstrap === 'undefined') {
                        console.error('Bootstrap not loaded - waiting');
                        setTimeout(showModal, 500);
                        return;
                    }
                    
                    try {
                        // Check if modal already has an instance
                        let modal = bootstrap.Modal.getInstance(modalElement);
                        if (!modal) {
                            modal = new bootstrap.Modal(modalElement);
                        }
                        modal.show();
                    } catch (error) {
                        console.error('Error creating modal:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to open modal: ' + error.message
                        });
                    }
                };
                
                // Try to show modal immediately, with fallback delays
                showModal();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load user data'
                });
            });
    };

    
    
    window.quickRestoreUser = function(userId) {
        Swal.fire({
            title: 'Restore User?',
            text: 'This user will be able to login again.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('restore_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'User Restored',
                            text: 'User has been restored successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to restore user'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred: ' + error.message
                    });
                });
            }
        });
    };

    (function() {
        console.log('Initializing table...');
        var total_record = 0;
        var table_data = <?php echo $json_table . ";\r\n" ?>;
        console.log('Table data:', table_data);
        total_record = table_data.length;
        
        // Define action functions INSIDE IIFE and make them global
        window.openEditModal = function(userId) {
            console.log('=== openEditModal called with userId:', userId);
            fetch(`get_user.php?id=${userId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(user => {
                    console.log('User data received:', user);
                    if (user.success === false) {
                        throw new Error(user.message || 'Failed to load user data');
                    }
                    
                    const setFieldValue = (fieldId, value) => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.value = value || '';
                            console.log(`Set ${fieldId} to:`, value || '');
                        } else {
                            console.warn(`Field not found: ${fieldId}`);
                        }
                    };
                    
                    setFieldValue('editUserId', user.id);
                    setFieldValue('editFirstName', user.firstname);
                    setFieldValue('editLastName', user.lastname);
                    setFieldValue('editEmail', user.email);
                    setFieldValue('editStudentId', user.student_id);
                    setFieldValue('editRole', user.role_id);
                    setFieldValue('editCourse', user.course);
                    setFieldValue('editClassification', user.classification || 'Regular');
                    setFieldValue('editStatus', user.status || 'Active');
                    
                    const changePasswordToggle = document.getElementById('changePasswordToggle');
                    if (changePasswordToggle) {
                        changePasswordToggle.checked = false;
                    }
                    
                    const passwordFields = document.getElementById('passwordFields');
                    if (passwordFields) {
                        passwordFields.style.display = 'none';
                    }
                    
                    setFieldValue('editPassword', '');
                    setFieldValue('editConfirmPassword', '');
                    
                    const editMessage = document.getElementById('editMessage');
                    if (editMessage) {
                        editMessage.innerHTML = '';
                    }
                    
                    console.log('Attempting to show modal...');
                    const modalElement = document.getElementById('editUserModal');
                    console.log('Modal element found:', !!modalElement);
                    if (modalElement) {
                        console.log('Bootstrap available:', typeof bootstrap);
                        const modal = new bootstrap.Modal(modalElement);
                        console.log('Modal created:', !!modal);
                        modal.show();
                        console.log('Modal show() called');
                    } else {
                        console.error('Modal element not found');
                    }
                })
                .catch(error => {
                    console.error('Error in openEditModal:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to load user data'
                    });
                });
        };

        window.quickArchiveUser = function(userId) {
            Swal.fire({
                title: 'Set User Inactive?',
                text: 'Setting this user to inactive will prevent them from logging in.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff9800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, set inactive!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('archive_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'User Set Inactive',
                                text: 'User has been set to inactive successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to set user inactive'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred: ' + error.message
                        });
                    });
                }
            });
        };

        window.quickDeleteUser = function(userId) {
            console.log('=== quickDeleteUser called with userId:', userId);
            console.log('Current session info:', {
                user_id: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>,
                role_id: <?php echo isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 'null'; ?>
            });
            
            Swal.fire({
                title: 'Delete User?',
                text: 'This action cannot be undone!',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                console.log('SweetAlert result:', result);
                if (result.isConfirmed) {
                    console.log('User confirmed deletion, sending request...');
                    console.log('CSRF token:', '<?php echo $_SESSION['csrf_token']; ?>');
                    
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        console.log('Delete response status:', response.status);
                        console.log('Delete response headers:', response.headers);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Delete response data:', data);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'User Deleted',
                                text: 'User has been deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                console.log('Reloading page...');
                                location.reload();
                            });
                        } else {
                            console.error('Delete failed:', data.message);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to delete user'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Delete fetch error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred: ' + error.message
                        });
                    });
                }
            });
        };

        window.quickRestoreUser = function(userId) {
            Swal.fire({
                title: 'Restore User?',
                text: 'This user will be able to login again.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('restore_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'User Restored',
                                text: 'User has been restored successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to restore user'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred: ' + error.message
                        });
                    });
                }
            });
        }
        
        })();
    
    // Event delegation for HTML table action buttons (OUTSIDE IIFE)
    document.addEventListener('click', function(e) {
        console.log('=== CLICK DETECTED ===');
        console.log('Target:', e.target);
        console.log('Target classes:', e.target.className);
        
        const button = e.target.closest('.btn-action');
        console.log('Closest button:', button);
        
        if (!button) {
            console.log('No button found, returning');
            return;
        }
        
        const userId = button.getAttribute('data-id');
        const action = button.classList.contains('btn-edit') ? 'edit' : 
                      button.classList.contains('btn-restore') ? 'restore' : 
                      button.classList.contains('btn-archive') ? 'archive' : 
                      button.classList.contains('btn-delete') ? 'delete' : null;
        
        console.log('User ID:', userId);
        console.log('Action:', action);
        
        if (!action || !userId) {
            console.log('Missing action or userId, returning');
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // Define functions globally or use window object
        switch(action) {
            case 'edit':
                console.log('Calling openSimpleEditModal');
                const name = button.getAttribute('data-name');
                const email = button.getAttribute('data-email');
                const status = button.getAttribute('data-status');
                openSimpleEditModal(parseInt(userId), name, email, status);
                break;
            case 'archive':
                console.log('Calling quickArchiveUser');
                if (typeof window.quickArchiveUser === 'function') {
                    window.quickArchiveUser(parseInt(userId));
                } else {
                    console.error('quickArchiveUser not found');
                }
                break;
            case 'restore':
                console.log('Calling quickRestoreUser');
                if (typeof window.quickRestoreUser === 'function') {
                    window.quickRestoreUser(parseInt(userId));
                } else {
                    console.error('quickRestoreUser not found');
                }
                break;
            case 'delete':
                console.log('Calling quickDeleteUser');
                if (typeof window.quickDeleteUser === 'function') {
                    window.quickDeleteUser(parseInt(userId));
                } else {
                    console.error('quickDeleteUser not found');
                }
                break;
        }
    });
    
    // Initialize entry count display
    document.getElementById('total-count').textContent = total_record;
    document.getElementById('showing-count').textContent = total_record;
    
    // Initialize Tabulator
    // Function to get role name from role_id
        function getRoleName(roleId) {
            const roleMap = {
                1: {name: 'ADMIN', class: 'badge bg-danger'},
                2: {name: 'DEAN', class: 'badge bg-primary'},
                3: {name: 'REGISTRAR', class: 'badge bg-info'},
                4: {name: 'BSIT', class: 'badge bg-success'},
                5: {name: 'BSCS', class: 'badge bg-warning'},
                6: {name: 'STUDENT', class: 'badge bg-secondary'}
            };
            return roleMap[roleId] || {name: 'UNKNOWN', class: 'badge bg-dark'};
        }

        // Format action buttons
        const actionButtons = function(cell) {
            const data = cell.getRow().getData();
            const wrapper = document.createElement("div");
            wrapper.className = "action-buttons";
            
            // View button
            const viewBtn = document.createElement("button");
            viewBtn.className = "btn btn-action btn-edit view-btn";
            viewBtn.setAttribute("data-id", data.id);
            viewBtn.innerHTML = '<i class="fas fa-eye"></i>';
            viewBtn.title = "View Details";
            
            // Edit button
            const editBtn = document.createElement("button");
            editBtn.className = "btn btn-action btn-edit edit-btn";
            editBtn.setAttribute("data-id", data.id);
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.title = "Edit User";
            
            // Delete button
            const deleteBtn = document.createElement("button");
            deleteBtn.className = "btn btn-action btn-delete delete-btn";
            deleteBtn.setAttribute("data-id", data.id);
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.title = "Delete User";
            
            wrapper.appendChild(viewBtn);
            wrapper.appendChild(editBtn);
            wrapper.appendChild(deleteBtn);
            
            return wrapper;
        };
        
        // Format status
        const formatStatus = function(cell) {
            const status = cell.getValue();
            const badge = document.createElement("span");
            badge.className = `badge-status ${status.toLowerCase() === 'active' ? 'status-active' : 'status-inactive'}`;
            badge.textContent = status;
            return badge;
        };
        
        // Initialize Tabulator
        const table = new Tabulator("#user-table", {
            data: table_data,
            layout: "fitDataFill",
            height: "calc(100vh - 300px)",
            printAsHtml: true,
            pagination: "local",
            paginationSize: 10,
            paginationSizeSelector: [10, 20, 50, 100],
            movableColumns: true,
            responsiveLayout: "collapse",
            columns: [
                {
                    title: "ID",
                    field: "id",
                    width: 120,
                    headerFilter: "input",
                    headerFilterPlaceholder: "Search ID..."
                },
                {
                    title: "FULL NAME",
                    field: "firstname",
                    headerFilter: "input",
                    headerFilterPlaceholder: "Search name",
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        return `<strong>${data.lastname || ''}, ${data.firstname || ''}</strong>`;
                    },
                    hozAlign: "left",
                    width: 250,
                    headerSort: true,
                    sorter: function(a, b, aRow, bRow, column, dir, sorterParams) {
                        const aName = (aRow.getData().lastname || '') + (aRow.getData().firstname || '');
                        const bName = (bRow.getData().lastname || '') + (bRow.getData().firstname || '');
                        return aName.localeCompare(bName);
                    }
            }, {
                title: "EMAIL",
                field: "email",
                headerFilter: "input",
                headerFilterPlaceholder: "Search email",
                hozAlign: "left",
                width: 200,
                formatter: function(cell) {
                    const email = cell.getValue() || '';
                    return `<a href="mailto:${email}" class="text-primary">${email}</a>`;
                }
            }, {
                title: "ROLE",
                field: "role_id",
                width: 120,
                hozAlign: "center",
                headerFilter: "select",
                headerFilterParams: {
                    "1": "Admin",
                    "2": "Dean",
                    "3": "Registrar",
                    "4": "BSIT",
                    "5": "BSCS",
                    "6": "Student"
                },
                formatter: function(cell) {
                    const role = getRoleName(cell.getValue());
                    return `<span class="${role.class}">${role.name}</span>`;
                },
                headerSort: true
            }, {
                title: "ACTIONS",
                field: "actions",
                width: 150,
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    const data = cell.getRow().getData();
                    console.log('=== FORMATTER DEBUG ===');
                    console.log('Row data:', data);
                    
                    const status = data.status || 'Active';
                    const isInactive = (status === 'Archived' || status === 'Inactive');
                    
                    console.log('Status:', status);
                    console.log('Is inactive:', isInactive);
                    console.log('User ID:', data.id);
                    
                    // Create button elements using Tabulator's built-in formatter
                    const container = document.createElement("div");
                    container.className = "action-buttons";
                    
                    // Edit button
                    const editBtn = document.createElement("button");
                    editBtn.type = "button";
                    editBtn.className = "btn btn-action btn-edit";
                    editBtn.setAttribute("data-action", "edit");
                    editBtn.setAttribute("data-user-id", data.id);
                    editBtn.title = "Edit User";
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    
                    // Archive/Restore button
                    const actionBtn = document.createElement("button");
                    actionBtn.type = "button";
                    if (isInactive) {
                        actionBtn.className = "btn btn-action btn-restore";
                        actionBtn.setAttribute("data-action", "restore");
                        actionBtn.title = "Restore User";
                        actionBtn.innerHTML = '<i class="fas fa-undo"></i>';
                    } else {
                        actionBtn.className = "btn btn-action btn-archive";
                        actionBtn.setAttribute("data-action", "archive");
                        actionBtn.title = "Set User Inactive";
                        actionBtn.innerHTML = '<i class="fas fa-archive"></i>';
                    }
                    actionBtn.setAttribute("data-user-id", data.id);
                    
                    // Delete button
                    const deleteBtn = document.createElement("button");
                    deleteBtn.type = "button";
                    deleteBtn.className = "btn btn-action btn-delete";
                    deleteBtn.setAttribute("data-action", "delete");
                    deleteBtn.setAttribute("data-user-id", data.id);
                    deleteBtn.title = "Delete User";
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    
                    container.appendChild(editBtn);
                    container.appendChild(actionBtn);
                    container.appendChild(deleteBtn);
                    
                    console.log('Created container with buttons:', container);
                    return container;
                },
                cellClick: function(e, cell) {
                    console.log('=== CELL CLICK DETECTED ===');
                    console.log('Event target:', e.target);
                    console.log('Event target tagName:', e.target.tagName);
                    console.log('Event target className:', e.target.className);
                    
                    // Get the clicked button
                    const button = e.target.closest('button');
                    console.log('Closest button:', button);
                    
                    if (!button) {
                        console.log('No button found, returning');
                        return;
                    }
                    
                    // Get action and user ID from data attributes
                    const action = button.dataset.action;
                    const userId = button.dataset.userId;
                    
                    console.log('Button action:', action);
                    console.log('Button userId:', userId);
                    console.log('Button dataset:', button.dataset);
                    
                    if (!action || !userId) {
                        console.log('Missing action or userId, returning');
                        console.log('Action exists:', !!action);
                        console.log('UserId exists:', !!userId);
                        return;
                    }
                    
                    // Prevent row selection
                    e.stopPropagation();
                    
                    console.log('About to execute action:', action, 'for user:', userId);
                    
                    // Execute the appropriate function
                    switch(action) {
                        case 'edit':
                            console.log('Calling openEditModal with userId:', userId);
                            if (typeof openEditModal === 'function') {
                                openEditModal(parseInt(userId));
                            } else {
                                console.error('openEditModal is not a function!');
                            }
                            break;
                        case 'archive':
                            console.log('Calling quickArchiveUser with userId:', userId);
                            if (typeof quickArchiveUser === 'function') {
                                quickArchiveUser(parseInt(userId));
                            } else {
                                console.error('quickArchiveUser is not a function!');
                            }
                            break;
                        case 'restore':
                            console.log('Calling quickRestoreUser with userId:', userId);
                            if (typeof quickRestoreUser === 'function') {
                                quickRestoreUser(parseInt(userId));
                            } else {
                                console.error('quickRestoreUser is not a function!');
                            }
                            break;
                        case 'delete':
                            console.log('Calling quickDeleteUser with userId:', userId);
                            if (typeof quickDeleteUser === 'function') {
                                quickDeleteUser(parseInt(userId));
                            } else {
                                console.error('quickDeleteUser is not a function!');
                            }
                            break;
                        default:
                            console.error('Unknown action:', action);
                    }
                }
            }, {
                title: "STATUS",
                field: "status",
                width: 100,
                hozAlign: "center",
                formatter: function(cell) {
                    const status = cell.getValue() || 'Active';
                    const statusClass = status === 'Active' ? 'badge bg-success' : 'badge bg-danger';
                    return `<span class="${statusClass}">${status}</span>`;
                }
            }, {
                title: "CLASSIFICATION",
                field: "classification",
                width: 100,
                hozAlign: "center",
                formatter: function(cell) {
                    const classification = cell.getValue() || 'Regular';
                    const classificationClass = classification === 'Irregular' ? 'badge bg-warning' : 'badge bg-success';
                    return `<span class="${classificationClass}">${classification}</span>`;
                }
            }],
            rowUpdated: function(row) {
                // Update showing count when data changes
                updateEntryCounts();
            },
            dataFiltered: function(filters, rows) {
                // Update showing count when filtered
                updateEntryCounts();
            },
        });
        
        // Function to update showing/total entry counts
        function updateEntryCounts() {
            const showing = table.getDataCount("active");
            const total = table.getDataCount();
            document.getElementById('showing-count').textContent = showing;
            document.getElementById('total-count').textContent = total;
        }
        
        // Update counts when data is loaded or filtered
        table.on("dataLoaded", updateEntryCounts);
        table.on("dataFiltered", updateEntryCounts);
        
        // Initialize counts
        updateEntryCounts();
        
        // Simplified event delegation - remove conflicts with onclick attributes
        document.addEventListener('click', function(e) {
            // Only handle buttons that don't have onclick attributes
            const target = e.target.closest('.edit-btn, .delete-btn, .view-btn');
            if (!target) return;
            
            // Skip if button has onclick attribute (let it work naturally)
            if (target.hasAttribute('onclick')) return;
            
            console.log('Button clicked:', target.className);
        });
        
        // Search functionality
        document.getElementById('search-input')?.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const value = e.target.value.toLowerCase();
                if (value === '') {
                    table.clearFilter();
                    return;
                }
                
                table.setFilter(function(data) {
                    // Check all relevant fields for matches
                    return (
                        String(data.firstname || '').toLowerCase().includes(value) ||
                        String(data.lastname || '').toLowerCase().includes(value) ||
                        String(data.email || '').toLowerCase().includes(value) ||
                        String(data.student_id || '').toLowerCase().includes(value) ||
                        String(data.section || '').toLowerCase().includes(value) ||
                        String(data.year_level || '').toLowerCase().includes(value)
                    );
                });
                updateEntryCounts();
            }, 300);
        });

        // Add download button handlers
        const downloadCsv = document.getElementById('download-csv');
        const downloadJson = document.getElementById('download-json');
        const downloadXlsx = document.getElementById('download-xlsx');
        const printTable = document.getElementById('print-table');
        
        if (downloadCsv) {
            downloadCsv.addEventListener('click', function() {
                table.download("csv", "list_" + getFormattedTime() + ".csv", {
                    bom: true
                });
            });
        }

        if (downloadJson) {
            downloadJson.addEventListener('click', function() {
                table.download("json", "users_" + new Date().toISOString().slice(0, 10) + ".json");
            });
        }
        
        if (downloadXlsx) {
            downloadXlsx.addEventListener('click', function() {
                table.download("xlsx", "users_" + new Date().toISOString().slice(0, 10) + ".xlsx");
            });
        }
        
        // Helper function to update record counts
        function updateRecordCounts() {
            const total = table.getDataCount();
            const filtered = table.getDataCount('active');
            document.getElementById('total-count').textContent = total;
            document.getElementById('showing-count').textContent = filtered;
        }
        
        // Initialize counts
        updateRecordCounts();
        
        // Update counts when data changes
        table.on('dataProcessed', updateRecordCounts);
        table.on('dataFiltered', updateRecordCounts);
        

        // Event delegation for edit/delete buttons - REMOVED (consolidated above)
        // document.addEventListener('click', function(e) {
        //     // Edit button
        //     if (e.target.closest('.edit-btn')) {
        //         const id = e.target.closest('.edit-btn').dataset.id;
        //         window.location.href = 'edit_user.php?id=' + id;
        //     }
        //     // Delete button
        //     if (e.target.closest('.delete-btn')) {
        //         const id = e.target.closest('.delete-btn').dataset.id;
        //         if (confirm('Are you sure you want to delete this user?')) {
        //             fetch('delete_user.php?id=' + id)
        //                 .then(response => response.json())
        //                 .then(data => {
        //                     if (data.success) {
        //                         window.location.reload();
        //                     } else {
        //                         alert('Error deleting user: ' + data.message);
        //                     }
        //                 })
        //                 .catch(error => {
        //                     console.error('Error:', error);
        //                     alert('An error occurred while deleting the user');
        //                 });
        //         }
        //     }
        // });

        // Show notifications if any
        <?php 
        $msg_success = $session_class->getValue('msg_success');
        if (!empty($msg_success)): 
            $session_class->dropValue('msg_success');
        ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: <?php echo json_encode($msg_success); ?>,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000
        });
        <?php 
        endif;
        
        $msg_error = $session_class->getValue('msg_error');
        if (!empty($msg_error)): 
            $session_class->dropValue('msg_error');
        ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: <?php echo json_encode($msg_error); ?>,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000
        });
        <?php 
        endif;
        
        $msg_password = $session_class->getValue('msg_password');
        if (!empty($msg_password) && is_array($msg_password)): 
            $session_class->dropValue('msg_password');
        ?>
        if (typeof password_modal === 'function') {
            password_modal(<?php echo json_encode($msg_password['title'] ?? ''); ?>, <?php echo json_encode($msg_password['content_msg'] ?? ''); ?>);
        }
        <?php endif; ?>

// Document ready function to ensure DOM is fully loaded
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Toggle password fields when checkbox is clicked
    $(document).on('change', '#changePasswordToggle', function() {
        const isChecked = $(this).is(':checked');
        $('#passwordFields').slideToggle('fast');
        
        // Toggle required attribute and clear fields when hiding
        if (isChecked) {
            $('#editPassword, #editConfirmPassword').attr('required', 'required');
        } else {
            $('#editPassword, #editConfirmPassword').removeAttr('required').val('');
            $('.is-invalid').removeClass('is-invalid');
        }
    });

    // Toggle password visibility
    $(document).on('click', '.toggle-password', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        const $input = $(`#${targetId}`);
        const $icon = $(this).find('i');
        const type = $input.attr('type') === 'password' ? 'text' : 'password';
        const newTitle = type === 'password' ? 'Show password' : 'Hide password';
        
        $input.attr('type', type);
        $icon.toggleClass('fa-eye fa-eye-slash');
        $(this).attr('title', newTitle).tooltip('hide').tooltip('show');
    });

    // Initialize modal's password toggle state
    $('#editUserModal').on('show.bs.modal', function() {
        $('#changePasswordToggle').prop('checked', false);
        $('#passwordFields').hide();
        $('.toggle-password').attr('title', 'Show password');
        $('.toggle-password i').removeClass('fa-eye-slash').addClass('fa-eye');
        $('#editPassword, #editConfirmPassword').attr('type', 'password');
    });
}); // End of document ready

    // Handle edit user button click
    $(document).on('click', '.edit-user', function(e) {
        e.preventDefault();
        
        const userId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        // Get user data from the row
        const userData = {
            id: userId,
            firstname: $row.find('td:eq(1)').text().trim().split(' ')[0],
            lastname: $row.find('td:eq(1)').text().trim().split(' ').slice(1).join(' ').trim(),
            email: $row.find('td:eq(2)').text().trim(),
            student_id: $row.find('td:eq(3)').text().trim() === 'N/A' ? '' : $row.find('td:eq(3)').text().trim(),
            role: $row.find('td:eq(4)').text().trim(),
            course: $row.find('td:eq(5)').text().trim(),
            classification: $row.find('.status-toggle').data('current-classification') || ''
        };
    
    // Map role names to role IDs (adjust according to your roles table)
    const roleMap = {
        'Administrator': '1',
        'Dean': '2',
        'Registrar': '3',
        'BSIT Student': '4',
        'BSCS Student': '5',
        'Student': '6'
    };
    
    // Populate the form
    const $modal = $('#editUserModal');
    
    // Set classification if it exists
    if (userData.classification) {
        $('#editClassification').val(userData.classification);
    }
    $modal.find('#editUserId').val(userData.id);
    $modal.find('#editFirstName').val(userData.firstname);
    $modal.find('#editLastName').val(userData.lastname);
    $modal.find('#editEmail').val(userData.email);
    $modal.find('#editStudentId').val(userData.student_id);
    $modal.find('#editRole').val(roleMap[userData.role] || '6');
    $modal.find('#editCourse').val(userData.course);
    
    // Set classification if available
    const classification = $row.find('.classification-option').data('value') || '';
    $modal.find('#editClassification').val(classification);
    
    // Reset password fields and toggle
    $modal.find('#changePasswordToggle').prop('checked', false);
    $modal.find('.password-fields').hide();
    $modal.find('#editPassword, #editConfirmPassword').val('').removeAttr('required');
    
    // Clear any previous messages
    $modal.find('#formMessage').empty();
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
});

// Handle add user button
$('#userModal').on('show.bs.modal', function() {
    if (!$(this).data('edit-mode')) {
        // Reset form for new user
        $('#userForm')[0].reset();
        $('#userId').val('');
        $('#modalTitle').text('Add New User');
        $('#passwordRequired').show();
        $('#password').attr('required', 'required');
    }
    $(this).data('edit-mode', false);
});

// Handle form submission
$('#userForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const $submitBtn = $('#saveUserBtn');
    const originalBtnText = $submitBtn.html();
    
    // Show loading state
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    
    // Get the classification value
    const classification = $('#editClassification').val();
    formData.append('classification', classification);
    
    // Submit form via AJAX
    $.ajax({
        url: 'update_user.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Reload the page to show changes
            window.location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Error saving user:', error);
            $('#formMessage').html('<div class="alert alert-danger">An error occurred while saving. Please try again.</div>');
        },
        complete: function() {
            $submitBtn.prop('disabled', false).html(originalBtnText);
        }
    });
});

// Handle classification dropdown selection
$(document).on('click', '.classification-option', function(e) {
    e.preventDefault();
    
    const $this = $(this);
    const newClassification = $this.data('value');
    const $dropdown = $this.closest('.dropdown');
    const $button = $dropdown.find('.status-toggle');
    const userId = $button.data('user-id');
    
    // Update the button text and style
    $button.text(newClassification);
    $button.removeClass('btn-success btn-warning')
           .addClass(newClassification === 'Irregular' ? 'btn-warning' : 'btn-success');
    
    // Update the data attribute
    $button.data('current-classification', newClassification);
    
    // Send AJAX request to update database
    $.ajax({
        url: 'update_user.php',
        type: 'POST',
        data: {
            user_id: userId,
            classification: newClassification,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: `Classification changed to ${newClassification}`,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                // Revert the button if update failed
                const oldClassification = $button.data('current-classification');
                $button.text(oldClassification);
                $button.removeClass('btn-success btn-warning')
                       .addClass(oldClassification === 'Irregular' ? 'btn-warning' : 'btn-success');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to update classification'
                });
            }
        },
        error: function(xhr, status, error) {
            // Revert the button if AJAX failed
            const oldClassification = $button.data('current-classification');
            $button.text(oldClassification);
            $button.removeClass('btn-success btn-warning')
                   .addClass(oldClassification === 'Irregular' ? 'btn-warning' : 'btn-success');
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update classification. Please try again.'
            });
        }
    });
    
    // Close the dropdown
    $dropdown.removeClass('show');
});

// Handle delete user - REMOVED (consolidated above)
        // $(document).on('click', '.delete-user', function() {
        //     const userId = $(this).data('id');
        //     const $row = $(this).closest('tr');
        //     
        //     Swal.fire({
        //         title: 'Are you sure?',
        //         text: "You won't be able to revert this!",
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonColor: '#3085d6',
        //         cancelButtonColor: '#d33',
        //         confirmButtonText: 'Yes, delete it!'
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             // Make AJAX call to delete user
        //             $.ajax({
        //                 url: 'delete_user.php',
        //                 type: 'POST',
        //                 data: { user_id: userId },
        //                 dataType: 'json',
        //                 success: function(response) {
        //                     if (response.success) {
        //                         Swal.fire(
        //                             'Deleted!',
        //                             'User has been deleted.',
        //                             'success'
        //                         ).then(() => {
        //                             // Remove the row from the table
        //                             $row.fadeOut(400, function() {
        //                                 $(this).remove();
        //                             });
        //                         });
        //                     } else {
        //                         throw new Error(response.message || 'Failed to delete user');
        //                     }
        //                 },
        //                 error: function(xhr, status, error) {
        //                     let errorMessage = 'An error occurred while deleting the user.';
        //                     if (xhr.responseJSON && xhr.responseJSON.message) {
        //                         errorMessage = xhr.responseJSON.message;
        //                     }
        //                     Swal.fire({
        //                         icon: 'error',
        //                         title: 'Error',
        //                         text: errorMessage
        //                     });
        //                 }
        //             });
        //         }
        //     });
        // }
    
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to sidebar menu items
        const menuItems = document.querySelectorAll('#sidebar .nav-link');
        menuItems.forEach((item, index) => {
            item.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s forwards`;
            item.style.opacity = '0';
        });

        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('#userTable tbody tr');
        tableRows.forEach(row => {
            row.style.transition = 'all 0.3s ease';
            row.addEventListener('mouseenter', () => {
                row.style.transform = 'scale(1.01)';
                row.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
            });
            row.addEventListener('mouseleave', () => {
                row.style.transform = 'scale(1)';
                row.style.boxShadow = 'none';
            });
        });

        // Event delegation for HTML table action buttons
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-action');
            if (!button) return;
            
            const userId = button.getAttribute('data-id');
            const action = button.classList.contains('btn-edit') ? 'edit' : 
                          button.classList.contains('btn-restore') ? 'restore' : 
                          button.classList.contains('btn-archive') ? 'archive' : 
                          button.classList.contains('btn-delete') ? 'delete' : null;
            
            if (!action || !userId) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            switch(action) {
                case 'edit':
                    openEditModal(parseInt(userId));
                    break;
                case 'archive':
                    quickArchiveUser(parseInt(userId));
                    break;
                case 'restore':
                    quickRestoreUser(parseInt(userId));
                    break;
                case 'delete':
                    quickDeleteUser(parseInt(userId));
                    break;
            }
        });

        // Handle form submission with better validation and feedback
        document.addEventListener('submit', async function(e) {
            if (e.target.matches('#editUserForm, #userForm')) {
                e.preventDefault();
                
                const form = e.target;
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Reset previous validation
                form.classList.remove('was-validated');
                const invalidFields = form.querySelectorAll('.is-invalid');
                invalidFields.forEach(field => field.classList.remove('is-invalid'));
                
                // Validate form
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-loading');
                submitBtn.innerHTML = 'Processing...';
                
                try {
                    const response = await fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: result.message || 'Operation completed successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the page or update the table
                            window.location.reload();
                        });
                    } else {
                        throw new Error(result.message || 'An error occurred');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred while processing your request'
                    });
                } finally {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        });
    })
</script>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 15px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #4285f4, #669df6); border: none; border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="editUserModalLabel" style="color: white; font-weight: 600;">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div id="editMessage"></div>
                <form id="editUserForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="user_id" id="editUserId">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editFirstName" name="firstname" required style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editLastName" name="lastname" required style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="editEmail" name="email" required style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editStudentId" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="editStudentId" name="student_id" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editRole" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="editRole" name="role_id" required style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                                    <option value="">Select Role</option>
                                    <option value="1">Administrator</option>
                                    <option value="2">Dean</option>
                                    <option value="3">Registrar</option>
                                    <option value="4">BSIT Student</option>
                                    <option value="5">BSCS Student</option>
                                    <option value="6">Student</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editCourse" class="form-label">Course</label>
                                <input type="text" class="form-control" id="editCourse" name="course" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editClassification" class="form-label">Classification</label>
                                <select class="form-select" id="editClassification" name="classification" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                                    <option value="Regular">Regular</option>
                                    <option value="Irregular">Irregular</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="editStatus" name="status" required style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px;">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <input type="checkbox" id="changePasswordToggle"> Change Password?
                        </label>
                    </div>

                    <div id="passwordFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editPassword" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editPassword" name="password" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px 0 0 8px;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('editPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editConfirmPassword" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editConfirmPassword" name="confirm_password" style="border: 1px solid rgba(66, 133, 244, 0.2); border-radius: 8px 0 0 8px;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('editConfirmPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(66, 133, 244, 0.1); padding: 20px; gap: 10px;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-color: #6c757d; color: #6c757d;">Cancel</button>
                <button type="button" class="btn btn-warning" id="archiveUserBtn" onclick="archiveUser()" style="background: #ff9800; border-color: #ff9800; color: white; font-weight: 600;">
                    <i class="fas fa-archive me-2"></i>Set Inactive
                </button>
                <button type="button" class="btn btn-danger" id="deleteUserBtn" onclick="deleteUser()" style="background: #dc3545; border-color: #dc3545; color: white; font-weight: 600;">
                    <i class="fas fa-trash me-2"></i>Delete User
                </button>
                <button type="submit" class="btn btn-primary" id="saveUserBtn" form="editUserForm" style="background: linear-gradient(135deg, #4285f4, #669df6); border: none; color: white; font-weight: 600;">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    field.type = field.type === 'password' ? 'text' : 'password';
}

// Handle password toggle checkbox
document.getElementById('changePasswordToggle')?.addEventListener('change', function() {
    const passwordFields = document.getElementById('passwordFields');
    const editPassword = document.getElementById('editPassword');
    const editConfirmPassword = document.getElementById('editConfirmPassword');
    
    if (this.checked) {
        passwordFields.style.display = 'block';
        editPassword.setAttribute('required', 'required');
        editConfirmPassword.setAttribute('required', 'required');
    } else {
        passwordFields.style.display = 'none';
        editPassword.removeAttribute('required');
        editConfirmPassword.removeAttribute('required');
        editPassword.value = '';
        editConfirmPassword.value = '';
    }
});

// Archive user
function archiveUser() {
    const userId = document.getElementById('editUserId').value;
    
    Swal.fire({
        title: 'Set User Inactive?',
        text: 'Setting this user to inactive will prevent them from logging in.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff9800',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, set inactive!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('archive_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'User Set Inactive',
                        text: 'User has been set to inactive successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to set user inactive'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while setting user inactive'
                });
            });
        }
    });
}

// Delete user
function deleteUser() {
    const userId = document.getElementById('editUserId').value;
    
    Swal.fire({
        title: 'Delete User?',
        text: 'This action cannot be undone!',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'User Deleted',
                        text: 'User has been deleted successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete user'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the user'
                });
            });
        }
    });
}

// Handle edit form submission
document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.getElementById('editPassword').value;
    const confirmPassword = document.getElementById('editConfirmPassword').value;
    
    // Validate passwords if change password is checked
    if (document.getElementById('changePasswordToggle').checked) {
        if (!password || !confirmPassword) {
            showEditMessage('Please fill in both password fields', 'danger');
            return;
        }
        if (password !== confirmPassword) {
            showEditMessage('Passwords do not match', 'danger');
            return;
        }
        if (password.length < 6) {
            showEditMessage('Password must be at least 6 characters long', 'danger');
            return;
        }
    }
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('saveUserBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showEditMessage('User updated successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showEditMessage(data.message || 'Failed to update user', 'danger');
        }
    })
    .catch(error => {
        showEditMessage('An error occurred', 'danger');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function showEditMessage(message, type) {
    const messageDiv = document.getElementById('editMessage');
    messageDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
}
</script>

</html>