<?php
session_start();
require_once '../config/connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$upload_dir = '../adminpage/uploads/esignatures/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if file was uploaded without errors
if (isset($_FILES["esignature"]) && $_FILES["esignature"]["error"] == 0) {
    $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
    $filename = $_FILES["esignature"]["name"];
    $filetype = $_FILES["esignature"]["type"];
    $filesize = $_FILES["esignature"]["size"];
    $maxsize = 2 * 1024 * 1024; // 2MB max file size

    // Verify file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed)) {
        die("Error: Please select a valid file format (JPG, JPEG, PNG).");
    }

    // Verify file size
    if ($filesize > $maxsize) {
        die("Error: File size is larger than the 2MB limit.");
    }

    // Verify MIME type of the file
    if (in_array($filetype, $allowed)) {
        // Generate new filename
        $new_filename = 'esign_' . $student_id . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;

        // Move the uploaded file to its new location
        if (move_uploaded_file($_FILES["esignature"]["tmp_name"], $upload_path)) {
            // Update database
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check if record exists
            $check = $conn->prepare("SELECT id FROM signin_db WHERE student_id = ?");
            $check->bind_param("s", $student_id);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE signin_db SET esignature = ? WHERE student_id = ?");
                $stmt->bind_param("ss", $new_filename, $student_id);
            } else {
                // Insert new record (if needed)
                $stmt = $conn->prepare("INSERT INTO signin_db (esignature, student_id) VALUES (?, ?)");
                $stmt->bind_param("ss", $new_filename, $student_id);
            }
            
            if ($stmt->execute()) {
                // Redirect back with success message
                header("Location: dcipros1st.php?success=1");
            } else {
                echo "Error: " . $conn->error;
            }
            
            $stmt->close();
            $conn->close();
        } else {
            echo "Error: There was a problem uploading your file. Please try again.";
        }
    } else {
        echo "Error: There was a problem with your file. Please try again.";
    }
} else {
    echo "Error: " . $_FILES["esignature"]["error"];
}
?>
