<?php
require_once '../db_connect.php';

echo "<h2>🔧 Add E-Signature Column to signin_db Table</h2>";

// Check if column already exists
$columnCheck = $conn->query("SHOW COLUMNS FROM signin_db LIKE 'esignature'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ esignature column already exists</p>";
} else {
    // Add the esignature column
    $alter_sql = "ALTER TABLE signin_db ADD COLUMN esignature VARCHAR(255) NULL AFTER profile_image";
    
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✅ esignature column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to add esignature column: " . $conn->error . "</p>";
    }
}

// Verify the column was added
echo "<h3>📋 Updated Table Structure</h3>";
$columns = $conn->query("SHOW COLUMNS FROM signin_db");
if ($columns) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($column = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Could not retrieve table structure</p>";
}

echo "<p><a href='test_esignature_upload.php'>← Test Upload</a></p>";
echo "<p><a href='profile.php'>← Back to Profile</a></p>";
?>
