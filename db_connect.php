<?php
// Detect environment: localhost (XAMPP/dev) vs production (hosting)
$host = $_SERVER['HTTP_HOST'] ?? '';

$isLocalHost = (
    PHP_SAPI === 'cli' ||
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    strpos($host, 'localhost:') === 0 ||
    strpos($host, '127.0.0.1:') === 0
);

// Simple environment flag other scripts can use
if (!defined('APP_ENV')) {
    define('APP_ENV', $isLocalHost ? 'local' : 'production');
}

if ($isLocalHost) {
    // Local XAMPP database settings
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "ccc_curriculum_evaluation";

    // First connect to MySQL server without selecting a database
    $serverConn = @new mysqli($servername, $username, $password);
    if ($serverConn->connect_error) {
        die("MySQL server connection failed: " . $serverConn->connect_error);
    }

    // Ensure the local database exists; create it if missing
    $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . $serverConn->real_escape_string($dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$serverConn->query($createDbSql)) {
        die("Failed to ensure local database `$dbname`: " . $serverConn->error);
    }
    $serverConn->close();

    // Now connect to the ensured database
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} else {
    // Production hosting database settings
    $servername = "localhost";
    $username   = "u220649928_public_html";
    $password   = "RoZz_puGeCivic96Vti";
    $dbname     = "u220649928_ccc_curriculum";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set character set and collation
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
?>