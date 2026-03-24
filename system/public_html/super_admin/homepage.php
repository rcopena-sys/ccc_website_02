<?php
session_start();
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
        /* Loading spinner */
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
        
        /* Improved form feedback */
        .is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        :root {
            --primary-color: #008B8B;
            --secondary-color: #006d6d;
            --background-color: #f5f7f8;
            --text-color: #333;
        }
        
        .status-green { color: #28a745; }
        .status-red { color: #dc3545; }
        .status-orange { color: #fd7e14; }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        /* Sidebar styles */
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: linear-gradient(135deg, #008B8B 0%, #0d47a1 100%);
            color: #fff;
            transition: all 0.3s;
            position: relative;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            animation: slideInLeft 0.5s ease-out;
        }

        .sidebar.active {
            margin-left: -250px;
        }

        #sidebar .sidebar-header {
            padding: 25px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1.6rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            position: relative;
            padding-bottom: 10px;
        }
        
        .sidebar-header h4:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: #00ffff;
            border-radius: 3px;
        }

        #sidebar ul.components {
            padding: 15px 0;
        }
        
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1em;
            display: block;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            align-items: center;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
            margin: 5px 15px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar ul li a:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transition: width 0.3s ease;
            z-index: 0;
        }
        
        .sidebar ul li a:hover:before {
            width: 100%;
        }

        .sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            padding-left: 30px;
        }

        .sidebar ul li.active > a {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border-left: 4px solid #00ffff;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 172, 193, 0.2);
        }

        .sidebar ul li a i {
            margin-right: 15px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
            color: #00ffff;
            background: rgba(0, 255, 255, 0.1);
            border-radius: 6px;
            padding: 5px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .sidebar ul li.active > a i,
        .sidebar ul li a:hover i {
            color: #00a8ff;
        }

        .sidebar ul ul li a {
            padding: 12px 20px 12px 50px;
            font-size: 0.95em;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            border-left: none;
            font-weight: 400;
            position: relative;
        }
        
        .sidebar ul ul li a:before {
            content: '→';
            position: absolute;
            left: 25px;
            opacity: 0;
            transition: all 0.3s ease;
            color: #00ffff;
        }
        
        .sidebar ul ul li a:hover:before {
            left: 20px;
            opacity: 1;
        }

        /* Submenu styles */
        .sidebar ul ul {
            background: rgba(0, 0, 0, 0.1);
            margin: 5px 15px 5px 30px;
            border-radius: 6px;
            overflow: hidden;
            border-left: 2px solid rgba(0, 255, 255, 0.2);
        }

        /* Content area styles */
        #content {
            width: calc(100% - 280px);
            min-height: 100vh;
            margin-left: 280px;
            transition: all 0.3s;
            padding: 25px;
            background-color: #f8f9fa;
        }

        #content.active {
            width: 100%;
        }

        /* Toggle button */
        #sidebarCollapse {
            background: transparent;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .tabulator-row {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .tabulator-row.tabulator-row-even {
            background-color: #f9f9f9;
        }
        
        .tabulator-row.tabulator-selectable:hover {
            background-color: #f0f9f9 !important;
            cursor: pointer;
        }
        
        .tabulator-row:hover {
            background-color: #f0f9f9 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 139, 139, 0.1);
        }
        
        .navigation {
            background-color: #fff;
            border-bottom: 1px solid #008B8B;
            padding: 15px 25px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .card-header {
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .bg-teal {
            background-color: var(--primary-color) !important;
        }
        
        .btn-teal {
            background: linear-gradient(135deg, #008b8b, #006d6d);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 139, 139, 0.3);
        }
        
        .btn-teal:hover {
            background: linear-gradient(135deg, #00a8a8, #008080);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 139, 139, 0.4);
            color: white;
        }
        
        .btn-teal:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0, 139, 139, 0.3);
        }
        
        .btn-teal:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .tabulator {
            border: none;
            background-color: transparent;
        }
        
        .tabulator-page.active {
            background: linear-gradient(135deg, #008b8b, #006d6d) !important;
            color: white !important;
            border: none !important;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 139, 139, 0.3);
            transform: translateY(-1px);
        }
        
        .tabulator .tabulator-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 10px 15px;
            border-radius: 0 0 8px 8px;
        }
        
        .tabulator .tabulator-page {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 0 3px;
            padding: 5px 10px;
            transition: all 0.2s ease;
        }
        
        .tabulator .tabulator-page:hover:not(.disabled):not(.active) {
            background: #e9ecef;
            color: #008b8b;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 20px;
            margin: 20px 0;
        }

        /* Main Table Styling */
        .tabulator {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        /* Table Header */
        .tabulator .tabulator-header {
            background: linear-gradient(135deg, #008b8b, #006d6d);
            border: none;
        }
        
        /* Table Header Columns */
        .tabulator .tabulator-header .tabulator-col {
            background: transparent;
            border: none;
            padding: 15px 12px;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        
        .tabulator .tabulator-header .tabulator-col.tabulator-sortable:hover {
            background-color: rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }
        
        /* Table Rows */
        .tabulator .tabulator-tableHolder .tabulator-table {
            background: white;
        }
        
        .tabulator .tabulator-row {
            border-bottom: 1px solid #f0f2f5;
            transition: all 0.2s ease;
        }
        
        .tabulator .tabulator-row.tabulator-row-even {
            background-color: #f8f9fa;
        }
        
        .tabulator .tabulator-row:hover {
            background-color: #f1f8ff !important;
        }
        
        .tabulator .tabulator-cell {
            padding: 15px 10px;
            border-right: none;
            color: #495057;
            vertical-align: middle;
        }
        
        /* Action Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .btn-action i {
            font-size: 14px;
        }
        
        .btn-edit {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.2);
        }
        
        .btn-edit:hover {
            background: #0d6efd;
            color: white;
        }
        
        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .btn-delete:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Status Badges */
        .badge-status {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 80px;
            display: inline-block;
            text-align: center;
        }
        
        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #842029;
        }
        
        /* Role Badges */
        .badge-role {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Search and Filter */
        .table-controls {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        
        .search-box {
            position: relative;
            max-width: 300px;
        }
        
        .search-box .form-control {
            padding-left: 40px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            height: 42px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #adb5bd;
        }
        
        /* Pagination */
        .tabulator-page {
            margin: 0 5px;
            border-radius: 6px !important;
            min-width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6 !important;
            background: white !important;
            color: #0d6efd !important;
        }
        
        .tabulator-page.active {
            background: #0d6efd !important;
            color: white !important;
            border-color: #0d6efd !important;
        }
        
        .tabulator-page-size {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 5px 10px;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        
        .tabulator .tabulator-header .tabulator-col:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .tabulator-cell {
            padding: 0.9rem 1rem;
            border-right: 1px solid rgba(0, 0, 0, 0.03);
            color: #444;
            font-size: 0.95em;
            vertical-align: middle;
            transition: all 0.2s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .tabulator-cell:first-child {
            color: #008b8b;
            font-weight: 500;
        }
        
        .download-buttons {
            margin-top: 1rem;
        }
        
        .download-buttons .btn {
            margin-right: 0.5rem;
        }
        
        /* Custom search inputs */
        .tabulator-header-filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.4rem;
            width: 100%;
        }
        
        /* Add these new animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.95); opacity: 0; }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Add staggered animations for cards */
        .card:nth-child(1) { animation-delay: 0.2s; }
        .card:nth-child(2) { animation-delay: 0.3s; }
        .card:nth-child(3) { animation-delay: 0.4s; }
        .card:nth-child(4) { animation-delay: 0.5s; }

        /* Animate table rows */
        #userTable tbody tr {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        #userTable tbody tr:nth-child(1) { animation-delay: 0.2s; }
        #userTable tbody tr:nth-child(2) { animation-delay: 0.3s; }
        #userTable tbody tr:nth-child(3) { animation-delay: 0.4s; }
        /* Continue with more delays if needed */

        /* Modal animations */
        .modal-content {
            animation: bounceIn 0.4s ease-out;
        }

        /* Loading animation */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="active">
            <div class="sidebar-header">
                <h4>CCC Admin Panel</h4>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="#usersSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-users"></i> User Management
                    </a>
                    <ul class="collapse list-unstyled" id="usersSubmenu">
                        <li><a href="add_user.php"><i class="fas fa-user-plus"></i> Add New User</a></li>
                        <li><a href="user_role.php"><i class="fas fa-user-shield"></i> User Roles</a></li>
                        <li><a href="edit_user.php"><i class="fas fa-user-edit"></i> Edit User</a></li>
                    </ul>
                </li>
                <li>
                    <a href="calendars.php">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </a>
                </li>
                <li class="flex items-center space-x-2 p-2 rounded-lg hover:bg-blue-600 hover:text-white transition-colors">
                    <a href="activity.php" class="flex items-center space-x-2 w-full text-gray-700 hover:text-white">
                        <i class="fas fa-clipboard-list text-lg"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>
                <li>
                    <a href="view_feedback.php">
                        <i class="fas fa-comment-alt"></i> Feedback
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light navigation">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <!-- Notification Bell -->
                        <a href="../notification.php" class="nav-link me-3 position-relative">
                            <i class="fas fa-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.2rem 0.4rem;">
                                <i class="fas fa-circle"></i>
                            </span>
                        </a>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i> 
                                <span class="d-none d-md-inline">Admin</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
 
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0">User Management</h4>
                            <p class="text-muted mb-0">Manage all system users and their permissions</p>
                        </div>
                        <div class="d-flex">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search-input" class="form-control" placeholder="Search users...">
                            </div>
            
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="userTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Student ID</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Classification</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT s.*, r.role_name 
                                                  FROM signin_db s
                                                  LEFT JOIN roles r ON s.role_id = r.role_id
                                                  ORDER BY s.id DESC";
                                        $result = $conn->query($query);
                                        
                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                $statusClass = $row['status'] == 'Active' ? 'text-success' : 'text-danger';
                                                $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                                                $userId = htmlspecialchars($row['id'], ENT_QUOTES);
                                                $fullName = htmlspecialchars($row['firstname'] . ' ' . $row['lastname'], ENT_QUOTES);
                                                $email = htmlspecialchars($row['email'], ENT_QUOTES);
                                                $roleName = htmlspecialchars($row['role_name'], ENT_QUOTES);
                                                $status = htmlspecialchars($row['status'], ENT_QUOTES);
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

echo "<tr class='user-row' data-id='" . $userId . "'>
    <td>" . $userId . "</td>
    <td>" . $fullName . "</td>
    <td>" . $email . "</td>
    <td>" . $student_id . "</td>
    <td>" . $roleName . "</td>
    <td><span class='" . $statusClass . "'>" . $status . "</span></td>
    " . $classificationDropdown . "
</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    </div>
                </div>
                <footer class="mt-4">
                    <div class="container text-center">
                        <span>Copyright &copy; <?php echo date('Y'); ?> City College of Calamba. All Rights Reserved.</span>
                    </div>
                </footer>
            </div>

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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
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
    (function() {
        console.log('Initializing table...');
        var total_record = 0;
        var table_data = <?php echo $json_table . ";\r\n" ?>;
        console.log('Table data:', table_data);
        total_record = table_data.length;
        
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
                width: 120,
                hozAlign: "center",
                headerSort: false,
                formatter: function(cell) {
                    const data = cell.getRow().getData();
                    return `
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary btn-sm edit-btn" data-id="${data.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${data.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                },
                cellClick: function(e, cell) {
                    // Prevent row selection when clicking action buttons
                    e.stopPropagation();
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
        
        // Add event listeners for action buttons
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.edit-btn, .delete-btn, .view-btn, .btn-action');
            if (!target) return;
            
            // If clicking on the icon inside the button, get the parent button
            const btn = target.classList.contains('btn-action') ? target : target.closest('.btn-action');
            if (!btn) return;
            
            const id = btn.getAttribute('data-id');
            const action = target.classList.contains('edit-btn') ? 'edit' : 
                         target.classList.contains('delete-btn') ? 'delete' : 'view';
            
            const row = table.getRow(id);
            const data = row.getData();
            
            switch(action) {
                case 'edit':
                    try {
                        // Populate modal with user data
                        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        const form = document.getElementById('editUserForm');
                        const status = data.status || 'Active';
                        const statusRadio = document.querySelector(`input[name="status"][value="${status}"]`);
                        if (statusRadio) {
                            statusRadio.checked = true;
                        }
                        
                        // Clear previous messages
                        document.getElementById('editUserMessage').innerHTML = '';
                        
                        // Show the modal
                        editModal.show();
                        
                        // Focus on first input
                        document.getElementById('editFirstName').focus();
                    } catch (error) {
                        console.error('Error opening edit modal:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load user data. Please try again.',
                            confirmButtonColor: '#008B8B'
                        });
                    }
                    
                    // Handle form submission
                    const editForm = document.getElementById('editUserForm');
                    const handleFormSubmit = async function(e) {
                        e.preventDefault();
                        
                        const form = e.target;
                        if (!form.checkValidity()) {
                            e.stopPropagation();
                            form.classList.add('was-validated');
                            return;
                        }
                        
                        const formData = new FormData(form);
                        const saveBtn = form.querySelector('button[type="submit"]');
                        const originalBtnText = saveBtn.innerHTML;
                        const spinner = saveBtn.querySelector('.spinner-border');
                        
                        // Show loading state
                        saveBtn.disabled = true;
                        spinner.classList.remove('d-none');
                        
                        try {
                            const response = await fetch('update_user.php', {
                                method: 'POST',
                                body: formData
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                // Update the table
                                row.update({
                                    firstname: formData.get('firstname'),
                                    lastname: formData.get('lastname'),
                                    email: formData.get('email'),
                                    role: formData.get('role')
                                });
                                
                                // Show success message
                                document.getElementById('editUserMessage').innerHTML = `
                                    <div class="alert alert-success">User updated successfully!</div>
                                `;
                                
                                // Close modal after 1.5 seconds
                                setTimeout(() => {
                                    editModal.hide();
                                }, 1500);
                            } else {
                                throw new Error(result.error || 'Failed to update user');
                            }
                        } catch (error) {
                            console.error('Error updating user:', error);
                            document.getElementById('editUserMessage').innerHTML = `
                                <div class="alert alert-danger">${error.message || 'An error occurred while updating the user'}</div>
                            `;
                        } finally {
                            // Reset button state
                            saveBtn.disabled = false;
                            spinner.classList.add('d-none');
                        }
                    };
                    
                    // Remove previous event listener to avoid duplicates
                    editForm.removeEventListener('submit', handleFormSubmit);
                    editForm.addEventListener('submit', handleFormSubmit);
                    break;
                    
                case 'delete':
                    // Handle delete action
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Delete ${data.firstname} ${data.lastname}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Here you would typically make an AJAX call to delete the user
                            console.log('Deleting user:', id);
                            // For demo, just remove the row
                            row.delete();
                            
                            Swal.fire(
                                'Deleted!',
                                'The user has been deleted.',
                                'success'
                            );
                            
                            // Update the counts
                            updateRecordCounts();
                        }
                    });
                    break;
                    
                case 'view':
                    // Handle view action
                    Swal.fire({
                        title: 'User Details',
                        html: `
                            <div class="text-start">
                                <p><strong>ID:</strong> ${data.id}</p>
                                <p><strong>Name:</strong> ${data.firstname} ${data.lastname}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Role:</strong> ${data.role_id ? ['Admin', 'Dean', 'Registrar', 'BSIT', 'BSCS'][data.role_id - 1] : 'N/A'}</p>
                                <p><strong>Status:</strong> <span class="badge bg-${data.status === 'Active' ? 'success' : 'danger'}">${data.status || 'Inactive'}</span></p>
                            </div>
                        `,
                        confirmButtonText: 'Close'
                    });
                    break;
            }
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
        

        // Event delegation for edit/delete buttons
        document.addEventListener('click', function(e) {
            // Edit button
            if (e.target.closest('.edit-btn')) {
                const id = e.target.closest('.edit-btn').dataset.id;
                window.location.href = 'edit_user.php?id=' + id;
            }
            // Delete button
            if (e.target.closest('.delete-btn')) {
                const id = e.target.closest('.delete-btn').dataset.id;
                if (confirm('Are you sure you want to delete this user?')) {
                    fetch('delete_user.php?id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Error deleting user: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the user');
                        });
                }
            }
        });

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
    })();

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

// Handle delete user
$(document).on('click', '.delete-user'), function() {
    const userId = $(this).data('id');
    const $row = $(this).closest('tr');
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Make AJAX call to delete user
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: { user_id: userId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            'User has been deleted.',
                            'success'
                        ).then(() => {
                            // Remove the row from the table
                            $row.fadeOut(400, function() {
                                $(this).remove();
                            });
                        });
                    } else {
                        throw new Error(response.message || 'Failed to delete user');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An error occurred while deleting the user.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        }
    });
}
    
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

</html>