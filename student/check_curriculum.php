<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "ccc_curriculum_evaluation");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all tables in the database
$tables = $conn->query("SHOW TABLES");
echo "<h2>Tables in database:</h2>";
while ($table = $tables->fetch_array()) {
    echo "<h3>Table: " . $table[0] . "</h3>";
    
    // Get table structure
    $structure = $conn->query("DESCRIBE " . $table[0]);
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
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
    
    // Get sample data
    $data = $conn->query("SELECT * FROM " . $table[0] . " LIMIT 5");
    if ($data->num_rows > 0) {
        echo "<h4>Sample data:</h4>";
        // Get field names
        $fields = $data->fetch_fields();
        echo "<table border='1'><tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Reset data pointer
        $data->data_seek(0);
        
        // Get rows
        while ($row = $data->fetch_row()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Close connection
$conn->close();
?>
