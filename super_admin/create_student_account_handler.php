<?php
// Always return JSON
header('Content-Type: application/json');

// Enable logging (safe for live server)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

// Start output buffering
ob_start();

// Database Connection (centralized, environment-aware)
require_once __DIR__ . '/../db_connect.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

/* ============================
   HELPER FUNCTIONS
============================ */

function generateSecurePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';

    $password .= strtolower($chars[rand(0, 25)]);
    $password .= strtoupper($chars[rand(26, 51)]);
    $password .= $chars[rand(52, 61)];
    $password .= $chars[rand(62, 71)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $chars[rand(0, 71)];
    }

    return str_shuffle($password);
}

function generateUsername($email) {
    $username = explode('@', $email)[0];
    $username = strtolower($username);
    $username = preg_replace('/[^a-z0-9._-]/', '', $username);
    return !empty($username) ? $username : ('user' . time());
}

function sendCredentialsEmail($studentName, $email, $password, $role) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rozzo4968@gmail.com';
        $mail->Password   = 'elduyrelyltgjhdr'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom('rozzo4968@gmail.com', 'Student Account System');
        $mail->addAddress($email, $studentName);

        $mail->isHTML(true);
        $mail->Subject = 'Your Student Account Credentials';

        $mail->Body = "
            <h2>Welcome to the Student Portal</h2>
            <p><strong>Name:</strong> {$studentName}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Password:</strong> {$password}</p>
            <p><strong>Role:</strong> {$role}</p>
            <p>Please change your password after first login.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

/* ============================
   MAIN PROCESS
============================ */

$response = ["success" => false, "message" => ""];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id   = $_POST['student_id'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $email        = $_POST['email'] ?? '';
    $role         = $_POST['role'] ?? '';

    if (!$student_id || !$student_name || !$email || !$role) {
        $response['message'] = "All required fields must be filled.";
        echo json_encode($response);
        exit;
    }

    $username = generateUsername($email);
    $password = generateSecurePassword();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Split name
    $names = preg_split('/\s+/', trim($student_name));
    $firstname = $names[0] ?? '';
    $lastname  = count($names) > 1 ? implode(' ', array_slice($names, 1)) : '';

    // Role mapping
    $role_id = 5;
    if (stripos($role, 'BSIT') !== false) {
        $role_id = 4;
    }

    // Get classification
    $classification = null;
    $cls_stmt = $conn->prepare("SELECT classification FROM students_db WHERE student_id = ? OR email = ? LIMIT 1");
    if ($cls_stmt) {
        $cls_stmt->bind_param("ss", $student_id, $email);
        $cls_stmt->execute();
        $result = $cls_stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $classification = $result->fetch_assoc()['classification'];
        }
        $cls_stmt->close();
    }

    // Check duplicate
    $check = $conn->prepare("SELECT id FROM signin_db WHERE student_id = ? OR email = ?");
    $check->bind_param("ss", $student_id, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $response['message'] = "Student already has an account.";
        echo json_encode($response);
        exit;
    }
    $check->close();

    // Insert
    $stmt = $conn->prepare("INSERT INTO signin_db 
        (student_id, firstname, lastname, email, password, course, classification, role_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if (!$stmt) {
        $response['message'] = "Prepare failed: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param(
        "sssssssi",
        $student_id,
        $firstname,
        $lastname,
        $email,
        $hashed_password,
        $role,
        $classification,
        $role_id
    );

    if ($stmt->execute()) {

        $emailSent = sendCredentialsEmail($student_name, $email, $password, $role);

        $response['success'] = true;
        $response['message'] = $emailSent
            ? "Account created and email sent."
            : "Account created but email failed.";

        $response['username'] = $username;
        $response['password'] = $password;

    } else {
        $response['message'] = "Insert failed: " . $stmt->error;
    }

    $stmt->close();

} else {
    $response['message'] = "Invalid request method.";
}

// Clean output and return JSON
ob_clean();
echo json_encode($response);
exit;
