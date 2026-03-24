<?php
require_once '../student/db_connect.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($title) || empty($date)) {
        $error = "Title and Date are required";
    } else {
        $stmt = $conn->prepare("INSERT INTO events (title, date, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $date, $description);
        
        if ($stmt->execute()) {
            echo "<script>
                // Get the current date
                var currentDate = new Date();
                var year = currentDate.getFullYear();
                var month = String(currentDate.getMonth() + 1).padStart(2, '0');
                var day = String(currentDate.getDate()).padStart(2, '0');
                var eventDate = year + '-' + month + '-' + day;

                // Redirect to calendar with success animation
                window.location.href = 'calendar.php?saved=' + eventDate;
            </script>";
        } else {
            echo "<script>
                alert('Error adding event: " . $conn->error . "');
                window.location.href = 'calendar.php';
            </script>";
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
    <title>Add Event - School Calendar</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .add-event-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #374151;
            margin: 0;
        }

        .form-header p {
            color: #6b7280;
            margin: 10px 0 0;
        }

        .event-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            color: #374151;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .form-submit {
            padding: 14px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-submit:hover {
            background: #2563eb;
        }

        .error-message {
            color: #ef4444;
            margin: 10px 0;
            padding: 10px;
            background: #fee2e2;
            border-radius: 6px;
        }

        .success-message {
            color: #10b981;
            margin: 10px 0;
            padding: 10px;
            background: #dcfce7;
            border-radius: 6px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="add-event-container">
        <div class="form-header">
            <h2>Add New Event</h2>
            <p>Fill in the details below to add a new event to the calendar</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="event-form" method="POST" action="">
            <div class="form-group">
                <label for="title">Event Title *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="date">Event Date *</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>

            <button type="submit" class="form-submit">Add Event</button>
        </form>

        <div class="back-link">
            <a href="calendar.php">← Back to Calendar</a>
        </div>
    </div>
</body>
</html>
