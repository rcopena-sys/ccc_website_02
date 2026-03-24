<?php
require_once 'db_connect.php';

// Check the password column structure
$result = $conn->query("SHOW COLUMNS FROM signin_db LIKE 'password'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Password Column Type: " . $row['Type'] . "<br>";
    echo "Password Column Null: " . $row['Null'] . "<br>";
    echo "Password Column Key: " . $row['Key'] . "<br>";
    echo "Password Column Default: " . ($row['Default'] ?? 'NULL') . "<br>";
    echo "Password Column Extra: " . $row['Extra'] . "<br>";
} else {
    echo "Password column not found in signin_db table.<br>";
}

// Verify password hashing
$test_password = 'test123';
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
$verify = password_verify($test_password, $hashed_password);

echo "<br>Password Hashing Test:<br>";
echo "Original: $test_password<br>";
echo "Hashed: $hashed_password<br>";
echo "Verify Result: " . ($verify ? 'Success' : 'Failed') . "<br>";

// Check if we can connect to the database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "<br>Database connection successful!<br>";
    
    // Check if we can query the signin_db table
    $result = $conn->query("SELECT COUNT(*) as count FROM signin_db");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Number of users in signin_db: " . $row['count'] . "<br>";
    } else {
        echo "Error querying signin_db: " . $conn->error . "<br>";
    }
}

$conn->close();
?>
