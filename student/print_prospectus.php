<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Get student information
$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_id, course, classification, profile_image FROM signin_db WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Get assigned curriculum from assign_curriculum table
$assigned_curriculum = [];
$program = 'BSIT'; // Default program
$fiscal_year = date('Y') . '-' . (date('Y') + 1); // Default fiscal year

// Try to get curriculum info - use a simple approach
try {
    $curriculum_query = "SELECT * FROM assign_curriculum LIMIT 1";
    $stmt = $conn->prepare($curriculum_query);
    if ($stmt) {
        $stmt->execute();
        $curriculum_result = $stmt->get_result();
        
        if ($curriculum_result && $curriculum_result->num_rows > 0) {
            $row = $curriculum_result->fetch_assoc();
            // Try to extract program and fiscal year from available columns
            if (isset($row['program'])) {
                $program = $row['program'];
            } elseif (isset($row['program_name'])) {
                $program = $row['program_name'];
            }
            if (isset($row['fiscal_year'])) {
                $fiscal_year = $row['fiscal_year'];
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Use default values if query fails
    error_log("Curriculum query failed: " . $e->getMessage());
}

// Also try to get program from student record as fallback
if (isset($student['course']) && !empty($student['course'])) {
    $program = $student['course'];
}

// Handle program variations
if ($program === 'BSCS' || $program === 'BS Computer Science') {
    $program = 'BSCS';
} elseif ($program === 'BSIT' || $program === 'BS Information Technology') {
    $program = 'BSIT';
}

echo "<!-- Debug: Final program: " . htmlspecialchars($program) . " -->";
echo "<!-- Debug: Fiscal year: " . htmlspecialchars($fiscal_year) . " -->";

// Get all curriculum data organized by year and semester
$curriculum_data = [];
$semesters = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];

foreach ($semesters as $semester) {
    $curriculum_data[$semester] = [];
    
    // Try multiple approaches to get courses
    $courses_found = false;
    
    // Approach 1: With program and year_semester
    $course_query = "SELECT course_code, course_title, total_units, prerequisites 
                    FROM curriculum 
                    WHERE program = ? AND year_semester = ? 
                    ORDER BY course_code";
    $stmt = $conn->prepare($course_query);
    $stmt->bind_param("ss", $program, $semester);
    $stmt->execute();
    $course_result = $stmt->get_result();
    
    echo "<!-- Debug: Approach 1 - Program: " . htmlspecialchars($program) . ", Semester: " . htmlspecialchars($semester) . " -->";
    
    if ($course_result->num_rows > 0) {
        while ($course = $course_result->fetch_assoc()) {
            $curriculum_data[$semester][] = $course;
            echo "<!-- Debug: Course found - Code: " . htmlspecialchars($course['course_code']) . ", Title: " . htmlspecialchars($course['course_title']) . " -->";
        }
        $courses_found = true;
    }
    $stmt->close();
    
    // Approach 2: If no courses found, try with just year_semester
    if (!$courses_found) {
        $course_query = "SELECT course_code, course_title, total_units, prerequisites 
                        FROM curriculum 
                        WHERE year_semester = ? 
                        ORDER BY course_code";
        $stmt = $conn->prepare($course_query);
        $stmt->bind_param("s", $semester);
        $stmt->execute();
        $course_result = $stmt->get_result();
        
        echo "<!-- Debug: Approach 2 - Just Semester: " . htmlspecialchars($semester) . " -->";
        
        if ($course_result->num_rows > 0) {
            while ($course = $course_result->fetch_assoc()) {
                $curriculum_data[$semester][] = $course;
                echo "<!-- Debug: Course found - Code: " . htmlspecialchars($course['course_code']) . ", Title: " . htmlspecialchars($course['course_title']) . " -->";
            }
            $courses_found = true;
        }
        $stmt->close();
    }
    
    // Approach 3: If still no courses, try to get any courses
    if (!$courses_found) {
        $course_query = "SELECT course_code, course_title, total_units, prerequisites 
                        FROM curriculum 
                        ORDER BY course_code 
                        LIMIT 10";
        $stmt = $conn->prepare($course_query);
        $stmt->execute();
        $course_result = $stmt->get_result();
        
        echo "<!-- Debug: Approach 3 - Any courses sample -->";
        
        if ($course_result->num_rows > 0) {
            while ($course = $course_result->fetch_assoc()) {
                echo "<!-- Debug: Sample course - Code: " . htmlspecialchars($course['course_code']) . ", Title: " . htmlspecialchars($course['course_title']) . " -->";
            }
        }
        $stmt->close();
    }
    
    echo "<!-- Debug: Courses for $semester: " . count($curriculum_data[$semester]) . " -->";
}

// Get existing grades for this student (copy from dcipros1st.php approach)
$gradesByCode = [];

// Get grades for this student from grades_db
if (!empty($student_id)) {
    try {
        // Check if grades table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'grades_db'");
        if ($tableCheck->num_rows > 0) {
            $gradeStmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ?");
            $gradeStmt->bind_param("s", $student_id);
            $gradeStmt->execute();
            $gradeResult = $gradeStmt->get_result();
            
            while ($grade = $gradeResult->fetch_assoc()) {
                $courseCode = trim(strtoupper($grade['course_code']));
                $gradesByCode[$courseCode] = [
                    'grade' => $grade['final_grade'] ?? 'N/A',
                    'remarks' => $grade['remarks'] ?? 'N/A',
                    'course_title' => $grade['course_title'] ?? '',
                    'year' => $grade['year'] ?? '',
                    'sem' => $grade['sem'] ?? ''
                ];
            }
            $gradeStmt->close();
        }
    } catch (Exception $e) {
        echo '<!-- Error getting grades: ' . htmlspecialchars($e->getMessage()) . ' -->';
    }
}

echo "<!-- Debug: Student ID: " . htmlspecialchars($student_id) . " -->";
echo "<!-- Debug: Found " . count($gradesByCode) . " grade records in grades_db -->";

// Build a normalized grades map to handle code formatting differences (e.g., "IT 201" vs "IT201")
$normalizedGrades = [];
foreach ($gradesByCode as $gcode => $ginfo) {
    $gNorm = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($gcode)));
    if ($gNorm !== '') {
        $normalizedGrades[$gNorm] = $ginfo;
    }
}

// Function to get grade display (updated to use dcipros1st.php approach)
function getGradeDisplay($courseCode, $gradesByCode, $normalizedGrades, $allowInput = false) {
    $courseCode = strtoupper(trim($courseCode));
    $courseNorm = preg_replace('/[^A-Z0-9]/', '', $courseCode);
    
    echo "<!-- Debug: Looking for grade for course: " . htmlspecialchars($courseCode) . " (normalized: " . htmlspecialchars($courseNorm) . ") -->";
    
    // First try exact match
    if (isset($gradesByCode[$courseCode])) {
        $grade = $gradesByCode[$courseCode]['grade'];
        echo "<!-- Debug: Found exact grade: " . htmlspecialchars($grade) . " -->";
        
        // Check if grade is failed - if failed, return empty
        if (isFailedGrade($grade)) {
            echo "<!-- Debug: Grade is failed, hiding it -->";
            return '<span class="grade-cell no-grade"></span>';
        }
        
        return displayGradeCell($grade);
    }
    
    // Then try normalized match
    if (isset($normalizedGrades[$courseNorm])) {
        $grade = $normalizedGrades[$courseNorm]['grade'];
        echo "<!-- Debug: Found normalized grade: " . htmlspecialchars($grade) . " -->";
        
        // Check if grade is failed - if failed, return empty
        if (isFailedGrade($grade)) {
            echo "<!-- Debug: Grade is failed, hiding it -->";
            return '<span class="grade-cell no-grade"></span>';
        }
        
        return displayGradeCell($grade);
    }
    
    echo "<!-- Debug: No grade found for course: " . htmlspecialchars($courseCode) . " -->";
    
    // Always show empty grade cell, no input fields
    return '<span class="grade-cell no-grade"></span>';
}

// Helper function to check if grade is failed
function isFailedGrade($grade) {
    if ($grade === null || $grade === '' || $grade === 'N/A') {
        return false;
    }
    
    $gradeUpper = strtoupper($grade);
    
    // Check numeric grades
    if (is_numeric($grade)) {
        return $grade >= 5.00;
    }
    
    // Check text grades
    return in_array($gradeUpper, ['FAIL','FAILED','F','FA','U']);
}

// Helper function to display grade cell with proper styling
function displayGradeCell($grade) {
    $gradeClass = 'no-grade';
    if ($grade !== null && $grade !== '' && $grade !== 'N/A') {
        $gradeUpper = strtoupper($grade);
        if (is_numeric($grade)) {
            if ($grade <= 3.00) {
                $gradeClass = 'passed-grade';
            } elseif ($grade >= 5.00) {
                $gradeClass = 'failed-grade';
            } else {
                $gradeClass = 'other-grade';
            }
        } else {
            if (in_array($gradeUpper, ['PASS', 'PASSED', 'COMPLETE', 'COMPLETED', 'S', 'SA', 'S1', 'S2', 'S3', 'S4'])) {
                $gradeClass = 'passed-grade';
            } elseif (in_array($gradeUpper, ['FAIL','FAILED','F','FA','U'])) {
                $gradeClass = 'failed-grade';
            } elseif (in_array($gradeUpper, ['INC','INCOMPLETE'])) {
                $gradeClass = 'other-grade';
            }
        }
    }
    
    return '<span class="grade-cell ' . $gradeClass . '">' . 
           htmlspecialchars($grade) . '</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Prospectus - <?php echo htmlspecialchars($program); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/prospectus.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            margin: 0;
            padding: 10px;
            line-height: 1.4;
        }
        
        .prospectus-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            position: relative;
            min-height: auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .official-logo {
            position: relative;
            width: 80px;
            height: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .logo-image {
            width: 70px;
            height: 85px;
            object-fit: contain;
        }
        
        .institution-info h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .institution-info h2 {
            font-size: 14px;
            font-weight: normal;
            margin: 5px 0;
            color: #333;
        }
        
        .institution-info p {
            font-size: 12px;
            margin: 2px 0;
            color: #666;
        }
        
        .barcode-section {
            text-align: right;
        }
        
        #barcode {
            height: 50px;
            margin-bottom: 5px;
        }
        
        .barcode-text {
            font-size: 10px;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .prospectus-title {
            font-size: 28px;
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        
        .program-info {
            margin: 30px 0;
        }
        
        .program-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .student-fields {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .field-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .field-group label {
            font-weight: bold;
            font-size: 14px;
        }
        
        .field-line {
            border-bottom: 1px solid #333;
            width: 200px;
            height: 20px;
        }
        
        .curriculum-section {
            margin-top: 30px;
        }
        
        .year-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .semester-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            width: 100%;
            overflow: hidden;
        }
        
        .semester {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
        }
        
        .semester-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            background: #003366;
            color: white;
            padding: 8px;
            margin-bottom: 0;
        }
        
        .curriculum-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            table-layout: fixed;
        }
        .curriculum-table th,
        .curriculum-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.2;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        /* Stronger column width rules to prevent overlap */
        .curriculum-table th:nth-child(1),
        .curriculum-table td:nth-child(1) {
            width: 5% !important;
            min-width: 5% !important;
            max-width: 5% !important;
        }
        .curriculum-table th:nth-child(2),
        .curriculum-table td:nth-child(2) {
            width: 10% !important;
            min-width: 10% !important;
            max-width: 10% !important;
        }
        .curriculum-table th:nth-child(3),
        .curriculum-table td:nth-child(3) {
            width: 12% !important;
            min-width: 12% !important;
            max-width: 12% !important;
        }
        .curriculum-table th:nth-child(4),
        .curriculum-table td:nth-child(4) {
            width: 35% !important;
            min-width: 35% !important;
            max-width: 35% !important;
            text-align: left;
        }
        .curriculum-table th:nth-child(5),
        .curriculum-table td:nth-child(5) {
            width: 8% !important;
            min-width: 8% !important;
            max-width: 8% !important;
        }
        .curriculum-table th:nth-child(6),
        .curriculum-table td:nth-child(6) {
            width: 20% !important;
            min-width: 20% !important;
            max-width: 20% !important;
            text-align: left;
            font-size: 10px;
        }
        
        .curriculum-table th:first-child {
            background: transparent !important;
            color: transparent !important;
        }
        
        .curriculum-table td {
            padding: 8px 6px;
            text-align: center;
            border: none;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
        }
        
        .curriculum-table .course-title {
            text-align: left;
            padding-left: 10px;
        }
        
        .grade-cell {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-decoration: underline;
            min-width: 40px;
            display: inline-block;
        }
        
        .grade-input {
            width: 50px;
            text-align: center;
            font-size: 10px;
            border: 1px solid #ccc;
            padding: 2px;
        }
        
        .grade-cell.passed-grade {
            background-color: #d4edda;
            color: #155724;
        }
        
        .grade-cell.failed-grade {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .grade-cell.other-grade {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .grade-cell.no-grade {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .sidebar {
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            padding: 30px 10px 10px 10px;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .print-button {
            position: fixed;
            right: 25px;
            bottom: 20px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            background-color: #bb2d3b;
            color: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .print-button:hover {
            background-color: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }
            body {
                zoom: 0.8;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white !important;
                font-size: 9px !important;
                line-height: 1.1 !important;
            }
            .sidebar, .print-button, .modal, .watermark {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .prospectus-container {
                box-shadow: none !important;
                padding: 5px !important;
                margin: 0 auto !important;
                width: 100% !important;
            }
            .semester-container {
                gap: 10px !important;
                margin-bottom: 15px !important;
            }
            .semester {
                margin-bottom: 10px !important;
            }
            .curriculum-table {
                font-size: 8px !important;
            }
            .curriculum-table th, .curriculum-table td {
                padding: 3px 2px !important;
                font-size: 8px !important;
            }
            .year-title {
                font-size: 12px !important;
                margin: 10px 0 5px 0 !important;
            }
            .semester-title {
                font-size: 9px !important;
                padding: 3px !important;
            }
            .grade-input {
                display: none !important;
            }
            .grade-cell {
                font-size: 7px !important;
            }
            .header-section {
                margin-bottom: 15px !important;
            }
            .institution-info h1 {
                font-size: 14px !important;
            }
            .institution-info h2 {
                font-size: 10px !important;
            }
            .institution-info p {
                font-size: 8px !important;
            }
            .prospectus-title {
                font-size: 16px !important;
            }
            .program-title {
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="d-flex flex-column align-items-center mb-3">
            <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : ($program === 'BSCS' ? 'css.png' : 'its.png'); ?>" 
                 alt="Profile" 
                 class="rounded-circle mb-3" 
                 style="width: 80px; height: 80px; cursor: pointer;"
                 data-bs-toggle="modal" 
                 data-bs-target="#profileModal">
            <h5 class="text-center"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h5>
            <p class="text-center mb-0"><?php echo htmlspecialchars($student['student_id']); ?></p>
            <p class="text-center small"><?php echo htmlspecialchars($program); ?></p>
        </div>
        
        <?php
        // Get notification count
        $sidebar_unread = 0;
        try {
            $notif_query = "SELECT COUNT(*) as unread FROM notification WHERE student_id = ? AND is_read = 0";
            $notif_stmt = $conn->prepare($notif_query);
            $notif_stmt->bind_param("s", $student_id);
            $notif_stmt->execute();
            $notif_result = $notif_stmt->get_result();
            if ($row = $notif_result->fetch_assoc()) {
                $sidebar_unread = $row['unread'];
            }
            $notif_stmt->close();
        } catch (Exception $e) {
            // Handle notification error silently
        }
        ?>
        <a href="notification.php" class="btn btn-light d-inline-flex align-items-center gap-2 mt-2" id="sidebarNotificationButton" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($sidebar_unread > 0): ?>
                <span class="badge bg-danger"><?php echo $sidebar_unread; ?></span>
            <?php endif; ?>
        </a>
        
        <?php if ($program === 'BSCS'): ?>
        <!-- BSCS Navigation -->
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a href="cs_studash.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="cs1st.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> First Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="cs2nd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Second Year 
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="cs3rd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Third Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="cs4th.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Fourth Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="print_prospectus.php" class="nav-link text-white active">
                    <i class="bi bi-printer"></i> Print Prospectus
                </a>
            </li>
        </ul>
        <?php else: ?>
        <!-- BSIT Navigation (Default) -->
        <ul class="nav flex-column">
             <li class="nav-item mb-2">
                <a href="dci_page.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros1st.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> First Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros2nd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Second Year 
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros3rd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Third Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros4th.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Fourth Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="print_prospectus.php" class="nav-link text-white active">
                    <i class="bi bi-printer"></i> Print Prospectus
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <button onclick="window.print()" class="print-button d-print-none">
            <i class="bi bi-printer"></i> Print
        </button>

        <div class="prospectus-container">
            <div class="header-section">
                <div class="logo-section">
                    <div class="official-logo">
                        <img src="chomelogo.png" alt="City College of Calamba Logo" class="logo-image">
                    </div>
                    <div class="institution-info">
                        <h1>CITY COLLEGE OF CALAMBA</h1>
                        <h2>OFFICE OF THE COLLEGE REGISTRAR</h2>
                        <p>Old Municipal Site, Brgy. VII, Poblacion, Calamba City, Laguna</p>
                        <p>4027 Philippines</p>
                    </div>
                </div>
                <div class="barcode-section text-center mb-3">
                    <div class="barcode-container d-inline-block">
                        <svg id="barcode" style="display: block; margin: 0 auto;"></svg>
                        <div class="barcode-text small mt-1">ID: <?php echo htmlspecialchars($student['student_id']); ?></div>
                    </div>
                    <div class="prospectus-title">PROSPECTUS</div>
                </div>
            </div>

            <div class="program-info">
                <div class="program-title">PROGRAM: <?php echo htmlspecialchars($program); ?> - FISCAL YEAR: <?php echo htmlspecialchars($fiscal_year); ?></div>
                
                <div class="student-fields">
                    <div class="field-group">
                        <label>Name:</label>
                        <div class="field-line"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></div>
                    </div>
                    <div class="field-group">
                        <label>Student ID:</label>
                        <div class="field-line"><?php echo htmlspecialchars($student['student_id']); ?></div>
                    </div>
                    <div class="field-group">
                        <label>Course:</label>
                        <div class="field-line"><?php echo htmlspecialchars($student['course']); ?></div>
                    </div>
                </div>
            </div>

            <div class="curriculum-section">
                <!-- First Year -->
                <div class="year-title">FIRST YEAR</div>
                <div class="semester-container">
                    <div class="semester">
                        <div class="semester-title">FIRST SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['1-1'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="semester">
                        <div class="semester-title">SECOND SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['1-2'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Second Year -->
                <div class="year-title">SECOND YEAR</div>
                <div class="semester-container">
                    <div class="semester">
                        <div class="semester-title">FIRST SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['2-1'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="semester">
                        <div class="semester-title">SECOND SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['2-2'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Third Year -->
                <div class="year-title">THIRD YEAR</div>
                <div class="semester-container">
                    <div class="semester">
                        <div class="semester-title">FIRST SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['3-1'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="semester">
                        <div class="semester-title">SECOND SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['3-2'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fourth Year -->
                <div class="year-title">FOURTH YEAR</div>
                <div class="semester-container">
                    <div class="semester">
                        <div class="semester-title">FIRST SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['4-1'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="semester">
                        <div class="semester-title">SECOND SEMESTER</div>
                        <div class="table-responsive">
                            <table class="curriculum-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Grade</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Units</th>
                                        <th>Pre-requisite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curriculum_data['4-2'] as $course): ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo getGradeDisplay($course['course_code'], $gradesByCode, $normalizedGrades, false); ?></td>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td class="course-title"><?php echo htmlspecialchars($course['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($course['total_units']); ?></td>
                                        <td><?php echo htmlspecialchars($course['prerequisites'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate barcode
        document.addEventListener('DOMContentLoaded', function() {
            JsBarcode("#barcode", "<?php echo htmlspecialchars($student['student_id']); ?>", {
                format: "CODE128",
                width: 1.5,
                height: 40,
                displayValue: false
            });
        });

        // Validate grade input (removed since no input fields)
        // document.addEventListener('input', function(e) {
        //     if (e.target.classList.contains('grade-input')) {
        //         const value = parseFloat(e.target.value);
        //         if (value < 1 || value > 5) {
        //             e.target.style.borderColor = 'red';
        //         } else {
        //             e.target.style.borderColor = '#ccc';
        //         }
        //     }
        // });
    </script>
</body>
</html>
