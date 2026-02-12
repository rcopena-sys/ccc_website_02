<?php
session_start();
require_once '../db_connect.php';

echo "<h2>🔍 Debug Upload Attempt</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ User not logged in. Please <a href='../login.php'>login</a> first.</p>";
    echo "<p>Current session data: " . print_r($_SESSION, true) . "</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ Logged in User ID: $user_id</p>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>📤 Form Submitted</h3>";
    echo "<p>POST data: " . print_r($_POST, true) . "</p>";
    echo "<p>FILES data: " . print_r($_FILES, true) . "</p>";
    
    // Check if esignature file was uploaded
    if (isset($_FILES['esignature'])) {
        echo "<p>✅ E-signature field found in FILES array</p>";
        
        if ($_FILES['esignature']['error'] === UPLOAD_ERR_OK) {
            echo "<p>✅ No upload error detected</p>";
            
            // Check file info
            $file_info = $_FILES['esignature'];
            echo "<p>Original name: " . $file_info['name'] . "</p>";
            echo "<p>File type: " . $file_info['type'] . "</p>";
            echo "<p>File size: " . $file_info['size'] . " bytes</p>";
            echo "<p>Temp file: " . $file_info['tmp_name'] . "</p>";
            
            // Check if temp file exists
            if (file_exists($file_info['tmp_name'])) {
                echo "<p>✅ Temp file exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Temp file does NOT exist!</p>";
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (in_array($file_info['type'], $allowed_types)) {
                echo "<p>✅ File type is allowed</p>";
            } else {
                echo "<p style='color: red;'>❌ File type not allowed: " . $file_info['type'] . "</p>";
            }
            
            // Validate file size
            $max_size = 2 * 1024 * 1024; // 2MB
            if ($file_info['size'] <= $max_size) {
                echo "<p>✅ File size is within limit</p>";
            } else {
                echo "<p style='color: red;'>❌ File too large: " . $file_info['size'] . " bytes</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Upload error code: " . $_FILES['esignature']['error'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No esignature field in FILES array</p>";
    }
} else {
    echo "<h3>📋 Waiting for Form Submission</h3>";
    echo "<p>Request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    echo "<p>Please submit the form to see debug information.</p>";
    
    // Show upload form
    ?>
    <form method="POST" enctype="multipart/form-data">
        <div>
            <label for="esignature">Select signature file:</label><br>
            <input type="file" name="esignature" id="esignature" accept="image/*" required><br><br>
            <input type="text" name="firstname" placeholder="First name" required><br><br>
            <input type="text" name="lastname" placeholder="Last name" required><br><br>
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="submit" value="Test Upload" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px;">
        </div>
    </form>
    <?php
}

echo "<p><a href='profile.php'>← Back to Profile</a></p>";
?>
