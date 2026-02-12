<?php
session_start();
require_once '../db_connect.php';

echo "<h2>🧪 Test E-Signature Upload</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ User not logged in. Please <a href='../login.php'>login</a> first.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>✅ User ID: $user_id</p>";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['esignature'])) {
    echo "<h3>📤 Processing Upload</h3>";
    
    // Debug information
    echo "<p>FILES array: " . print_r($_FILES, true) . "</p>";
    
    if ($_FILES['esignature']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['esignature']['type'];
        $file_size = $_FILES['esignature']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        echo "<p>File type: $file_type</p>";
        echo "<p>File size: $file_size bytes</p>";
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            // Create e-signatures directory if it doesn't exist
            $upload_dir = 'uploads/esignatures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                echo "<p>✅ Created upload directory: $upload_dir</p>";
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['esignature']['name'], PATHINFO_EXTENSION);
            $esignature_filename = 'esign_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $esignature_filename;
            
            echo "<p>Generated filename: $esignature_filename</p>";
            echo "<p>Upload path: $upload_path</p>";
            
            // Upload new signature
            if (move_uploaded_file($_FILES['esignature']['tmp_name'], $upload_path)) {
                echo "<p style='color: green;'>✅ File uploaded successfully!</p>";
                
                // Update database
                $update_sql = "UPDATE signin_db SET esignature = ? WHERE id = ?";
                if ($conn instanceof mysqli) {
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
                        $error_msg = "Failed to prepare statement";
                        if ($conn instanceof mysqli) {
                            $error_msg .= ": " . mysqli_error($conn);
                        } elseif ($conn instanceof PDO) {
                            $error_msg .= ": " . $conn->errorInfo()[2];
                        }
                        echo "<p style='color: red;'>❌ $error_msg</p>";
                    }
                } elseif ($conn instanceof PDO) {
                    $stmt = $conn->prepare($update_sql);
                    if ($stmt) {
                        if ($stmt->execute([$esignature_filename, $user_id])) {
                            echo "<p style='color: green;'>✅ Database updated successfully!</p>";
                            echo "<p><img src='$upload_path' style='max-height: 100px; border: 1px solid #ddd;' alt='Uploaded Signature'></p>";
                        } else {
                            echo "<p style='color: red;'>❌ Database update failed: " . implode(', ', $stmt->errorInfo()) . "</p>";
                        }
                    } else {
                        $error_msg = "Failed to prepare statement";
                        $error_msg .= ": " . implode(', ', $conn->errorInfo());
                        echo "<p style='color: red;'>❌ $error_msg</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>❌ Failed to upload file</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid file type or size</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Upload error code: " . $_FILES['esignature']['error'] . "</p>";
    }
}

// Check current signature
echo "<h3>📋 Current Status</h3>";
if ($conn instanceof mysqli) {
    /** @var mysqli_stmt|false $stmt */
    $stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        /** @var mysqli_result|false $result */
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (!empty($user['esignature'])) {
                echo "<p>Current signature: " . $user['esignature'] . "</p>";
                $signature_file = 'uploads/esignatures/' . $user['esignature'];
                if (file_exists($signature_file)) {
                    echo "<p style='color: green;'>✅ Signature file exists</p>";
                    echo "<img src='$signature_file' style='max-height: 100px; border: 1px solid #ddd;' alt='Current Signature'>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Signature file not found</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No signature in database</p>";
            }
        }
        $stmt->close();
    }
} elseif ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
    if ($stmt) {
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['esignature'])) {
            echo "<p>Current signature: " . $user['esignature'] . "</p>";
            $signature_file = 'uploads/esignatures/' . $user['esignature'];
            if (file_exists($signature_file)) {
                echo "<p style='color: green;'>✅ Signature file exists</p>";
                echo "<img src='$signature_file' style='max-height: 100px; border: 1px solid #ddd;' alt='Current Signature'>";
            } else {
                echo "<p style='color: orange;'>⚠️ Signature file not found</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No signature in database</p>";
        }
    }
}
?>

<h3>📤 Upload Test Form</h3>
<form method="POST" enctype="multipart/form-data">
    <div>
        <label for="esignature">Select PNG/JPG file:</label><br>
        <input type="file" name="esignature" id="esignature" accept="image/*" required><br><br>
        <input type="submit" value="Upload Signature" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px;">
    </div>
</form>

<p><a href="profile.php">← Back to Profile</a></p>
