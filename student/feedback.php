<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Initialize student data
$student = [
    'firstname' => '',
    'lastname' => '',
    'studentnumber' => '',
    'course' => 'BSIT',
    'academic_year' => ''
];

// Fetch student info from the database
$studentnumber = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_id, academic_year, course FROM signin_db WHERE student_id = ?");
$stmt->bind_param("s", $studentnumber);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student['firstname'] = $row['firstname'];
    $student['lastname'] = $row['lastname'];
    $student['studentnumber'] = $row['student_id'];
    $student['academic_year'] = $row['academic_year'];
    $student['course'] = $row['course'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - DCI</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .form-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .submit-btn {
            background: linear-gradient(135deg, #4f46e5, #60a5fa);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="bg-blue-100 min-h-screen">
    <!-- Back Button -->
    <a href="<?php echo ($student['course'] === 'BSCS' ? 'cs_studash.php' : 'dci_page.php'); ?>" class="fixed top-4 right-4 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full shadow-lg flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
    </a>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">Feedback Form</h1>
                <p class="text-gray-600">We value your feedback to improve our services.</p>
            </div>

            <div class="form-section">
                <form action="submit_feedback.php" method="POST" class="space-y-6">
                    <div class="form-group">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div class="form-group">
                        <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                        <select id="rating" name="rating" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Rating</option>
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Very Good)</option>
                            <option value="3">⭐⭐⭐ (Good)</option>
                            <option value="2">⭐⭐ (Fair)</option>
                            <option value="1">⭐ (Poor)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">Your Feedback</label>
                        <textarea id="feedback" name="feedback" rows="6" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="submit-btn">
                            Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>
