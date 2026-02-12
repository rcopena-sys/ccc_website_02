<?php
require_once 'config.php';

$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_id'], $_POST['course_code'], $_POST['year'], $_POST['sem'], $_POST['grade'])) {
        $student_id = $conn->real_escape_string($_POST['student_id']);
        $course_code = $conn->real_escape_string($_POST['course_code']);
        $year = $conn->real_escape_string($_POST['year']);
        $sem = $conn->real_escape_string($_POST['sem']);
        $grade = $conn->real_escape_string($_POST['grade']);
        
        // Check if this is an edit or new entry
        $is_edit = isset($_GET['edit']) && $_GET['edit'] === 'true';
        
        if ($is_edit) {
            // Update existing grade
            $sql = "UPDATE grades_db 
                    SET final_grade = '$grade' 
                    WHERE student_id = '$student_id' 
                    AND course_code = '$course_code' 
                    AND year = '$year' 
                    AND sem = '$sem'";
        } else {
            // Insert new grade
            $sql = "INSERT INTO grades_db (student_id, course_code, year, sem, final_grade) 
                    VALUES ('$student_id', '$course_code', '$year', '$sem', '$grade')
                    ON DUPLICATE KEY UPDATE final_grade = '$grade'";
        }
        
        if ($conn->query($sql) === TRUE) {
            $success = true;
            // Refresh the page to show the updated data
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
$search_results = [];
$search_performed = false;


if (!empty($_GET['search_student']) || !empty($_GET['search_course']) || !empty($_GET['year']) || !empty($_GET['sem'])) {
    $search_student = $conn->real_escape_string($_GET['search_student'] ?? '');
    $search_course = $conn->real_escape_string($_GET['search_course'] ?? '');
    $search_year = $conn->real_escape_string($_GET['year'] ?? '');
    $search_sem = $conn->real_escape_string($_GET['sem'] ?? '');
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search_student)) {
        $where_conditions[] = "student_id LIKE ?";
        $params[] = "%$search_student%";
        $types .= 's';
    }
    
    if (!empty($search_course)) {
        $where_conditions[] = "course_code LIKE ?";
        $params[] = "%$search_course%";
        $types .= 's';
    }

    if (!empty($search_year)) {
        $where_conditions[] = "year = ?";
        $params[] = $search_year;
        $types .= 's';
    }

    if (!empty($search_sem)) {
        $where_conditions[] = "sem = ?";
        $params[] = $search_sem;
        $types .= 's';
    }
    
    if (!empty($where_conditions)) {
        $sql = "SELECT * FROM grades_db WHERE " . implode(' AND ', $where_conditions) . " ORDER BY year DESC, sem, student_id, course_code";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_performed = true;
            $stmt->close();
        }
    }
}

// Handle grade entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($conn->real_escape_string($_POST['student_id'] ?? ''));
    $course_code = trim($conn->real_escape_string($_POST['course_code'] ?? ''));
    $year = $conn->real_escape_string($_POST['year'] ?? '');
    $sem = $conn->real_escape_string($_POST['sem'] ?? '');
    $final_grade = trim($conn->real_escape_string($_POST['grade'] ?? ''));
    $course_title = trim($conn->real_escape_string($_POST['course_title'] ?? ''));

    // Validation
    if (empty($student_id) || empty($course_code) || empty($year) || empty($sem) || empty($final_grade)) {
        $error = 'All required fields must be filled.';
    } elseif (!is_numeric($final_grade) || $final_grade < 1.0 || $final_grade > 5.0) {
        $error = 'Grade must be a number between 1.0 and 5.0.';
    } else {
        // Check if grade already exists for this student and course
        $check_stmt = $conn->prepare("SELECT student_id FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
        if ($check_stmt) {
            $check_stmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
            $check_stmt->execute();
            $existing = $check_stmt->get_result();
            
            if ($existing->num_rows > 0) {
                // Update existing grade
                $update_stmt = $conn->prepare("UPDATE grades_db SET final_grade = ?, course_title = ? WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param('ssssss', $final_grade, $course_title, $student_id, $course_code, $year, $sem);
                    if ($update_stmt->execute()) {
                        $success = true;
                        $success_message = 'Grade updated successfully!';
                    } else {
                        $error = 'Failed to update grade: ' . $conn->error;
                    }
                    $update_stmt->close();
                }
            } else {
                // Insert new grade
                $insert_stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, year, sem, final_grade, course_title) VALUES (?, ?, ?, ?, ?, ?)");
                if ($insert_stmt) {
                    $insert_stmt->bind_param('ssssss', $student_id, $course_code, $year, $sem, $final_grade, $course_title);
                    if ($insert_stmt->execute()) {
                        $success = true;
                        $success_message = 'Grade record inserted successfully!';
                    } else {
                        $error = 'Failed to insert record: ' . $conn->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}

// Handle delete request
if (isset($_POST['delete']) && isset($_POST['student_id'], $_POST['course_code'], $_POST['year'], $_POST['sem'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $course_code = $conn->real_escape_string($_POST['course_code']);
    $year = $conn->real_escape_string($_POST['year']);
    $sem = $conn->real_escape_string($_POST['sem']);
    $delete_stmt = $conn->prepare("DELETE FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
    if ($delete_stmt) {
        $delete_stmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
        if ($delete_stmt->execute()) {
            $success = true;
            $success_message = 'Grade record deleted successfully!';
        } else {
            $error = 'Failed to delete record: ' . $conn->error;
        }
        $delete_stmt->close();
    } else {
        $error = 'Failed to prepare delete statement: ' . $conn->error;
    }
}

// Get course codes for dropdown
$course_codes = [];
$course_result = $conn->query("SELECT DISTINCT course_code, course_title FROM grades_db ORDER BY course_code");
if ($course_result) {
    while ($row = $course_result->fetch_assoc()) {
        $course_codes[] = $row;
    }
}

// Get student IDs for dropdown
$student_ids = [];
$student_result = $conn->query("SELECT DISTINCT student_id FROM grades_db ORDER BY student_id");
if ($student_result) {
    while ($row = $student_result->fetch_assoc()) {
        $student_ids[] = $row['student_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management System</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --border-radius: 0.35rem;
            --box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            background-color: var(--secondary-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #4e73df;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.04em;
            color: #4e73df;
            background-color: #f8f9fa;
            border-bottom-width: 1px;
            padding: 0.75rem;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.75rem 1rem;
            background-color: white;
            border-bottom-width: 1px;
            box-shadow: none;
        }
        
        .table-hover > tbody > tr:hover > * {
            --bs-table-accent-bg: rgba(0, 0, 0, 0.02);
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }
        
        .grade-success { background-color: #d4edda !important; }
        .grade-warning { background-color: #fff3cd !important; }
        .grade-danger { background-color: #f8d7da !important; }
        
        .search-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .action-buttons .btn {
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            color: #d1d3e2;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 1rem 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-footer {
            background-color: #f8f9fc;
            border-top: 1px solid #e3e6f0;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xxl-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h3 mb-0"><i class="bi bi-mortarboard me-2"></i>Grade Management System</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboardr.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Grades</li>
                    </ol>
                </nav>
            </div>
            <a href="dashboardr.php" class="btn btn-outline-primary d-flex align-items-center">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success === true): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($success_message ?? 'Operation completed successfully!') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Search Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="bi bi-search me-2"></i>Search Grades</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="search_student" class="form-label">Student ID</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <input type="text" class="form-control" id="search_student" name="search_student" 
                                       value="<?= htmlspecialchars($_GET['search_student'] ?? '') ?>" 
                                       placeholder="Enter student ID">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="search_course" class="form-label">Course Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-journal-text"></i></span>
                                <input type="text" class="form-control" id="search_course" name="search_course" 
                                       value="<?= htmlspecialchars($_GET['search_course'] ?? '') ?>" 
                                       placeholder="Enter course code">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">All Years</option>
                                <?php
                                $yearQuery = "SELECT DISTINCT year FROM grades_db WHERE year IS NOT NULL ORDER BY year DESC";
                                $yearResult = $conn->query($yearQuery);
                                $selectedYear = $_GET['year'] ?? '';
                                while ($year = $yearResult->fetch_assoc()) {
                                    $selected = ($selectedYear == $year['year']) ? 'selected' : '';
                                    echo "<option value='{$year['year']}' $selected>Year {$year['year']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="sem" class="form-label">Semester</label>
                            <select class="form-select" id="sem" name="sem">
                                <option value="">All Semesters</option>
                                <option value="1" <?= (isset($_GET['sem']) && $_GET['sem'] == 1) ? 'selected' : '' ?>>1st Semester</option>
                                <option value="2" <?= (isset($_GET['sem']) && $_GET['sem'] == 2) ? 'selected' : '' ?>>2nd Semester</option>
                                <option value="3" <?= (isset($_GET['sem']) && $_GET['sem'] == 3) ? 'selected' : '' ?>>Summer</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <div class="d-grid gap-2 d-md-flex w-100">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="bi bi-search me-1"></i> Search
                                </button>
                                <?php if (isset($_GET['search_student']) || isset($_GET['search_course']) || isset($_GET['year']) || isset($_GET['sem'])): ?>
                                    <a href="studentgrade.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class="bi bi-table me-2"></i>
                <?= $search_performed ? 'Search Results' : 'All Grades' ?>
            </h4>
            <a href="addsturegs.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add New Grade
            </a>
        </div>
            <a href="upload_grades_csv.php" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i> Upload CSV
            </a>
        </div>

        <!-- Display Grades -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive mx-auto" style="max-width: 98%;">
                    <table class="table table-hover mb-0 mx-auto text-center" style="background-color: white; width: 100%; max-width: 98%;">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th class="text-nowrap text-center">Student ID</th>
                                <th class="text-nowrap text-center">Program</th>
                                <th class="text-center">Title</th>
                                <th class="text-nowrap text-center">Year</th>
                                <th class="text-nowrap text-center">Sem</th>
                                <th class="text-nowrap text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $results = [];
                        if ($search_performed) {
                            $results = $search_results;
                        } else {
                            // Modified query to join with assign_curriculum table
                            $query = "SELECT g.*, ac.program, ac.fiscal_year 
                                     FROM grades_db g
                                     LEFT JOIN assign_curriculum ac ON g.student_id = ac.program_id
                                     ORDER BY g.student_id, g.course_code";
                            
                            $all_grades = $conn->query($query);
                            if ($all_grades) {
                                $results = $all_grades->fetch_all(MYSQLI_ASSOC);
                            }
                        }
                        
                        if (!empty($results)): 
                            foreach ($results as $grade): 
                                $grade_class = '';
                                $numeric_grade = is_numeric($grade['final_grade']) ? (float)$grade['final_grade'] : null;
                                 
                                if ($numeric_grade !== null) {
                                    if ($numeric_grade >= 3.0) {
                                        $grade_class = 'table-danger';
                                    } elseif ($numeric_grade >= 2.0) {
                                        $grade_class = 'table-warning';
                                    } else {
                                        $grade_class = 'table-success';
                                    }
                                }
                        ?>
                            <tr class="<?= $grade_class ?> text-center" style="cursor: pointer;" onclick="event.stopPropagation();">
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <?= htmlspecialchars($grade['student_id']) ?>
                                    </div>
                                </td>
                                <td class="text-nowrap fw-semibold"><?= htmlspecialchars($grade['course_code']) ?></td>
                                <td class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($grade['course_title'] ?? 'N/A') ?>">
                                    <?= htmlspecialchars($grade['course_title'] ?? 'N/A') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <?= htmlspecialchars($grade['year']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <?= htmlspecialchars($grade['sem']) ?>
                                    </span>
                                </td>
                                <td class="text-center fw-bold">
                                    <?php 
                                    $grade_class = '';
                                    $display_grade = $grade['final_grade'];
                                    $numeric_grade = is_numeric($display_grade) ? (float)$display_grade : null;
                                    
                                    if ($numeric_grade !== null) {
                                        if ($numeric_grade == 5.0) {
                                            $grade_class = 'bg-danger';
                                            $display_text = '5.0 (Failed)';
                                        } elseif ($numeric_grade >= 3.25 && $numeric_grade <= 4.0) {
                                            $grade_class = 'bg-warning';
                                            $display_text = number_format($numeric_grade, 1) . ' (INC)';
                                        } elseif ($numeric_grade >= 1.0 && $numeric_grade <= 3.0) {
                                            $grade_class = 'bg-success';
                                            $display_text = number_format($numeric_grade, 1) . ' ';
                                        } else {
                                            $grade_class = 'bg-secondary';
                                            $display_text = number_format($numeric_grade, 1);
                                        }
                                    } else {
                                        $grade_class = 'bg-secondary';
                                        $display_text = htmlspecialchars($display_grade);
                                    }
                                    ?>
                                    <span class="badge rounded-pill <?= $grade_class ?> text-white" style="min-width: 70px;">
                                        <?= $display_text ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5 class="mt-3 mb-2">No grades found</h5>
                                        <p class="text-muted mb-0">
                                            <?= $search_performed ? 'Try adjusting your search criteria' : 'No grades have been added yet' ?>
                                        </p>
                                        <?php if ($search_performed): ?>
                                            <a href="studentgrade.php" class="btn btn-sm btn-outline-primary mt-3">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Search
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Grade Entry Modal -->
        <div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="gradeModalLabel">Add New Grade</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="gradeForm" method="POST" action="studentgrade.php" class="needs-validation" novalidate>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="student_id" class="form-label">Student ID <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           placeholder="Enter student ID" required>
                                </div>
                                <div class="invalid-feedback">
                                    Please provide a student ID.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-journal-text"></i></span>
                                    <input type="text" class="form-control" id="course_code" name="course_code" 
                                           placeholder="Enter course code" required>
                                </div>
                                <div id="courseList" class="list-group mt-1 position-absolute w-100 z-3" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                <div class="invalid-feedback">
                                    Please provide a course code.
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="course_title" class="form-label">Course Title</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-journal-bookmark"></i></span>
                                    <input type="text" class="form-control" id="course_title" name="course_title" readonly>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <select class="form-select" id="year" name="year" required>
                                        <option value="" selected disabled>Select Year</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                                <div class="invalid-feedback">
                                    Please select an academic year.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sem" class="form-label">Semester <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar-week"></i></span>
                                    <select class="form-select" id="sem" name="sem" required>
                                        <option value="" selected disabled>Select Semester</option>
                                        <option value="1">1st Semester</option>
                                        <option value="2">2nd Semester</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>
                                <div class="invalid-feedback">
                                    Please select a semester.
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="grade" class="form-label">Grade <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-award"></i></span>
                                    <input type="text" class="form-control" id="grade" name="grade" 
                                           placeholder="Enter final grade (e.g., 1.0, 1.25, 2.5, 3.0, 5.0, INC, DRP)" required>
                                </div>
                                <div class="form-text text-muted">
                                    Enter the final grade (e.g., 1.0, 1.25, 2.5, 3.0, 5.0, INC, DRP, etc.)
                                </div>
                                <div class="invalid-feedback">
                                    Please provide a valid grade.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add Grade
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Missing Grades Section -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-warning bg-opacity-10">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Subjects with Missing Grades</h5>
                    <div class="w-100 w-md-auto">
                        <form method="GET" class="d-flex flex-column flex-md-row gap-2">
                            <div class="input-group input-group-sm" style="min-width: 180px;">
                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                <select name="year" class="form-select form-select-sm">
                                    <option value="">All Years</option>
                                    <?php
                                    $yearQuery = "SELECT DISTINCT year FROM grades_db WHERE year IS NOT NULL ORDER BY year DESC";
                                    $yearResult = $conn->query($yearQuery);
                                    $selectedYear = isset($_GET['year']) ? $_GET['year'] : '';
                                    while ($year = $yearResult->fetch_assoc()) {
                                        $selected = ($selectedYear == $year['year']) ? 'selected' : '';
                                        echo "<option value='{$year['year']}' $selected>Year {$year['year']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="input-group input-group-sm" style="min-width: 160px;">
                                <span class="input-group-text"><i class="bi bi-calendar-week"></i></span>
                                <select name="sem" class="form-select form-select-sm">
                                    <option value="">All Semesters</option>
                                    <option value="1" <?= (isset($_GET['sem']) && $_GET['sem'] == 1) ? 'selected' : '' ?>>1st Semester</option>
                                    <option value="2" <?= (isset($_GET['sem']) && $_GET['sem'] == 2) ? 'selected' : '' ?>>2nd Semester</option>
                                    <option value="3" <?= (isset($_GET['sem']) && $_GET['sem'] == 3) ? 'selected' : '' ?>>Summer</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="bi bi-funnel me-1"></i> Filter
                                </button>
                                <?php if (isset($_GET['year']) || isset($_GET['sem'])): ?>
                                    <a href="studentgrade.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-x-lg"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php
                // Build the base query
                $whereClause = "WHERE (g.final_grade IS NULL OR g.final_grade = '') 
                               AND g.course_code != ''
                               AND g.year IS NOT NULL
                               AND g.sem IS NOT NULL";
                
                // Add year filter if set
                if (isset($_GET['year']) && !empty($_GET['year'])) {
                    $year = $conn->real_escape_string($_GET['year']);
                    $whereClause .= " AND g.year = '$year'";
                }
                
                // Add semester filter if set
                if (isset($_GET['sem']) && !empty($_GET['sem'])) {
                    $sem = $conn->real_escape_string($_GET['sem']);
                    $whereClause .= " AND g.sem = '$sem'";
                }
                
                $missingGradesQuery = "
                    SELECT 
                        g.year,
                        g.sem,
                        s.student_id, 
                        s.firstname, 
                        s.lastname, 
                        s.middlename, 
                        s.suffix, 
                        g.course_code, 
                        c.course_title,
                        p.program_name,
                        p.program_code
                    FROM grades_db g
                    JOIN signin_db s ON s.student_id = g.student_id
                    LEFT JOIN curriculum c ON g.course_code = c.course_code
                    LEFT JOIN program p ON s.program = p.program_id
                    $whereClause
                    ORDER BY g.year DESC, g.sem, p.program_code, s.lastname, s.firstname, g.course_code
                ";
                
                $missingGradesResult = $conn->query($missingGradesQuery);
                
                if ($missingGradesResult) {
                    if ($missingGradesResult->num_rows > 0) {
                        $currentYear = null;
                        $currentSem = null;
                        $currentProgram = null;
                        $hasData = false;
                    
                    while ($row = $missingGradesResult->fetch_assoc()) {
                        // Check if we need to start a new year/semester section
                        if ($currentYear !== $row['year'] || $currentSem !== $row['sem']) {
                            // Close previous section if exists
                            if ($currentYear !== null) {
                                echo "</tbody></table></div></div>";
                            }
                            
                            $currentYear = $row['year'];
                            $currentSem = $row['sem'];
                            $currentProgram = $row['program_code'];
                            
                            // Get semester display name
                            $semesterName = '';
                            switch($currentSem) {
                                case 1: $semesterName = '1st Semester'; break;
                                case 2: $semesterName = '2nd Semester'; break;
                                case 3: $semesterName = 'Summer'; break;
                                default: $semesterName = 'N/A';
                            }
                            
                            // Start new section
                            echo "<div class='p-4 border-bottom'>";
                            echo "<h5 class='mb-3'><i class='bi bi-calendar3 me-2'></i>Year {$currentYear} - {$semesterName}</h5>";
                            echo "<div class='table-responsive d-flex justify-content-center'>";
                            echo "<table class='table table-hover mb-0 text-center' style='width: 98%'>";
                            echo "<thead class='table-light'>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Program</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Action</th>
                                    </tr>
                                  </thead>
                                  <tbody>";
                        }
                        
                        $hasData = true;
                        $studentName = htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . 
                            (!empty($row['middlename']) ? ' ' . substr($row['middlename'], 0, 1) . '.' : '') .
                            (!empty($row['suffix']) ? ' ' . $row['suffix'] : ''));
                        
                        echo "<tr>";
                        echo "<td class='align-middle'>" . htmlspecialchars($row['student_id']) . "</td>";
                        echo "<td class='align-middle'>" . $studentName . "</td>";
                        echo "<td class='align-middle'><span class='badge bg-info text-dark'>" . htmlspecialchars($row['program_code']) . "</span></td>";
                        echo "<td class='align-middle'>" . htmlspecialchars($row['course_code']) . "</td>";
                        echo "<td class='align-middle'>" . (!empty($row['course_title']) ? htmlspecialchars($row['course_title']) : 'N/A') . "</td>";
                        echo "<td class='align-middle'>
                                <button class='btn btn-sm btn-warning edit-grade' 
                                        data-student-id='" . htmlspecialchars($row['student_id']) . "'
                                        data-course-code='" . htmlspecialchars($row['course_code']) . "'
                                        data-year='" . htmlspecialchars($row['year']) . "'
                                        data-sem='" . htmlspecialchars($row['sem']) . "'
                                        data-bs-toggle='tooltip' 
                                        title='Add missing grade'>
                                    <i class='bi bi-pencil me-1'></i> Add Grade
                                </button>
                              </td>";
                        echo "</tr>";
                    }
                    
                        // Close the last section if any data was displayed
                        if ($hasData) {
                            echo "</tbody></table></div></div>";
                        } else {
                            // Show a message when no results match the filter
                            $filterMessage = "No missing grades found";
                            if (isset($_GET['year']) || isset($_GET['sem'])) {
                                $filterMessage .= " for the selected filters";
                                if (isset($_GET['year'])) {
                                    $filterMessage .= ": Year " . htmlspecialchars($_GET['year']);
                                }
                                if (isset($_GET['sem'])) {
                                    $semName = [1 => '1st Semester', 2 => '2nd Semester', 3 => 'Summer'][$_GET['sem']] ?? '';
                                    $filterMessage .= isset($_GET['year']) ? ", $semName" : ": $semName";
                                }
                            } else {
                                $filterMessage = "No missing grades found. All courses have been graded.";
                            }
                            
                            echo "<div class='p-4 text-center text-muted'>
                                    <i class='bi bi-check-circle-fill text-success me-2'></i>
                                    $filterMessage
                                  </div>";
                        }
                    } else {
                        // Handle query error
                        echo "<div class='p-4 text-center text-danger'>
                                <i class='bi bi-exclamation-triangle-fill me-2'></i>
                                Error fetching missing grades. Please try again later.
                              </div>";
                    }
                } else {
                    // Handle query error
                    echo "<div class='p-4 text-center text-danger'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>
                            Error fetching missing grades. Please try again later.
                          </div>";
                }
                ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle edit grade button clicks
    document.querySelectorAll('.edit-grade').forEach(button => {
        button.addEventListener('click', function() {
            editGrade(
                this.dataset.studentId,
                this.dataset.courseCode,
                this.dataset.year,
                this.dataset.sem,
                this.dataset.grade,
                this.dataset.courseTitle || ''
            );
        });
    });
            
            // Handle delete grade button clicks
            document.querySelectorAll('.delete-grade').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this grade? This action cannot be undone.')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'studentgrade.php';
                        
                        const fields = [
                            { name: 'student_id', value: this.dataset.studentId },
                            { name: 'course_code', value: this.dataset.courseCode },
                            { name: 'year', value: this.dataset.year },
                            { name: 'sem', value: this.dataset.sem },
                            { name: 'delete', value: '1' }
                        ];
                        
                        fields.forEach(field => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = field.name;
                            input.value = field.value;
                            form.appendChild(input);
                        });
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
            
            // Real-time grade formatting
            const gradeInput = document.getElementById('grade');
            if (gradeInput) {
                gradeInput.addEventListener('input', handleGradeInput);
            }
            
            // Course code autocomplete
            const courseCodeInput = document.getElementById('course_code');
            if (courseCodeInput) {
                courseCodeInput.addEventListener('input', handleCourseCodeInput);
            }
            
            // Close course list when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#course_code') && !e.target.closest('#courseList')) {
                    const courseList = document.getElementById('courseList');
                    if (courseList) courseList.style.display = 'none';
                }
            });
            
            // Auto-focus on modal shown
            const gradeModal = document.getElementById('gradeModal');
            if (gradeModal) {
                gradeModal.addEventListener('shown.bs.modal', function() {
                    const firstInput = this.querySelector('input:not([type="hidden"]), select');
                    if (firstInput) firstInput.focus();
                });
            }
        });
        
        // Edit grade function
        function editGrade(studentId, courseCode, year, sem, grade, courseTitle = '') {
            // Set form values
            document.getElementById('student_id').value = studentId;
            document.getElementById('course_code').value = courseCode;
            document.getElementById('year').value = year;
            document.getElementById('sem').value = sem;
            document.getElementById('grade').value = grade;
            document.getElementById('course_title').value = courseTitle;
            
            // Update UI
            const modalTitle = document.getElementById('gradeModalLabel');
            const submitBtn = document.querySelector('#gradeForm [type="submit"]');
            modalTitle.innerHTML = '<i class="bi bi-pencil"></i> Edit Grade';
            submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Update Grade';
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-warning');
            
            // Set form action for update
            document.getElementById('gradeForm').action = 'studentgrade.php?edit=true';
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('gradeModal'));
            modal.show();
        }
        
        // Handle grade input with real-time validation
        function handleGradeInput(e) {
            const input = e.target;
            let value = input.value.trim().toUpperCase();
            
            // Auto-capitalize non-numeric grades
            if (isNaN(value)) {
                input.value = value;
            }
            // Auto-format numeric grades
            else if (value.includes('.')) {
                const parts = value.split('.');
                if (parts[1] && parts[1].length > 2) {
                    input.value = parseFloat(value).toFixed(2);
                }
            }
            
            // Validate in real-time
            validateGradeInput(input);
        }
        
        // Handle course code input with autocomplete
        function handleCourseCodeInput(e) {
            const input = e.target;
            const value = input.value.trim().toUpperCase();
            const courseList = document.getElementById('courseList');
            
            if (!courseList) return;
            
            if (value.length < 2) {
                courseList.style.display = 'none';
                document.getElementById('course_title').value = '';
                return;
            }
            
            // In a real implementation, you would fetch this from the server
            // For now, we'll use the PHP variable passed to JavaScript
            const availableCourses = <?= json_encode($course_codes) ?>;
            
            const filteredCourses = availableCourses.filter(course => 
                course.course_code.includes(value) || 
                (course.course_title && course.course_title.toUpperCase().includes(value))
            );
            
            courseList.innerHTML = '';
            
            if (filteredCourses.length > 0) {
                filteredCourses.forEach(course => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action text-start';
                    item.innerHTML = `
                        <div class="fw-bold">${course.course_code}</div>
                        <small class="text-muted">${course.course_title || ''}</small>
                    `;
                    item.addEventListener('click', () => {
                        input.value = course.course_code;
                        document.getElementById('course_title').value = course.course_title || '';
                        courseList.style.display = 'none';
                        document.getElementById('year').focus();
                    });
                    courseList.appendChild(item);
                });
                courseList.style.display = 'block';
            } else {
                const noResults = document.createElement('div');
                noResults.className = 'list-group-item text-muted';
                noResults.textContent = 'No matching courses found';
                courseList.appendChild(noResults);
                courseList.style.display = 'block';
            }
        }
        
        // Validate grade input
        function validateGradeInput(input) {
            const value = input.value.trim().toUpperCase();
            const grade = parseFloat(value);
            
            // Reset validation state
            input.classList.remove('is-invalid', 'is-valid');
            
            // Clear any existing error messages
            const existingError = input.nextElementSibling;
            if (existingError && existingError.classList.contains('invalid-feedback')) {
                existingError.remove();
            }
            
            // Skip validation if empty
            if (!value) return true;
            
            // Numeric validation
            if (!isNaN(grade)) {
                if (grade < 1.0 || grade > 5.0) {
                    showValidationError(input, 'Grade must be between 1.0 and 5.0');
                    return false;
                }
                
                // Check for valid grade increments (e.g., 1.0, 1.25, 1.5, 1.75, etc.)
                const validIncrements = [0, 25, 50, 75];
                const decimalPart = Math.round((grade % 1) * 100);
                if (grade % 1 !== 0 && !validIncrements.includes(decimalPart)) {
                    showValidationError(input, 'Invalid grade increment. Use .00, .25, .50, or .75');
                    return false;
                }
                
                input.classList.add('is-valid');
                return true;
            } 
            // Non-numeric validation
            else {
                const validNonNumericGrades = ['INC', 'DRP', 'PASS', 'FAIL', 'W', 'D', 'WITHDRAWN', 'DROPPED'];
                if (!validNonNumericGrades.includes(value)) {
                    showValidationError(input, `Invalid grade. Must be 1.0-5.0 or one of: ${validNonNumericGrades.join(', ')}`);
                    return false;
                }
                
                input.classList.add('is-valid');
                return true;
            }
        }
        
        // Show validation error message
        function showValidationError(inputElement, message) {
            inputElement.classList.add('is-invalid');
            
            // Create and show new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            
            // Insert after the input
            inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
            
            // Focus the problematic field
            inputElement.focus();
        }
        
        // Form submission handler
        document.getElementById('gradeForm').addEventListener('submit', function(e) {
            const form = e.target;
            const gradeInput = document.getElementById('grade');
            const gradeValue = gradeInput.value.trim().toUpperCase();
            
            // Validate all required fields
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return false;
            }
            
            // Validate grade field
            if (!validateGradeInput(gradeInput)) {
                e.preventDefault();
                return false;
            }
            
            // If we got here, form is valid
            return true;
        });
        
        // Reset modal when closed
        document.getElementById('gradeModal').addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('gradeForm');
            form.reset();
            form.action = 'studentgrade.php';
            form.classList.remove('was-validated');
            
            // Reset UI elements
            document.getElementById('courseList').innerHTML = '';
            document.getElementById('course_title').value = '';
            
            // Reset modal title and button
            document.getElementById('gradeModalLabel').innerHTML = '<i class="bi bi-plus-circle"></i> Add New Grade';
            const submitBtn = document.querySelector('#gradeForm [type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add Grade';
                submitBtn.classList.remove('btn-warning');
                submitBtn.classList.add('btn-primary');
            }
            
            // Clear validation states
            document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });
            
            // Remove any validation messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        });
    </script>
</script>
</body>
</html>
