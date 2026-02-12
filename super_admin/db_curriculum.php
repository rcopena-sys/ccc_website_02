<?php
// Database connection settings for ccc_curriculum_evaluation
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ccc_curriculum_evaluation";

// Create connection
$conn_curriculum = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn_curriculum->connect_error) {
    die("Curriculum database connection failed: " . $conn_curriculum->connect_error);
}

// Make the connection available globally
$GLOBALS['conn_curriculum'] = $conn_curriculum;
?>