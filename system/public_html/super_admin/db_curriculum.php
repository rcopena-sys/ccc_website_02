<?php
// Database connection settings for ccc_curriculum_evaluation
$servername = "localhost";
$username = "u353705507_ccc_curriculum";
$password = "RoZz_puGeCivic96Vti1";
$dbname = "u353705507_ccc_cureval";


// Create connection
$conn_curriculum = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn_curriculum->connect_error) {
    die("Curriculum database connection failed: " . $conn_curriculum->connect_error);
}

// Make the connection available globally
$GLOBALS['conn_curriculum'] = $conn_curriculum;
?>