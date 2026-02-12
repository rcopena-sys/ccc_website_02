<?php
// Database configuration (environment-aware)

// Define DB constants once, based on environment
if (!defined('DB_SERVER')) {
    $hostHeader = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $isLocal = ($hostHeader === 'localhost' || $hostHeader === '127.0.0.1' || $hostHeader === '');

    if ($isLocal) {
        // Local development (XAMPP)
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');  // Default XAMPP username
        define('DB_PASSWORD', '');      // Default XAMPP password (empty)
        define('DB_NAME', 'ccc_curriculum_evaluation'); // Local database
    } else {
        // Production server
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'u220649928_public_html');
        define('DB_PASSWORD', 'RoZz_puGeCivic96Vti');
        define('DB_NAME', 'u220649928_ccc_curriculum');
    }
}

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com'); // Replace with your email
define('SMTP_PASSWORD', 'your-app-password');    // Use App Password, not your Gmail password

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

// Database connection
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log the error and show a user-friendly message
    error_log($e->getMessage());
    // Show detailed error for debugging
    if (php_sapi_name() === 'cli' || isset($_GET['debug'])) {
        die("Database Error: " . $e->getMessage());
    } else {
        die("We're experiencing technical difficulties. Please try again later. Error: " . $e->getMessage());
    }
}
?>
