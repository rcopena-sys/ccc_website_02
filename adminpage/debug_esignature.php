<?php
require_once '../db_connect.php';

echo "<h2>E-Signature Debug Test</h2>";

// Check current user
$user_id = $_SESSION['user_id'] ?? 1; // Use user ID 1 if not logged in
echo "<p>Testing with User ID: " . $user_id . "</p>";

// Check if esignature column exists and has data
$stmt = $conn->prepare("SELECT id, firstname, lastname, esignature FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<h3>Current User Data:</h3>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
    echo "<li><strong>Name:</strong> " . $user['firstname'] . " " . $user['lastname'] . "</li>";
    echo "<li><strong>E-Signature:</strong> " . ($user['esignature'] ?: 'NULL (no signature)') . "</li>";
    echo "</ul>";
    
    if ($user['esignature']) {
        $file_path = 'uploads/esignatures/' . $user['esignature'];
        echo "<h3>File Check:</h3>";
        echo "<p><strong>Expected Path:</strong> " . $file_path . "</p>";
        
        if (file_exists($file_path)) {
            echo "<p style='color: green;'>✅ File exists!</p>";
            echo "<p><strong>File Size:</strong> " . filesize($file_path) . " bytes</p>";
            echo "<p><strong>Last Modified:</strong> " . date('Y-m-d H:i:s', filemtime($file_path)) . "</p>";
            echo "<img src='" . $file_path . "' style='max-width: 200px; border: 1px solid #ccc; padding: 5px;' alt='E-Signature'>";
        } else {
            echo "<p style='color: red;'>❌ File does not exist at expected path!</p>";
            echo "<p><strong>Upload Directory:</strong> uploads/esignatures/</p>";
            
            // Check if directory exists
            if (is_dir('uploads/esignatures/')) {
                echo "<p style='color: green;'>✅ Directory exists</p>";
                // List files in directory
                $files = glob('uploads/esignatures/*');
                if ($files) {
                    echo "<p>Files in directory:</p><ul>";
                    foreach ($files as $file) {
                        echo "<li>" . basename($file) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No files in directory</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Directory does not exist!</p>";
            }
        }
    } else {
        echo "<h3>Upload Test:</h3>";
        echo "<p>No signature found. Let's test the upload functionality...</p>";
        echo "<p><a href='profile.php'>Go to Profile Page to Upload</a></p>";
    }
} else {
    echo "<p style='color: red;'>❌ User not found in database!</p>";
}

$stmt->close();
$conn->close();
?>

<h3>Manual Upload Test Form</h3>
<form action="profile.php" method="POST" enctype="multipart/form-data" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <h4>Test E-Signature Upload</h4>
    <input type="file" name="esignature" accept="image/*" required><br><br>
    <input type="hidden" name="firstname" value="Test">
    <input type="hidden" name="lastname" value="User">
    <input type="hidden" name="email" value="test@example.com">
    <button type="submit" style="background: #1e3c72; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Upload Test Signature</button>
</form>
