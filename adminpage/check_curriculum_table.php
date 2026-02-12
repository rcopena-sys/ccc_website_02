<?php
require_once '../db_connect.php';

echo "<h2>Check Curriculum Related Tables</h2>";

// Check if there's a curriculum table
echo "<h3>Available tables with 'curriculum' in name:</h3>";
$result = $conn->query("SHOW TABLES LIKE '%curriculum%'");
if ($result) {
    echo "<table border='1'><tr><th>Table Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $value) {
            echo "<tr><td>" . htmlspecialchars($value) . "</td></tr>";
        }
    }
    echo "</table>";
}

// Check assign_curriculum data with curriculum_id
echo "<h3>assign_curriculum data with curriculum_id:</h3>";
$result = $conn->query("SELECT program_id, curriculum_id, program FROM assign_curriculum LIMIT 10");
if ($result) {
    echo "<table border='1'><tr><th>Program ID</th><th>Curriculum ID</th><th>Program</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['program_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['curriculum_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['program']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if there's a curriculum table with actual curriculum names
echo "<h3>Check for curriculum table:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'curriculum'");
if ($result && $result->num_rows > 0) {
    echo "<p>Found curriculum table!</p>";
    $result = $conn->query("SHOW COLUMNS FROM curriculum");
    if ($result) {
        echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['Field']) . "</td><td>" . htmlspecialchars($row['Type']) . "</td></tr>";
        }
        echo "</table>";
    }
    
    // Show sample curriculum data
    $result = $conn->query("SELECT * FROM curriculum LIMIT 5");
    if ($result) {
        echo "<h3>Sample curriculum data:</h3>";
        echo "<table border='1'>";
        if ($result->num_rows > 0) {
            $fields = $result->fetch_fields();
            echo "<tr>";
            foreach ($fields as $field) {
                echo "<th>" . htmlspecialchars($field->name) . "</th>";
            }
            echo "</tr>";
            
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
} else {
    echo "<p>No curriculum table found</p>";
}

$conn->close();
?>
