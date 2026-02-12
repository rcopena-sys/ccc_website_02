<?php
session_start();
require_once '../db_connect.php';

echo "<h2>🧪 Test Form Submission</h2>";

// Check session
echo "<h3>🔍 Session Check</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    
    // Try to find a user to simulate login
    /** @var mysqli_result $user_check */
    $user_check = $conn->query("SELECT id, firstname, lastname FROM signin_db LIMIT 1");
    if ($user_check && $user_check->num_rows > 0) {
        $user = $user_check->fetch_assoc();
        echo "<p>Found user: " . $user['firstname'] . " " . $user['lastname'] . " (ID: " . $user['id'] . ")</p>";
        echo "<p>Simulating login...</p>";
        $_SESSION['user_id'] = $user['id'];
        echo "<p style='color: green;'>✅ Simulated login successful</p>";
    } else {
        echo "<p style='color: red;'>❌ No users found in database</p>";
        exit();
    }
}

$user_id = $_SESSION['user_id'];
echo "<p>Current User ID: $user_id</p>";

// Check form submission
echo "<h3>📤 Form Submission Check</h3>";
echo "<p>Request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: green;'>✅ Form was submitted!</p>";
    echo "<p>POST data: " . print_r($_POST, true) . "</p>";
    echo "<p>FILES data: " . print_r($_FILES, true) . "</p>";
    
    // Check if esignature was uploaded
    if (isset($_FILES['esignature'])) {
        echo "<p>✅ E-signature field found</p>";
        if ($_FILES['esignature']['error'] === UPLOAD_ERR_OK) {
            echo "<p>✅ File uploaded successfully</p>";
            
            // Try to save the file
            $upload_dir = 'uploads/esignatures/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['esignature']['name'], PATHINFO_EXTENSION);
            $filename = 'test_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['esignature']['tmp_name'], $upload_path)) {
                echo "<p style='color: green;'>✅ File saved to: $upload_path</p>";
                
                // Update database
                /** @var mysqli_stmt $stmt */
                $stmt = $conn->prepare("UPDATE signin_db SET esignature = ? WHERE id = ?");
                $stmt->bind_param("si", $filename, $user_id);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Database updated successfully!</p>";
                    echo "<p>Signature filename: $filename</p>";
                } else {
                    echo "<p style='color: red;'>❌ Database update failed: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p style='color: red;'>❌ Failed to save file</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Upload error: " . $_FILES['esignature']['error'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No esignature field in FILES</p>";
    }
} else {
    echo "<p>Form not submitted yet. Use the form below to test.</p>";
    
    // Show test form
    ?>
    <form method="POST" enctype="multipart/form-data">
        <div style="padding: 20px; border: 1px solid #ccc; border-radius: 5px; margin: 20px 0;">
            <h4>Test Upload Form</h4>
            <div style="margin: 10px 0;">
                <label for="esignature">Select signature file:</label><br>
                <input type="file" name="esignature" id="esignature" accept="image/*" required>
            </div>
            <div style="margin: 10px 0;">
                <label for="firstname">First name:</label><br>
                <input type="text" name="firstname" id="firstname" required>
            </div>
            <div style="margin: 10px 0;">
                <label for="lastname">Last name:</label><br>
                <input type="text" name="lastname" id="lastname" required>
            </div>
            <div style="margin: 10px 0;">
                <label for="email">Email:</label><br>
                <input type="email" name="email" id="email" required>
            </div>
            <div style="margin: 20px 0;">
                <input type="submit" value="Test Upload" style="padding: 10px 20px; background: #1e3c72; color: white; border: none; border-radius: 5px;">
            </div>
        </div>
    </form>
    <?php
}

echo "<p><a href='profile.php'>← Try Profile Page</a></p>";
?>
