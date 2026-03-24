<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user role
$role_id = $_SESSION['role_id'] ?? 0;

// Redirect based on role
switch($role_id) {
    case 1: // Super Admin
        header('Location: super_admin/notification.php');
        break;
    case 2: // Admin
        header('Location: adminpage/notification_page.php');
        break;
    case 3: // Student
    default:
        header('Location: student/notification.php');
        break;
}

exit();
?>
