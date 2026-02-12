<?php
session_start();
require 'config.php';
date_default_timezone_set('Asia/Manila');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $otp   = trim($_POST['otp']);

    // Check if OTP is valid
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW()");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // OTP is valid → store email in session and redirect
        $_SESSION['reset_email'] = $email;
        header("Location: create_password.php");
        exit;
    } else {
        $error = "Invalid or expired OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="card mt-5" style="max-width:400px; margin:auto;">
        <div class="card-header bg-primary text-white text-center">
            <h4>Verify OTP</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Email">
                    <label for="email">Email address</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="otp" name="otp" required placeholder="Enter OTP">
                    <label for="otp">OTP Code</label>
                </div>
                <button type="submit" name="verify_otp" class="btn btn-primary w-100">Verify OTP</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
