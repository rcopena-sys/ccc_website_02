<?php
require_once '../db_connect.php';

echo "<h2>Database Column Check</h2>";

// Check signin_db columns
echo "<h3>signin_db columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM signin_db");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
    }
    echo "</table>";
}

// Check students_db columns
echo "<h3>students_db columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM students_db");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
    }
    echo "</table>";
}

$conn->close();
?>
