<?php
// Student module database connection (environment-aware)

// Define shared DB constants once, based on environment
if (!defined('DB_SERVER')) {
    $hostHeader = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $isLocal = ($hostHeader === 'localhost' || $hostHeader === '127.0.0.1' || $hostHeader === '');

    if ($isLocal) {
        // Local development (XAMPP)
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'ccc_curriculum_evaluation'); // Your local database name
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
$dbname = DB_NAME; // Active database name based on environment

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>