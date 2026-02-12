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

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Check if it's an AJAX request for events
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $sql = "SELECT * FROM calendar";
    $result = $conn->query($sql);
    $events = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = array(
                'id' => $row['id'],
                'title' => $row['title'],
                'date' => $row['event_date'],
                'description' => $row['description']
            );
        }
    }
    header('Content-Type: application/json');
    echo json_encode($events);
    exit();
}

// Handle event creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_event') {
    $title = $conn->real_escape_string($_POST['title']);
    $date = $conn->real_escape_string($_POST['date']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql = "INSERT INTO calendar (title, event_date, description) VALUES ('$title', '$date', '$description')";
    
    $response = array();
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = "Event created successfully";
        $response['eventId'] = $conn->insert_id;
    } else {
        $response['success'] = false;
        $response['message'] = "Error: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle event deletion


// Initial events load
$sql = "SELECT * FROM calendar";
$result = $conn->query($sql);
$events = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = array(
            'id' => $row['id'],
            'title' => $row['title'],
            'date' => $row['event_date'],
            'description' => $row['description']
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Events - City College of Calamba</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-image: url('schol.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            padding: 30px 10px 10px 10px;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1100;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            transition: left 0.3s cubic-bezier(.4,0,.2,1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 280px;
                left: -300px;
            }
            .sidebar.active {
                left: 0;
            }
            .profile-image {
                width: 90px;
                height: 90px;
            }
            .sidebar-name {
                font-size: 1rem;
            }
            .sidebar-label {
                font-size: 0.85rem;
            }
            .sidebar-value {
                font-size: 0.9rem;
            }
            .sidebar .btn {
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            .sidebar-clock {
                font-size: 0.9rem;
                padding: 3px 8px;
            }
        }
        
        @media (max-width: 360px) {
            .sidebar {
                width: 260px;
                left: -280px;
            }
            .profile-image {
                width: 80px;
                height: 80px;
            }
            .sidebar-name {
                font-size: 0.9rem;
            }
            .sidebar-label {
                font-size: 0.8rem;
            }
            .sidebar-value {
                font-size: 0.85rem;
            }
            .sidebar .btn {
                font-size: 0.85rem;
                padding: 6px 10px;
            }
        }
        .sidebar.collapsed {
            left: -260px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s cubic-bezier(.4,0,.2,1);
        }
        .main-content.collapsed {
            margin-left: 0;
        }
        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1200;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
            transition: background 0.2s, transform 0.2s;
        }
        
        @media (max-width: 480px) {
            .menu-toggle {
                width: 40px;
                height: 40px;
                font-size: 1.3rem;
                top: 10px;
                left: 10px;
            }
        }
        
        @media (max-width: 360px) {
            .menu-toggle {
                width: 36px;
                height: 36px;
                font-size: 1.2rem;
                top: 8px;
                left: 8px;
            }
        }
        .menu-toggle:focus, .menu-toggle:hover {
            background: #0b5ed7;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.active {
                left: 0;
            }
            .main-content {
                margin-left: 0;
                padding: 10px;
                width: 100%;
            }
            .main-content.overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.25);
                z-index: 1090;
                overflow: hidden;
            }
            .calendar-container {
                padding: 10px 5px;
                margin: 0 auto;
            }
            .month-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .month-box {
                padding: 10px 5px;
            }
            .event-list {
                font-size: 0.95rem;
                margin-bottom: 15px;
            }
            .year-nav {
                margin-bottom: 15px;
            }
            #current-year {
                font-size: 1.5rem;
                margin: 0 10px;
            }
            .year-btn {
                font-size: 1rem;
                padding: 8px 12px;
            }
        }
        .profile-image {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: #fff;
            margin-bottom: 10px;
        }
        .sidebar-name {
            font-family: 'Segoe UI Semibold', 'Arial', sans-serif;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .sidebar-label {
            font-size: 0.98rem;
            color: #e0e0e0;
            font-weight: 500;
        }
        .sidebar-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 600;
        }
        .btn-danger {
            background: #dc3545 !important;
            border: none !important;
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn-danger:hover, .btn-danger:focus {
            background: #b02a37 !important;
            color: #fff !important;
        }
        .btn-outline-light {
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar-clock {
            font-size: 1.1rem;
            color: #fff;
            background: rgba(0,0,0,0.09);
            border-radius: 8px;
            padding: 4px 12px;
            display: inline-block;
            margin-top: 10px;
            font-family: 'Consolas', 'Courier New', monospace;
            letter-spacing: 1px;
        }
        .sidebar-link {
            text-decoration: none !important;
            font-size: 1.1rem;
        }
        .sidebar {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            width: 100%;
        }
        
        @media (max-width: 767.98px) {
            .container {
                width: 100%;
                padding: 0 5px;
            }
        }
        .content-wrapper {
            background-color: rgba(255, 255, 255, 0.9);
            min-height: 40vh;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 30px 20px;
        }
        .year-card {
            transition: transform 0.2s;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 16px;
        }
        .year-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 6px 32px 0 rgba(31,38,135,0.18);
        }
        .sidebar .btn {
            font-weight: 500;
        }
        .sidebar .btn-danger {
            margin-top: 10px;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #4f8cff 0%, #1cefff 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }
        .btn-gradient:hover, .btn-gradient:focus {
            background: linear-gradient(90deg, #1cefff 0%, #4f8cff 100%);
            color: #fff;
            transform: scale(1.05);
            box-shadow: 0 4px 18px rgba(31,38,135,0.16);
        }
        
        /* Calendar Styles */
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
        }
        .year-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .year-btn {
            background: none;
            border: none;
            color: #6c757d;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 10px;
        }
        .year-btn:hover {
            color: #0d6efd;
        }
        /* Month grid responsive */
.month-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    width: 100%;
}

@media (max-width: 991.98px) {
    .month-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}

@media (max-width: 767.98px) {
    .month-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

        .month-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 0;
        }
        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        .has-event {
            background-color: #ff9800 !important;
            color: white !important;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .has-event:hover {
            transform: scale(1.1);
        }
        .event-list {
            width: 100%;
            margin-bottom: 24px;
        }
        .event-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        @media (max-width: 991.98px) {
            .calendar-container {
                padding: 10px 2px;
            }
            .month-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .month-box {
                padding: 12px 6px;
            }
            .event-list {
                font-size: 0.98rem;
            }
        }
        @media (max-width: 767.98px) {
            .calendar-container {
                padding: 15px 10px;
                margin: 0 auto;
                max-width: 95%;
                width: 95%;
                max-height: 80vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .month-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                width: 100%;
            }
            .month-box {
                padding: 15px 10px;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }
            .days-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
                width: 100%;
                max-width: 100%;
            }
            .days-grid > div {
                font-size: 0.85rem;
                padding: 4px 2px;
                min-height: 28px;
                text-align: center;
            }
            .days-grid > div[style*="font-weight: bold"] {
                font-size: 0.75rem;
                padding: 6px 2px;
            }
            .event-list {
                font-size: 0.9rem;
                margin-bottom: 15px;
                width: 100%;
            }
            .event-item {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            .year-nav {
                flex-direction: column;
                gap: 10px;
                margin-bottom: 15px;
                width: 100%;
            }
            #current-year {
                font-size: 1.3rem;
                margin: 0;
            }
            .year-btn {
                font-size: 0.9rem;
                padding: 6px 15px;
                align-self: center;
            }
            .content-wrapper {
                padding: 15px 10px;
                border-radius: 8px;
                margin: 0 5px;
            }
            .calendar-container h2 {
                font-size: 1.5rem;
                margin: 0 0 15px 0;
                text-align: center;
            }
            .event-list h3 {
                font-size: 1.2rem;
                margin-bottom: 10px;
                text-align: center;
            }
        }
        @media (max-width: 575.98px) {
            .calendar-container {
                padding: 10px 5px;
                margin: 0 auto;
                width: 98%;
                max-width: 98%;
            }
            .month-box {
                padding: 10px 5px;
                width: 100%;
            }
            .event-list {
                font-size: 0.85rem;
                margin-bottom: 10px;
                width: 100%;
            }
            .event-item {
                padding: 6px 8px;
                font-size: 0.8rem;
            }
            .days-grid > div {
                font-size: 0.75rem;
                padding: 3px 1px;
                min-height: 24px;
            }
            .days-grid > div[style*="font-weight: bold"] {
                font-size: 0.7rem;
                padding: 4px 1px;
            }
            .month-box h3 {
                font-size: 1rem;
                margin: 0 0 10px 0;
            }
            .calendar-container h2 {
                font-size: 1.3rem;
                margin: 0 0 10px 0;
                text-align: center;
            }
            .event-list h3 {
                font-size: 1.1rem;
                margin-bottom: 8px;
                text-align: center;
            }
            #current-year {
                font-size: 1.1rem;
            }
            .year-btn {
                font-size: 0.8rem;
                padding: 5px 10px;
            }
            .content-wrapper {
                padding: 10px 5px;
                margin: 0 3px;
            }
            .has-event {
                transform: scale(1.05);
            }
        }
        
        @media (max-width: 480px) {
            .calendar-container {
                padding: 8px 3px;
                margin: 0 auto;
                width: 99%;
                max-width: 99%;
            }
            .month-box {
                padding: 8px 3px;
                width: 100%;
            }
            .month-box h3 {
                font-size: 0.9rem;
            }
            .event-item {
                padding: 5px 6px;
                font-size: 0.75rem;
            }
            .days-grid > div {
                font-size: 0.7rem;
                padding: 2px 1px;
                min-height: 22px;
            }
            .days-grid > div[style*="font-weight: bold"] {
                font-size: 0.65rem;
                padding: 3px 1px;
            }
            .calendar-container h2 {
                font-size: 1.2rem;
                text-align: center;
            }
            .event-list h3 {
                font-size: 1rem;
                text-align: center;
            }
            .content-wrapper {
                margin: 0 2px;
                padding: 8px 3px;
            }
        }
        
        @media (max-width: 360px) {
            .calendar-container {
                padding: 5px 2px;
                margin: 0 auto;
                width: 100%;
                max-width: 100%;
            }
            .days-grid {
                gap: 2px;
            }
            .days-grid > div {
                font-size: 0.65rem;
                padding: 1px;
                min-height: 20px;
            }
            .days-grid > div[style*="font-weight: bold"] {
                font-size: 0.6rem;
                padding: 2px 1px;
            }
            .month-box {
                padding: 5px 2px;
                width: 100%;
            }
            .month-box h3 {
                font-size: 0.85rem;
                margin: 0 0 8px 0;
            }
            .event-item {
                padding: 4px 5px;
                font-size: 0.7rem;
            }
            .calendar-container h2 {
                font-size: 1.1rem;
                text-align: center;
            }
            .event-list h3 {
                font-size: 0.95rem;
                text-align: center;
            }
            .content-wrapper {
                margin: 0 1px;
                padding: 5px 2px;
            }
        }
        
        /* Event Details Modal Responsive Styles */
        .event-details {
            display: none !important;
        }
        .event-details.active {
            display: flex !important;
        }
        
        @media (max-width: 767.98px) {
            .event-details div[style*="background: white"] {
                width: 95% !important;
                padding: 15px !important;
                margin: 10px !important;
            }
            .event-details h3 {
                font-size: 1.2rem !important;
            }
            .event-details p {
                font-size: 0.9rem !important;
            }
            .event-details .close-btn {
                font-size: 1.3rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .event-details div[style*="background: white"] {
                width: 98% !important;
                padding: 12px !important;
                margin: 5px !important;
            }
            .event-details h3 {
                font-size: 1.1rem !important;
            }
            .event-details p {
                font-size: 0.85rem !important;
            }
            .event-details .close-btn {
                font-size: 1.2rem !important;
            }
        }
        
        @media (max-width: 360px) {
            .event-details div[style*="background: white"] {
                width: 99% !important;
                padding: 10px !important;
                margin: 2px !important;
            }
            .event-details h3 {
                font-size: 1rem !important;
            }
            .event-details p {
                font-size: 0.8rem !important;
            }
            .event-details .close-btn {
                font-size: 1.1rem !important;
            }
        }
        
        /* Touch-friendly improvements */
        .has-event:hover, .has-event:focus {
            transform: scale(1.1);
        }
        
        @media (hover: none) and (pointer: coarse) {
            .has-event:active {
                transform: scale(1.15);
                background-color: #e68900 !important;
            }
            .year-btn:active {
                background-color: #f8f9fa;
                border-radius: 4px;
            }
            .menu-toggle:active {
                transform: scale(0.95);
            }
        }
        
        /* Better mobile scrolling */
        @media (max-width: 767.98px) {
            .calendar-container {
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }
            .month-box {
                scroll-behavior: smooth;
            }
        }
        
        /* Prevent horizontal scroll on mobile */
        @media (max-width: 767.98px) {
            body {
                overflow-x: hidden;
            }
            .main-content {
                overflow-x: hidden;
            }
            .calendar-container {
                overflow-x: hidden;
            }
        }
        
        /* Loading states for mobile */
        @media (max-width: 767.98px) {
            .loading {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #0d6efd;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <link href='https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.2.1/css/fontawesome.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/main.min.css' rel='stylesheet' />
</head>
<body>
    <!-- Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
    </button>
    <!-- Sidebar -->
<div class="sidebar d-flex flex-column align-items-center" id="sidebar">
    <!-- Profile and Info -->
    <div class="w-100 text-center">
        <div id="profile-section" class="mb-4">
            <form id="profileForm" action="upload_profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_picture" id="profileInput" accept="image/*" onchange="document.getElementById('profileForm').submit();">
            </form>
            <img src="profile_pictures/default.jpg" alt="Student Profile" class="profile-image mb-2" id="profileImage" style="cursor:pointer;">
            <h4 class="fw-bold mt-2 mb-1 sidebar-name">
                <?php
                    if (!empty($student['lastname']) && !empty($student['firstname'])) {
                        echo htmlspecialchars($student['lastname'] . ', ' . $student['firstname']);
                    } else {
                        echo "Student Name";
                    }
                ?>
            </h4>
            <div>
                <span class="sidebar-label">Student Number:</span>
                <span class="sidebar-value"><?php echo htmlspecialchars($student['studentnumber']); ?></span>
            </div>
            <div id="sidebar-clock" class="sidebar-clock mt-3"></div>
        </div>
        <div class="mt-4">
            <a href="<?php echo ($student['course'] === 'BSCS' ? 'cs_studash.php' : 'dci_page.php'); ?>" class="btn btn-gradient w-100">
                <i class="bi bi-house-door me-2"></i>Dashboard
            </a>
        </div>

          <!-- Dashboard, Feedback and Logout Buttons -->
          <div class="d-grid gap-3 w-100 mt-4 px-2">
    <a href="calendar_events.php" class="btn btn-outline-light fw-semibold sidebar-link">Calendar Events</a>
    <a href="feedback.php" class="btn btn-outline-light fw-semibold sidebar-link">Feedback</a>
    <a href="logout.php" class="btn btn-danger fw-bold sidebar-link mt-2">Logout</a>
</div>
    </div>
</div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
    <script>
    // Sidebar toggle logic
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const menuToggle = document.getElementById('menuToggle');
        function closeSidebar() {
            sidebar.classList.remove('active');
            mainContent.classList.remove('overlay');
        }
        function openSidebar() {
            sidebar.classList.add('active');
            mainContent.classList.add('overlay');
        }
        function isMobile() {
            return window.innerWidth <= 991.98;
        }
        menuToggle.addEventListener('click', function() {
            if (isMobile()) {
                if (sidebar.classList.contains('active')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
            }
        });
        // Close sidebar overlay when clicking outside on mobile
        mainContent.addEventListener('click', function(e) {
            if (isMobile() && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
        // Responsive reset on resize
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('overlay');
            }
        });
    });
    </script>
        <div class="container">
            <div class="content-wrapper">
                <div class="calendar-container" style="display: flex; flex-direction: column; align-items: center;">
                    <h2 style="color: #374151; margin: 0 0 20px 0;">Calendar of Events</h2>
                    
                  
                    <!-- Year Navigation -->
                    <div class="year-nav" style="margin-bottom: 20px;">
                        <button class="year-btn prev" onclick="changeYear(-1)">&lt;</button>
                        <span id="current-year" style="font-size: 2rem; margin: 0 20px;">2025</span>
                        <button class="year-btn next" onclick="changeYear(1)">&gt;</button>
                    </div>

                    <!-- Event List -->
                    <div class="event-list">
                        <h3>Current Events</h3>
                        <div id="events-container"></div>
                    </div>

                    <!-- Month Grid -->
                    <div class="month-grid">
                        <?php
                        $months = array(
                            'January', 'February', 'March',
                            'April', 'May', 'June',
                            'July', 'August', 'September',
                            'October', 'November', 'December'
                        );
                        foreach ($months as $month) {
                            echo '<div class="month-box" style="background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <h3 style="color: #374151; margin: 0 0 15px 0;">' . $month . '</h3>
                                <div class="days-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px;">
                                    <div style="text-align: center; font-weight: bold;">Sun</div>
                                    <div style="text-align: center; font-weight: bold;">Mon</div>
                                    <div style="text-align: center; font-weight: bold;">Tue</div>
                                    <div style="text-align: center; font-weight: bold;">Wed</div>
                                    <div style="text-align: center; font-weight: bold;">Thu</div>
                                    <div style="text-align: center; font-weight: bold;">Fri</div>
                                    <div style="text-align: center; font-weight: bold;">Sat</div>
                                </div>
                            </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div class="event-details" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
            <div style="background: white; padding: 20px; border-radius: 10px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 id="event-title" style="margin: 0; color: #374151;"></h3>
                    <button class="close-btn" onclick="closeEventDetails()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>
                <p id="event-date" style="margin: 10px 0; color: #6c757d;"></p>
                <div id="event-description" style="margin: 15px 0; color: #374151;"></div>
            </div>
        </div>
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: [
                    {
                        title: 'Enrollment Period',
                        start: '2025-05-29',
                        end: '2025-06-05',
                        color: '#4CAF50',
                        description: 'Annual enrollment period for new and returning students'
                    },
                    {
                        title: 'Midterm Exams',
                        start: '2025-07-15',
                        end: '2025-07-20',
                        color: '#FF9800',
                        description: 'Midterm examinations for all courses'
                    },
                    {
                        title: 'Final Exams',
                        start: '2025-11-10',
                        end: '2025-11-15',
                        color: '#F44336',
                        description: 'Final examinations for all courses'
                    },
                    {
                        title: 'Graduation',
                        start: '2025-12-15',
                        color: '#2196F3',
                        description: 'Annual graduation ceremony'
                    }
                ],
                eventClick: function(info) {
                    const details = document.querySelector('.event-details');
                    const closeBtn = document.querySelector('.close-btn');
                    const title = document.getElementById('event-title');
                    const date = document.getElementById('event-date');
                    const description = document.getElementById('event-description');

                    title.textContent = info.event.title;
                    date.textContent = `Date: ${info.event.start.toLocaleDateString()}`;
                    description.textContent = info.event.extendedProps.description;
                    details.classList.add('active');

                    closeBtn.onclick = () => {
                        details.classList.remove('active');
                    };
                }
            });
            calendar.render();
        });
    </script>
    <script>
        // Initialize year
        let currentYear = 2025;

        // Function to change year
        function changeYear(amount) {
            currentYear += amount;
            document.getElementById('current-year').textContent = currentYear;
            updateMonthDays();
        }

        // Initialize events from PHP
        let events = <?php echo json_encode($events); ?>;

        function showEventForm() {
            document.getElementById('eventForm').classList.add('active');
            document.getElementById('eventTitle').focus();
        }

        function closeEventForm() {
            document.getElementById('eventForm').classList.remove('active');
            document.getElementById('createEventForm').reset();
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        }

        function handleSubmit(event) {
            event.preventDefault();
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            
            const title = document.getElementById('eventTitle').value.trim();
            const date = document.getElementById('eventDate').value;
            const description = document.getElementById('eventDescription').value.trim();
            
            // Validate inputs
            let hasError = false;
            
            if (!title) {
                document.getElementById('titleError').style.display = 'block';
                hasError = true;
            }
            
            if (!date) {
                document.getElementById('dateError').style.display = 'block';
                hasError = true;
            }
            
            if (hasError) {
                return;
            }

            // Disable save button while processing
            const saveBtn = document.getElementById('saveEventBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData();
            formData.append('action', 'create_event');
            formData.append('title', title);
            formData.append('date', date);
            formData.append('description', description);

            fetch('calendar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Refresh the events list from the server
                    return fetch('calendar.php', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Error creating event');
                }
            })
            .then(res => res.json())
            .then(newEvents => {
                events = newEvents;
                updateMonthDays();
                displayEvents();
                
                // Show success message
                alert('Event successfully created!');
                
                // Reset form
                document.getElementById('createEventForm').reset();
                
                // Close form
                closeEventForm();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating event: ' + error.message);
            })
            .finally(() => {
                // Re-enable save button
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Event';
            });
        }

        function deleteEvent(eventId) {
            if (!confirm('Are you sure you want to delete this event?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete_event');
            formData.append('eventId', eventId);

            fetch('calendar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Refresh the events list from the server
                    fetch('calendar.php', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(newEvents => {
                        events = newEvents;
                        updateMonthDays();
                        displayEvents();
                        alert('Event deleted successfully!');
                    });
                } else {
                    alert('Error deleting event: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting event. Please try again.');
            });
        }

        function displayEvents() {
            const container = document.getElementById('events-container');
            container.innerHTML = '';

            events.forEach(event => {
                const eventElement = document.createElement('div');
                eventElement.className = 'event-item';
                eventElement.innerHTML = `
                    <div>
                        <strong>${event.title}</strong> - ${new Date(event.date).toLocaleDateString()}
                    </div>

                `;
                container.appendChild(eventElement);
            });
        }

        // Modify updateMonthDays function to highlight days with events
        function updateMonthDays() {
            const months = [
                'January', 'February', 'March',
                'April', 'May', 'June',
                'July', 'August', 'September',
                'October', 'November', 'December'
            ];

            months.forEach((month, monthIndex) => {
                const monthBox = document.querySelector(`.month-box:nth-child(${monthIndex + 1}) .days-grid`);
                if (monthBox) {
                    // Clear existing days
                    monthBox.innerHTML = `
                        <div style="text-align: center; font-weight: bold;">Sun</div>
                        <div style="text-align: center; font-weight: bold;">Mon</div>
                        <div style="text-align: center; font-weight: bold;">Tue</div>
                        <div style="text-align: center; font-weight: bold;">Wed</div>
                        <div style="text-align: center; font-weight: bold;">Thu</div>
                        <div style="text-align: center; font-weight: bold;">Fri</div>
                        <div style="text-align: center; font-weight: bold;">Sat</div>
                    `;

                    const date = new Date(currentYear, monthIndex, 1);
                    const firstDay = date.getDay();
                    const daysInMonth = new Date(currentYear, monthIndex + 1, 0).getDate();

                    // Add empty cells for days before first day
                    for (let i = 0; i < firstDay; i++) {
                        monthBox.innerHTML += '<div style="background: #f8f9fa;"></div>';
                    }

                    // Add days of the month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const dayCell = document.createElement('div');
                        dayCell.textContent = day;
                        dayCell.style.textAlign = 'center';
                        
                        // Check if there are events on this day
                        const currentDate = new Date(currentYear, monthIndex, day).toISOString().split('T')[0];
                        const hasEvent = events.some(event => event.date === currentDate);
                        
                        if (hasEvent) {
                            dayCell.classList.add('has-event');
                            dayCell.onclick = () => showEventsForDay(currentDate);
                        }

                        // Highlight current date
                        const today = new Date();
                        if (today.getDate() === day && 
                            today.getMonth() === monthIndex && 
                            today.getFullYear() === currentYear) {
                            dayCell.style.backgroundColor = '#4CAF50';
                            dayCell.style.color = 'white';
                            dayCell.style.borderRadius = '50%';
                        }

                        monthBox.appendChild(dayCell);
                    }
                }
            });
        }

        function showEventsForDay(date) {
            const dayEvents = events.filter(event => event.date === date);
            if (dayEvents.length > 0) {
                const details = document.querySelector('.event-details');
                const title = document.getElementById('event-title');
                const dateEl = document.getElementById('event-date');
                const description = document.getElementById('event-description');

                title.textContent = 'Events for ' + new Date(date).toLocaleDateString();
                dateEl.textContent = dayEvents.length + ' event(s)';
                description.innerHTML = dayEvents.map(event => 
                    `<div style="margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                        <strong>${event.title}</strong><br>
                        ${event.description || 'No description'}
                    </div>`
                ).join('');

                details.classList.add('active');
            }
        }

        function closeEventDetails() {
            document.querySelector('.event-details').classList.remove('active');
        }

        // Initialize calendar and events
        document.addEventListener('DOMContentLoaded', function() {
            updateMonthDays();
            displayEvents();
        });
    </script>
    <script>
function updateClock() {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    const timeString = `${hours12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
    document.getElementById('sidebar-clock').textContent = timeString;
}

setInterval(updateClock, 1000);
updateClock();

// Make profile image clickable to upload
const profileImage = document.getElementById('profileImage');
const profileInput = document.getElementById('profileInput');
if (profileImage && profileInput) {
    profileImage.addEventListener('click', function() {
        profileInput.click();
    });
}
</script>
</body>
</html>
