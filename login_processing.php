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

// Login success - reset failed attempts
$reset_attempts = $conn->prepare("UPDATE signin_db SET failed_attempts = 0, last_failed_attempt = NULL, account_locked_until = NULL WHERE email = ?");
$reset_attempts->bind_param("s", $email);
$reset_attempts->execute();
$reset_attempts->close();

// Record successful login
recordSuccessfulAttempt($conn, $email, $ip_address, $user_agent);

// Create session / remember token
$session_id = bin2hex(random_bytes(32));
$remember_token = $remember ? bin2hex(random_bytes(32)) : null;
$expires_at = date('Y-m-d H:i:s', time() + ($remember ? 86400 * 30 : 86400)); // 30 days or 1 day

// Deactivate old sessions for this user
$upd = $conn->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?");
$upd->bind_param("i", $user['id']);
$upd->execute();
$upd->close();

// Insert new session
$ins = $conn->prepare("INSERT INTO user_sessions (session_id, user_id, remember_token, ip_address, user_agent, expires_at, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
$ins->bind_param("sissss", $session_id, $user['id'], $remember_token, $ip_address, $user_agent, $expires_at);
$ins->execute();
$ins->close();

// Set PHP session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['firstname'] = $user['firstname'];
$_SESSION['lastname'] = $user['lastname'];
$_SESSION['email'] = $user['email'];
$_SESSION['student_id'] = $user['student_id'];
$_SESSION['academic_year'] = $user['academic_year'];
$_SESSION['course'] = $user['course'];
$_SESSION['session_id'] = $session_id;

// Set secure cookie for remember me (only if using HTTPS set secure=true)
if ($remember && $remember_token) {
    setcookie('remember_token', $remember_token, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
}

// Log activity (best-effort, don't break login if it fails)
try {
    $activity = "User logged in from IP: {$ip_address}";
    $log = $conn->prepare("INSERT INTO activity_logs (User_ID, Activity_Type, Activity_Details) VALUES (?, ?, ?)");
    $atype = 'Login';
    $log->bind_param("iss", $user['id'], $atype, $activity);
    $log->execute();
    $log->close();
} catch (Exception $e) {
    error_log("Activity log failed: " . $e->getMessage());
}

// Redirect by role
switch ((int)$user['role_id']) {
    case 1: header("Location: super admin/homepage.php"); break;
    case 2: header("Location: adminpage/dashboard2.php"); break;
    case 3: header("Location: registrar/dashboardr.php"); break;
    case 4: header("Location: student/dci_page.php"); break;
    case 5: header("Location: student/cs_studash.php"); break;
    default:
        $_SESSION['toast_error'] = "Invalid user role. Please contact administrator.";
        header("Location: index.php"); exit();
}
exit();

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