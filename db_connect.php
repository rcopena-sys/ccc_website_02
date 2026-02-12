<?php
// Root-level database connection (environment-aware)

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

$servername = DB_SERVER;
$username = DB_USERNAME;
$password = DB_PASSWORD;
$dbname = DB_NAME;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set and collation
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
?>