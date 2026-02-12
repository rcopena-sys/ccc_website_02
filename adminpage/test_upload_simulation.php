<?php
require_once '../db_connect.php';

echo "<h2>🧪 Simulate E-Signature Upload Test</h2>";

// Simulate a logged-in user (use a real user ID from the database)
$test_user_id = 1; // Start with ID 1, but we'll check if it exists

echo "<h3>1. Find a valid user</h3>";
$find_user = $conn->query("SELECT id, firstname, lastname, student_id FROM signin_db LIMIT 1");
if ($find_user && $find_user->num_rows > 0) {
    $user = $find_user->fetch_assoc();
    $test_user_id = $user['id'];
    echo "<p>✅ Found test user: " . $user['firstname'] . " " . $user['lastname'] . " (ID: " . $user['id'] . ")</p>";
} else {
    echo "<p style='color: red;'>❌ No users found in database</p>";
    exit();
}

echo "<h3>2. Current esignature status</h3>";
$stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        echo "<p>Current signature: " . ($user_data['esignature'] ?: 'NULL') . "</p>";
    }
    $stmt->close();
}

echo "<h3>3. Test database update</h3>";
$test_filename = 'test_signature_' . time() . '.jpg';
$update_sql = "UPDATE signin_db SET esignature = ? WHERE id = ?";

if ($conn instanceof mysqli) {
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        $stmt->bind_param("si", $test_filename, $test_user_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Database update successful!</p>";
            echo "<p>Updated filename: $test_filename</p>";
            echo "<p>Affected rows: " . $stmt->affected_rows . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Database update failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Failed to prepare statement: " . $conn->error . "</p>";
    }
} elseif ($conn instanceof PDO) {
    $stmt = $conn->prepare($update_sql);
    if ($stmt) {
        if ($stmt->execute([$test_filename, $test_user_id])) {
            echo "<p style='color: green;'>✅ Database update successful!</p>";
            echo "<p>Updated filename: $test_filename</p>";
            echo "<p>Affected rows: " . $stmt->rowCount() . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Database update failed: " . implode(', ', $stmt->errorInfo()) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to prepare statement: " . implode(', ', $conn->errorInfo()) . "</p>";
    }
}

echo "<h3>4. Verify update</h3>";
$stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        echo "<p>Updated signature: " . ($user_data['esignature'] ?: 'NULL') . "</p>";
        if ($user_data['esignature'] === $test_filename) {
            echo "<p style='color: green;'>✅ Update verified successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Update verification failed!</p>";
        }
    }
    $stmt->close();
}

echo "<h3>5. Test cleanup (set back to NULL)</h3>";
$null_value = null;
$update_null_sql = "UPDATE signin_db SET esignature = ? WHERE id = ?";
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare($update_null_sql);
    if ($stmt) {
        $stmt->bind_param("si", $null_value, $test_user_id);
        $stmt->execute();
        $stmt->close();
        echo "<p style='color: green;'>✅ Cleanup completed</p>";
    }
} elseif ($conn instanceof PDO) {
    $stmt = $conn->prepare($update_null_sql);
    if ($stmt) {
        $stmt->execute([$null_value, $test_user_id]);
        echo "<p style='color: green;'>✅ Cleanup completed</p>";
    }
}

echo "<p><a href='test_esignature_upload.php'>← Test Upload</a></p>";
echo "<p><a href='profile.php'>← Back to Profile</a></p>";
?>
