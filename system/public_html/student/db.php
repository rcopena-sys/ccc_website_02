<?php
$servername = "localhost";
$username = "u353705507_ccc_curriculum";
$password = "RoZz_puGeCivic96Vti1";
$dbname = "u353705507_ccc_cureval";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("connection failed". $conn->connect_error);
}
?>