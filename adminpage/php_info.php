<?php
echo "<h2>PHP Info for File Uploads</h2>";

// Check PHP file upload configuration
echo "<h3>File Upload Configuration</h3>";
echo "<p>file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "</p>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";
echo "<p>upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'System default') . "</p>";

// Check error reporting
echo "<h3>Error Reporting</h3>";
echo "<p>display_errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p>error_reporting: " . ini_get('error_reporting') . "</p>";

// Test session
echo "<h3>Session Status</h3>";
echo "<p>Session status: " . session_status() . "</p>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";
}

// Show current directory permissions
echo "<h3>Directory Permissions</h3>";
$upload_dir = 'uploads/esignatures/';
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Upload directory: " . __DIR__ . '/' . $upload_dir . "</p>";
if (file_exists($upload_dir)) {
    echo "<p>Upload directory exists</p>";
    echo "<p>Is writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
} else {
    echo "<p>Upload directory does not exist</p>";
}

// Test basic file upload without database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Basic Upload Test</h3>";
    echo "<p>FILES array: " . print_r($_FILES, true) . "</p>";
    
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✅ Basic upload successful</p>";
        echo "<p>Temp file: " . $_FILES['test_file']['tmp_name'] . "</p>";
        echo "<p>File exists in temp: " . (file_exists($_FILES['test_file']['tmp_name']) ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Upload error: " . $_FILES['test_file']['error'] . "</p>";
    }
}
?>

<h3>Basic Upload Test Form</h3>
<form method="POST" enctype="multipart/form-data">
    <div>
        <label for="test_file">Select any file:</label><br>
        <input type="file" name="test_file" id="test_file" required><br><br>
        <input type="submit" value="Test Basic Upload" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px;">
    </div>
</form>

<p><a href="profile.php">← Back to Profile</a></p>
