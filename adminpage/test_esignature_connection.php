<?php
// Test script to verify e-signature database connection
require_once '../db_connect.php';

echo "<h2>E-Signature Database Connection Test</h2>";

// Test 1: Check database connection
/** @var mysqli $conn */
if ($conn->connect_error) {
    die("<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>");
} else {
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
}

// Test 2: Check if esignature column exists
echo "<h3>Checking esignature column...</h3>";
/** @var mysqli_result|false $column_check */
$column_check = $conn->query("SHOW COLUMNS FROM signin_db LIKE 'esignature'");

if ($column_check && $column_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ E-signature column exists in signin_db table!</p>";
    
    // Show column details
    /** @var array $column_info */
    $column_info = $column_check->fetch_assoc();
    echo "<ul>";
    echo "<li><strong>Field:</strong> " . ($column_info['Field'] ?? 'N/A') . "</li>";
    echo "<li><strong>Type:</strong> " . ($column_info['Type'] ?? 'N/A') . "</li>";
    echo "<li><strong>Null:</strong> " . ($column_info['Null'] ?? 'N/A') . "</li>";
    echo "<li><strong>Default:</strong> " . ($column_info['Default'] ?? 'N/A') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ E-signature column does not exist!</p>";
    echo "<p><strong>Solution:</strong> Run the SQL query from add_esignature_column.sql file:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
    echo "ALTER TABLE signin_db ADD COLUMN esignature VARCHAR(255) DEFAULT NULL;";
    echo "</pre>";
}

// Test 3: Check uploads directory
echo "<h3>Checking uploads directory...</h3>";
$upload_dir = 'uploads/esignatures/';
if (file_exists($upload_dir)) {
    echo "<p style='color: green;'>✅ Uploads directory exists: " . $upload_dir . "</p>";
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✅ Directory is writable!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Directory exists but may not be writable!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Uploads directory does not exist!</p>";
    echo "<p><strong>Solution:</strong> Create the directory: mkdir -p " . $upload_dir . "</p>";
}

// Test 4: Sample query to test user data retrieval
echo "<h3>Testing user data retrieval...</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $test_query = "SELECT id, firstname, lastname, email, esignature FROM signin_db WHERE id = ?";
    /** @var mysqli_stmt|false $stmt */
    $stmt = $conn->prepare($test_query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        /** @var mysqli_result|false $result */
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            /** @var array $user */
            $user = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ User data retrieved successfully!</p>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . ($user['id'] ?? 'N/A') . "</li>";
            echo "<li><strong>Name:</strong> " . ($user['firstname'] ?? 'N/A') . " " . ($user['lastname'] ?? 'N/A') . "</li>";
            echo "<li><strong>Email:</strong> " . ($user['email'] ?? 'N/A') . "</li>";
            echo "<li><strong>E-Signature:</strong> " . ($user['esignature'] ? $user['esignature'] : 'None') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ User data not found!</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Failed to prepare statement!</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ User not logged in - cannot test user data retrieval</p>";
}

// Test 5: Check if signature files exist
echo "<h3>Checking existing signature files...</h3>";
if (file_exists($upload_dir)) {
    $files = glob($upload_dir . "*");
    if ($files) {
        echo "<p style='color: green;'>✅ Found " . count($files) . " signature file(s):</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No signature files found yet</p>";
    }
}

/** @var mysqli $conn */
$conn->close();
echo "<p><a href='profile.php'>← Back to Profile Page</a></p>";
?>
