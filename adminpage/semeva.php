<?php
// Database connection
require_once 'config.php';

// Initialize variables
$student_id = '';
$student_info = null;
$selected_semester = '';
$semesters = [];
$grades_for_semester = [];
$error_message = '';
$program = ''; // Will store BSIT or BSCS

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $query = "SELECT 1 FROM information_schema.columns WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'";
    $result = $conn->query($query);
    return $result && $result->num_rows > 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $selected_semester = trim($_POST['semester'] ?? '');

    // Get column names with fallbacks (but curriculum table in your dump uses these names)
    $codeCol = columnExists($conn, 'curriculum', 'course_code') ? 'course_code' : (columnExists($conn, 'curriculum', 'subject_code') ? 'subject_code' : 'course_code');
    $titleCol = columnExists($conn, 'curriculum', 'course_title') ? 'course_title' : (columnExists($conn, 'curriculum', 'course_title') ? 'course_title' : 'course_title');
    $lecCol = columnExists($conn, 'curriculum', 'lec_units') ? 'lec_units' : (columnExists($conn, 'curriculum', 'lecture_units') ? 'lecture_units' : 'lec_units');
    $labCol = columnExists($conn, 'curriculum', 'lab_units') ? 'lab_units' : (columnExists($conn, 'curriculum', 'laboratory_units') ? 'laboratory_units' : 'lab_units');
    $totalCol = columnExists($conn, 'curriculum', 'total_units') ? 'total_units' : (columnExists($conn, 'curriculum', 'units') ? 'units' : 'total_units');
    $preCol = columnExists($conn, 'curriculum', 'prerequisites') ? 'prerequisites' : (columnExists($conn, 'curriculum', 'pre_req') ? 'pre_req' : 'prerequisites');

    // Fetch student info - try students_db first, then grades_db
    $stmt = $conn->prepare("SELECT * FROM students_db WHERE student_id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_info = $result->fetch_assoc();
        $stmt->close();
        
        // If not found in students_db, try to get basic info from grades_db
        if (!$student_info && $result->num_rows === 0) {
            // Check if this student exists in grades_db
            $checkStmt = $conn->prepare("SELECT DISTINCT TRIM(student_id) AS sid FROM grades_db WHERE TRIM(student_id) = ? LIMIT 1");
            if ($checkStmt) {
                $trimmed_id = $student_id;
                $checkStmt->bind_param('s', $trimmed_id);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    // Try to detect program from signin_db table
                    $detectedProgram = '';
                    
                    // Query to get program from signin_db using student_id
                    if (columnExists($conn, 'signin_db', 'course')) {
                        $signinStmt = $conn->prepare("SELECT course FROM signin_db WHERE student_id = ? LIMIT 1");
                        if ($signinStmt) {
                            $signinStmt->bind_param('s', $student_id);
                            $signinStmt->execute();
                            $signinResult = $signinStmt->get_result();
                            if ($signinRow = $signinResult->fetch_assoc()) {
                                $courseFromSignin = $signinRow['course'];
                                
                                // Check curriculum table to determine program from course
                                if (columnExists($conn, 'curriculum', 'program')) {
                                    $codeColForProg = $codeCol;
                                    $progStmt = $conn->prepare("SELECT DISTINCT program FROM curriculum WHERE $codeColForProg = ? LIMIT 1");
                                    if ($progStmt) {
                                        $progStmt->bind_param('s', $courseFromSignin);
                                        $progStmt->execute();
                                        $progResult = $progStmt->get_result();
                                        if ($progRow = $progResult->fetch_assoc()) {
                                            $detectedProgram = strtoupper(trim($progRow['program'] ?? ''));
                                        }
                                        $progStmt->close();
                                    }
                                }
                            }
                            $signinStmt->close();
                        }
                    }
                    
                    // Create a basic student_info array from grades_db data
                    $student_info = [
                        'student_id' => $student_id,
                        'student_name' => $student_id, // Fallback name
                        'programs' => $detectedProgram ?: 'BSCS' // Use detected program or default to BSCS
                    ];
                } else {
                    $error_message = "Student with ID '{$student_id}' not found in the database. Please check the student ID.";
                }
                $checkStmt->close();
            }
        }
    } else {
        $error_message = 'Failed to prepare student info query: ' . $conn->error;
    }
    
    if ($student_info) {
            // Determine program from student info
            $program_field = trim(strtoupper($student_info['programs'] ?? $student_info['program'] ?? ''));
            if (strpos($program_field, 'BSIT') !== false) {
                $program = 'BSIT';
            } elseif (strpos($program_field, 'BSCS') !== false) {
                $program = 'BSCS';
            } else {
                // Default
                $program = 'BSCS';
            }
            
            // Generate year-level based semester options (1-1 to 4-2)
            $semesters = [];
            for ($year = 1; $year <= 4; $year++) {
                for ($sem = 1; $sem <= 2; $sem++) {
                    $semesters[] = "$year-$sem";
                }
            }

            // Default to first semester if none selected
            if ($selected_semester === '' && !empty($semesters)) {
                $selected_semester = $semesters[0];
            }

            if ($selected_semester !== '') {
                // Parse the year and semester from the selected value (format: 'YEAR-SEM')
                $parts = explode('-', $selected_semester, 2);
                $ySel = $parts[0] ?? '';  // Year level (1-4)
                $sSel = $parts[1] ?? '';  // Semester (1-2)
                
                // For grades_db, year and sem are stored as simple numbers/strings like '1' and '1'
                $dbYear = $ySel;
                $dbSem = $sSel;

                // Build the query to get courses for the selected semester
                // Use a completely different approach - process data in PHP to ensure single entries
                $curriculumQuery = "
                    -- Get all courses from grades_db (will deduplicate in PHP)
                    SELECT 
                        CONVERT(g.course_code USING utf8mb4) COLLATE utf8mb4_unicode_ci AS course_code,
                        CONVERT(COALESCE(c.course_title, g.course_title, g.course_code) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS course_title,
                        COALESCE(c.lec_units, 0) AS lec_units,
                        COALESCE(c.lab_units, 0) AS lab_units,
                        COALESCE(c.total_units, 0) AS total_units,
                        CONVERT(COALESCE(c.prerequisites, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS prerequisites,
                        CONVERT(g.final_grade USING utf8mb4) COLLATE utf8mb4_unicode_ci AS final_grade,
                        'grade' AS source_type
                    FROM grades_db g
                    LEFT JOIN curriculum c ON CONVERT(TRIM(g.course_code) USING utf8mb4) = CONVERT(TRIM(c.$codeCol) USING utf8mb4)
                    WHERE CONVERT(TRIM(g.student_id) USING utf8mb4) = ?
                      AND g.year = ?
                      AND g.sem = ?
                    
                    UNION ALL
                    
                    -- Get all courses from irregular_db (will deduplicate in PHP)
                    SELECT 
                        CONVERT(i.course_code USING utf8mb4) COLLATE utf8mb4_unicode_ci AS course_code,
                        CONVERT(COALESCE(c.course_title, i.course_title, i.course_code) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS course_title,
                        COALESCE(i.lec_units, c.lec_units, 0) AS lec_units,
                        COALESCE(i.lab_units, c.lab_units, 0) AS lab_units,
                        COALESCE(i.total_units, c.total_units, 0) AS total_units,
                        CONVERT(COALESCE(i.prerequisites, c.prerequisites, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci AS prerequisites,
                        NULL AS final_grade,
                        'irregular' AS source_type
                    FROM irregular_db i
                    LEFT JOIN curriculum c ON CONVERT(TRIM(i.course_code) USING utf8mb4) = CONVERT(TRIM(c.$codeCol) USING utf8mb4)
                    WHERE CONVERT(TRIM(i.student_id) USING utf8mb4) = ?
                      AND i.year_level = ?
                      AND i.semester = ?
                      AND i.status = 'active'
                ";

                $curriculumQuery .= " ORDER BY TRIM(course_code)";
                
                // Prepare the statement
                $stmt2 = $conn->prepare($curriculumQuery);
                if ($stmt2) {
                    // Set up parameters for both SELECT statements
                    $paramTypes = 'ssssss';  // grades_db: student_id, year, sem + irregular_db: student_id, year_level, semester
                    $params = [ $student_id, $dbYear, $dbSem, $student_id, $ySel, $sSel ];
                    
                    // Bind parameters (spread)
                    $stmt2->bind_param($paramTypes, ...$params);
                    
                    // Execute the query
                    if ($stmt2->execute()) {
                        $result = $stmt2->get_result();
                        if ($result) {
                            $all_courses = $result->fetch_all(MYSQLI_ASSOC);
                            
                            // Deduplicate courses in PHP - prioritize grades over irregular
                            $unique_courses = [];
                            foreach ($all_courses as $course) {
                                $course_code = trim($course['course_code'] ?? '');
                                
                                // If course already exists, keep the grade version (higher priority)
                                if (isset($unique_courses[$course_code])) {
                                    // Keep existing if it's a grade, replace if current is grade and existing is irregular
                                    if ($course['source_type'] === 'grade' && $unique_courses[$course_code]['source_type'] === 'irregular') {
                                        $unique_courses[$course_code] = $course;
                                    }
                                    // If both are same type, keep the first one
                                } else {
                                    $unique_courses[$course_code] = $course;
                                }
                            }
                            
                            // Convert back to indexed array
                            $grades_for_semester = array_values($unique_courses);

                            // Debug (uncomment for server logs)
                            // error_log("Student: $student_id, Year: $dbYear, Sem: $dbSem");
                            // error_log("Found " . count($all_courses) . " total courses, " . count($grades_for_semester) . " unique courses");
                            
                            if (empty($grades_for_semester)) {
                                // Try to show all courses for this student for debugging
                                $debugQuery = "SELECT TRIM(student_id) AS sid, course_code, year, sem, final_grade FROM grades_db WHERE TRIM(student_id) = ?";
                                $debugStmt = $conn->prepare($debugQuery);
                                if ($debugStmt) {
                                    $debugStmt->bind_param('s', $student_id);
                                    $debugStmt->execute();
                                    $allCourses = $debugStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    // error_log("All courses for student: " . print_r($allCourses, true));
                                    $debugStmt->close();
                                }
                            }
                        } else {
                            $error_message = 'Failed to get result set: ' . $stmt2->error;
                        }
                    } else {
                        $error_message = 'Failed to execute query: ' . $stmt2->error;
                    }
                    $stmt2->close();
                } else {
                    $error_message = 'Failed to prepare query: ' . $conn->error;
                }
            }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Evaluation by Semester</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .grade-report { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px #ddd; }
        .table th, .table td { vertical-align: middle !important; }
        .semester-title { background: #f1f1f1; padding: 10px; margin-top: 30px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body style="background:#f8f9fa;">
<div class="container mt-5">
    <div class="grade-report">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <img src="chomelogo.png" alt="CCC Logo" style="height:60px;">
                <div class="ml-3">
                    <h5 class="mb-0">CITY COLLEGE OF CALAMBA</h5>
                    <div>Student Evaluation Report by Semester</div>
                </div>
            </div>
            <div>
                <a href="dashboard2.php" class="btn btn-outline-secondary me-2">Back to Dashboard</a>
                <a href="list.php" class="btn btn-primary">Student List</a>
            </div>
        </div>
        <form method="POST" class="mb-4">
            <div class="form-row align-items-end">
                <div class="col-auto">
                    <label for="student_id">Student ID:</label>
                    <input type="text" id="student_id" name="student_id" class="form-control" value="<?php echo htmlspecialchars($student_id); ?>" required>
                </div>
                <div class="col-auto">
                    <label for="semester">Semester:</label>
                    <select id="semester" name="semester" class="form-control" <?php echo empty($semesters) ? 'disabled' : ''; ?>>
                        <option value="">-- Select Semester --</option>
                        <?php 
                        $yearNames = [1 => '1st Year', '2nd Year', '3rd Year', '4th Year'];
                        $semNames = [1 => '1st Semester', '2nd Semester'];
                        
                        foreach ($semesters as $sem): 
                            list($year, $semNum) = explode('-', $sem);
                            $displayText = $yearNames[$year] . ' - ' . $semNames[$semNum];
                        ?>
                            <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo ($selected_semester===$sem)?'selected':''; ?>>
                                <?php echo htmlspecialchars($displayText); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">View</button>
                </div>
            </div>
        </form>
        <?php if (!empty($semesters)): ?>
            <div class="mb-3">
                <?php foreach ($semesters as $sem): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                        <input type="hidden" name="semester" value="<?php echo htmlspecialchars($sem); ?>">
                        <button type="submit" class="btn btn-sm <?php echo ($selected_semester===$sem)?'btn-primary':'btn-outline-primary'; ?> mb-1">
                            <?php echo htmlspecialchars($sem); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if ($student_info): ?>
            <div class="mb-4">
                <strong>Name:</strong> <?php 
                    // Try to get name from last_name + first_name, fallback to student_name, then to student_id
                    $name = '';
                    if (!empty($student_info['last_name']) || !empty($student_info['first_name'])) {
                        $name = trim(($student_info['last_name'] ?? '') . ', ' . ($student_info['first_name'] ?? ''));
                    } elseif (!empty($student_info['student_name'])) {
                        $name = $student_info['student_name'];
                    } else {
                        $name = $student_info['student_id'] ?? 'Unknown';
                    }
                    echo htmlspecialchars($name);
                ?><br>
                <strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id'] ?? ''); ?><br>
                <strong>Program:</strong> 
                <?php 
                    $program_display = !empty($program) ? $program : htmlspecialchars($student_info['program'] ?? ($student_info['programs'] ?? 'N/A'));
                    echo htmlspecialchars($program_display);
                ?>
                <br>
            
            </div>
            <style>
                .curriculum-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 0.9rem;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .curriculum-table th {
                    background-color: #3a7bd5;
                    color: white;
                    font-weight: bold;
                    padding: 12px 8px;
                    text-align: center;
                }
                .curriculum-table td {
                    padding: 12px 8px;
                    border-bottom: 1px solid #e0e0e0;
                    vertical-align: middle;
                }
                .curriculum-table tbody tr:hover {
                    background-color: #f5f8ff;
                }
                .semester-title {
                    background-color: #f8f9fa;
                    padding: 12px 20px;
                    margin: 25px 0 15px 0;
                    border-left: 4px solid #3a7bd5;
                    font-weight: 600;
                    color: #2c3e50;
                    font-size: 1.1rem;
                    border-radius: 4px 0 0 4px;
                }
                .grade-cell {
                    font-weight: 600;
                    text-align: center;
                    min-width: 50px;
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 4px;
                    font-size: 0.9rem;
                }
                .grade-passed {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .grade-failed {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .badge-course {
                    background-color: #e9ecef;
                    color: #212529;
                    font-family: monospace;
                    font-weight: 600;
                    padding: 0.3em 0.7em;
                    border-radius: 4px;
                    border: 1px solid #dee2e6;
                    display: inline-block;
                    min-width: 80px;
                    text-align: center;
                }
                .total-units-row {
                    background-color: #f1f8ff;
                    font-weight: 600;
                    border-top: 2px solid #3a7bd5;
                }
                .total-units-row td {
                    padding: 12px 15px !important;
                }
                .text-center {
                    text-align: center;
                }
                .text-muted {
                    color: #6c757d !important;
                }
                .prereq-cell {
                    font-size: 0.85rem;
                    color: #495057;
                    font-style: italic;
                }
                .irregular-subject {
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                }
                .irregular-subject .badge-course {
                    background-color: #ffc107;
                    color: #212529;
                }
                .source-badge {
                    font-size: 0.75rem;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .source-irregular {
                    background-color: #ffc107;
                    color: #212529;
                }
                .source-regular {
                    background-color: #28a745;
                    color: white;
                }
            </style>

            <?php if ($selected_semester !== '' && !empty($grades_for_semester)): ?>
                <div class="semester-title"><?= htmlspecialchars($selected_semester) ?></div>
                <div class="table-responsive">
                    <table class="curriculum-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">CODE</th>
                                <th>COURSE TITLE</th>
                                <th style="width: 6%; text-align: center;">LEC</th>
                                <th style="width: 6%; text-align: center;">LAB</th>
                                <th style="width: 8%; text-align: center;">UNITS</th>
                                <th style="width: 15%; text-align: center;">PRE-REQ</th>
                                <th style="width: 10%; text-align: center;">FINAL GRADE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalUnits = 0.0;
                            foreach ($grades_for_semester as $grade): 
                                $gCode  = trim($grade['course_code'] ?? '');
                                $gTitle = $grade['course_title'] ?? '';
                                $gLec   = (float)($grade['lec_units'] ?? 0);
                                $gLab   = (float)($grade['lab_units'] ?? 0);
                                $gUnits = (float)($grade['total_units'] ?? ($gLec + $gLab));
                                $gFinal = trim($grade['final_grade'] ?? '');
                                
                                // Only show units if there's a passing grade (simple heuristic)
                                $displayUnits = (!empty($gFinal) && $gFinal !== 'N/A' && strtoupper($gFinal) !== 'INC' && strtoupper($gFinal) !== 'DRP' && $gFinal !== '5.0') ? $gUnits : '';
                                
                                // Calculate total units for passed courses
                                if (!empty($gFinal) && $gFinal !== 'N/A' && strtoupper($gFinal) !== 'INC' && strtoupper($gFinal) !== 'DRP' && $gFinal !== '5.0') {
                                    $totalUnits += (float)$gUnits;
                                }
                                
                                // Determine grade class and display value
                                $gradeClass = '';
                                $displayGrade = $gFinal;
                                if (is_numeric($gFinal)) {
                                    $gradeNum = floatval($gFinal);
                                    if ($gradeNum <= 3.0) {
                                        $gradeClass = 'grade-passed';
                                        $displayGrade = $gFinal;
                                    } elseif ($gradeNum >= 3.25 && $gradeNum <= 4.0) {
                                        $gradeClass = 'grade-failed';
                                        $displayGrade = 'INC';
                                    } elseif ($gradeNum >= 5.0) {
                                        $gradeClass = 'grade-failed';
                                        $displayGrade = 'Failed';
                                    } else {
                                        $gradeClass = 'grade-failed';
                                        $displayGrade = 'Failed';
                                    }
                                }
                                
                                // Determine if this is an irregular subject (check if final_grade is null)
                                $isIrregular = is_null($grade['final_grade']);
                                $rowClass = $isIrregular ? 'irregular-subject' : '';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td><span class="badge-course"><?= htmlspecialchars($gCode) ?></span></td>
                                <td><?= htmlspecialchars($gTitle) ?></td>
                                <td class="text-center"><?= number_format($gLec, 1) ?></td>
                                <td class="text-center"><?= number_format($gLab, 1) ?></td>
                                <td class="text-center"><?= $displayUnits !== '' ? number_format($displayUnits, 1) : '<span class="text-muted">-</span>' ?></td>
                                <td class="prereq-cell">
                                    <?php 
                                    $prereq = $grade['prerequisites'] ?? '';
                                    echo $prereq !== '' ? htmlspecialchars($prereq) : '<span class="text-muted">None</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($gFinal)): ?>
                                        <span class="grade-cell <?= $gradeClass ?>"><?= htmlspecialchars($displayGrade) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total Units Row -->
                            <tr class="total-units-row">
                                <td colspan="2">TOTAL UNITS</td>
                                <td class="text-center"><?= number_format(array_sum(array_map(function($g) { return (float)($g['lec_units'] ?? 0); }, $grades_for_semester)), 1) ?></td>
                                <td class="text-center"><?= number_format(array_sum(array_map(function($g) { return (float)($g['lab_units'] ?? 0); }, $grades_for_semester)), 1) ?></td>
                                <td class="text-center"><?= number_format($totalUnits, 1) ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No grades found for this student<?php echo $selected_semester? ' in the selected semester.' : '.'; ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
