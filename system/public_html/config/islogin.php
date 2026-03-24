<?php
session_start();

function isLogin() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

if (!isLogin()) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$g_user_role = getUserRole();
?>