<?php
// Database configuration (environment-aware)

// Define shared DB constants once, based on environment
if (!defined('DB_SERVER')) {
    $hostHeader = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $isLocal = ($hostHeader === 'localhost' || $hostHeader === '127.0.0.1' || $hostHeader === '');

    if ($isLocal) {
        // Local development (XAMPP)
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'ccc_curriculum_evaluation');
    } else {
        // Production server
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'u220649928_public_html');
        define('DB_PASSWORD', 'RoZz_puGeCivic96Vti');
        define('DB_NAME', 'u220649928_ccc_curriculum');
    }
}

$host = DB_SERVER;
$username = DB_USERNAME;
$password = DB_PASSWORD;
$database = DB_NAME;

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Manila');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
