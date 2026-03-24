<?php
session_start();
require 'db_connect.php';

// Constants for login security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 5);

// DEV: enable errors during debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Ensure $conn exists and is MySQLi
if (!isset($conn) || !$conn) {
    error_log("Database connection missing");
    die("Database connection failed");
}
if ($conn instanceof mysqli) {
    $conn->set_charset('utf8mb4');
}

// Helpers for client info
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (strpos($ip_address, ',') !== false) { $ip_address = trim(explode(',', $ip_address)[0]); }
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? true : false;

// Basic validation
if (empty($email)) {
    $_SESSION['error'] = "Please enter your email.";
    header("Location: index.php"); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header("Location: index.php"); exit();
}
if (empty($password)) {
    $_SESSION['error'] = "Please enter your password.";
    header("Location: index.php"); exit();
}

// Check if account exists and get current status
$stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, student_id, academic_year, course, role_id, status, 
                        failed_attempts, last_failed_attempt, account_locked_until 
                        FROM signin_db WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if account is locked
if ($user && $user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
    $remaining_time = strtotime($user['account_locked_until']) - time();
    $minutes = max(1, ceil($remaining_time / 60));
    $_SESSION['toast_error'] = "Account locked due to multiple failed attempts. Try again in {$minutes} minutes.";
    header("Location: index.php");
    exit();
}

// Reset lock if lockout period has passed
if ($user && $user['account_locked_until'] && strtotime($user['account_locked_until']) <= time()) {
    $reset_stmt = $conn->prepare("UPDATE signin_db SET failed_attempts = 0, account_locked_until = NULL, last_failed_attempt = NULL WHERE email = ?");
    $reset_stmt->bind_param("s", $email);
    $reset_stmt->execute();
    $reset_stmt->close();
    $user['failed_attempts'] = 0; // Update local user data
}
$stmt->close();

// User record was already fetched above, check if it exists

if (!$user) {
    recordFailedAttempt($conn, $email, $ip_address, $user_agent);
    $_SESSION['toast_error'] = "Invalid email or password.";
    header("Location: index.php"); exit();
}

if ($user['status'] !== 'Active') {
    $_SESSION['toast_error'] = "Your account is inactive. Please contact the administrator.";
    header("Location: index.php"); exit();
}

// Verify password
if (!$user || !password_verify($password, $user['password'])) {
    // Increment failed attempts for existing user
    if ($user) {
        $new_attempts = ($user['failed_attempts'] ?? 0) + 1;
        $lock_until = ($new_attempts >= MAX_LOGIN_ATTEMPTS) 
            ? date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes')) 
            : null;
        
        // Debug logging
        error_log("Login failed for $email. Attempt $new_attempts of " . MAX_LOGIN_ATTEMPTS);
            
        $update = $conn->prepare("UPDATE signin_db SET 
            failed_attempts = ?, 
            last_failed_attempt = NOW(),
            account_locked_until = ?
            WHERE email = ?");
        if (!$update) {
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        
        $update->bind_param("iss", $new_attempts, $lock_until, $email);
        if (!$update->execute()) {
            error_log("Execute failed: (" . $update->errno . ") " . $update->error);
        } else {
            error_log("Updated failed_attempts to $new_attempts for $email");
        }
        $update->close();
        
        if ($new_attempts >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['toast_error'] = "Account locked. Please try again in " . LOCKOUT_MINUTES . " minutes.";
            error_log("Account $email locked after $new_attempts failed attempts");
        } else {
            $_SESSION['toast_error'] = "Invalid email or password.\nFailed attempt: {$new_attempts} of " . MAX_LOGIN_ATTEMPTS;
        }
    } else {
        // User doesn't exist, but don't reveal that
        $_SESSION['toast_error'] = "Invalid email or password.";
    }
    
    // Record failed attempt
    if (function_exists('recordFailedAttempt')) {
        recordFailedAttempt($conn, $email, $ip_address, $user_agent);
    }
    
    header("Location: index.php");
    exit();
}

// Re-hash if needed
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    $up = $conn->prepare("UPDATE signin_db SET password = ? WHERE id = ?");
    $up->bind_param("si", $newHash, $user['id']);
    $up->execute();
    $up->close();
}

// Generate OTP for 2FA
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store OTP in database
$otpStmt = $conn->prepare("INSERT INTO two_factor_auth (user_id, token, expires_at) VALUES (?, ?, ?)");
$otpStmt->bind_param("iss", $user['id'], $otp, $expiresAt);
$otpStmt->execute();

// Store user data in session for 2FA
$_SESSION['user_id_2fa'] = $user['id'];
$_SESSION['email_2fa'] = $user['email'];
$_SESSION['firstname_2fa'] = $user['firstname'];
$_SESSION['lastname_2fa'] = $user['lastname'];
$_SESSION['role_id_2fa'] = $user['role_id'];

// Store remember preference in session (will be used after 2FA)
if ($remember) {
    $_SESSION['remember_me'] = true;
}

// Send OTP via email
require 'vendor/autoload.php';
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com'; // Replace with your email
    $mail->Password = 'your-app-password'; // Replace with your app password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('noreply@ccc.edu.ph', 'CCC Security');
    $mail->addAddress($user['email']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Login Verification Code';
    $mail->Body = sprintf(
        '<h2>Your Verification Code</h2>
        <p>Hello %s,</p>
        <p>Your verification code is: <strong>%s</strong></p>
        <p>This code will expire in 5 minutes.</p>
        <p>If you didn\'t request this code, please ignore this email or contact support.</p>',
        htmlspecialchars($user['firstname']),
        $otp
    );

    $mail->send();
    
    // Log the 2FA attempt
    $logAction = '2FA Code Sent';
    $logDetails = sprintf(
        '2FA verification code sent to %s %s (%s)',
        $user['firstname'],
        $user['lastname'],
        $user['email']
    );
    
    // Insert the activity log
    $log = $conn->prepare("INSERT INTO activity_logs (User_ID, Activity_Type, Activity_Details) VALUES (?, ?, ?)");
    if ($log) {
        $log->bind_param('iss', 
            $user['id'], 
            $logAction, 
            $logDetails
        );
        $log->execute();
        $log->close();
    }

    // Redirect to 2FA verification
    header('Location: two_factor_auth.php');
    exit();
    
} catch (Exception $e) {
    error_log('2FA email sending failed: ' . $e->getMessage());
    $_SESSION['toast_error'] = 'Failed to send verification code. Please try again.';
    header("Location: index.php");
    exit();
}
// No code should be executed after the 2FA redirect

// ---------------- Helper Functions ----------------
function recordFailedAttempt($conn, $email, $ip, $ua) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success, user_agent) VALUES (?, ?, 0, ?)");
    $stmt->bind_param("sss", $email, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

function recordSuccessfulAttempt($conn, $email, $ip, $ua) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success, user_agent) VALUES (?, ?, 1, ?)");
    $stmt->bind_param("sss", $email, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

function clearFailedAttempts($conn, $email) {
    // Clean up old login attempts
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email = ? AND success = 0 AND attempt_time < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
}

function checkAndLockAccount($conn, $email, $ip, $full_name) {
    // This function is kept for backward compatibility but the main logic is now in the login flow
    $stmt = $conn->prepare("SELECT failed_attempts FROM signin_db WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $failed_count = (int)($res['failed_attempts'] ?? 0);
    if ($failed_count >= 5) {

        // Email notification (non-blocking)
        try {
            sendLockoutEmail($email, $full_name, $ip, $failed_count);
        } catch (Exception $e) {
            error_log("Lockout email failed: " . $e->getMessage());
        }
    }
}

function sendLockoutEmail($email, $full_name, $ip_address, $attempts) {
    date_default_timezone_set('Asia/Manila');
    $current_time = date('F j, Y — g:i A');
    $masked_ip = maskIP($ip_address);
    $subject = "Account security alert";
    $body = "<p>Hello {$full_name},</p><p>We detected {$attempts} failed login attempts on {$current_time} from IP {$masked_ip}.</p>";
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: noreply@example.com\r\n";
    @mail($email, $subject, $body, $headers);
}

function maskIP($ip) {
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        return "{$parts[0]}.{$parts[1]}.{$parts[2]}.XX";
    }
    return 'Unknown';
}
?>