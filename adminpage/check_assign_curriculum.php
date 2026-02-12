<?php
require_once '../db_connect.php';

echo "<h2>Assign Curriculum Table Structure</h2>";

// Check assign_curriculum columns
echo "<h3>assign_curriculum columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM assign_curriculum");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
    }
    echo "</table>";
}

// Check if there's a programs or roles table for program_id reference
echo "<h3>roles table (for program_id reference):</h3>";
$result = $conn->query("SHOW COLUMNS FROM roles");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
    }
    echo "</table>";
}

// Show sample data from assign_curriculum
echo "<h3>Sample data from assign_curriculum:</h3>";
$result = $conn->query("SELECT * FROM assign_curriculum LIMIT 5");
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        // Get column names
        $fields = $result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Reset pointer and show data
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";
}

// Show roles data for program_id mapping
echo "<h3>Roles data (program_id mapping):</h3>";
$result = $conn->query("SELECT * FROM roles");
if ($result) {
    echo "<table border='1'>";
    if ($result->num_rows > 0) {
        // Get column names
        $fields = $result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Reset pointer and show data
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</table>";
}

$conn->close();
?>
