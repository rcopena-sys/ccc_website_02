<?php
// Database connection settings
$servername = "localhost";
$username = "u220649928_public_html";
$password = "RoZz_puGeCivic96Vti";
$dbname = "u220649928_ccc_curriculum";

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