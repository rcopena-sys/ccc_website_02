<?php
// Enable error reporting for debugging but don't display to browser
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Enable logging
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\htdocs\website\super_admin\debug.log');

// Start output buffering to prevent any HTML output
ob_start();

include 'db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

// Function to generate secure password
function generateSecurePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    // Ensure at least one lowercase, one uppercase, one digit, and one special character
    $password .= strtolower($chars[rand(0, 25)]);
    $password .= strtoupper($chars[rand(26, 51)]);
    $password .= $chars[rand(52, 61)];
    $password .= $chars[rand(62, 71)];
    
    // Fill the rest
    for ($i = 4; $i < $length; $i++) {
        $password .= $chars[rand(0, 71)];
    }
    
    return str_shuffle($password);
}

// Function to generate username from student email
function generateUsername($studentName, $studentId, $email) {
    // Use email as username (before @ symbol)
    $username = explode('@', $email)[0];
    
    // Clean up the username
    $username = strtolower($username);
    $username = preg_replace('/[^a-z0-9._-]/', '', $username);
    // Return sanitized local-part; DB does not store separate username
    return !empty($username) ? $username : ('user' . time());
}

// Function to send credentials email
function sendCredentialsEmail($studentName, $email, $username, $password) {
    $mail = new PHPMailer(true);
    
    try {
        // Enable debugging
        $mail->SMTPDebug = 0; // Set to 2 for debugging, 0 for production
        $mail->Debugoutput = 'html';
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rozzo4968@gmail.com';
        $mail->Password = 'elduyrelyltgjhdr'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Set charset and encoding
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Recipients
        $mail->setFrom('rozzo4968@gmail.com', 'Student Account System');
        $mail->addAddress($email, $studentName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Student Account Credentials';
        
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
            <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 style="color: #2563eb; text-align: center; margin-bottom: 30px;">Welcome to the Student Portal</h2>
                
                <div style="background-color: #e8f4fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #1e40af; margin-top: 0;">Your Login Credentials</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px; font-weight: bold; color: #374151; width: 120px;">Student Name:</td>
                            <td style="padding: 10px; color: #1f2937;">' . htmlspecialchars($studentName) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; font-weight: bold; color: #374151;">Email (Login):</td>
                            <td style="padding: 10px; color: #1f2937; font-weight: bold; background-color: #fef3c7;">' . htmlspecialchars($email) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; font-weight: bold; color: #374151;">Password:</td>
                            <td style="padding: 10px; color: #1f2937; font-weight: bold; background-color: #fef3c7;">' . htmlspecialchars($password) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; font-weight: bold; color: #374151;">Role:</td>
                            <td style="padding: 10px; color: #1f2937;">' . htmlspecialchars($GLOBALS['current_role']) . '</td>
                        </tr>
                    </table>
                </div>
                
                <div style="margin: 30px 0; padding: 20px; background-color: #f0f9ff; border-radius: 8px; border-left: 4px solid #2563eb;">
                    <h3 style="color: #1e40af; margin-top: 0;">Important Instructions:</h3>
                    <ul style="color: #374151; line-height: 1.6;">
                        <li>Keep your credentials secure and do not share them with anyone</li>
                        <li>Change your password after first login for security</li>
                        <li>Use your email address and password to access the student portal</li>
                        <li>If you forget your password, contact the administrator</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f3f4f6; border-radius: 8px;">
                    <p style="color: #6b7280; margin: 0;">If you have any questions or need assistance, please contact the IT Department.</p>
                    <p style="color: #6b7280; margin: 10px 0 0 0;">Thank you!</p>
                </div>
            </div>
        </div>';
        
        $mail->AltBody = '
        Student Account Credentials
        
        Student Name: ' . $studentName . '
        Email (Login): ' . $email . '
        Password: ' . $password . '
        Role: ' . $GLOBALS['current_role'] . '
        
        Important Instructions:
        - Keep your credentials secure and do not share them with anyone
        - Change your password after first login for security
        - Use your email address and password to access the student portal
        - If you forget your password, contact the administrator';
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log email error
        $emailErrorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'EMAIL_ERROR',
            'error_message' => $e->getMessage(),
            'student_email' => $email
        ];
        file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($emailErrorLog) . "\n", FILE_APPEND);
        
        return false;
    }
}

header('Content-Type: application/json');

// Log all incoming data
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'headers' => getallheaders()
];
file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($logData) . "\n", FILE_APPEND);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Store role globally for email function
    $GLOBALS['current_role'] = $role;
    
    // Validation
    if (empty($student_id) || empty($student_name) || empty($email) || empty($role)) {
        $response['message'] = 'Student ID, name, email, and role are required';
        echo json_encode($response);
        exit;
    }
    
    // Auto-generate username if not provided
    if (empty($username)) {
        $username = generateUsername($student_name, $student_id, $email);
    }
    
    // Auto-generate password if not provided
    if (empty($password)) {
        $password = generateSecurePassword();
    }
    
    if (strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters long';
        echo json_encode($response);
        exit;
    }
    
    // Check if student already has an account
    $check_student_query = "SELECT COUNT(*) as count FROM signin_db WHERE student_id = '$student_id' OR email = '$email'";
    $check_student_result = $conn->query($check_student_query);
    if ($check_student_result && $row = $check_student_result->fetch_assoc()) {
        if ($row['count'] > 0) {
            $response['message'] = 'Student already has an account';
            echo json_encode($response);
            exit;
        }
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Log the data before insertion
    $insertLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'BEFORE_INSERT',
        'student_id' => $student_id,
        'student_name' => $student_name,
        'email' => $email,
        'username' => $username,
        'password_length' => strlen($password),
        'hashed_password_length' => strlen($hashed_password),
        'role' => $role
    ];
    file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($insertLog) . "\n", FILE_APPEND);

    // Derive first and last name
    $names = preg_split('/\s+/', trim($student_name));
    $firstname = $names[0] ?? '';
    $lastname = count($names) > 1 ? implode(' ', array_slice($names, 1)) : '';

    // Map role/course to role_id (based on existing data)
    $course = $role;
    $role_id = 5; // default student role
    if (stripos($course, 'BSIT') !== false) {
        $role_id = 4; // DCI Student
    } elseif (stripos($course, 'BSCS') !== false) {
        $role_id = 5; // CS Student
    }

    // Fetch classification from students_db (regular/irregular)
    $classification = null;
    $cls_stmt = $conn->prepare("SELECT classification FROM students_db WHERE student_id = ? OR email = ? LIMIT 1");
    if ($cls_stmt) {
        $cls_stmt->bind_param('ss', $student_id, $email);
        if ($cls_stmt->execute()) {
            $cls_result = $cls_stmt->get_result();
            if ($cls_result && $cls_result->num_rows > 0) {
                $cls_row = $cls_result->fetch_assoc();
                $classification = $cls_row['classification'] ?? null;
            }
        }
        $cls_stmt->close();
    }

    // Use prepared statement aligned with signin_db schema
    $stmt = $conn->prepare("INSERT INTO signin_db (student_id, firstname, lastname, email, password, course, classification, role_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('sssssssi', $student_id, $firstname, $lastname, $email, $hashed_password, $course, $classification, $role_id);
    }

    // Log the SQL parameters for the prepared insert
    $queryLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'SQL_PREPARED_INSERT',
        'table' => 'signin_db',
        'params' => [
            'student_id' => $student_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'course' => $course,
            'classification' => $classification,
            'role_id' => $role_id
        ]
    ];
    file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($queryLog) . "\n", FILE_APPEND);

    $executed = false;
    if ($stmt) {
        $executed = $stmt->execute();
    } else {
        // Fallback (should not use in production)
        $executed = false;
    }
    if ($executed) {
        // Log successful insertion
        $successLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'INSERT_SUCCESS',
            'message' => 'Account successfully created in signin_db',
            'inserted_id' => $conn->insert_id
        ];
        file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($successLog) . "\n", FILE_APPEND);
        
        // Send email with credentials
        $emailSent = sendCredentialsEmail($student_name, $email, $username, $password);
        
        if ($emailSent) {
            $response['success'] = true;
            $response['message'] = 'Account created successfully! Credentials sent to student email.';
            $response['username'] = $username; // Include generated username
            $response['password'] = $password; // Include generated password
        } else {
            $response['success'] = true;
            $response['message'] = 'Account created successfully! However, email failed to send. Username: ' . $username . ', Password: ' . $password;
            $response['username'] = $username;
            $response['password'] = $password;
        }
    } else {
        // Log database error
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'INSERT_ERROR',
            'error_message' => $conn->error,
            'error_code' => $conn->errno,
            'query' => 'prepared_insert_signin_db'
        ];
        file_put_contents('C:\xampp\htdocs\website\super_admin\debug.log', json_encode($errorLog) . "\n", FILE_APPEND);
        
        $response['message'] = 'Error creating account: ' . ($stmt ? $stmt->error : $conn->error);
        error_log('Database Error: ' . ($stmt ? $stmt->error : $conn->error));
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Clean any output buffer and send clean JSON
ob_clean();
echo json_encode($response);
?>

