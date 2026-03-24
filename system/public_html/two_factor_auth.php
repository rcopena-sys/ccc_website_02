<?php
// Start the session at the very beginning
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log session data at the start
error_log("=== Starting OTP Verification Process ===");
error_log("Session data: " . print_r($_SESSION, true));

// Include necessary files
require_once 'db_connect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error_message = '';
$success_message = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp'] ?? '');
    
    error_log("OTP received: " . $otp);
    error_log("User ID from session: " . ($_SESSION['user_id_2fa'] ?? 'Not set'));
    
    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        $error_message = "Please enter a valid 6-digit OTP.";
        error_log("Invalid OTP format: " . $otp);
    } else {
        try {
            // Verify the OTP
            $stmt = $conn->prepare("SELECT * FROM two_factor_auth 
                                   WHERE user_id = ? AND token = ? 
                                   AND used = 0 AND expires_at > NOW()
                                   ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param("is", $_SESSION['user_id_2fa'], $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $token = $result->fetch_assoc();
                error_log("Valid OTP found in database. Token ID: " . $token['id']);
                
                // Mark the token as used
                $updateStmt = $conn->prepare("UPDATE two_factor_auth SET used = 1 WHERE id = ?");
                $updateStmt->bind_param("i", $token['id']);
                if (!$updateStmt->execute()) {
                    error_log("Failed to mark OTP as used: " . $updateStmt->error);
                }
                
                // Set session variables
                error_log("Setting session variables for user: " . $_SESSION['user_id_2fa']);
                error_log("Role ID from 2FA session: " . ($_SESSION['role_id_2fa'] ?? 'Not set'));
                
                $_SESSION['user_id'] = $_SESSION['user_id_2fa'];
                $_SESSION['email'] = $_SESSION['email_2fa'] ?? '';
                $_SESSION['firstname'] = $_SESSION['firstname_2fa'] ?? '';
                $_SESSION['lastname'] = $_SESSION['lastname_2fa'] ?? '';
                $_SESSION['role_id'] = (int)($_SESSION['role_id_2fa'] ?? 0);
                
                error_log("Session variables set. User ID: " . $_SESSION['user_id'] . ", Role ID: " . $_SESSION['role_id']);
                
                // Handle remember me functionality
                if (isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === true) {
                    $remember_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
                    
                    $stmt = $conn->prepare("UPDATE signin_db SET remember_token = ?, token_expires = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $remember_token, $expires_at, $_SESSION['user_id']);
                    $stmt->execute();
                    
                    // Set secure cookie
                    setcookie('remember_token', $remember_token, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true);
                    
                    unset($_SESSION['remember_me']);
                }
                
                // Clear 2FA session variables
                $clear_vars = ['user_id_2fa', 'email_2fa', 'firstname_2fa', 'lastname_2fa', 'role_id_2fa'];
                foreach ($clear_vars as $var) {
                    unset($_SESSION[$var]);
                }
                
                // Redirect based on role
                $role_id = (int)$_SESSION['role_id'];
                error_log("Preparing to redirect. Role ID: " . $role_id);

                // Match original post-login redirects from login_processing.php
                switch ($role_id) {
                    case 1:
                        $path = "super_admin/homepage.php";
                        break;
                    case 2:
                        $path = "adminpage/dashboard2.php";
                        break;
                    case 3:
                        $path = "registrar/dashboardr.php";
                        break;
                    case 4:
                        $path = "student/dci_page.php";
                        break;
                    case 5:
                        $path = "student/cs_studash.php";
                        break;
                    default:
                        $path = "index.php?error=invalid_role";
                        break;
                }

                // Build the full URL: use /website base only on localhost, root on production
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = (defined('APP_ENV') && APP_ENV === 'local') ? '/website' : '';
                $redirect_url = $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($path, '/');

                error_log("Final redirect URL: " . $redirect_url);

                // Clear all output buffers
                while (ob_get_level()) ob_end_clean();

                // Perform the redirect
                if (!headers_sent()) {
                    header("Location: " . $redirect_url);
                    exit();
                } else {
                    error_log("Headers already sent. Output started at: " . headers_sent($filename, $linenum) . " in $filename on line $linenum");
                    die("Redirect failed. Please <a href='$redirect_url'>click here</a> to continue.");
                }
            } else {
                $error_message = "Invalid or expired OTP. Please try again.";
                error_log("No valid OTP found in database for the provided code");
                error_log("Debug - User ID: " . $_SESSION['user_id_2fa'] . ", OTP: " . $otp);
            }
        } catch (Exception $e) {
            $error_message = "An error occurred. Please try again later.";
            error_log("2FA Error: " . $e->getMessage());
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    try {
        if (!isset($_SESSION['user_id_2fa']) || empty($_SESSION['user_id_2fa'])) {
            throw new Exception("User session expired. Please login again.");
        }
        
        $user_id = $_SESSION['user_id_2fa'];
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        error_log("Resending OTP for user ID: " . $user_id);
        
        $stmt = $conn->prepare("INSERT INTO two_factor_auth (user_id, token, expires_at) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iss", $user_id, $otp, $expiresAt);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save OTP: " . $stmt->error);
        }
        
        // Send OTP via email (implement your email sending logic here)
        // For now, we'll just set a success message
        $success_message = "A new OTP has been sent to your email.";
        error_log("New OTP generated for user ID: " . $user_id);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Resend OTP Error: " . $e->getMessage());
        
        // If it's a session expired error, redirect to login
        if (strpos($error_message, 'session expired') !== false) {
            $_SESSION['error'] = $error_message;
            header("Location: index.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication</title>
    <style>
        .otp-input {
            letter-spacing: 0.5em;
            font-family: monospace;
            font-size: 1.2em;
            text-align: center;
            padding: 0.5em;
            width: 100%;
        }
        .otp-input::placeholder {
            letter-spacing: normal;
            color: #6c757d;
            opacity: 0.5;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            const otpForm = document.getElementById('otpForm');
            
            // Only allow numeric input
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
            });
            
            // Prevent form submission on Enter key
            otpForm.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
            
            // Focus the input on page load
            otpInput.focus();
        });
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-container {
            max-width: 450px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header i {
            font-size: 48px;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
            width: 100%;
            margin-top: 10px;
        }
        .btn-link {
            color: #0d6efd;
            text-decoration: none;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .otp-input {
            letter-spacing: 5px;
            font-size: 1.5rem;
            text-align: center;
            height: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Two-Factor Authentication</h2>
                <p class="text-muted">Enter the verification code sent to your email.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="otpForm">
                <div class="mb-3">
                    <label for="otp" class="form-label">Verification Code</label>
                    <input type="text" 
                           class="form-control otp-input" 
                           id="otp" 
                           name="otp" 
                           placeholder="123456" 
                           maxlength="6" 
                           pattern="\d{6}" 
                           inputmode="numeric"
                           title="Please enter the 6-digit code" 
                           required 
                           autofocus>
                    <div class="form-text">Enter the 6-digit code sent to your email.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="verify_otp" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Verify Code
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <form method="POST" id="resendForm">
                    <button type="submit" name="resend_otp" class="btn btn-link">
                        <i class="fas fa-redo me-1"></i>Resend Code
                    </button>
                </form>
                <span class="mx-2">|</span>
                <a href="index.php" class="btn btn-link">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            const otpForm = document.getElementById('otpForm');
            
            // Only allow numeric input
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
            });
            
            // Handle paste event
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                const numericValue = pastedData.replace(/[^0-9]/g, '').slice(0, 6);
                this.value = numericValue;
            });
            
            // Focus the OTP input when page loads
            otpInput.focus();
            
            // Select all text when input is focused
            otpInput.addEventListener('focus', function() {
                this.select();
            });
        });
    </script>
</body>
</html>