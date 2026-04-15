<?php
session_start();
require 'config.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: verify_otp.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email            = $_SESSION['reset_email'];

    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password and clear failed login attempts so the user can log in again
        $stmt = $conn->prepare("UPDATE signin_db SET password = ?, failed_attempts = 0, last_failed_attempt = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            // Clear session + reset requests
            $conn->query("DELETE FROM password_resets WHERE email = '{$email}'");
            unset($_SESSION['reset_email']);

            $success = "Password updated successfully. <a href='index.php'>Login now</a>";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
<div class="container">
    <div class="card mt-5" style="max-width:400px; margin:auto;">
        <div class="card-header bg-success text-white text-center">
            <h4>Create New Password</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-floating mb-3 password-wrapper">
                        <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="New Password">
                        <label for="new_password">New Password</label>
                        <i class="bi bi-eye toggle-password" data-target="new_password"></i>
                    </div>
                    <div class="form-floating mb-3 password-wrapper">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
                        <label for="confirm_password">Confirm Password</label>
                        <i class="bi bi-eye toggle-password" data-target="confirm_password"></i>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success w-100">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", function() {
        const target = document.getElementById(this.getAttribute("data-target"));
        if (target.type === "password") {
            target.type = "text";
            this.classList.remove("bi-eye");
            this.classList.add("bi-eye-slash");
        } else {
            target.type = "password";
            this.classList.remove("bi-eye-slash");
            this.classList.add("bi-eye");
        }
    });
});
</script>
</body>
</html>
