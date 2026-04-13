<?php
require_once 'config.php';

$success = false;
$success_message = '';
$error = '';
$search_results = [];
$search_performed = false;


if (!empty($_GET['search_student']) || !empty($_GET['search_course'])) {
    $search_student = $conn->real_escape_string($_GET['search_student'] ?? '');
    $search_course = $conn->real_escape_string($_GET['search_course'] ?? '');
    
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
    
    if (!empty($where_conditions)) {
        $sql = "SELECT * FROM grades_db WHERE " . implode(' AND ', $where_conditions) . " ORDER BY student_id, year, sem, course_code";
        $stmt = $conn->prepare($sql);
        
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $search_performed = true;
        }
        $stmt->close();
    }
}

// Handle grade entry (but ignore archive requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['archive'])) {
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

// Handle archive request (soft delete: move record to grades_archive table)
if (isset($_POST['archive']) && isset($_POST['student_id'], $_POST['course_code'], $_POST['year'], $_POST['sem'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $course_code = $conn->real_escape_string($_POST['course_code']);
    $year = $conn->real_escape_string($_POST['year']);
    $sem = $conn->real_escape_string($_POST['sem']);

    // Ensure archive table exists with same structure as grades_db
    if (!$conn->query("CREATE TABLE IF NOT EXISTS grades_archive LIKE grades_db")) {
        $error = 'Failed to ensure archive table exists: ' . $conn->error;
    } else {
        // First copy the record into the archive table
        $archive_stmt = $conn->prepare("INSERT INTO grades_archive SELECT * FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
        if ($archive_stmt) {
            $archive_stmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
            if ($archive_stmt->execute() && $archive_stmt->affected_rows > 0) {
                // Only delete from main table if archiving succeeded
                $delete_stmt = $conn->prepare("DELETE FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
                    if ($delete_stmt->execute()) {
                        $success = true;
                        $success_message = 'Grade record archived successfully!';
                    } else {
                        $error = 'Failed to remove record from active grades: ' . $conn->error;
                    }
                    $delete_stmt->close();
                } else {
                    $error = 'Failed to prepare removal from active grades: ' . $conn->error;
                }
            } else {
                $error = 'Failed to archive grade record (no matching record found).';
            }
            $archive_stmt->close();
        } else {
            $error = 'Failed to prepare archive statement: ' . $conn->error;
        }
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

// Get student IDs for dropdown (include archived so they remain searchable)
$student_ids = [];
// Ensure archive table exists so UNION query is safe
$conn->query("CREATE TABLE IF NOT EXISTS grades_archive LIKE grades_db");
$student_result = $conn->query("(SELECT DISTINCT student_id FROM grades_db) UNION (SELECT DISTINCT student_id FROM grades_archive) ORDER BY student_id");
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
    <title>Grade Management System</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .grade-success { background-color: #d4edda !important; }
        .grade-warning { background-color: #fff3cd !important; }
        .grade-danger { background-color: #f8d7da !important; }
        .search-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        /* Blue gradient for stats cards */
        .stats-card { background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%) !important; color: white; }
    </style>
</head>
<body class="bg-light">
    <a href="dashboardr.php" class="btn btn-outline-secondary position-absolute top-0 end-0 m-3" style="z-index:1050; min-width:40px; min-height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem;" title="Back to Dashboard">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3"><i class="bi bi-mortarboard"></i> Grade Management System</h2>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <h4 class="mb-3"><i class="bi bi-search"></i> Search Grades</h4>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="search_student" class="form-control" value="<?= htmlspecialchars($_GET['search_student'] ?? '') ?>" placeholder="Enter student ID">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="search_course" class="form-control" value="<?= htmlspecialchars($_GET['search_course'] ?? '') ?>" placeholder="Enter course code">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="stugra.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-3 d-flex gap-2">
            <a href="upload_grades_csv.php" class="btn btn-success">
                <i class="bi bi-file-earmark-arrow-up"></i> Upload CSV
            </a>
            <a href="achive_grades.php" class="btn btn-outline-warning">
                <i class="bi bi-archive"></i> View Archived Grades
            </a>
        </div>

        <!-- Display Grades -->
        <div class="mt-5">
            <h4 class="mb-3">
                <i class="bi bi-table"></i> 
                <?= $search_performed ? 'Search Results' : 'All Grades Database' ?>
            </h4>
            
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Student ID</th>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $display_data = $search_performed ? $search_results : $conn->query("SELECT * FROM grades_db ORDER BY student_id, year, sem, course_code")->fetch_all(MYSQLI_ASSOC);
                    
                    if (!empty($display_data)):
                        foreach ($display_data as $row): 
                            $grade = $row['final_grade'];
                            $grade_class = '';
                            $status = '';
                            $display_grade = $grade;
                            
                            if (is_numeric($grade)) {
                                if ($grade <= 3.0) {
                                    $grade_class = 'grade-success';
                                    $status = '<span class="badge bg-success">Passed</span>';
                                } elseif ($grade >= 3.25 && $grade <= 4.0) {
                                    $grade_class = 'grade-warning';
                                    $status = '<span class="badge bg-warning text-dark">Incomplete</span>';
                                    $display_grade = 'INC';
                                } elseif ($grade >= 5.0) {
                                    $grade_class = 'grade-danger';
                                    $status = '<span class="badge bg-danger">Failed</span>';
                                    $display_grade = 'Failed';
                                } else {
                                    // Handle grades between 4.0 and 5.0 (if any)
                                    $grade_class = 'grade-danger';
                                    $status = '<span class="badge bg-danger">Failed</span>';
                                    $display_grade = 'Failed';
                                }
                            }
                    ?>
                            <tr class="<?= $grade_class ?>">
                                <td><strong><?= htmlspecialchars($row['student_id']) ?></strong></td>
                                <td><code><?= htmlspecialchars($row['course_code']) ?></code></td>
                                <td><?= htmlspecialchars($row['course_title'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['year']) ?></td>
                                <td><?= htmlspecialchars($row['sem']) ?></td>
                                <td><strong><?= htmlspecialchars($display_grade) ?></strong></td>
                                <td><?= $status ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editGrade('<?= htmlspecialchars($row['student_id']) ?>', '<?= htmlspecialchars($row['course_code']) ?>', '<?= htmlspecialchars($row['year']) ?>', '<?= htmlspecialchars($row['sem']) ?>', '<?= htmlspecialchars($grade) ?>', '<?= htmlspecialchars($row['course_title'] ?? '') ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="" style="display:inline;" class="archive-form">
                                        <input type="hidden" name="archive" value="1">
                                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($row['student_id']) ?>">
                                        <input type="hidden" name="course_code" value="<?= htmlspecialchars($row['course_code']) ?>">
                                        <input type="hidden" name="year" value="<?= htmlspecialchars($row['year']) ?>">
                                        <input type="hidden" name="sem" value="<?= htmlspecialchars($row['sem']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Archive">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                    <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div>
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #6c757d;"></i><br>
                                    <h5 class="mt-2">No grades found</h5>
                                    <p class="text-muted">
                                        <?= $search_performed ? 'Try adjusting your search criteria.' : 'Use Upload CSV to insert records.' ?>
                                    </p>
                                </div>
                            </td>
</tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grade Entry Modal -->
        <div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="POST" id="gradeForm">
                <div class="modal-header">
                  <h5 class="modal-title" id="gradeModalLabel">
                    <i class="bi bi-plus-circle"></i> Add/Edit Grade Record
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="row">
                    <div class="col-md-6">
                  <div class="mb-3">
                        <label class="form-label">Student ID *</label>
                        <input type="text" name="student_id" id="student_id" class="form-control" required list="studentList">
                        <datalist id="studentList">
                            <?php foreach ($student_ids as $id): ?>
                                <option value="<?= htmlspecialchars($id) ?>">
                            <?php endforeach; ?>
                        </datalist>
                      </div>
                  </div>
                    <div class="col-md-6">
                  <div class="mb-3">
                        <label class="form-label">Course Code *</label>
                        <input type="text" name="course_code" id="course_code" class="form-control" required list="courseList">
                        <datalist id="courseList">
                            <?php foreach ($course_codes as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_code']) ?>" data-title="<?= htmlspecialchars($course['course_title']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                      </div>
                    </div>
                  </div>
                  
                                    <div class="row">
                                        <div class="col-md-4">
                  <div class="mb-3">
                        <label class="form-label">Grade *</label>
                        <input type="number" name="grade" id="grade" class="form-control" step="0.25" min="1.0" max="5.0" required>
                        <div class="form-text"></div>
                      </div>
                    </div>
                  </div>

				  <!-- Hidden fields for year and semester (not shown in modal) -->
				  <input type="hidden" name="year" id="year" required>
				  <input type="hidden" name="sem" id="sem" required>

                  <div class="mb-3">
                    <label class="form-label">Course Title</label>
                    <input type="text" name="course_title" id="course_title" class="form-control" placeholder="Enter course title">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Save Grade
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-fill course title when course code is selected
document.getElementById('course_code').addEventListener('input', function() {
    const courseCode = this.value;
    const courseList = document.getElementById('courseList');
    const courseTitle = document.getElementById('course_title');
    
    for (let option of courseList.options) {
        if (option.value === courseCode) {
            courseTitle.value = option.dataset.title || '';
            break;
        }
    }
});

// Edit grade function
function editGrade(studentId, courseCode, year, sem, grade, courseTitle) {
    const studentInput = document.getElementById('student_id');
    const courseCodeInput = document.getElementById('course_code');
    const yearInput = document.getElementById('year');
    const semInput = document.getElementById('sem');
    const gradeInput = document.getElementById('grade');
    const courseTitleInput = document.getElementById('course_title');

    // Set field values
    studentInput.value = studentId;
    courseCodeInput.value = courseCode;
    yearInput.value = year;
    semInput.value = sem;
    gradeInput.value = grade;
    courseTitleInput.value = courseTitle;

    // Lock all fields except grade when editing
    studentInput.readOnly = true;
    courseCodeInput.readOnly = true;
    courseTitleInput.readOnly = true;

    document.getElementById('gradeModalLabel').innerHTML = '<i class="bi bi-pencil"></i> Edit Grade Record';
    
    const modal = new bootstrap.Modal(document.getElementById('gradeModal'));
    modal.show();
}

// Reset modal when closed
document.getElementById('gradeModal').addEventListener('hidden.bs.modal', function () {
    const studentInput = document.getElementById('student_id');
    const courseCodeInput = document.getElementById('course_code');
    const yearInput = document.getElementById('year');
    const semInput = document.getElementById('sem');
    const courseTitleInput = document.getElementById('course_title');

    // Re-enable all fields for potential future add use
    studentInput.readOnly = false;
    courseCodeInput.readOnly = false;
    courseTitleInput.readOnly = false;
    yearInput.value = '';
    semInput.value = '';

    document.getElementById('gradeForm').reset();
    document.getElementById('gradeModalLabel').innerHTML = '<i class="bi bi-plus-circle"></i> Add Grade Record';
});

// Form validation
document.getElementById('gradeForm').addEventListener('submit', function(e) {
    const grade = document.getElementById('grade').value;
    if (grade < 1.0 || grade > 5.0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Invalid Grade',
            text: 'Grade must be between 1.0 and 5.0.'
        });
        return false;
    }
});

// SweetAlert confirmation for archiving
document.querySelectorAll('.archive-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Archive Grade Record?',
            text: 'Are you sure you want to archive this grade record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
<?php if ($success === true): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: <?= json_encode($success_message ?? 'Operation completed successfully!') ?>,
        confirmButtonColor: '#3085d6'
    }).then(function() {
        window.location.href = 'studentgrade.php';
    });
});
</script>
<?php elseif (!empty($error)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: <?= json_encode($error) ?>,
        confirmButtonColor: '#d33'
    });
});
</script>
<?php endif; ?>
</body>
</html>
