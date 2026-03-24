<?php
session_start();
// Check if user is logged in and is an admin (role_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// Mark all feedback as read when viewing the page
$conn->query("UPDATE feedback_db SET is_read = 1");

// Get all feedback
$feedback_query = "SELECT * FROM feedback_db ORDER BY created_at DESC";
$feedback_result = $conn->query($feedback_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Feedback Management</h1>
            <a href="dashboard2.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto p-4">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">All Feedback</h2>
                <p class="text-gray-600">View and manage student feedback submissions</p>
            </div>
            
            <div class="divide-y divide-gray-200">
                <?php if ($feedback_result->num_rows > 0): ?>
                    <?php while ($feedback = $feedback_result->fetch_assoc()): 
                        // Parse the message to extract individual fields
                        $message = $feedback['message'];
                        $lines = explode("\n", $message);
                        $feedback_data = [];
                        
                        foreach ($lines as $line) {
                            $parts = explode(": ", $line, 2);
                            if (count($parts) === 2) {
                                $key = trim($parts[0]);
                                $value = trim($parts[1]);
                                $feedback_data[$key] = $value;
                            }
                        }
                        
                        $rating = isset($feedback_data['Rating']) ? $feedback_data['Rating'] : 'N/A';
                        $email = $feedback['email'];
                        $created_at = new DateTime($feedback['created_at']);
                        $formatted_date = $created_at->format('F j, Y \a\t g:i A');
                    ?>
                    <div class="p-4 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800">
                                    <?php echo htmlspecialchars($feedback_data['Name'] ?? 'Anonymous'); ?>
                                    <span class="text-sm text-gray-500 ml-2">
                                        &lt;<?php echo htmlspecialchars($email); ?>&gt;
                                    </span>
                                </h3>
                                <div class="flex items-center mt-1">
                                    <span class="text-yellow-400">
                                        <?php 
                                        if (preg_match('/(\d+)\/5/', $rating, $matches)) {
                                            $stars = (int)$matches[1];
                                            echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                                            echo " ($rating)";
                                        } else {
                                            echo $rating;
                                        }
                                        ?>
                                    </span>
                                    <span class="mx-2 text-gray-300">•</span>
                                    <span class="text-sm text-gray-500"><?php echo $formatted_date; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($feedback_data['Feedback'])): ?>
                            <div class="mt-3 text-gray-700 bg-gray-50 p-3 rounded-lg">
                                <?php echo nl2br(htmlspecialchars($feedback_data['Feedback'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                        <p>No feedback submissions yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize any interactive elements here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Add any JavaScript functionality here
        });
    </script>
</body>
</html>
