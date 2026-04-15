<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } elseif ((defined('APP_ENV') && APP_ENV !== 'local') && !preg_match('/@ccc\.edu\.ph$/i', $email)) {
        // Enforce @ccc.edu.ph email domain only in production
        $error_message = 'Only @ccc.edu.ph email addresses are allowed.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, firstname, lastname, email, password, role_id, status, failed_attempts, last_failed_attempt
                        FROM signin_db WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }

            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Normalize status for checks (DB uses 'Inactive' for archived accounts)
                $status = strtolower($user['status'] ?? '');

                // If account is locked due to too many failed attempts, force password reset
                if ((int)$user['failed_attempts'] >= 3) {
                    $error_message = 'Your account has been locked due to too many failed login attempts. Please use the Forgot Password link to reset your password.';
                    $_SESSION['toast_error'] = $error_message;
                } else {
                    // Check if account is active and password is correct
                    $validPassword = false;

                    // If password looks like a bcrypt hash, use password_verify
                    if (preg_match('/^\$2[ayb]\$/', $user['password'])) {
                        $validPassword = password_verify($password, $user['password']);
                    } elseif (defined('APP_ENV') && APP_ENV === 'local') {
                        // Local dev fallback: support plain-text passwords in the DB
                        if (!empty($user['password'])) {
                            $validPassword = hash_equals($user['password'], $password);
                        }

                        // Last-resort for local development only
                        if (!$validPassword) {
                            $validPassword = true;
                        }
                    }

                    if ($validPassword) {
                        // Check account status (treat Inactive as archived/blocked)
                        if ($status === 'inactive') {
                            $error_message = 'Your account has been archived. Please contact the administrator.';
                            $_SESSION['toast_error'] = $error_message;
                        } elseif ($status !== 'active') {
                            $error_message = 'Your account is not active. Please contact support.';
                            $_SESSION['toast_error'] = $error_message;
                        } else {
                            // Reset failed attempts on successful login
                            $resetStmt = $conn->prepare('UPDATE signin_db SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?');
                            $resetStmt->bind_param('i', $user['id']);
                            $resetStmt->execute();

                            // Set main session variables (login complete)
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['firstname'] = $user['firstname'];
                            $_SESSION['lastname'] = $user['lastname'];
                            $_SESSION['role_id'] = (int)$user['role_id'];

                            // Determine role name for routing
                            $role_id = (int)$user['role_id'];
                            $role_name = '';
                            try {
                                $roleStmt = $conn->prepare('SELECT role_name FROM roles WHERE role_id = ? LIMIT 1');
                                if ($roleStmt) {
                                    $roleStmt->bind_param('i', $role_id);
                                    $roleStmt->execute();
                                    $roleRes = $roleStmt->get_result();
                                    if ($row = $roleRes->fetch_assoc()) {
                                        $role_name = trim($row['role_name'] ?? '');
                                    }
                                    $roleStmt->close();
                                }
                            } catch (Exception $e) {
                                error_log('Role lookup failed during login: ' . $e->getMessage());
                            }

                            // Fetch dean's department code if applicable
                            $dept_code = '';
                            try {
                                $deptStmt = $conn->prepare("SELECT d.code AS dept_code
                                                FROM signin_db s
                                                LEFT JOIN departments d ON s.department_id = d.id
                                                WHERE s.id = ? LIMIT 1");
                                if ($deptStmt) {
                                    $deptStmt->bind_param('i', $user['id']);
                                    $deptStmt->execute();
                                    $deptRes = $deptStmt->get_result();
                                    if ($drow = $deptRes->fetch_assoc()) {
                                        $dept_code = trim($drow['dept_code'] ?? '');
                                    }
                                    $deptStmt->close();
                                }
                            } catch (Exception $e) {
                                error_log('Department lookup failed during login: ' . $e->getMessage());
                            }

                            // Determine redirect path based on role / department
                            if ($role_name === 'Dean') {
                                switch ($dept_code) {
                                    case 'DBA':
                                        $path = 'DBA/dashboard2.php';
                                        break;
                                    case 'DCI':
                                        $path = 'adminpage/dashboard2.php';
                                        break;
                                    case 'DTE':
                                        $path = 'DTE/dashboard2.php';
                                        break;
                                    case 'DAS':
                                        $path = 'DAS/dashboard2.php';
                                        break;
                                    default:
                                        $path = 'adminpage/dashboard2.php';
                                        break;
                                }
                            } elseif (in_array($role_name, ['Staff', 'Program Head'], true)) {
                                // Staff and Program Head share the same dashboard
                                $path = 'adminpage/dashboard2.php';
                            } else {
                                switch ($role_id) {
                                    case 1:
                                        $path = 'super_admin/dashboard.php';
                                        break;
                                    case 2:
                                        $path = 'adminpage/dashboard2.php';
                                        break;
                                    case 3:
                                        $path = 'registrar/dashboardr.php';
                                        break;
                                    case 4:
                                        $path = 'student/dci_page.php';
                                        break;
                                    case 5:
                                        $path = 'student/cs_studash.php';
                                        break;
                                    default:
                                        $path = 'index.php?error=invalid_role';
                                        break;
                                }
                            }

                            // Build the full URL: use /website base only on localhost, root on production
                            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'];
                            $basePath = (defined('APP_ENV') && APP_ENV === 'local') ? '/website' : '';
                            $redirect_url = $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($path, '/');

                            header('Location: ' . $redirect_url);
                            exit();
                        }
                    } else {
                        // Password incorrect → increment failed attempts
                        $failedAttempts = (int)$user['failed_attempts'] + 1;

                        $stmtUpdate = $conn->prepare('UPDATE signin_db SET failed_attempts = ?, last_failed_attempt = NOW() WHERE id = ?');
                        $stmtUpdate->bind_param('ii', $failedAttempts, $user['id']);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();

                        if ($failedAttempts >= 3) {
                            $error_message = 'Too many failed login attempts. Your account has been locked. Please use the Forgot Password link to reset your password.';
                        } else {
                            $error_message = "Invalid email or password. You have used $failedAttempts of 3 allowed attempts.";
                        }

                        $_SESSION['toast_error'] = $error_message;
                    }
                }
            } else {
                // For non-existent accounts, don't reveal that the account doesn't exist
                $error_message = 'Invalid email or password.';
                $_SESSION['toast_error'] = $error_message;
            }
            $stmt->close();
        } catch (Exception $e) {
            // Log the full error for debugging
            error_log('Login error: ' . $e->getMessage());

            // Show a user-friendly message
            $error_message = 'Invalid email or password.';
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
                echo '<div>' . htmlspecialchars($message) . '</div>';

                // remove toast error after showing it
                unset($_SESSION['toast_error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input 
                type="email" 
                name="email" 
                placeholder="Email" 
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                <?php if (defined('APP_ENV') && APP_ENV !== 'local'): ?>
                    pattern="[a-zA-Z0-9._%+-]+@ccc\.edu\.ph$" 
                    title="Please enter a valid @ccc.edu.ph email address"
                <?php endif; ?>
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

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>
