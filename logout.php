<?php
// Start the session
session_start();

// Attempt to write a non-fatal logout entry to activity_log_db if DB connection is available
try {
    // include db connection if not already present
    if (!isset($conn) && file_exists(__DIR__ . '/db_connect.php')) {
        require_once __DIR__ . '/db_connect.php';
    }

    $logUserId = $_SESSION['user_id'] ?? null;
    $logUsername = $_SESSION['email'] ?? null;
    $logAction = 'Logout';
    
    // Get role name for more detailed logging
    $roleName = match($_SESSION['role_id'] ?? 0) {
        1 => 'Super Admin',
        2 => 'Admin',
        3 => 'Registrar',
        4 => 'DCI Student',
        5 => 'CS Student',
        default => 'Student'
    };
    
    // Create detailed logout message
    $logDetails = sprintf(
        'User logged out - %s %s (%s)',
        $_SESSION['firstname'] ?? 'Unknown',
        $_SESSION['lastname'] ?? 'User',
        $roleName
    );
    
    $logIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $logUa = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (isset($conn) && $conn) {
        $ls = $conn->prepare("INSERT INTO activity_log_db (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        if ($ls) {
            $ls->bind_param('isssss', $logUserId, $logUsername, $logAction, $logDetails, $logIp, $logUa);
            $ls->execute();
            $ls->close();
        }
    }
} catch (Exception $e) {
    // Non-fatal: log to error log and proceed
    error_log('Activity log insert failed (logout): ' . $e->getMessage());
}

// Optionally set a short-lived flash cookie to show a logout message on the login page
// (we set this before destroying the session so the message survives)
$flashMsg = 'You have been logged out successfully.';
setcookie('flash_msg', base64_encode($flashMsg), time() + 8, '/');

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to index.php
header("Location: index.php");
exit();
?>
