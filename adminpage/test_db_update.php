<?php
require_once '../db_connect.php';

echo "<h2>🧪 Test Database Update</h2>";

// Get a test user
/** @var mysqli_result $result */
$result = $conn->query("SELECT id FROM signin_db LIMIT 1");
$test_user = $result->fetch_assoc();
if (!$test_user) {
    echo "<p style='color: red;'>❌ No users found in database</p>";
    exit();
}

$user_id = $test_user['id'];
echo "<p>Testing with User ID: $user_id</p>";

// Test 1: Check current esignature
/** @var mysqli_stmt $stmt */
$stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
/** @var mysqli_result $result */
$result = $stmt->get_result();
$current = $result->fetch_assoc();
echo "<p>Current esignature: " . ($current['esignature'] ?: 'NULL') . "</p>";
$stmt->close();

// Test 2: Update with a test filename
$test_filename = 'test_' . time() . '.png';
echo "<p>Attempting to update with: $test_filename</p>";

$update_sql = "UPDATE signin_db SET esignature = ? WHERE id = ?";
/** @var mysqli_stmt $stmt */
$stmt = $conn->prepare($update_sql);
if ($stmt) {
    $stmt->bind_param("si", $test_filename, $user_id);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Update successful! Affected rows: " . $stmt->affected_rows . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Update failed: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>❌ Prepare failed: " . $conn->error . "</p>";
}

// Test 3: Verify update
/** @var mysqli_stmt $stmt */
$stmt = $conn->prepare("SELECT esignature FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
/** @var mysqli_result $result */
$result = $stmt->get_result();
$updated = $result->fetch_assoc();
echo "<p>Updated esignature: " . ($updated['esignature'] ?: 'NULL') . "</p>";
$stmt->close();

// Test 4: Set back to NULL
$null_value = null;
/** @var mysqli_stmt $stmt */
$stmt = $conn->prepare("UPDATE signin_db SET esignature = ? WHERE id = ?");
$stmt->bind_param("si", $null_value, $user_id);
$stmt->execute();
$stmt->close();
echo "<p style='color: orange;'>⚠️ Reset to NULL for cleanup</p>";

echo "<p><a href='profile.php'>← Back to Profile</a></p>";
?>
