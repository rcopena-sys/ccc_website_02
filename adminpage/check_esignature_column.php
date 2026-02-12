<?php
require_once 'db.php';

echo "<h2>🔍 Check E-Signature Column in signin_db</h2>";

// Check if esignature column exists in signin_db
echo "<h3>📊 signin_db Table Structure</h3>";
$columnCheck = $conn->query("SHOW COLUMNS FROM signin_db LIKE 'esignature'");

if ($columnCheck && $columnCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ esignature column exists in signin_db</p>";
    
    $column = $columnCheck->fetch_assoc();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    echo "<tr>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . $column['Default'] . "</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ esignature column does NOT exist in signin_db</p>";
    
    // Show all columns in signin_db
    echo "<h4>Current columns in signin_db:</h4>";
    $allColumns = $conn->query("SHOW COLUMNS FROM signin_db");
    if ($allColumns) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($column = $allColumns->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Add the column
    echo "<h3>🔧 Adding esignature column...</h3>";
    $addColumnSQL = "ALTER TABLE signin_db ADD COLUMN esignature VARCHAR(255) DEFAULT NULL COMMENT 'E-signature filename'";
    
    if ($conn->query($addColumnSQL)) {
        echo "<p style='color: green;'>✅ esignature column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding column: " . $conn->error . "</p>";
    }
}

// Check current user's esignature
echo "<h3>👤 Current User E-Signature Status</h3>";
$user_id = $_SESSION['user_id'] ?? 0;
$userQuery = "SELECT id, firstname, lastname, email, esignature FROM signin_db WHERE id = ?";
$stmt = $conn->prepare($userQuery);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
        echo "<tr><td>Name</td><td>" . $user['firstname'] . " " . $user['lastname'] . "</td></tr>";
        echo "<tr><td>Email</td><td>" . $user['email'] . "</td></tr>";
        echo "<tr><td>E-Signature</td><td>" . ($user['esignature'] ?: 'NULL') . "</td></tr>";
        echo "</table>";
        
        if (!empty($user['esignature'])) {
            $signature_file = 'uploads/esignatures/' . $user['esignature'];
            if (file_exists($signature_file)) {
                echo "<p style='color: green;'>✅ Signature file exists: " . $user['esignature'] . "</p>";
                echo "<img src='" . $signature_file . "' style='max-height: 60px; border: 1px solid #ddd; padding: 5px;' alt='Current Signature'>";
            } else {
                echo "<p style='color: orange;'>⚠️ Signature file not found: " . $signature_file . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No signature in database</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ User not found</p>";
    }
    $stmt->close();
}

$conn->close();
?>

<p><a href="profile.php">← Back to Profile</a></p>
<p><a href="find_my_signatures.php">← Find My Signatures</a></p>
