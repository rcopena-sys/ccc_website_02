<?php
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

// Test email function
function testEmail() {
    $mail = new PHPMailer(true);
    
    try {
        // Enable debugging
        $mail->SMTPDebug = 2; // Show detailed debug info
        $mail->Debugoutput = 'html';
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rozzo4968@gmail.com';
        $mail->Password   = 'elduyrelyltgjhdr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Set charset
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Recipients
        $mail->setFrom('rozzo4968@gmail.com', 'Test System');
        $mail->addAddress('rozzo4968@gmail.com', 'Test Recipient');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email - PHPMailer Configuration';
        
        $mail->Body    = "
            <html>
            <body>
                <h2>Test Email</h2>
                <p>This is a test email to verify PHPMailer configuration.</p>
                <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p>If you receive this email, PHPMailer is working correctly!</p>
            </body>
            </html>";
        
        $mail->AltBody = "This is a test email to verify PHPMailer configuration.";
        
        $mail->send();
        echo "✅ Email sent successfully!";
        return true;
        
    } catch (Exception $e) {
        echo "❌ Email sending failed: " . $mail->ErrorInfo;
        echo "<br>Error: " . $e->getMessage();
        return false;
    }
}

// Run test
echo "<h1>PHPMailer Test</h1>";
testEmail();
?>
