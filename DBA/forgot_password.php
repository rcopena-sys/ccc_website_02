<?php
session_start();
require 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Set the default time zone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Function to generate OTP
function generateOTP() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to generate a secure token
function generateToken() {
    return bin2hex(random_bytes(32));
}

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rozzo4968@gmail.com';
        $mail->Password = 'elduyrelyltgjhdr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('rozzo4968@gmail.com', 'CCC Password Reset');
        $mail->addAddress($email);

        // Generate verification link and store data
        $token = generateToken();
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $verificationLink = $protocol . $host . dirname($_SERVER['PHP_SELF']) . "/reset_password.php";
        
        // Store token and OTP in database
        $updateToken = $GLOBALS['conn']->prepare("UPDATE password_resets SET token = ?, email = ?, otp = ? WHERE email = ?");
        $updateToken->bind_param("ssss", $token, $email, $otp, $email);
        $updateToken->execute();
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Your OTP for password reset is: <b>$otp</b></p>
            <p>This OTP will expire in 10 minutes.</p>
            <p>Click the link below to enter your OTP and reset your password:</p>
            <p><a href='$verificationLink' style='display: inline-block; background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>Or you can manually enter the OTP on the password reset page.</p>
            <p>If you did not request this password reset, please ignore this email.</p>";
        
        $mail->AltBody = "Your OTP for password reset is: $otp\nClick here to reset your password: $verificationLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // Check if email exists in signin_db
        $stmt = $conn->prepare("SELECT * FROM signin_db WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $otp = generateOTP();
            // Set expiry time 10 minutes from now in Asia/Manila timezone
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Delete any existing OTP for this email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Insert new OTP
            $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $otp, $expires);
            
            if ($stmt->execute() && sendOTPEmail($email, $otp)) {
                $success = "Password reset instructions have been sent to your email address. Please check your inbox.";
            } else {
                $error = "Failed to send reset instructions. Please try again.";
            }
        } else {
            $error = "Email address not found in our records.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
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
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
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
    
    <script>
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
