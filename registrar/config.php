<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u220649928_public_html');
define('DB_PASSWORD', 'RoZz_puGeCivic96Vti1');
define('DB_NAME', 'u220649928_ccc_curriculum');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'your-email-password'); // Replace with your email password

// Site configuration
define('SITE_NAME', 'CCC Curriculum Evaluation');
define('SITE_URL', 'http://localhost/website');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Use centralized database connection
require_once __DIR__ . '/../db_connect.php';
?>
