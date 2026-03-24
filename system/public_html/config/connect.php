<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ccc_curriculum_evaluation";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper functions for database operations
function call_mysql_query($query) {
    global $conn;
    return $conn->query($query);
}

function call_mysql_fetch_array($result) {
    return $result->fetch_array(MYSQLI_ASSOC);
}
?>