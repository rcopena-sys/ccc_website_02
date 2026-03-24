<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

// Get and validate inputs
$email = trim($_POST['email'] ?? '');
$student_number = trim($_POST['studentnumber'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email) || empty($student_number) || empty($password)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: index.php");
    exit();
}

try {
    // Prepare SQL to get user data including course and profile image
    $sql = "SELECT id, firstname, lastname, email, student_number, password, course, profile_image 
            FROM signin_db 
            WHERE email = ? AND student_number = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $email, $student_number);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows != 1) {
        throw new Exception("Invalid email, student number, or password.");
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception("Invalid email, student number, or password.");
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['student_id'] = $user['student_number'];
    $_SESSION['firstname'] = $user['firstname'];
    $_SESSION['lastname'] = $user['lastname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['course'] = $user['course'];
    $_SESSION['profile_image'] = !empty($user['profile_image']) ? $user['profile_image'] : '/website/default_profile.jpg';
    $_SESSION['loggedin'] = true;
    
    // Update last login
    $update_sql = "UPDATE signin_db SET last_login = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt) {
        $update_stmt->bind_param("i", $user['id']);
        $update_stmt->execute();
    }
    
    // Redirect based on course
    $course = strtoupper(trim($user['course']));
    if ($course === 'BSCS') {
        header("Location: cs_studash.php");
    } else if ($course === 'BSIT') {
        header("Location: dci_page.php");
    } else {
        // Default redirect if course not recognized
        header("Location: index.php");
    }
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: index.php");
    exit();
} finally {
    // Close statements if they were opened
    if (isset($stmt)) $stmt->close();
    if (isset($update_stmt)) $update_stmt->close();
}
