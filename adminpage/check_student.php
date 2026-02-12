<?php
require_once 'db.php';

// Check columns in signin_db table
echo "Columns in signin_db:\n";
$columns = $conn->query("SHOW COLUMNS FROM signin_db");
while ($col = $columns->fetch_assoc()) {
    echo "- " . $col['Field'] . "\n";
}

// Check for students in signin_db table
echo "\nSample students:\n";
$result = $conn->query("SELECT * FROM signin_db LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "Data: " . print_r($row, true) . "\n";
}
?>
