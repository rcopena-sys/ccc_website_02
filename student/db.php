<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "ccc_curriculum_evaluation";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("connection failed". $conn->connect_error);
}
?>