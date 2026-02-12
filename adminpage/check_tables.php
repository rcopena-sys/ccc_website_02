<?php
require_once 'db.php';

echo "<h2>Database Connection Test</h2>";

// Check connection
if ($conn->connect_error) {
    die("<div style='color:red;'>Connection failed: " . $conn->connect_error . "</div>");
}
echo "<div style='color:green;'>✓ Successfully connected to database: ccc_curriculum_evaluation</div>";

// List all tables in the database
echo "<h3>Tables in database:</h3>";
$result = $conn->query("SHOW TABLES");

if ($result->num_rows > 0) {
    echo "<ul>";
    while($row = $result->fetch_array()) {
        $table = $row[0];
        echo "<li>$table";
        
        // Show table structure
        $columns = $conn->query("SHOW COLUMNS FROM $table");
        if ($columns) {
            echo "<ul>";
            while($col = $columns->fetch_assoc()) {
                echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")";
                if ($col['Key'] == 'PRI') echo " <strong>[PRIMARY KEY]</strong>";
                if ($col['Null'] == 'NO') echo " <em>NOT NULL</em>";
                if ($col['Default'] !== null) echo " [Default: " . $col['Default'] . "]";
                echo "</li>";
            }
            echo "</ul>";
        }
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "No tables found in the database.";
}

$conn->close();
?>
