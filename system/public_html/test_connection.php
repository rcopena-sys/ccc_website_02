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
} else {
    echo "Successfully connected to ccc_curriculum_evaluation database!";
    
    // Get some basic information about the database
    $tables_result = $conn->query("SHOW TABLES");
    if ($tables_result) {
        echo "\n\nAvailable tables in the database:\n";
        while ($table = $tables_result->fetch_array()) {
            echo "- " . $table[0] . "\n";
        }
    }
}

// Close the connection
$conn->close();
?>