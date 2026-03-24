<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Initialize student data
$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_number, course FROM signin_db WHERE student_number = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Get grades for this student from grades_db for first year
$grades = [];
$gradesByCode = [];

$sql = "SELECT * FROM grades_db WHERE student_id = ? AND year = '1' ORDER BY sem, course_code";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error in prepare: " . $conn->error);
}
$stmt->bind_param("s", $student_id);

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $grades[$row['sem']][] = $row;
    $normalizedCode = trim(strtoupper($row['course_code']));
    $gradesByCode[$normalizedCode] = $row['final_grade'];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Year Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background-image: url('http://localhost/website/student/schol.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        .sidebar {
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            padding: 30px 10px 10px 10px;
            position: fixed;
            width: 250px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .subject-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: transform 0.3s, background-color 0.3s;
            cursor: pointer;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .semester-title {
            color: white;
            background: rgba(13, 110, 253, 0.9);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .print-button {
            position: fixed;
            right: 30px;
            bottom: 30px;
            z-index: 1000;
            width: 250px;
            padding: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-radius: 8px;
            background-color: #dc3545;
            color: white;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }
        .print-button:hover {
            background-color: #bb2d3b;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        @media print {
            .sidebar, .print-button {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            body {
                background-image: none;
            }
            .subject-card {
                break-inside: avoid;
            }
        }
        .subject-card.selected {
            background-color: #0d6efd;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }
        .grade-display {
            font-weight: bold;
            font-size: 1.1em;
            color: #0d6efd;
        }
        .no-grade {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="d-flex flex-column align-items-center mb-3">
            <img src="default_profile.jpg" alt="Profile" class="rounded-circle mb-3" style="width: 80px; height: 80px;">
            <h5 class="text-center"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h5>
            <p class="text-center"><?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="dci_page.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros1st.php" class="nav-link text-white active">
                    <i class="bi bi-book"></i> First Year 
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros2nd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Second Year 
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container mt-5">
            <h1 class="text-center mb-4">First Year Subjects</h1>
            
            <!-- First Semester -->
            <div class="semester-title" data-aos="fade-right">
                <h3>First Semester</h3>
            </div>
            
            <div class="row">
                <!-- First Semester Courses -->
                <div class="col-md-6">
                    <div class="subject-card">
                        <h5>IT 101 - Introduction to Information Technology</h5>
                        <p class="grade-display">
                            <?php 
                            echo isset($gradesByCode['IT101']) ? $gradesByCode['IT101'] : '<span class="no-grade">No grade yet</span>';
                            ?>
                        </p>
                    </div>
                    <!-- Add more subjects for first semester -->
                </div>

                <div class="col-md-6">
                    <div class="subject-card">
                        <h5>MATH 101 - College Algebra</h5>
                        <p class="grade-display">
                            <?php 
                            echo isset($gradesByCode['MATH101']) ? $gradesByCode['MATH101'] : '<span class="no-grade">No grade yet</span>';
                            ?>
                        </p>
                    </div>
                    <!-- Add more subjects for first semester -->
                </div>
            </div>

            <!-- Second Semester -->
            <div class="semester-title mt-5" data-aos="fade-left">
                <h3>Second Semester</h3>
            </div>
            
            <div class="row">
                <!-- Second Semester Courses -->
                <div class="col-md-6">
                    <div class="subject-card">
                        <h5>IT 102 - Computer Programming I</h5>
                        <p class="grade-display">
                            <?php 
                            echo isset($gradesByCode['IT102']) ? $gradesByCode['IT102'] : '<span class="no-grade">No grade yet</span>';
                            ?>
                        </p>
                    </div>
                    <!-- Add more subjects for second semester -->
                </div>

                <div class="col-md-6">
                    <div class="subject-card">
                        <h5>MATH 102 - Trigonometry</h5>
                        <p class="grade-display">
                            <?php 
                            echo isset($gradesByCode['MATH102']) ? $gradesByCode['MATH102'] : '<span class="no-grade">No grade yet</span>';
                            ?>
                        </p>
                    </div>
                    <!-- Add more subjects for second semester -->
                </div>
            </div>
        </div>
    </div>

    <button class="print-button" onclick="window.print()">
        <i class="bi bi-printer me-2"></i>Print Transcript
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
    </script>
</body>
</html>