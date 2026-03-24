<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db_connect.php';

echo "<h2>Login System Check</h2>";

// 1. Check database connection
if ($conn->connect_error) {
    die("<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>✓ Database connection successful</p>";

// 2. Check if signin_db table exists
$result = $conn->query("SHOW TABLES LIKE 'signin_db'");
if ($result->num_rows === 0) {
    die("<p style='color:red'>❌ Error: Table 'signin_db' does not exist in the database.</p>");
}
echo "<p style='color:green'>✓ Table 'signin_db' exists</p>";

// 3. Check table structure
$result = $conn->query("DESCRIBE signin_db");
if ($result === false) {
    die("<p style='color:red'>❌ Error describing table: " . $conn->error . "</p>");
}

echo "<h3>Table Structure:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Check if there are any users in the database
$result = $conn->query("SELECT COUNT(*) as count FROM signin_db");
if ($result === false) {
    die("<p style='color:red'>❌ Error counting users: " . $conn->error . "</p>");
}
$row = $result->fetch_assoc();
echo "<p>Number of users in database: " . $row['count'] . "</p>";

// 5. Test password hashing
$test_password = 'test123';
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
$verify = password_verify($test_password, $hashed_password);

echo "<h3>Password Hashing Test:</h3>";
echo "<p>Original: " . htmlspecialchars($test_password) . "</p>";
echo "<p>Hashed: " . htmlspecialchars($hashed_password) . "</p>";
echo "<p>Verification: " . ($verify ? '<span style="color:green">SUCCESS</span>' : '<span style="color:red">FAILED</span>') . "</p>";

// 6. Check PHP version
echo "<h3>PHP Version: " . phpversion() . "</h3>";

// 7. Check if password_hash is available
if (!function_exists('password_hash')) {
    echo "<p style='color:red'>❌ Password hashing functions are not available. Please upgrade your PHP version.";
} else {
    echo "<p style='color:green'>✓ Password hashing functions are available</p>";
}

// 8. Show sample user data (without passwords)
$result = $conn->query("SELECT id, email, student_id, role_id, status FROM signin_db LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h3>Sample Users (first 5):</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Student ID</th><th>Role ID</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['role_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
