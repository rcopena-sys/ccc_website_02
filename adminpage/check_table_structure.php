<?php
require_once '../db_connect.php';

echo "<h2>Signin_db Table Structure</h2>";

// Check table structure
$result = $conn->query("DESCRIBE signin_db");

if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
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
    
    // Generate SQL to add missing columns if needed
    echo "<h3>Column Analysis</h3>";
    $columns = [];
    $result->data_seek(0); // Reset result pointer
    
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $required_columns = ['contact', 'esignature'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "<p style='color: red;'>Missing columns: " . implode(', ', $missing_columns) . "</p>";
        echo "<h3>SQL to add missing columns:</h3>";
        
        foreach ($missing_columns as $column) {
            if ($column === 'contact') {
                echo "<pre>ALTER TABLE signin_db ADD COLUMN contact VARCHAR(20) DEFAULT NULL COMMENT 'Contact number';</pre>";
            } elseif ($column === 'esignature') {
                echo "<pre>ALTER TABLE signin_db ADD COLUMN esignature VARCHAR(255) DEFAULT NULL COMMENT 'E-signature filename';</pre>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ All required columns exist!</p>";
    }
    
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

$conn->close();
?>
