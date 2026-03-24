<?php
$servername = "localhost";
$username = "u353705507_ccc_curriculum";
$password = "RoZz_puGeCivic96Vti1";
$dbname = "u353705507_ccc_cureval";

$dbname = "ccc_curriculum_evaluation"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>