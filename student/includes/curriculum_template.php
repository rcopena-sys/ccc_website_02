<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/curriculum_functions.php';

// Get program and year level from the filename
$program = $_GET['program'] ?? '';
$year_level = $_GET['year_level'] ?? '';

// Get student info
$student_id = $_SESSION['student_id'];
$student = getStudentInfo($student_id);
$grades = getStudentGrades($student_id);
$fiscal_year = getCurrentFiscalYear();

// Get curriculum data
$curriculum = getCurriculumData($program, $year_level, $fiscal_year);

// Calculate total units
$first_sem_units = calculateTotalUnits($curriculum['first_sem']);
$second_sem_units = calculateTotalUnits($curriculum['second_sem']);
$midyear_units = calculateTotalUnits($curriculum['midyear']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($program); ?> - <?php echo htmlspecialchars($year_level); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .sidebar {
            background-color: #343a40;
            color: white;
            min-height: 100vh;
            padding: 20px 0;
        }
        .main-content {
            padding: 20px;
        }
        .prospectus-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 5em;
            transform: rotate(-45deg);
            top: 40%;
            left: 20%;
            pointer-events: none;
            z-index: 1;
        }
        .program-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #2c3e50;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .semester {
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        .semester-title {
            background-color: #6c757d;
            color: white;
            padding: 10px 15px;
            margin: 0;
            font-weight: bold;
        }
        .curriculum-table {
            width: 100%;
            border-collapse: collapse;
        }
        .curriculum-table th, 
        .curriculum-table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        .curriculum-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .grade-cell {
            text-align: center;
            font-weight: bold;
        }
        .passed {
            color: #28a745;
        }
        .failed {
            color: #dc3545;
        }
        .ongoing {
            color: #ffc107;
        }
        .total-units {
            text-align: right;
            font-weight: bold;
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar no-print">
                <div class="text-center mb-4">
                    <h4>Menu</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link text-white">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strtolower($program) . '1st.php'; ?>" class="nav-link text-white <?php echo $year_level === '1st Year' ? 'active' : ''; ?>">
                            <i class="bi bi-1-circle"></i> 1st Year
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strtolower($program) . '2nd.php'; ?>" class="nav-link text-white <?php echo $year_level === '2nd Year' ? 'active' : ''; ?>">
                            <i class="bi bi-2-circle"></i> 2nd Year
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strtolower($program) . '3rd.php'; ?>" class="nav-link text-white <?php echo $year_level === '3rd Year' ? 'active' : ''; ?>">
                            <i class="bi bi-3-circle"></i> 3rd Year
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strtolower($program) . '4th.php'; ?>" class="nav-link text-white <?php echo $year_level === '4th Year' ? 'active' : ''; ?>">
                            <i class="bi bi-4-circle"></i> 4th Year
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <button onclick="window.print()" class="btn btn-primary no-print print-button">
                    <i class="bi bi-printer"></i> Print
                </button>
                
                <div class="prospectus-container">
                    <div class="watermark"><?php echo $program; ?></div>
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2>Republic of the Philippines</h2>
                        <h3>CAVITE STATE UNIVERSITY</h3>
                        <h4>CCAT Rosario Campus</h4>
                        <h5>Rosario, Cavite</h5>
                    </div>
                    
                    <!-- Program Title -->
                    <div class="program-title">
                        <?php echo htmlspecialchars($program); ?> - <?php echo htmlspecialchars($year_level); ?>
                    </div>
                    
                    <!-- Student Info -->
                    <div class="student-info">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
                                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Program:</strong> <?php echo htmlspecialchars($program); ?></p>
                                <p><strong>Classification:</strong> <?php echo htmlspecialchars($student['classification']); ?></p>
                            </div>
                        </div>
                        <p><strong>Academic Year:</strong> <?php echo htmlspecialchars($fiscal_year); ?></p>
                    </div>
                    
                    <!-- First Semester -->
                    <?php if (!empty($curriculum['first_sem'])): ?>
                    <div class="semester">
                        <h4 class="semester-title">FIRST SEMESTER</h4>
                        <table class="curriculum-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Title</th>
                                    <th>Lec</th>
                                    <th>Lab</th>
                                    <th>Units</th>
                                    <th>Pre-requisite</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($curriculum['first_sem'] as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                    <td class="text-center"><?php echo $course['lec_units'] > 0 ? $course['lec_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['lab_units'] > 0 ? $course['lab_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['total_units']; ?></td>
                                    <td><?php echo htmlspecialchars($course['prerequisites'] ?: '-'); ?></td>
                                    <td class="grade-cell <?php 
                                        if (isset($grades[$course['subject_code']])) {
                                            $grade = (float)$grades[$course['subject_code']];
                                            if ($grade >= 3.0) {
                                                echo 'failed';
                                            } else {
                                                echo 'passed';
                                            }
                                        }
                                    ?>">
                                        <?php echo $grades[$course['subject_code']] ?? ''; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="total-units">
                            Total Units: <?php echo $first_sem_units; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Second Semester -->
                    <?php if (!empty($curriculum['second_sem'])): ?>
                    <div class="semester">
                        <h4 class="semester-title">SECOND SEMESTER</h4>
                        <table class="curriculum-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Title</th>
                                    <th>Lec</th>
                                    <th>Lab</th>
                                    <th>Units</th>
                                    <th>Pre-requisite</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($curriculum['second_sem'] as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                    <td class="text-center"><?php echo $course['lec_units'] > 0 ? $course['lec_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['lab_units'] > 0 ? $course['lab_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['total_units']; ?></td>
                                    <td><?php echo htmlspecialchars($course['prerequisites'] ?: '-'); ?></td>
                                    <td class="grade-cell <?php 
                                        if (isset($grades[$course['subject_code']])) {
                                            $grade = (float)$grades[$course['subject_code']];
                                            if ($grade >= 3.0) {
                                                echo 'failed';
                                            } else {
                                                echo 'passed';
                                            }
                                        }
                                    ?>">
                                        <?php echo $grades[$course['subject_code']] ?? ''; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="total-units">
                            Total Units: <?php echo $second_sem_units; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Midyear -->
                    <?php if (!empty($curriculum['midyear'])): ?>
                    <div class="semester">
                        <h4 class="semester-title">MIDYEAR</h4>
                        <table class="curriculum-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Title</th>
                                    <th>Lec</th>
                                    <th>Lab</th>
                                    <th>Units</th>
                                    <th>Pre-requisite</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($curriculum['midyear'] as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                    <td class="text-center"><?php echo $course['lec_units'] > 0 ? $course['lec_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['lab_units'] > 0 ? $course['lab_units'] : ''; ?></td>
                                    <td class="text-center"><?php echo $course['total_units']; ?></td>
                                    <td><?php echo htmlspecialchars($course['prerequisites'] ?: '-'); ?></td>
                                    <td class="grade-cell <?php 
                                        if (isset($grades[$course['subject_code']])) {
                                            $grade = (float)$grades[$course['subject_code']];
                                            if ($grade >= 3.0) {
                                                echo 'failed';
                                            } else {
                                                echo 'passed';
                                            }
                                        }
                                    ?>">
                                        <?php echo $grades[$course['subject_code']] ?? ''; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="total-units">
                            Total Units: <?php echo $midyear_units; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print when page loads (for printing)
        window.onload = function() {
            if (window.location.search.includes('print=1')) {
                window.print();
            }
        };
    </script>
</body>
</html>
