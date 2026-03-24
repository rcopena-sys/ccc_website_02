<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } elseif (!preg_match('/@ccc\.edu\.ph$/i', $email)) {
        $error_message = "Only @ccc.edu.ph email addresses are allowed.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role_id, status, failed_attempts, last_failed_attempt
                        FROM signin_db WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Check if account is active and password is correct
                if (password_verify($password, $user['password'])) {
                    // Check if account is active (case-insensitive check)
                    if (strtolower($user['status']) !== 'active') {
                        $error_message = "Your account is not active. Please contact support.";
                        $_SESSION['toast_error'] = $error_message;
                    } else {
                        // Reset failed attempts on successful login
                        $resetStmt = $conn->prepare("UPDATE signin_db SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
                        $resetStmt->bind_param("i", $user['id']);
                        $resetStmt->execute();
                        
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
                        
                        // Send OTP via email
                        require 'vendor/autoload.php';
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'rozzo4968@gmail.com'; // Your Gmail address
                            $mail->Password = 'elduyrelyltgjhdr'; // Your Gmail App Password
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
                            $logUserId = $user['id'];
                            $logUsername = $user['email'];
                            $logAction = '2FA Code Sent';
                            $logDetails = sprintf(
                                '2FA verification code sent to %s %s (%s)',
                                $user['firstname'],
                                $user['lastname'],
                                $user['email']
                            );
                            
                            // Insert the activity log if the table exists
                            if ($conn->query("SHOW TABLES LIKE 'activity_log_db'")->num_rows > 0) {
                                $ls = $conn->prepare("INSERT INTO activity_log_db (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
                                if ($ls) {
                                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                                    $ls->bind_param('isssss', 
                                        $logUserId, 
                                        $logUsername, 
                                        $logAction, 
                                        $logDetails, 
                                        $ipAddress, 
                                        $userAgent
                                    );
                                    $ls->execute();
                                    $ls->close();
                                }
                            }

                            // Redirect to 2FA verification
                            header('Location: two_factor_auth.php');
                            exit();
                            
                        } catch (Exception $e) {
                            error_log('2FA email sending failed: ' . $e->getMessage());
                            $error_message = 'Failed to send verification code. Please try again later.';
                            $_SESSION['toast_error'] = $error_message;
                        }
                    }
                } else {
                    // Password incorrect → increment failed attempts
                    $failedAttempts = $user['failed_attempts'] + 1;

                    $stmtUpdate = $conn->prepare("UPDATE signin_db SET failed_attempts = ?, last_failed_attempt = NOW() WHERE id = ?");
                    $stmtUpdate->bind_param("ii", $failedAttempts, $user['id']);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();

                    if ($failedAttempts >= 5) {
                        $error_message = "Too many failed attempts. Try again in 5 minutes.";
                    } else {
                        $error_message = "Invalid email or password. Failed attempts: $failedAttempts";
                    }

                    $_SESSION['toast_error'] = $error_message;
                }
            } else {
                // For non-existent accounts, don't reveal that the account doesn't exist
                $error_message = "Invalid email or password.";
                $_SESSION['toast_error'] = $error_message;
            }
            $stmt->close();
        } catch (Exception $e) {
            // Log the full error for debugging
            error_log("Login error: " . $e->getMessage());

            // Show a user-friendly message
            $error_message = "Invalid email or password.";
            $_SESSION['toast_error'] = $error_message;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City College of Calamba</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('ccc.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(5px);
            color: #333;
        }

        .logo {
            width: 100px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 10px;
            color: #1a1a1a;
            font-size: 24px;
        }

        h2 {
            margin: 0 0 20px;
            color: #1a1a1a;
            font-size: 16px;
        }

        .error-message {
            background: #fde8e8;
            border: 1px solid #f56565;
            color: #9b2c2c;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 0 auto 20px;
            max-width: 320px;
            font-size: 14px;
            line-height: 1.5;
            text-align: left;
            position: relative;
            padding-left: 40px;
        }

        .error-message:before {
            content: '!';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background: #f56565;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 1px solid #008B8B;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.9);
            transition: border-color 0.3s;
            height: 42px;
            /* Fixed height */
        }

        .password-container {
            position: relative;
            width: 100%;
            margin: 10px 0;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 15px;
            margin: 0;
            border: 1px solid #008B8B;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.9);
            transition: border-color 0.3s;
            height: 42px;
            position: relative;
            font-family: inherit;
            /* Ensure consistent font */
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        .password-container input[type="text"]:focus {
            border-color: #006666;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 139, 139, 0.5);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            margin: 0;
            pointer-events: auto;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #008B8B;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background-color: #006666;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .links {
            margin-top: 15px;
        }

        .links a {
            color: #0095f6;
            text-decoration: none;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .social-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .social-links a {
            color: #666;
            font-size: 20px;
        }

        @media (max-width: 320px) {
            .nav-container {
                display: flex;
                flex-direction: row;

                align-items: center;
            }

            .nav-links {
                display: flex;
                flex-direction: row;
                gap: 1px;
            }

            .nav-links a {
                font-size: 11px;
                padding: 5px 8px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <img src="ccc curriculum evaluation logo.png" alt="CCC Logo" class="logo">
        <h1>City College of Calamba</h1>
        <h2>CCC CURRICULUM EVALUATION</h2>

        <?php if (!empty($error_message) || !empty($_SESSION['toast_error'])): ?>
            <div class="error-message">
                <?php
                $message = !empty($error_message) ? $error_message : $_SESSION['toast_error'];
                echo '<div>' . $message . '</div>';

                // remove toast error after showing it
                unset($_SESSION['toast_error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input 
    type="email" 
    name="email" 
    placeholder="Email (must be @ccc.edu.ph)" 
    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
    pattern="[a-zA-Z0-9._%+-]+@ccc\.edu\.ph$" 
    title="Please enter a valid @ccc.edu.ph email address"
    required
>

            <div class="password-container">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="password-toggle fas fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" class="button">LOGIN</button>
        </form>

        <div class="links">
            <a href="forgot_password.php">forget password</a>
        </div>

    </div>

    <!-- Toast container for flash messages -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="flashToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="flashToastBody"></div>

            </div>
        </div>

        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function(e) {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        </script>
</body>

</html>