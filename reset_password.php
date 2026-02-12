<?php
session_start();
require 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$error = '';
$success = '';
$showOTPForm = true;
$showResetForm = false;

// Function to send password changed notification
function sendPasswordChangedEmail($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rozzo4968@gmail.com';
        $mail->Password = 'elduyrelyltgjhdr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rozzo4968@gmail.com', 'CCC Password Reset');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Changed Successfully';
        $mail->Body = "
            <h2>Password Change Notification</h2>
            <p>Your password has been successfully changed.</p>
            <p>If you did not make this change, please contact the administrator immediately.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if (isset($_GET['email']) && isset($_GET['otp']) && isset($_GET['token'])) {
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_token'] = $_GET['token'];
    // Remove any previous verification
    unset($_SESSION['otp_verified']);
} else if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $entered_otp = $_POST['otp'];
        $email = $_SESSION['reset_email'];
        $token = $_SESSION['reset_token'];
        
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND token = ? AND expires_at > NOW() AND used = 0");
        $stmt->bind_param("sss", $email, $entered_otp, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['otp_verified'] = true;
            $showOTPForm = false;
            $showResetForm = true;
            $success = "OTP verified successfully. Please set your new password.";
        } else {
            $error = "Invalid or expired OTP. Please try again.";
        }
    } else if (isset($_POST['reset_password'])) {
        if (!isset($_SESSION['otp_verified'])) {
            $error = "Please verify your OTP first.";
        } else {
            $email = $_SESSION['reset_email'];
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE signin_db SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $new_password, $email);
            
            if ($stmt->execute()) {
                // Mark OTP as used
                $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Send password changed notification
                if (sendPasswordChangedEmail($email)) {
                    $success = "Password has been reset successfully! A confirmation email has been sent to your address.";
                } else {
                    $success = "Password has been reset successfully! However, we couldn't send the confirmation email.";
                }
                // Log password change activity (non-fatal)
                try {
                    if (!isset($conn) && file_exists(__DIR__ . '/db_connect.php')) {
                        require_once __DIR__ . '/db_connect.php';
                    }
                    if (isset($conn)) {
                        $userId = null;
                        // Try to get user id from signin_db
                        $q = $conn->prepare('SELECT id FROM signin_db WHERE email = ? LIMIT 1');
                        if ($q) {
                            $q->bind_param('s', $email);
                            $q->execute();
                            $r = $q->get_result();
                            if ($row = $r->fetch_assoc()) $userId = $row['id'];
                            $q->close();
                        }

                        $username = $email;
                        $action = 'Password Changed';
                        $details = 'Password changed for ' . $email;
                        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

                        $ls = $conn->prepare("INSERT INTO activity_log_db (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($ls) {
                            $ls->bind_param('isssss', $userId, $username, $action, $details, $ip, $ua);
                            $ls->execute();
                            $ls->close();
                        }
                    }
                } catch (Exception $e) {
                    error_log('Activity log insert failed (reset_password): ' . $e->getMessage());
                }
                
                // Clear session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['otp_verified']);
                $showOTPForm = false;
                $showResetForm = false;
            } else {
                $error = "Failed to reset password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            max-width: 400px;
            margin: 50px auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            font-weight: bold;
        }
        .btn-primary {
            width: 100%;
        }
        .alert {
            margin-bottom: 15px;
        }
        .form-floating {
            margin-bottom: 15px;
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            background: none;
            border: none;
            color: #6c757d;
        }
        .form-control:focus {
            z-index: 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Reset Your Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                        <?php if (strpos($error, "expired") !== false): ?>
                            <div class="mt-2">
                                <a href="forgot_password.php" class="btn btn-outline-primary btn-sm">Request New Reset Link</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success && !$showOTPForm && !$showResetForm): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-outline-primary btn-sm">Back to Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if ($showOTPForm): ?>
                        <div class="text-center mb-4">
                            <p class="text-muted">Please enter the OTP code sent to your email address.</p>
                        </div>
                        <form method="POST" action="">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" maxlength="6" required>
                                <label for="otp">Enter OTP</label>
                            </div>
                            <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($showResetForm): ?>
                        <form method="POST" action="">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" required>
                                <label for="new_password">New Password</label>
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                    <i id="new_password_icon" class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                <label for="confirm_password">Confirm Password</label>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i id="confirm_password_icon" class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                field.type = 'password';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }

        // Password validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            if (this.confirm_password) {
                const password = this.new_password.value;
                const confirmPassword = this.confirm_password.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            }
        });
    </script>
</body>
</html>
