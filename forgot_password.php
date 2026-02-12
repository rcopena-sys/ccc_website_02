<?php
session_start();
require 'config.php'; // must contain $conn = new mysqli(...);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function: Generate OTP
function generateOTP() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function: Send OTP Email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rozzo4968@gmail.com';
        $mail->Password = 'elduyrelyltgjhdr'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('rozzo4968@gmail.com', 'CCC Password Reset');
        $mail->addAddress($email);

        // Build verification link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $host     = $_SERVER['HTTP_HOST'];
        $path     = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $verifyLink = $protocol . $host . $path . "/verify_otp.php?email=" . urlencode($email);

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Your OTP for password reset is: <b>$otp</b></p>
            <p>This OTP will expire in 10 minutes.</p>
            <p>You can enter it manually on the password reset page, or click below:</p>
            <p><a href='$verifyLink' style='display:inline-block;background:#0d6efd;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Verify OTP</a></p>
            <p>If you did not request this, please ignore this email.</p>";
        $mail->AltBody = "Your OTP for password reset is: $otp\n\nVerify here: $verifyLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo); // Debug log
        return false;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Check if email exists in signin_db
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = generateOTP();
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Delete any old OTP
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Insert new OTP
        $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $otp, $expires);

        if ($stmt->execute()) {
            if (sendOTPEmail($email, $otp)) {
                $success = "Password reset instructions have been sent to your email. Please check your inbox.";
                // Log activity (non-fatal)
                try {
                    if (!isset($conn) && file_exists(__DIR__ . '/db_connect.php')) {
                        require_once __DIR__ . '/db_connect.php';
                    }
                    if (isset($conn)) {
                        $userId = null;
                        $username = $email;
                        $action = 'Password Reset Requested';
                        $details = 'Requested password reset OTP for ' . $email;
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
                    error_log('Activity log insert failed (forgot_password): ' . $e->getMessage());
                }
            } else {
                $error = "Failed to send email. Check server logs for details.";
                // Log failure attempt
                try {
                    if (!isset($conn) && file_exists(__DIR__ . '/db_connect.php')) {
                        require_once __DIR__ . '/db_connect.php';
                    }
                    if (isset($conn)) {
                        $userId = null;
                        $username = $email;
                        $action = 'Password Reset Email Failed';
                        $details = 'Failed to send OTP to ' . $email;
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
                    error_log('Activity log insert failed (forgot_password_failure): ' . $e->getMessage());
                }
            }
        } else {
            $error = "Database error. Please try again.";
        }
    } else {
        $error = "Email address not found in our records.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { max-width: 400px; margin: 50px auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .card-header { background-color: #0d6efd; color: white; text-align: center; font-weight: bold; }
        .btn-primary { width: 100%; }
        .alert { margin-bottom: 15px; }
        .form-floating { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Password Reset</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST" action="">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                            <label for="email">Email address</label>
                        </div>
                        <button type="submit" name="send_otp" class="btn btn-primary">Send Reset Instructions</button>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
