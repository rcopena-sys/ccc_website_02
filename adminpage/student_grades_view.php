<?php
require_once 'config.php';

$student_id = '';
$course_code = '';
$grades_data = [];
$student_info = null;
$search_performed = false;

// Handle search
if (isset($_GET['student_id']) || isset($_GET['course_code'])) {
    $student_id = trim($conn->real_escape_string($_GET['student_id'] ?? ''));
    $course_code = trim($conn->real_escape_string($_GET['course_code'] ?? ''));
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($student_id)) {
        $where_conditions[] = "student_id = ?";
        $params[] = $student_id;
        $types .= 's';
    }
    
    if (!empty($course_code)) {
        $where_conditions[] = "course_code = ?";
        $params[] = $course_code;
        $types .= 's';
    }
    
    if (!empty($where_conditions)) {
        $sql = "SELECT * FROM grades_db WHERE " . implode(' AND ', $where_conditions) . " ORDER BY year, sem, course_code";
        $stmt = $conn->prepare($sql);
        
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $grades_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_performed = true;
        }
        $stmt->close();
        
        // Get student information if student_id is provided
        if (!empty($student_id)) {
            $student_stmt = $conn->prepare("SELECT * FROM signin_db WHERE student_number = ?");
            if ($student_stmt) {
                $student_stmt->bind_param('s', $student_id);
                $student_stmt->execute();
                $student_info = $student_stmt->get_result()->fetch_assoc();
                $student_stmt->close();
            }
        }
    }
}

// Get all student IDs for dropdown
$all_students = [];
$students_result = $conn->query("SELECT DISTINCT student_id FROM grades_db ORDER BY student_id");
if ($students_result) {
    while ($row = $students_result->fetch_assoc()) {
        $all_students[] = $row['student_id'];
    }
}

// Get all course codes for dropdown
$all_courses = [];
$courses_result = $conn->query("SELECT DISTINCT course_code, course_title FROM grades_db ORDER BY course_code");
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        $all_courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Grades View</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .grade-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .grade-success { background-color: #d4edda !important; border-left: 4px solid #28a745; }
        .grade-warning { background-color: #fff3cd !important; border-left: 4px solid #ffc107; }
        .grade-danger { background-color: #f8d7da !important; border-left: 4px solid #dc3545; }
        .search-section { background-color: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px; }
        .student-info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .year-section { background-color: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .semester-title { background-color: #007bff; color: white; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">
                <i class="bi bi-person-check"></i> Student Grades View
            </h2>
            <p class="text-muted">View grades per specific student number and course code</p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <h4 class="mb-3"><i class="bi bi-search"></i> Search Grades</h4>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Student ID</label>
                <select name="student_id" class="form-select">
                    <option value="">Select Student ID</option>
                    <?php foreach ($all_students as $id): ?>
                        <option value="<?= htmlspecialchars($id) ?>" <?= $student_id === $id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($id) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course Code</label>
                <select name="course_code" class="form-select">
                    <option value="">Select Course Code</option>
                    <?php foreach ($all_courses as $course): ?>
                        <option value="<?= htmlspecialchars($course['course_code']) ?>" <?= $course_code === $course['course_code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Search
                </button>
                <a href="student_grades_view.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <?php if ($search_performed && !empty($grades_data)): ?>
        <!-- Student Information -->
        <?php if ($student_info): ?>
            <div class="student-info">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="bi bi-person"></i> Student Information</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($student_info['firstname'] . ' ' . $student_info['lastname']) ?></p>
                        <p><strong>Student Number:</strong> <?= htmlspecialchars($student_info['student_number']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-book"></i> Course Information</h5>
                        <p><strong>Program:</strong> <?= htmlspecialchars($student_info['course'] ?? 'N/A') ?></p>
                        <p><strong>Total Grades Found:</strong> <?= count($grades_data) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Grades Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="grade-card">
                    <div class="card-body text-center">
                        <h5>Total Courses</h5>
                        <h3><?= count($grades_data) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="grade-card">
                    <div class="card-body text-center">
                        <h5>Passed</h5>
                        <?php
                        $passed = 0;
                        foreach ($grades_data as $grade) {
                            if (is_numeric($grade['final_grade']) && $grade['final_grade'] <= 3.0) {
                                $passed++;
                            }
                        }
                        ?>
                        <h3><?= $passed ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="grade-card">
                    <div class="card-body text-center">
                        <h5>Failed</h5>
                        <?php
                        $failed = 0;
                        foreach ($grades_data as $grade) {
                            if (is_numeric($grade['final_grade']) && $grade['final_grade'] > 3.0) {
                                $failed++;
                            }
                        }
                        ?>
                        <h3><?= $failed ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="grade-card">
                    <div class="card-body text-center">
                        <h5>Average</h5>
                        <?php
                        $total_grade = 0;
                        $grade_count = 0;
                        foreach ($grades_data as $grade) {
                            if (is_numeric($grade['final_grade'])) {
                                $total_grade += $grade['final_grade'];
                                $grade_count++;
                            }
                        }
                        $average = $grade_count > 0 ? round($total_grade / $grade_count, 2) : 'N/A';
                        ?>
                        <h3><?= $average ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades by Year and Semester -->
        <?php
        $grades_by_year = [];
        foreach ($grades_data as $grade) {
            $year = $grade['year'];
            $sem = $grade['sem'];
            if (!isset($grades_by_year[$year])) {
                $grades_by_year[$year] = [];
            }
            if (!isset($grades_by_year[$year][$sem])) {
                $grades_by_year[$year][$sem] = [];
            }
            $grades_by_year[$year][$sem][] = $grade;
        }
        
        foreach ($grades_by_year as $year => $semesters):
        ?>
            <div class="year-section">
                <h4 class="mb-3">
                    <i class="bi bi-calendar"></i> Year <?= htmlspecialchars($year) ?>
                </h4>
                
                <?php foreach ($semesters as $sem => $semester_grades): ?>
                    <div class="mb-4">
                        <div class="semester-title">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-week"></i> 
                                <?= $sem == '1' ? 'First' : 'Second' ?> Semester
                            </h5>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($semester_grades as $grade): 
                                        $grade_value = $grade['final_grade'];
                                        $grade_class = '';
                                        $status = '';
                                        
                                        if (is_numeric($grade_value)) {
                                            if ($grade_value <= 3.0) {
                                                $grade_class = 'grade-success';
                                                $status = '<span class="badge bg-success">Passed</span>';
                                            } elseif ($grade_value <= 4.0) {
                                                $grade_class = 'grade-warning';
                                                $status = '<span class="badge bg-warning text-dark">Conditional</span>';
                                            } else {
                                                $grade_class = 'grade-danger';
                                                $status = '<span class="badge bg-danger">Failed</span>';
                                            }
                                        }
                                    ?>
                                        <tr class="<?= $grade_class ?>">
                                            <td><code><?= htmlspecialchars($grade['course_code']) ?></code></td>
                                            <td><?= htmlspecialchars($grade['course_title'] ?? 'N/A') ?></td>
                                            <td><strong><?= htmlspecialchars($grade_value) ?></strong></td>
                                            <td><?= $status ?></td>
                                            <td>
                                                <a href="stugra.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

    <?php elseif ($search_performed && empty($grades_data)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No grades found for the specified criteria.
        </div>
    <?php else: ?>
        <div class="alert alert-secondary">
            <i class="bi bi-info-circle"></i> Please select a student ID and/or course code to view grades.
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mt-5">
        <h4 class="mb-3"><i class="bi bi-lightning"></i> Quick Actions</h4>
        <div class="row">
            <div class="col-md-4">
                <a href="stugra.php" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-plus-circle"></i> Add New Grade
                </a>
            </div>
            <div class="col-md-4">
                <a href="upload_grades_csv.php" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-file-earmark-arrow-up"></i> Upload CSV
                </a>
            </div>
            <div class="col-md-4">
                <a href="dashboard2.php" class="btn btn-secondary w-100 mb-2">
                    <i class="bi bi-speedometer2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-submit form when selections change
document.querySelectorAll('select[name="student_id"], select[name="course_code"]').forEach(select => {
    select.addEventListener('change', function() {
        if (this.value) {
            document.querySelector('form').submit();
        }
    });
});
</script>
</body>
</html> 