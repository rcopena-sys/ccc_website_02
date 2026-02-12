<?php
// Include database configuration
require_once 'config.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Basic validation
    if (empty($email) || empty($message)) {
        echo "<script>alert('Please fill in all fields!'); window.history.back();</script>";
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!'); window.history.back();</script>";
        exit();
    }
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO feedback_db (email, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $message);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "<script>alert('Feedback submitted successfully! Thank you for your feedback.'); window.location.href='feedback.php';</script>";
    } else {
        echo "<script>alert('Error submitting feedback. Please try again.'); window.history.back();</script>";
    }
    
    $stmt->close();
} else {
    // If not POST request, redirect to feedback page
    header("Location: feedback.php");
    exit();
}

$conn->close();
?> 