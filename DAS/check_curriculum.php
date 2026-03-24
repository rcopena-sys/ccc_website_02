<?php
require_once 'db.php';

// Check if the curriculum table exists
$result = $conn->query("SHOW COLUMNS FROM curriculum");
if ($result) {
    echo "<h2>Curriculum Table Structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check for sample data
$result = $conn->query("SELECT * FROM curriculum LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h2>Sample Data:</h2>";
    echo "<table border='1'><tr>";
    // Print headers
    $finfo = $result->fetch_fields();
    foreach ($finfo as $val) {
        echo "<th>" . htmlspecialchars($val->name) . "</th>";
    }
    echo "</tr>";
    
    // Print rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>
