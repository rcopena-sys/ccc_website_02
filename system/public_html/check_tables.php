<?php
require_once 'student/db.php';

// Get list of tables
$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
}

echo "<h2>Database Tables:</h2>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table";
    
    // Get table structure
    $result = $conn->query("DESCRIBE `$table`");
    if ($result) {
        echo "<ul>";
        while($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} - {$row['Type']}";
        }
        echo "</ul>";
    }
    echo "</li>";
}
echo "</ul>";

$conn->close();
?>
