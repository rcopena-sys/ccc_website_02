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

if ($isLocalHost) {
    // Local XAMPP database settings
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "ccc_curriculum_evaluation";
} else {
    // Production hosting database settings (as requested)
    $servername = "localhost";
    $username   = "u220649928_public_html";
    $password   = "RoZz_puGeCivic96Vti";
    $dbname     = "u220649928_ccc_curriculum";
}

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