<?php
// index.php

// ... (Database connection code) ...

// Get form data
$email = $_POST['email'];
$password = $_POST['password']; // Remember to hash this password!
$userType = $_POST['user_type'];

// ... (Your existing login logic to verify credentials) ...

// Example: Conditional redirect based on user type
if ($userType == 'dean') {
    header("Location: dean_page.php"); // Redirect deans to dci.php
    exit;
} elseif ($userType == 'registrar') {
    header("Location: registrar_page.php"); // Redirect registrars to registrar_page.php
    exit;
} else {
    // Handle invalid user type or login failure
    echo "Invalid user type or login failed.";
}

// ... (Close database connection) ...
?>