<?php
require_once 'db.php';

// Check if assign_curriculum table exists
$result = $conn->query("SHOW TABLES LIKE 'assign_curriculum'");
if ($result->num_rows == 0) {
    die("Error: assign_curriculum table does not exist");
}

// Get table structure
$result = $conn->query("DESCRIBE assign_curriculum");
if (!$result) {
    die("Error describing table: " . $conn->error);
}

echo "<h2>assign_curriculum Table Structure:</h2>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Show sample data
echo "<h2>Sample Data (first 5 rows):</h2>";
$sample = $conn->query("SELECT * FROM assign_curriculum LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<table border='1'><tr>";
    // Header row
    $fields = [];
    while ($field = $sample->fetch_field()) {
        $fields[] = $field->name;
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr>";
    
    // Data rows
    $sample->data_seek(0);
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<td>" . htmlspecialchars($row[$field] ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found in assign_curriculum table";
}
?>
