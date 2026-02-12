<?php
// Simple email test using PHP mail function
$to = 'rozzo4968@gmail.com';
$subject = 'Test Email - Simple PHP Mail';
$message = 'This is a test email to verify PHP mail function is working.';
$headers = 'From: test@localhost' . "\r\n" .
           'Reply-To: test@localhost' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo '✅ PHP mail function works!';
} else {
    echo '❌ PHP mail function failed.';
}

echo '<br>';

// Test PHPMailer
try {
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'rozzo4968@gmail.com';
    $mail->Password   = 'elduyrelyltgjhdr';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    
    $mail->setFrom('rozzo4968@gmail.com', 'Test');
    $mail->addAddress('rozzo4968@gmail.com', 'Test Recipient');
    
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = 'This is a test email from PHPMailer.';
    
    $mail->send();
    echo '✅ PHPMailer works!';
    
} catch (Exception $e) {
    echo '❌ PHPMailer failed: ' . $e->getMessage();
}
?>
