<?php
session_start();
require_once '../db_connect.php';

echo "<h2>🧪 Test Complete Signature Upload Flow</h2>";

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$user_id = $_SESSION['user_id'];
echo "<p>✅ Simulated logged-in user with ID: $user_id</p>";

// Get current user data
/** @var mysqli_stmt $stmt */
$stmt = $conn->prepare("SELECT * FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
/** @var mysqli_result $result */
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

echo "<h3>📋 Current User Data</h3>";
echo "<p>Name: " . $user['firstname'] . " " . $user['lastname'] . "</p>";
echo "<p>Email: " . $user['email'] . "</p>";
echo "<p>Current signature: " . ($user['esignature'] ?: 'NULL') . "</p>";

// Simulate form submission
echo "<h3>📝 Simulating Form Submission</h3>";

// Create a test file to simulate upload
$test_file_content = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A8A');

// Create temp file
$temp_file = tempnam(sys_get_temp_dir(), 'test_sig');
file_put_contents($temp_file, $test_file_content);

// Simulate $_FILES array
$_FILES['esignature'] = [
    'name' => 'test_signature.png',
    'type' => 'image/png',
    'size' => strlen($test_file_content),
    'tmp_name' => $temp_file,
    'error' => UPLOAD_ERR_OK
];

// Simulate $_POST data
$_POST = [
    'firstname' => $user['firstname'],
    'lastname' => $user['lastname'],
    'email' => $user['email'],
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => ''
];

echo "<p>✅ Simulated file upload: test_signature.png</p>";
echo "<p>✅ Simulated form data</p>";

// Now run the actual profile.php upload logic
include 'profile_upload_logic.php';

// Clean up
unlink($temp_file);

echo "<p><a href='profile.php'>← Test Real Upload</a></p>";
?>
