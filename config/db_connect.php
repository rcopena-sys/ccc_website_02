<?php
// Database configuration
$db_host = 'localhost';     // Database host
$db_username = 'u220649928_public_html';      // Database username
$db_password = 'RoZz_puGeCivic96Vti';          // Database password
$db_name = 'u220649928_ccc_curriculum';  // Database name

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character encoding
$conn->set_charset("utf8mb4");

// Set timezone if needed
// date_default_timezone_set('Asia/Manila');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
