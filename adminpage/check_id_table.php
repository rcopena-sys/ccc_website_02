<?php
include 'db.php';

echo "Checking for ID-related tables...\n";

// Show all tables that might contain ID information
$result = $conn->query("SHOW TABLES LIKE '%id%'");
echo "Tables with 'id' in name:\n";
while($row = $result->fetch_row()) {
    echo "- " . $row[0] . "\n";
}

echo "\nChecking common ID tables:\n";

// Check students_db structure
echo "\nstudents_db table:\n";
$result = $conn->query('DESCRIBE students_db');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

// Check signin_db structure
echo "\nsignin_db table:\n";
$result = $conn->query('DESCRIBE signin_db');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

$conn->close();
?>
