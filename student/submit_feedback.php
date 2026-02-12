<?php
// Include database configuration
require_once 'db_connect.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $rating = isset($_POST['rating']) ? trim($_POST['rating']) : '';
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($rating) || empty($feedback)) {
        echo "<script>alert('Please fill in all fields!'); window.history.back();</script>";
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!'); window.history.back();</script>";
        exit();
    }
    
    // Validate rating
    if (!in_array($rating, ['1', '2', '3', '4', '5'])) {
        echo "<script>alert('Please select a valid rating!'); window.history.back();</script>";
        exit();
    }
    
    // Combine all feedback data into a message
    $message = "Name: " . $name . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Rating: " . str_repeat("⭐", intval($rating)) . " (" . $rating . "/5)\n";
    $message .= "Feedback: " . $feedback;
    
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