<?php

// Function to check if a student should be classified as irregular
function checkIrregularStatus($conn, $studentId, $currentYear, $currentSem) {
    // Check regular courses
    $regularQuery = "SELECT COUNT(*) as failed_count FROM grades_db 
                    WHERE student_id = ? AND (final_grade >= 5.00 OR final_grade IS NULL)";
    $stmt = $conn->prepare($regularQuery);
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['failed_count'] > 0) return true;
        }
    }
    
    // Check irregular courses
    $irregularQuery = "SELECT COUNT(*) as failed_count FROM irregular_db 
                      WHERE student_id = ? AND year_level = ? AND semester = ? 
                      AND status = 'failed'";
    $stmt = $conn->prepare($irregularQuery);
    if ($stmt) {
        $stmt->bind_param('sii', $studentId, $currentYear, $currentSem);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['failed_count'] > 0) return true;
        }
    }
    
    return false;
}

// Function to get curriculum courses for regular students
function getCurriculumCourses($conn, $program = 'BSIT', $yearLevel = 3) {
    $curriculumCourses = [
        '3-1' => [],
        '3-2' => []
    ];
    
    // Connect to curriculum database
    $curriculumConn = new mysqli("localhost", "root", "", "ccc_curriculum_evaluation");
    if ($curriculumConn->connect_error) {
        echo '<!-- Error connecting to curriculum DB: ' . $curriculumConn->connect_error . ' -->';
        return $curriculumCourses;
    }
    
    // Get curriculum courses for third year
    $query = "SELECT course_code, course_title, lec_units, lab_units, total_units, prerequisites, year_semester 
              FROM curriculum_bsit 
              WHERE year_semester IN ('3-1', '3-2') 
              ORDER BY year_semester, course_code";
    
    $result = $curriculumConn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $semesterKey = $row['year_semester'];
            
            $courseData = [
                'course_code' => $row['course_code'] ?? '',
                'course_title' => $row['course_title'] ?? '',
                'lec_units' => $row['lec_units'] ?? 0,
                'lab_units' => $row['lab_units'] ?? 0,
                'total_units' => $row['total_units'] ?? 0,
                'prerequisites' => $row['prerequisites'] ?? '',
                'is_irregular' => false
            ];
            
            if (isset($curriculumCourses[$semesterKey])) {
                $curriculumCourses[$semesterKey][] = $courseData;
            }
        }
    }
    
    $curriculumConn->close();
    return $curriculumCourses;
}

// Function to get irregular courses for a student
function getIrregularCourses($conn, $studentId, $year, $semester) {
    $irregularCourses = [];
    $query = "SELECT * FROM irregular_db 
              WHERE student_id = ? AND year_level = ? AND semester = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sii", $studentId, $year, $semester);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $irregularCourses[] = [
                'course_code' => $row['course_code'] ?? $row['course_code'] ?? '',
                'course_title' => $row['course_title'] ?? $row['subject_description'] ?? 'No Title',
                'units' => $row['units'] ?? 0,
                'grade' => $row['grade'] ?? null,
                'is_irregular' => true
            ];
        }
        $stmt->close();
    }
    
    return $irregularCourses;
}

// Function for flexible subject code matching
function tryFlexibleMatching($conn, $tableName, $subjectCodes, &$completedUnits, $columns) {
    echo '<!-- Trying flexible matching for subject codes -->';
    
    // Try with trimmed and normalized codes
    $likeConditions = [];
    foreach ($subjectCodes as $code) {
        $cleanCode = trim($code);
        $likeConditions[] = "UPPER(TRIM(course_code)) LIKE '%" . $conn->real_escape_string($cleanCode) . "%'";
    }
    
    $sql = "SELECT course_code, " . 
          (in_array('total_units', $columns) ? 'total_units' : 'units') . " as units
          FROM `$tableName`
          WHERE program = 'BSIT' 
          AND (year_semester = '3-1' OR year_semester = '3-2')
          AND (" . implode(' OR ', $likeConditions) . ")";
    
    echo '<!-- Flexible query: ' . htmlspecialchars($sql) . ' -->';
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo '<!-- Found ' . $result->num_rows . ' courses with flexible matching -->';
        while ($row = $result->fetch_assoc()) {
            $units = (float)$row['units'];
            $completedUnits += $units;
            echo '<!-- Flexible match: ' . $row['course_code'] . ' - ' . $units . ' units -->';
        }
    }
}

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Initialize student data
$student_id = $_SESSION['student_id'];

// Get student information first
$stmt = $conn->prepare("SELECT firstname, lastname, student_id, course, classification, profile_image FROM signin_db WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Load courses based on student classification
$courses = [];
$studentClassification = strtolower($student['classification'] ?? 'regular');

echo '<!-- Debug: Student classification: ' . htmlspecialchars($studentClassification) . ' -->';

if ($studentClassification === 'irregular') {
    // Load irregular courses for irregular students
    echo '<!-- Loading irregular courses for third year -->';
    $irregularCourses = array_merge(
        getIrregularCourses($conn, $student_id, 3, 1),
        getIrregularCourses($conn, $student_id, 3, 2)
    );
    
    // Organize irregular courses by semester
    $courses = [
        '3-1' => [],
        '3-2' => []
    ];
    
    foreach ($irregularCourses as $course) {
        $semesterKey = '3-' . ($course['semester'] ?? 1);
        if (isset($courses[$semesterKey])) {
            $courses[$semesterKey][] = $course;
        }
        
        // Add irregular courses to gradesByCode for display
        $courseCode = strtoupper(trim($course['course_code']));
        if (!isset($gradesByCode[$courseCode])) {
            $gradesByCode[$courseCode] = [
                'grade' => $course['grade'] ?? 'N/A',
                'remarks' => 'Irregular',
                'course_title' => $course['course_title'],
                'year' => 3,
                'sem' => $course['semester'] ?? 1
            ];
        }
    }
} else {
    // Load curriculum courses for regular students
    echo '<!-- Loading curriculum courses for third year regular student -->';
    $courses = getCurriculumCourses($conn, 'BSIT', 3);
}

// Initialize total units
$totalUnits = 0;
$first_sem_units = 0; // Initialize first semester units
$second_sem_units = 0; // Initialize second semester units

// Initialize grades array
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
        error_log("Error fetching grades: " . $e->getMessage());
    }
}

// Debug output
echo '<!-- Debug: Current session student_id: ' . htmlspecialchars($student_id) . ' -->';
echo '<!-- Debug: Found ' . count($gradesByCode) . ' grade records in grades_db -->';

// Build a normalized grades map to handle code formatting differences (e.g., "IT 201" vs "IT201")
$normalizedGrades = [];
foreach ($gradesByCode as $gcode => $ginfo) {
    $gNorm = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($gcode)));
    if ($gNorm !== '') {
        $normalizedGrades[$gNorm] = $ginfo;
    }
}

// Function to calculate total units for a year
function calculateYearlyUnits($courses) {
    $totalUnits = 0;
    foreach ($courses as $semester) {
        foreach ($semester as $code) {
            if (in_array($code, ['PATHFit 1', 'NSTP 101', 'PATHFit 2', 'NSTP 102', 'PATHFit 3', 'NSTP 201', 'PATHFit 4', 'NSTP 202'])) {
                $totalUnits += 2;
            } else {
                $totalUnits += 3;
            }
        }
    }
    return $totalUnits;
}

// Rest of your existing PHP code remains the same until the HTML head
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>BSIT Prospectus - First Year</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/prospectus.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <!-- Rest of your existing CSS remains the same -->
    <style>
        /* Responsive Table Styles */
        .table-responsive-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .curriculum-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 600px; /* Minimum width before scrolling */
        }
        
        .curriculum-table th,
        .curriculum-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .curriculum-table th {
            background-color: #6f42c1;
            color: white;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .curriculum-table tbody tr:hover {
            background-color: rgba(111, 66, 193, 0.05);
        }
        
        /* Make the table more compact on mobile */
        @media (max-width: 768px) {
            .curriculum-table th,
            .curriculum-table td {
                padding: 8px 6px;
                font-size: 14px;
            }
            
            .semester-title {
                font-size: 1.1rem;
                padding: 10px;
            }
            
            .year-title {
                font-size: 1.3rem;
                margin: 20px 0 10px;
            }
        }
        
        /* For very small screens */
        @media (max-width: 480px) {
            .curriculum-table th,
            .curriculum-table td {
                padding: 6px 4px;
                font-size: 13px;
            }
            
            .grade-cell {
                min-width: 60px;
            }
        }
        
        /* Mobile Menu Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #6f42c1;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 16px;
        }

        /* Overlay for mobile menu */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        /* Sidebar styles for mobile */
        @media (max-width: 991.98px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                position: fixed;
                height: 100%;
                z-index: 999;
                width: 280px;
                overflow-y: auto;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .prospectus-container {
                padding: 60px 15px 30px;
            }

            .semester-container {
                flex-direction: column;
            }

            .semester {
                width: 100%;
                margin-bottom: 30px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 0.1cm;
            }
            body {
                zoom: 0.75;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: white !important;
                font-size: 9px !important;
                line-height: 1.1 !important;
            }
            .sidebar, .print-button, .modal, .watermark, .page-number {
                display: none !important;
            }
            /* Hide date, time, and page numbers when printing */
            .date-time, .page-number, .footer, .header-date {
                display: none !important;
            }
            /* Hide all header and footer elements */
            .student-info-header, .prospectus-header, .print-header {
                display: none !important;
            }
            /* Hide any date/time displays */
            .current-date, .print-date, .timestamp {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .prospectus-container {
                box-shadow: none !important;
                padding: 2px 5px !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-height: 100vh !important;
                overflow: hidden !important;
            }
            /* Reduce spacing for print but keep readability */
            .semester-container {
                gap: 5px !important;
                margin-bottom: 5px !important;
            }
            .semester {
                margin-bottom: 3px !important;
            }
            .curriculum-table {
                font-size: 8px !important;
            }
            .curriculum-table th, .curriculum-table td {
                padding: 3px 2px !important;
                font-size: 8px !important;
            }
            .grade-cell {
                font-size: 7px !important;
            }
            /* Reduce program director section spacing */
            .program-director-section {
                margin-top: 5px !important;
                page-break-inside: avoid;
            }
            .decision-summary {
                margin: 3px 0 !important;
                padding: 4px !important;
            }
            .decision-summary h5 {
                font-size: 10px !important;
                margin-bottom: 2px !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .prospectus-container {
                box-shadow: none !important;
                padding: 2px 5px !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                max-height: 100vh !important;
                overflow: hidden !important;
            }
            .semester {
                margin-bottom: 3px !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            .semester-title {
                font-size: 9px !important;
                padding: 2px 3px !important;
                margin: 1px 0 !important;
                background: #003366 !important;
                color: white !important;
                text-align: center !important;
                padding-left: 1500px !important;
                max-width: 1717px !important;
                margin-left: auto !important;
                margin-right: 0 !important;
                padding-right: 20px !important;
            }
            .curriculum-table {
                font-size: 7px !important;
                margin-bottom: 3px !important;
            }
            .curriculum-table th,
            .curriculum-table td {
                padding: 2px 2px !important;
                line-height: 1.0 !important;
            }
            .curriculum-table th:first-child {
                background: transparent !important;
                color: transparent !important;
            }
            .curriculum-table th {
                background: #0d6efd !important;
                color: white !important;
            }
            .year-title {
                font-size: 10px !important;
                margin: 3px 0 2px 0 !important;
                padding: 1px 3px !important;
            }
            .header-section {
                margin-bottom: 3px !important;
                padding-bottom: 3px !important;
            }
            .institution-info h1 {
                font-size: 12px !important;
                margin: 0 !important;
            }
            .institution-info h2 {
                font-size: 8px !important;
                margin: 1px 0 !important;
            }
            .institution-info p {
                font-size: 7px !important;
                margin: 0 0 1px 0 !important;
            }
            .program-title {
                font-size: 10px !important;
                margin: 2px 0 !important;
            }
            .student-fields {
                margin: 2px 0 !important;
            }
            .field-line {
                width: 120px !important;
            }
            /* Ensure program director stays on same page */
            .program-director-section {
                margin-top: 2px !important;
                padding-top: 0 !important;
                page-break-inside: avoid;
                position: relative !important;
            }
            /* Force everything to fit on one page */
            * {
                page-break-inside: avoid !important;
            }
            /* Reduce all margins and padding */
            div, p, h1, h2, h3, h4, h5, h6 {
                margin-top: 1px !important;
                margin-bottom: 1px !important;
                padding-top: 1px !important;
                padding-bottom: 1px !important;
            }
        }

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
            margin-bottom: 40px;
           
            padding-bottom: 25px;
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
            margin: 40px 0;
        }
        
        .program-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .student-fields {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
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
            margin-top: 40px;
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
            margin-bottom: 40px;
        }
        
        .semester {
            flex: 1;
        }
        
        .semester-title {
            font-size: 12px 8px;
            font-weight: bold;
            text-align: center;
            /* Adjust this value to position text from left edge (in pixels) */
            padding-left: 1500px;
            background: #003366;
            color: white;
            padding: 8px;
            margin-bottom: 0;
            max-width: 1516px;
            margin-left: auto;
            margin-right: 0;
            padding-right: 20px;
        }
        .semester-title th:first-child {
            background: transparent !important;
            color: transparent !important;
        }
        
        
        .curriculum-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            border: none;
        }
        
        .curriculum-table th {
            background: #0d6efd;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: none;
        }
        
        .curriculum-table th:first-child {
            background: transparent !important;
            color: transparent !important;
        }
        .curriculum-table td {
            padding: 10px 8px;
            text-align: center;
            border: none;
            border-bottom: 1px solid #dee2e6;
        }
        
        .curriculum-table .course-title {
            text-align: left;
            padding-left: 12px;
            border-left: none;
        }
        
        .semester {
            margin-bottom: 15px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }
        .grade-cell {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            text-decoration: underline;
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
        
        .prereq-failed {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 2px solid #dc3545 !important;
        }
        
        .prereq-failed .grade-cell {
            background-color: #dc3545 !important;
            color: white !important;
            font-weight: bold;
        }
        

        
        .page-number {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 14px;
            text-decoration: underline;
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
            bottom: 5px;
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
        
        .print-button i {
            font-size: 18px;
        }
        
        .print-button:hover {
            background-color: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }
        
        .print-button:active {
            transform: translateY(0);
        }
        
        @media print {
            /* Hide sidebar and print button */
            .sidebar, .print-button, .mobile-header, .overlay {
                display: none !important;
            }
            
            /* Hide decision summary when printing */
            .decision-summary {
                display: none !important;
            }
            
            /* Adjust main content */
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            
            /* Reset body styles */
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 10px !important;
            }
            
            /* Adjust container */
            .prospectus-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            
            /* Ensure tables are properly sized */
            .table-responsive-container {
                width: 100% !important;
                overflow: visible !important;
            }
            
            /* Hide any other elements that shouldn't print */
            .d-print-none {
                display: none !important;
            }
            
            /* Make sure the content takes full width */
            .semester {
                page-break-inside: avoid;
                margin-bottom: 10px !important;
            }
            
            /* Adjust font sizes for better print */
            .curriculum-table {
                font-size: 8px !important;
            }
            
            .semester-title {
                font-size: 10px !important;
                padding: 5px !important;
                margin: 5px 0 !important;
            }
        }
        
        @media screen {
            .prospectus-container {
                background: white;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header d-lg-none">
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="student-info-mobile">
            <div class="fw-bold"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></div>
            <div class="small">ID: <?php echo htmlspecialchars($student['student_id']); ?></div>
        </div>
        <div style="width: 40px;"></div> <!-- For balance -->
    </div>
    
    <!-- Overlay for mobile menu -->
    <div class="overlay" id="overlay"></div>
    
    <div class="sidebar">
        <div class="d-flex flex-column align-items-center mb-3">
            <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'its.png'; ?>" 
                 alt="Profile" 
                 class="rounded-circle mb-3" 
                 style="width: 80px; height: 80px; cursor: pointer;"
                 data-bs-toggle="modal" 
                 data-bs-target="#profileModal">
            <h5 class="text-center"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h5>
            <p class="text-center"><?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>
        <?php
        // Sidebar notification bell: show unread count if notifications table exists
        $sidebar_unread = 0;
        if (isset($_SESSION['student_id'])) {
            try {
                $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    $tmpStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                    if ($tmpStmt) {
                        $tmpStmt->bind_param("s", $_SESSION['student_id']);
                        $tmpStmt->execute();
                        $tmpRes = $tmpStmt->get_result();
                        if ($tmpRes) {
                            $sidebar_unread = (int)($tmpRes->fetch_assoc()['count'] ?? 0);
                        }
                        $tmpStmt->close();
                    }
                }
            } catch (Exception $e) {
                error_log('Notification badge check failed: ' . $e->getMessage());
                $sidebar_unread = 0;
            }
        }
        ?>
        <a href="notification.php" class="btn btn-light d-inline-flex align-items-center gap-2 mt-2" id="sidebarNotificationButton" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($sidebar_unread > 0): ?>
                <span class="badge bg-danger"><?php echo $sidebar_unread; ?></span>
            <?php endif; ?>
        </a>
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
                <a href="print_prospectus.php" class="nav-link text-white">
                    <i class="bi bi-printer"></i> Print Prospectus
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <!-- Print Button -->
        <button onclick="window.print()" class="print-button d-print-none">
            <i class="bi bi-printer"></i>
        </button>

        <div class="prospectus-container">
            <!-- Watermark -->
            <div class="watermark">FOR PERSONAL USE ONLY</div>
            
            <!-- Header Section -->
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
                        <div class="prospectus-title fw-bold">PROSPECTUS</div>
                    </div>
                    <script>
                        // Initialize barcode with student ID
                        document.addEventListener('DOMContentLoaded', function() {
                            try {
                                JsBarcode('#barcode', '<?php echo $student['student_id']; ?>', {
                                    format: 'CODE128',
                                    width: 1.5,
                                    height: 50,
                                    displayValue: false,
                                    margin: 2,
                                    background: 'transparent',
                                    lineColor: '#000',
                                    valid: function(valid) {
                                        if (!valid) {
                                            console.warn('Barcode validation failed, using alternative format');
                                            JsBarcode('#barcode', 'STD-<?php echo $student['student_id']; ?>', {
                                                format: 'CODE128',
                                                width: 1.5,
                                                height: 50,
                                                displayValue: false,
                                                margin: 2
                                            });
                                        }
                                    }
                                });
                            } catch (e) {
                                console.error('Barcode generation failed:', e);
                            }
                                // Ensure sidebar closed on initial load
                                (function(){ try{ sidebar.classList.remove('show'); overlay.classList.remove('show'); overlay.style.display='none'; document.body.style.overflow=''; }catch(e){} })();
                        });
                    </script>
                </div>
            </div>

            
            <div class="student-info mb-4">
                <div class="row">
                <div class="program-title">Bachelor of Science in Information Technology
                </div>
                    <div class="col-md-6">
                        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
                        <p><strong>Student no.:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                       <p><strong>classification:</strong> <?php echo htmlspecialchars($student['classification']); ?></p>
                       <p><strong>Completed Units:</strong> <?php echo $totalUnits; ?></p>
                    </div>
                </div>
            </div>
        
        <?php
        // Database connection for curriculum
        $host = "localhost";
        $user = "root";
        $pass = "";
        $db = "ccc_curriculum_evaluation";

        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Debug: Show database being used
        echo '<!-- Using database: ' . $db . ' -->';
        
        // Get the correct table name (case-sensitive)
        $tableName = 'irregular_db';
        $tables = $conn->query("SHOW TABLES");
        $allTables = [];
        while($table = $tables->fetch_array()) {
            $allTables[] = $table[0];
        }
        echo '<!-- Available tables: ' . implode(', ', $allTables) . ' -->';
        
        // Try to find the irregular_db table (case-insensitive)
        $tableName = '';
        foreach ($allTables as $table) {
            if (strtolower($table) === 'irregular_db') {
                $tableName = $table;
                break;
            }
        }
        
        if (empty($tableName)) {
            die('<!-- Error: Could not find irregular_db table in database -->');
        }
        
        echo '<!-- Using table: ' . $tableName . ' -->';
        
        // Calculate total units for BSIT program - Third Year
        $totalProgramUnits = 0;
        $completedUnits = 0;
        $first_sem_units = 0; // Reset for calculation
        $second_sem_units = 0; // Reset for calculation
        
        // Calculate units from the courses array (already loaded by classification)
        foreach ($courses as $semesterKey => $semesterCourses) {
            foreach ($semesterCourses as $course) {
                $units = isset($course['total_units']) ? (float)$course['total_units'] : 0;
                $totalProgramUnits += $units;
                
                // Add to semester-specific totals
                if ($semesterKey === '3-1') {
                    $first_sem_units += $units;
                } elseif ($semesterKey === '3-2') {
                    $second_sem_units += $units;
                }
                
                // Check if this course is completed
                $subjectCode = trim(strtoupper($course['course_code']));
                if (isset($gradesByCode[$subjectCode])) {
                    $completedUnits += $units;
                }
            }
        }
        
        ?>
        <div class="year-title">THIRD YEAR</div>
        
        <div class="semester">
            <div class="semester-title">FIRST SEMESTER</div>
            <div class="table-responsive">
                <table class="curriculum-table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Code</th>
                            <th>Course Title</th>
                            <th>Lec</th>
                            <th>Lab</th>
                            <th>Units</th>
                            <th>Pre-req</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowNum = 1;
                        $displayedFirstSemUnits = 0; // Initialize displayed first semester units
                        // Track failed subjects from first semester so we can mark dependent second-semester courses
                        $failedFirstSem = [];
                        foreach ($courses['3-1'] as $course) {
                            $subjectCode = trim($course['course_code']);
                            $gradeInfo = $gradesByCode[strtoupper($subjectCode)] ?? null;
                            $grade = $gradeInfo['grade'] ?? '';
                            
                            // Add to displayed first semester units
                            $displayedFirstSemUnits += $course['total_units'] ?? 0;
                            $gradeClass = 'grade-cell';
                            $displayGrade = '';
                            $hasGrade = !empty($grade);
                            
                            if ($hasGrade) {
                                $gradeUpper = strtoupper($grade);
                                $displayGrade = $grade;
                                
                                // Grade processing logic
                                if (in_array($gradeUpper, ['PASS', 'PASSED', 'COMPLETE', 'COMPLETED', 'S', 'SA', 'S1', 'S2', 'S3', 'S4'])) {
                                    $gradeClass = 'grade-cell passed';
                                } 
                                elseif (in_array($gradeUpper, ['FAIL', 'FAILED', 'F', 'FA', 'U', 'INC', 'INCOMPLETE'])) {
                                    $gradeClass = 'grade-cell failed';
                                }
                                elseif (is_numeric($grade)) {
                                    $gradeNumeric = floatval($grade);
                                    if ($gradeNumeric <= 3.0 && $gradeNumeric >= 1.0) {
                                        $displayGrade = number_format($gradeNumeric, 1);
                                        $gradeClass = 'grade-cell passed';
                                    } elseif ($gradeNumeric >= 3.25 && $gradeNumeric <= 4.0) {
                                        $displayGrade = 'INC';
                                        $gradeClass = 'grade-cell failed';
                                    } elseif ($gradeNumeric >= 5.0) {
                                        $displayGrade = 'Failed';
                                        $gradeClass = 'grade-cell failed';
                                    } else {
                                        $displayGrade = number_format($gradeNumeric, 1);
                                        $gradeClass = 'grade-cell failed';
                                    }
                                }
                            }
                            
                            // Determine if this course is failed so we can mark dependent subjects
                            $rowClass = '';
                            $isFailed = false;
                            if ($hasGrade) {
                                $failKeywords = ['FAIL','FAILED','F','FA','U'];
                                if (in_array($gradeUpper, $failKeywords)) {
                                    $isFailed = true;
                                } elseif (is_numeric($grade) && floatval($grade) > 3.0) {
                                    $isFailed = true;
                                }
                            }
                            if ($isFailed) {
                                $rowClass = 'prereq-failed';
                                $failedFirstSem[] = strtoupper(trim($subjectCode));
                            }

                            echo '<tr' . (!empty($rowClass) ? ' class="' . $rowClass . '"' : '') . '>';
                            echo '<td class="' . $gradeClass . '">' . htmlspecialchars($displayGrade) . '</td>';
                            echo '<td>' . htmlspecialchars($subjectCode) . '</td>';
                            echo '<td>' . htmlspecialchars($course['course_title'] ?? ($course['subject_description'] ?? '')) . '</td>';
                            echo '<td>' . ($course['lec_units'] ?? '0') . '</td>';
                            echo '<td>' . ($course['lab_units'] ?? '0') . '</td>';
                            echo '<td>' . ($course['total_units'] ?? '0') . '</td>';
                            echo '<td>' . (!empty($course['prerequisites']) ? htmlspecialchars($course['prerequisites']) : '-') . '</td>';
                            echo '</tr>';
                            $rowNum++;
                        }
                        ?>
                    </tbody>
                </table>
                <div style="display: flex; justify-content: flex-end; align-items: center; margin: 20px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd;">
                    <span style="font-weight: bold; margin-right: 10px; font-size: 1.1em;">Total Units: </span>
                    <span style="font-weight: bold; font-size: 1.2em; color: #0d6efd;"><?php echo number_format($displayedFirstSemUnits, 1); ?></span>
                </div>
            </div>
        </div>

        <div class="semester">
            <div class="semester-title">SECOND SEMESTER</div>
            <div class="table-responsive">
                <table class="curriculum-table">
                    <thead>
                        <tr>
                            <th>Grade</th>
                            <th>Code</th>
                            <th>Course Title</th>
                            <th>Lec</th>
                            <th>Lab</th>
                            <th>Units</th>
                            <th>Pre-req</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rowNum = 1;
                        // Track failed subjects from first semester to block second semester courses
                        $failedFirstSem = [];
                        foreach ($courses['3-1'] as $firstSemCourse) {
                            $firstSemCode = trim($firstSemCourse['course_code']);
                            $firstSemGrade = $gradesByCode[strtoupper($firstSemCode)]['grade'] ?? '';
                            
                            if (!empty($firstSemGrade)) {
                                $gradeUpper = strtoupper($firstSemGrade);
                                $isFailed = in_array($gradeUpper, ['F', 'FAIL', 'FAILED', 'FA', 'U']) || 
                                           (is_numeric($firstSemGrade) && floatval($firstSemGrade) > 3.0);
                                
                                if ($isFailed) {
                                    $failedFirstSem[] = strtoupper(trim($firstSemCode));
                                }
                            }
                        }

                        // Process second semester courses
                        $displayedCourses = [];
                        $displayedSecondSemUnits = 0;
                        
                        foreach ($courses['3-2'] as $course) {
                            $subjectCode = trim($course['course_code']);
                            $subjectCodeUpper = strtoupper($subjectCode);
                            $gradeInfo = $gradesByCode[$subjectCodeUpper] ?? null;
                            $grade = $gradeInfo['grade'] ?? '';
                            $gradeClass = 'grade-cell';
                            
                            // Skip if this is an irregular course that wasn't taken
                            $isIrregularCourse = false;
                            foreach ($irregularCourses as $irreg) {
                                if (strtoupper(trim($irreg['course_code'])) === $subjectCodeUpper) {
                                    $isIrregularCourse = true;
                                    break;
                                }
                            }
                            
                            if (!$isIrregularCourse) {
                                // Check if any prerequisites were failed in first semester
                                $hasFailedPrereq = false;
                                $prereqText = $course['prerequisites'] ?? '';
                                
                                if (!empty($prereqText)) {
                                    $prereqCodes = preg_split('/\s*,\s*/', $prereqText);
                                    foreach ($prereqCodes as $pr) {
                                        $pr = trim($pr);
                                        if (in_array(strtoupper($pr), $failedFirstSem)) {
                                            $hasFailedPrereq = true;
                                            break;
                                        }
                                    }
                                }
                                
                                if ($hasFailedPrereq) {
                                    continue; // Skip this course if prerequisites were failed
                                }
                            }
                            
                            // Add to displayed courses if it passes all checks
                            $displayedCourses[] = $course;
                            // Add the units of this course to the displayed second semester units
                            $displayedSecondSemUnits += $course['total_units'] ?? 0;
                            $displayGrade = '-';
                            $hasGrade = !empty($grade);
                            
                            if ($hasGrade) {
                                $gradeUpper = strtoupper($grade);
                                $displayGrade = $grade;
                                
                                // Grade processing logic
                                if (in_array($gradeUpper, ['PASS', 'PASSED', 'COMPLETE', 'COMPLETED', 'S', 'SA', 'S1', 'S2', 'S3', 'S4'])) {
                                    $gradeClass = 'grade-cell passed';
                                } 
                                elseif (in_array($gradeUpper, ['FAIL', 'FAILED', 'F', 'FA', 'U', 'INC', 'INCOMPLETE'])) {
                                    $gradeClass = 'grade-cell failed';
                                }
                                elseif (is_numeric($grade)) {
                                    $gradeNumeric = floatval($grade);
                                    if ($gradeNumeric <= 3.0 && $gradeNumeric >= 1.0) {
                                        $displayGrade = number_format($gradeNumeric, 1);
                                        $gradeClass = 'grade-cell passed';
                                    } elseif ($gradeNumeric >= 3.25 && $gradeNumeric <= 4.0) {
                                        $displayGrade = 'INC';
                                        $gradeClass = 'grade-cell failed';
                                    } elseif ($gradeNumeric >= 5.0) {
                                        $displayGrade = 'Failed';
                                        $gradeClass = 'grade-cell failed';
                                    } else {
                                        $displayGrade = number_format($gradeNumeric, 1);
                                        $gradeClass = 'grade-cell failed';
                                    }
                                }
                            }
                            
                            // Check prerequisites: if any prerequisite was failed in first semester or not yet passed, block this course
                            // BUT skip blocking for Regular students
                            $rowClass = '';
                            $prereqText = $course['prerequisites'] ?? '';
                            $studentClassification = strtoupper(trim($student['classification'] ?? ''));
                            
                            if (!empty($prereqText) && $studentClassification !== 'REGULAR') {
                                // Split prerequisites by comma or whitespace
                                $prereqCodes = preg_split('/\s*,\s*/', $prereqText);
                                foreach ($prereqCodes as $pr) {
                                    $pr = trim($pr);
                                    if ($pr === '') continue;
                                    $prUpper = strtoupper($pr);
                                    $prNorm = preg_replace('/[^A-Z0-9]/', '', $prUpper);

                                    // If prerequisite is explicitly failed in first semester (normalized), mark blocked
                                    foreach ($failedFirstSem as $failedCode) {
                                        $failedNorm = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($failedCode)));
                                        if ($prNorm !== '' && ($prNorm === $failedNorm || strpos($prNorm, $failedNorm) !== false || strpos($failedNorm, $prNorm) !== false)) {
                                            $rowClass = 'prereq-failed';
                                            break 2; // break out of both loops
                                        }
                                    }

                                    // If student has a grade for prereq (normalized) and it's passing, continue; if grade exists but failed, block
                                    if (!empty($prNorm) && isset($normalizedGrades[$prNorm])) {
                                        $prGrade = $normalizedGrades[$prNorm]['grade'];
                                        $prGradeUpper = strtoupper($prGrade);
                                        $prFailKeywords = ['FAIL','FAILED','F','FA','U'];
                                        if (in_array($prGradeUpper, $prFailKeywords) || (is_numeric($prGrade) && floatval($prGrade) > 3.0)) {
                                            $rowClass = 'prereq-failed';
                                            break 2;
                                        }
                                        // else it's passed, ok
                                    } else {
                                        // No grade found for prerequisite -> treat as locked (can't take)
                                        $rowClass = 'prereq-failed';
                                        break;
                                    }
                                }
                            }

                            echo '<tr' . (!empty($rowClass) ? ' class="' . $rowClass . '"' : '') . '>';
                            echo '<td class="' . $gradeClass . '">' . htmlspecialchars($displayGrade) . '</td>';
                            echo '<td>' . htmlspecialchars($subjectCode) . '</td>';
                            echo '<td>' . htmlspecialchars($course['course_title'] ?? ($course['subject_description'] ?? '')) . '</td>';
                            echo '<td>' . ($course['lec_units'] ?? '0') . '</td>';
                            echo '<td>' . ($course['lab_units'] ?? '0') . '</td>';
                            echo '<td>' . ($course['total_units'] ?? '0') . '</td>';
                            echo '<td>' . (!empty($course['prerequisites']) ? htmlspecialchars($course['prerequisites']) : '-') . '</td>';
                            echo '</tr>';
                            $rowNum++;
                        }
                        ?>
                    </tbody>
                </table>
                <div style="display: flex; justify-content: flex-end; align-items: center; margin: 20px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd;">
                    <span style="font-weight: bold; margin-right: 10px; font-size: 1.1em;">Total Units: </span>
                    <span style="font-weight: bold; font-size: 1.2em; color: #0d6efd;"><?php echo number_format($displayedSecondSemUnits, 1); ?></span>
                </div>
            </div>
        </div>

        <?php
        // Decision support for Year 1: compute average, failed/incomplete counts and show decision
        $numericSum = 0.0;
        $numericCount = 0;
        $failedCount = 0;
        $incompleteCount = 0;
        $totalSubjects = 0;
        $completedUnits = 0;
        $totalPossibleUnits = 0;
        $gradedCourses = [];

        $yearSubjects = array_merge($courses['3-1'] ?? [], $courses['3-2'] ?? []);
        foreach ($yearSubjects as $c) {
            $totalSubjects++;
            $code = strtoupper(trim($c['course_code'] ?? ''));
            $norm = preg_replace('/[^A-Z0-9]/', '', $code);
            $gradeInfo = $normalizedGrades[$norm] ?? ($gradesByCode[$code] ?? null);
            $g = $gradeInfo['grade'] ?? '';
            $units = $c['total_units'] ?? 0;
            $totalPossibleUnits += $units;
            
            if ($g === '' || strtoupper($g) === 'N/A') {
                $incompleteCount++;
                continue;
            }
            
            // Track completed units for courses with grades
            $gUp = strtoupper(trim($g));
            $isPassing = false;
            
            if (is_numeric($g)) {
                $val = floatval($g);
                $numericSum += $val;
                $numericCount++;
                if ($val <= 3.0) {
                    $isPassing = true;
                    $completedUnits += $units;
                } else {
                    $failedCount++;
                }
            } else {
                if (in_array($gUp, ['PASS', 'PASSED', 'COMPLETE', 'COMPLETED', 'S', 'SA', 'S1', 'S2', 'S3', 'S4'])) {
                    $isPassing = true;
                    $completedUnits += $units;
                } elseif (in_array($gUp, ['FAIL','FAILED','F','FA','U'])) {
                    $failedCount++;
                } elseif (in_array($gUp, ['INC','INCOMPLETE'])) {
                    $incompleteCount++;
                }
            }
            
            // Track graded courses for the summary
            if ($g !== '') {
                $gradedCourses[] = [
                    'code' => $code,
                    'title' => $c['course_title'] ?? '',
                    'units' => $units,
                    'grade' => $g,
                    'isPassing' => $isPassing
                ];
            }
        }

        $averageGrade = $numericCount > 0 ? ($numericSum / $numericCount) : null;

        if ($failedCount >= 3) {
            $decision = "At Risk";
            $decisionColor = "#dc3545"; // Red
        } elseif ($averageGrade !== null && $averageGrade < 2.0) {
            $decision = "Excellent";
            $decisionColor = "#198754"; // Green
        } elseif ($averageGrade !== null && $averageGrade >= 2.0 && $averageGrade <= 3.0) {
            $decision = "Normal Progress";
            $decisionColor = "#0d6efd"; // Blue
        } elseif ($incompleteCount >= 1) {
            $decision = "Incomplete Requirements";
            $decisionColor = "#ffc107"; // Yellow
        } elseif ($failedCount > 0 && $failedCount < 3) {
            $decision = "Needs Improvement";
            $decisionColor = "#fd7e14"; // Orange
        } elseif ($averageGrade !== null && $averageGrade > 3.0 && $averageGrade < 5.0) {
            $decision = "Warning: Low Performance";
            $decisionColor = "#dc3545"; // Red
        } elseif ($averageGrade === 5.0) {
            $decision = "Failed";
            $decisionColor = "#dc3545"; // Red
        } else {
            $decision = "Undefined Status";
            $decisionColor = "#6c757d"; // Gray
        }

        // Calculate completion percentage
        $completionPercentage = $totalPossibleUnits > 0 ? round(($completedUnits / $totalPossibleUnits) * 100) : 0;

        echo '<div class="decision-summary" style="margin:20px 0;padding:15px;border-radius:6px;background:#f8f9fa;border-left:6px solid ' . $decisionColor . ';box-shadow:0 2px 4px rgba(0,0,0,0.05);">';
        echo '<h5 style="margin-top:0;color:#2c3e50;border-bottom:1px solid #dee2e6;padding-bottom:8px;margin-bottom:12px;">Academic Decision Summary - Third Year BSIT</h5>';
        
        // Progress bar for completed units
        echo '<div style="margin-bottom:12px;">';
        echo '<div style="display:flex;justify-content:space-between;margin-bottom:5px;">';
        echo '<span>Progress: ' . $completionPercentage . '% Complete</span>';
        echo '<span>' . $completedUnits . ' / ' . $totalPossibleUnits . ' units</span>';
        echo '</div>';
        echo '<div style="height:8px;background:#e9ecef;border-radius:4px;overflow:hidden;">';
        echo '<div style="height:100%;width:' . $completionPercentage . '%;background:' . $decisionColor . ';transition:width 0.3s ease;"></div>';
        echo '</div></div>';
        
        // Update session with total units
        $_SESSION['total_units'] = $completedUnits;
        $totalUnits = $completedUnits; // Update totalUnits for display
        
        // Academic metrics
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:10px;margin-bottom:10px;">';
        echo '<div><strong>Average Grade:</strong> ' . ($averageGrade !== null ? number_format($averageGrade, 2) : 'N/A') . '</div>';
        echo '<div><strong>Completed Units:</strong> ' . $completedUnits . ' / ' . $totalPossibleUnits . '</div>';
        echo '<div><strong>Failed Subjects:</strong> ' . $failedCount . '</div>';
        echo '<div><strong>Incomplete:</strong> ' . $incompleteCount . '</div>';
        echo '</div>';
        
        // Decision with color coding
        echo '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #dee2e6;text-align:center;">';
        echo '<strong>Academic Status:</strong> <span style="color:' . $decisionColor . ';font-weight:bold;">' . htmlspecialchars($decision) . '</span>';
        echo '</div>';
        
        echo 'Total Subjects (year): ' . $totalSubjects . '<br/>';
        echo '</div>';

        // Add Program Director section with e-signature
        echo '<div class="program-director-section" style="margin-top:5px;text-align:center;page-break-inside:avoid;">';
        echo '<div style="margin-top:2px;">';
        echo '______________________________________';
        echo '</div>';
        
        // Check for e-signature for 3rd year semesters (3-1 and 3-2)
        $student_id = $studentData['student_id'] ?? '';
        $signature_displayed = false;
        
        if (!empty($student_id)) {
            // Check for 3rd year semesters signature
            $sigQuery = "SELECT signature_filename FROM evaluation_signatures 
                        WHERE student_id = ? AND year_semester IN ('3-1', '3-2') 
                        ORDER BY evaluation_date DESC LIMIT 1";
            $sigStmt = $conn->prepare($sigQuery);
            if ($sigStmt) {
                $sigStmt->bind_param('s', $student_id);
                $sigStmt->execute();
                $sigResult = $sigStmt->get_result();
                if ($sigResult && $sigResult->num_rows > 0) {
                    $sigRow = $sigResult->fetch_assoc();
                    $signature_file = $sigRow['signature_filename'];
                    
                    if (!empty($signature_file) && file_exists("../adminpage/uploads/evaluation_signatures/" . $signature_file)) {
                        echo '<div style="margin-top:5px;">';
                        echo '<img src="../adminpage/uploads/evaluation_signatures/' . htmlspecialchars($signature_file) . 
                             '" alt="Program Director Signature" style="max-height:40px;max-width:150px;">';
                        echo '</div>';
                        $signature_displayed = true;
                    }
                }
                $sigStmt->close();
            }
        }
        
        echo '<div style="margin-top:2px;font-size:9px;">';
        echo 'PROGRAM DIRECTOR';
        echo '</div>';
        echo '</div>';

        // Close database connection
        $conn->close();
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle mobile menu
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.getElementById('overlay').classList.toggle('show');
        });
        
        // Close menu when clicking overlay
        document.getElementById('overlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('show');
            this.classList.remove('show');
        });
        
        // Close menu when clicking outside on larger screens
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                document.querySelector('.sidebar').classList.remove('show');
                document.getElementById('overlay').classList.remove('show');
            }
        });
    </script>
</body>
</html>
