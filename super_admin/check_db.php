<?php
session_start();
require_once '../db_connect.php';

echo "<h3>Database Connection Test</h3>";

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>✓ Database connection successful</p>";

// List all tables
echo "<h4>Database Tables:</h4>";
$result = $conn->query("SHOW TABLES");
if ($result->num_rows > 0) {
    echo "<ul>";
    while($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No tables found in the database.</p>";
}

// Check users table structure
if ($result->num_rows > 0) {
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<h4>Users Table Structure:</h4>";
        $columns = $conn->query("SHOW COLUMNS FROM users");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while($row = $columns->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show first 5 users for debugging
        echo "<h4>Sample Users (first 5):</h4>";
        $users = $conn->query("SELECT * FROM users LIMIT 5");
        if ($users->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            // Header
            $first_row = $users->fetch_assoc();
            echo "<tr>";
            foreach(array_keys($first_row) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // First row
            echo "<tr>";
            foreach($first_row as $value) {
                echo "<td>" . htmlspecialchars($value) . "&nbsp;</td>";
            }
            echo "</tr>";
            
            // Remaining rows
            while($row = $users->fetch_assoc()) {
                echo "<tr>";
                foreach($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "&nbsp;</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No users found in the database.</p>";
        }
    } else {
        echo "<p style='color:red'>The 'users' table does not exist in the database.</p>";
    }
}

// Show session data
echo "<h4>Session Data:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$conn->close();
?>
