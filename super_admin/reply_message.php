<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is super admin (role_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM users WHERE id = ?";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin = $admin_stmt->get_result()->fetch_assoc();
$admin_stmt->close();

// Check if this is a reply to an existing message
$original_message = null;
$recipient_id = null;
$subject = '';

if (isset($_GET['reply_to']) && is_numeric($_GET['reply_to'])) {
    $reply_to = (int)$_GET['reply_to'];
    
    // Get the original message details
    $message_query = "SELECT m.*, s.firstname, s.lastname, s.email, s.id as user_id, s.student_id 
                     FROM messages_db m
                     JOIN signin_db s ON m.sender_id = s.student_id OR (m.sender_id = CONCAT('user_', s.id))
                     WHERE m.id = ? AND m.recipient_type = 'admin'";
    
    $message_stmt = $conn->prepare($message_query);
    $message_stmt->bind_param("i", $reply_to);
    $message_stmt->execute();
    $original_message = $message_stmt->get_result()->fetch_assoc();
    $message_stmt->close();
    
    if ($original_message) {
        $recipient_id = $original_message['sender_id'];
        // Add 'Re: ' prefix if not already present
        $subject = 'Re: ' . (strpos($original_message['subject'], 'Re: ') === 0 ? 
                   $original_message['subject'] : $original_message['subject']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $recipient_id = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : 0;
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    if (empty($errors)) {
        // Insert the reply into the database
        $insert_query = "INSERT INTO messages_db 
                        (sender_id, recipient_id, recipient_type, subject, message, status, created_at, updated_at) 
                        VALUES (?, ?, 'student', ?, ?, 'sent', NOW(), NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $admin_id = $admin['id'];
        $stmt->bind_param("iiss", $admin_id, $recipient_id, $subject, $message);
        
        if ($stmt->execute()) {
            // Mark the original message as replied if this is a reply
            if (isset($_POST['original_message_id'])) {
                $update_original = $conn->prepare("UPDATE messages_db SET status = 'replied', updated_at = NOW() WHERE id = ?");
                $update_original->bind_param("i", $_POST['original_message_id']);
                $update_original->execute();
                $update_original->close();
            }
            
            // Redirect to notifications with success message
            header("Location: notification.php?success=message_sent");
            exit();
        } else {
            $errors[] = 'Failed to send message. Please try again.';
            error_log("Error sending message: " . $stmt->error);
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($original_message) ? 'Reply to Message' : 'New Message'; ?> - Super Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .message-preview {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.25rem;
        }
        .message-preview h6 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .message-preview p {
            color: #6c757d;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Back Button -->
        <a href="notification.php" class="btn btn-outline-secondary mb-4">
            <i class="fas fa-arrow-left me-1"></i> Back to Notifications
        </a>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo isset($original_message) ? 'Reply to Message' : 'New Message'; ?></h4>
            </div>
            <div class="card-body">
                <?php if (isset($original_message)): ?>
                <div class="message-preview mb-4">
                    <h6>Original Message</h6>
                    <p><strong>From:</strong> <?php echo htmlspecialchars($original_message['firstname'] . ' ' . $original_message['lastname']); ?></p>
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($original_message['subject']); ?></p>
                    <p><strong>Message:</strong></p>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($original_message['message'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if (isset($original_message)): ?>
                        <input type="hidden" name="original_message_id" value="<?php echo $original_message['id']; ?>">
                        <input type="hidden" name="recipient_id" value="<?php echo $original_message['sender_id']; ?>">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">To</label>
                            <input type="text" class="form-control" id="recipient" 
                                   value="<?php echo htmlspecialchars($original_message['firstname'] . ' ' . $original_message['lastname'] . ' (' . $original_message['email'] . ')'); ?>" 
                                   disabled>
                        </div>
                    <?php else: ?>
                        <!-- Add recipient selection for new messages -->
                        <div class="mb-3">
                            <label for="recipient_id" class="form-label">To</label>
                            <select class="form-select" id="recipient_id" name="recipient_id" required>
                                <option value="">Select a student...</option>
                                <?php
                                // Fetch all students for the dropdown
                                $students_query = "SELECT student_id, firstname, lastname, email FROM signin_db ORDER BY lastname, firstname";
                                $students_result = $conn->query($students_query);
                                while ($student = $students_result->fetch_assoc()):
                                    $selected = ($student['student_id'] == $recipient_id) ? 'selected' : '';
                                    echo "<option value='{$student['student_id']}' $selected>";
                                    echo htmlspecialchars("{$student['lastname']}, {$student['firstname']} ({$student['email']})");
                                    echo "</option>";
                                endwhile;
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" 
                               value="<?php echo htmlspecialchars($subject); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="10" required placeholder="Type your message here..."><?php 
                            echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; 
                        ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="notification.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Make the textarea responsive and match the previous TinyMCE height */
        #message {
            min-height: 300px;
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        /* Add focus styling for better UX */
        #message:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</body>
</html>
<?php $conn->close(); ?>
