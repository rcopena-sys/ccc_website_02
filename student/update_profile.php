<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$response = ['success' => false, 'message' => ''];
$student_id = $_SESSION['student_id'];

try {
    // Check if this is just a profile picture update
    $isPictureUpdate = isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK;
    
    if ($isPictureUpdate) {
        // Skip email validation for picture-only updates
        $firstname = '';
        $lastname = '';
        $email = '';
    } else {
        // Get form data for regular profile updates
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate required fields for profile updates
        if (empty($firstname) || empty($lastname) || empty($email)) {
            throw new Exception('All fields are required for profile updates');
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email is already in use by another account
        $checkEmail = $conn->prepare("SELECT student_id FROM signin_db WHERE email = ? AND student_id != ?");
        $checkEmail->bind_param("ss", $email, $student_id);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('This email is already in use by another account');
        }
        $checkEmail->close();
    }
    
    // Handle file upload
    $profile_image_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Only JPG, PNG, and GIF files are allowed');
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            throw new Exception('File size must be less than 2MB');
        }
        
        // Create uploads directory if it doesn't exist (relative to the root)
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/website/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . $student_id . '_' . time() . '.' . $file_extension;
        $destination = $upload_dir . $filename;
        $web_path = '/website/uploads/profiles/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $profile_image_path = $web_path;
            // Update session with new profile image path
            $_SESSION['profile_image'] = $web_path;
        } else {
            throw new Exception('Failed to upload profile image');
        }
    }
    
    // Update database
    $conn->begin_transaction();
    
    try {
        // Update user information
        // Build the query based on which fields are being updated
        $updates = [];
        $types = '';
        $params = [];
        
        // Always update these fields if they're not empty
        if (!empty($firstname)) {
            $updates[] = 'firstname = ?';
            $types .= 's';
            $params[] = &$firstname;
        }
        
        if (!empty($lastname)) {
            $updates[] = 'lastname = ?';
            $types .= 's';
            $params[] = &$lastname;
        }
        
        if (!empty($email)) {
            $updates[] = 'email = ?';
            $types .= 's';
            $params[] = &$email;
        }
        
        // Handle profile image update
        if ($profile_image_path) {
            $updates[] = 'profile_image = ?';
            $types .= 's';
            $params[] = &$profile_image_path;
        }
        
        // If we have fields to update
        if (!empty($updates)) {
            $query = "UPDATE signin_db SET " . implode(', ', $updates) . " WHERE student_id = ?";
            $types .= 's';
            $params[] = &$student_id;
            
            $stmt = $conn->prepare($query);
            
            // Create an array of references for bind_param
            $bindParams = [&$types];
            foreach ($params as &$param) {
                $bindParams[] = &$param;
            }
            
            // Bind parameters dynamically
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update profile');
            }
            
            // Update session variables if needed
            if (!empty($firstname)) $_SESSION['firstname'] = $firstname;
            if (!empty($lastname)) $_SESSION['lastname'] = $lastname;
            if (!empty($email)) $_SESSION['email'] = $email;
            
            // If we updated the profile image, update the session and ensure the path is consistent
            if ($profile_image_path) {
                // Ensure the path is stored consistently (with leading slash)
                $web_path = strpos($web_path, '/') === 0 ? $web_path : '/' . ltrim($web_path, '/');
                $_SESSION['profile_image'] = $web_path;
                $response['profile_image'] = $web_path;
                
                // Also update the database with the correct path
                $updateImgStmt = $conn->prepare("UPDATE signin_db SET profile_image = ? WHERE student_id = ?");
                $updateImgStmt->bind_param("ss", $web_path, $student_id);
                $updateImgStmt->execute();
                $updateImgStmt->close();
            }
            
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
    $conn->commit();
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
