<?php
session_start();
require_once '../db_connect.php';

echo "<h2>🔍 Debug E-Signature Upload Process</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ User not logged in. Please <a href='../login.php'>login</a> first.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ Logged in User ID: $user_id</p>";

// 1. Check database connection and esignature column
echo "<h3>1. Database Check</h3>";
$columnCheck = $conn->query("SHOW COLUMNS FROM signin_db LIKE 'esignature'");
if ($columnCheck && $columnCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ esignature column exists</p>";
} else {
    echo "<p style='color: red;'>❌ esignature column missing</p>";
    exit();
}

// 2. Check current user data
echo "<h3>2. Current User Data</h3>";
/** @var mysqli_stmt|false $stmt */
$stmt = $conn->prepare("SELECT id, firstname, lastname, esignature FROM signin_db WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    /** @var mysqli_result|false $result */
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>User: " . $user['firstname'] . " " . $user['lastname'] . "</p>";
        echo "<p>Current signature: " . ($user['esignature'] ?: 'NULL') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ User not found in database</p>";
        exit();
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>❌ Database query failed</p>";
    exit();
}

// 3. Check uploads directory
echo "<h3>3. Upload Directory Check</h3>";
$upload_dir = 'uploads/esignatures/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created upload directory: $upload_dir</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create upload directory</p>";
        exit();
    }
} else {
    echo "<p style='color: green;'>✅ Upload directory exists: $upload_dir</p>";
}

// 4. Check if directory is writable
if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✅ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
}

// 5. Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>4. Upload Process</h3>";
    
    // Debug all POST and FILES data
    echo "<p><strong>POST data:</strong> " . print_r($_POST, true) . "</p>";
    echo "<p><strong>FILES data:</strong> " . print_r($_FILES, true) . "</p>";
    
    if (isset($_FILES['esignature']) && $_FILES['esignature']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✅ File uploaded successfully</p>";
        
        $file_info = $_FILES['esignature'];
        echo "<p>Original name: " . $file_info['name'] . "</p>";
        echo "<p>Type: " . $file_info['type'] . "</p>";
        echo "<p>Size: " . $file_info['size'] . " bytes</p>";
        echo "<p>Temp file: " . $file_info['tmp_name'] . "</p>";
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($file_info['type'], $allowed_types)) {
            echo "<p style='color: green;'>✅ File type valid</p>";
        } else {
            echo "<p style='color: red;'>❌ Invalid file type: " . $file_info['type'] . "</p>";
        }
        
        if ($file_info['size'] <= $max_size) {
            echo "<p style='color: green;'>✅ File size valid</p>";
        } else {
            echo "<p style='color: red;'>❌ File too large: " . $file_info['size'] . " bytes</p>";
        }
        
        // Generate filename and move file
        $file_extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $esignature_filename = 'esign_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $esignature_filename;
        
        echo "<p>Generated filename: $esignature_filename</p>";
        echo "<p>Upload path: $upload_path</p>";
        
        if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
            echo "<p style='color: green;'>✅ File moved to upload directory</p>";
            
            // Update database
            $update_sql = "UPDATE signin_db SET esignature = ? WHERE id = ?";
            /** @var mysqli_stmt|false $stmt */
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param("si", $esignature_filename, $user_id);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Database updated successfully!</p>";
                    echo "<p><img src='$upload_path' style='max-height: 100px; border: 1px solid #ddd;' alt='Uploaded Signature'></p>";
                } else {
                    echo "<p style='color: red;'>❌ Database update failed: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p style='color: red;'>❌ Failed to prepare update statement</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to move uploaded file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Upload error: " . ($_FILES['esignature']['error'] ?? 'No file uploaded') . "</p>";
    }
}
?>

<h3>📤 Test Upload Form</h3>
<form method="POST" enctype="multipart/form-data">
    <div>
        <label for="esignature">Select PNG file:</label><br>
        <input type="file" name="esignature" id="esignature" accept="image/png,image/jpeg,image/jpg,image/gif" required><br><br>
        <input type="submit" value="Test Upload" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px;">
    </div>
</form>

<p><a href="profile.php">← Back to Profile</a></p>
<p><a href="test_esignature_upload.php">← Simple Test</a></p>
