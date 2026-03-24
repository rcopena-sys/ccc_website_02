<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "ccc_curriculum_evaluation";

$conn = new mysqli($host, $username, $password, $database);

if ($userType == 'dean') {
    header("Location: Dean.php");
    exit;
} elseif ($userType == 'registrar') {
    header("Location: registrar.php");
    exit;
} else {

    echo "Invalid user type or login failed.";
}
if ($conn->connect_error) {
    die("connection failed". $conn->connect_error);
}
?>