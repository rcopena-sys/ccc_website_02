<?php
echo "<h2>🔧 Test PHP Upload Settings</h2>";

// Check PHP upload configuration
echo "<h3>📊 PHP Upload Configuration</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th><th>Recommended</th></tr>";

$uploadSettings = [
    'file_uploads' => ['On', 'On'],
    'upload_max_filesize' => ['2M', '2M+'],
    'post_max_size' => ['8M', '8M+'],
    'max_execution_time' => ['30', '30+'],
    'memory_limit' => ['128M', '128M+']
];

foreach ($uploadSettings as $setting => [$expected, $recommended]) {
    $current = ini_get($setting);
    $status = ($current === $expected || str_replace('M', '', $current) >= str_replace('M', '', $expected)) ? '✅' : '❌';
    echo "<tr><td>" . $setting . "</td><td>" . $current . "</td><td>" . $recommended . "</td><td>" . $status . "</td></tr>";
}
echo "</table>";

// Test file upload
echo "<h3>🧪 File Upload Test</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h4>Upload Results:</h4>";
    echo "<pre>";
    echo "FILES array:\n";
    print_r($_FILES);
    echo "\nPOST array:\n";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'>✅ File uploaded successfully!</p>";
        echo "<p>Original name: " . $_FILES['test_file']['name'] . "</p>";
        echo "<p>Size: " . $_FILES['test_file']['size'] . " bytes</p>";
        echo "<p>Type: " . $_FILES['test_file']['type'] . "</p>";
        echo "<p>Temporary location: " . $_FILES['test_file']['tmp_name'] . "</p>";
        
        // Test moving file
        $testDir = 'uploads/test/';
        if (!file_exists($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $testFile = $testDir . 'test_' . time() . '_' . $_FILES['test_file']['name'];
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $testFile)) {
            echo "<p style='color: green;'>✅ File moved successfully to: " . $testFile . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to move file</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ File upload failed</p>";
        if (isset($_FILES['test_file'])) {
            echo "<p>Error code: " . $_FILES['test_file']['error'] . "</p>";
        }
    }
} else {
    ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="test_file" class="form-label">Test File Upload</label>
            <input type="file" class="form-control" id="test_file" name="test_file" accept="image/*">
            <small class="text-muted">Choose any image file to test upload functionality</small>
        </div>
        <button type="submit" class="btn btn-primary">Test Upload</button>
    </form>
    <?php
}

// Check upload directory permissions
echo "<h3>📁 Directory Permissions</h3>";
$directories = [
    'uploads/' => 'Main uploads directory',
    'uploads/esignatures/' => 'E-signatures directory',
    'uploads/evaluation_signatures/' => 'Evaluation signatures directory'
];

foreach ($directories as $dir => $desc) {
    echo "<p><strong>" . $desc . ":</strong> ";
    if (file_exists($dir)) {
        echo "✅ Exists";
        if (is_writable($dir)) {
            echo " ✅ Writable";
        } else {
            echo " ❌ Not writable";
        }
    } else {
        echo "❌ Missing";
        if (mkdir($dir, 0755, true)) {
            echo " ✅ Created";
        } else {
            echo " ❌ Failed to create";
        }
    }
    echo "</p>";
}

// Check session
echo "<h3>👤 Session Info</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
?>

<p><a href="profile.php">← Back to Profile</a></p>
