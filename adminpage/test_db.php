<?php
try {
    // Update with your actual database credentials
    $servername = "localhost";
    $dbname = "ccc_curriculum_evaluation";  // Your database name
    $username = "root";  // Default username for XAMPP/WAMP
    $password = "";      // Default password for XAMPP/WAMP (usually blank)

    // Create a new PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
