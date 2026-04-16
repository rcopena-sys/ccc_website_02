<?php
require_once 'config.php';

$success = false;
$success_message = '';
$error = '';
$search_results = [];
$search_performed = false;

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

// Prepare grade rows for Tabulator
$display_data = [];
$grades_result = $conn->query("SELECT * FROM grades_db ORDER BY student_id, year, sem, course_code");
if ($grades_result) {
    $display_data = $grades_result->fetch_all(MYSQLI_ASSOC);
}
$grades_data = [];

foreach ($display_data as $row) {
    $gradeValue = $row['final_grade'] ?? '';
    $displayGrade = $gradeValue;
    $statusHtml = '';
    $rowClass = '';

    if (is_numeric($gradeValue)) {
        $numericGrade = (float)$gradeValue;

        if ($numericGrade <= 3.0) {
            $rowClass = 'grade-success';
            $statusHtml = '<span class="badge bg-success">Passed</span>';
        } elseif ($numericGrade >= 3.25 && $numericGrade <= 4.0) {
            $rowClass = 'grade-warning';
            $statusHtml = '<span class="badge bg-warning text-dark">Incomplete</span>';
            $displayGrade = 'INC';
        } elseif ($numericGrade >= 5.0) {
            $rowClass = 'grade-danger';
            $statusHtml = '<span class="badge bg-danger">Failed</span>';
            $displayGrade = 'Failed';
        } else {
            $rowClass = 'grade-danger';
            $statusHtml = '<span class="badge bg-danger">Failed</span>';
            $displayGrade = 'Failed';
        }
    } else {
        $statusHtml = '<span class="badge bg-secondary">N/A</span>';
    }

    $grades_data[] = [
        'student_id' => $row['student_id'] ?? '',
        'course_code' => $row['course_code'] ?? '',
        'course_title' => $row['course_title'] ?? 'N/A',
        'year' => $row['year'] ?? '',
        'sem' => $row['sem'] ?? '',
        'final_grade' => $gradeValue,
        'display_grade' => $displayGrade,
        'status_html' => $statusHtml,
        'row_class' => $rowClass,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grade Management System</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .grade-success { background-color: #d4edda !important; }
        .grade-warning { background-color: #fff3cd !important; }
        .grade-danger { background-color: #f8d7da !important; }
        .search-section { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        /* Blue gradient for stats cards */
        .stats-card { background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%) !important; color: white; }
        .grades-table-shell {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }
        #gradesTabulator {
            min-height: 420px;
        }
        .tabulator {
            border-radius: 10px;
            overflow: hidden;
        }
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
                All Grades Database
            </h4>
            
            <div class="grades-table-shell">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="text-muted small">
                        <?= count($grades_data) ?> record<?= count($grades_data) === 1 ? '' : 's' ?> loaded.
                    </div>
                    <div class="text-muted small">
                        Use the column filters and pagination controls in the grid.
                    </div>
                </div>
                <div id="gradesTabulator"></div>
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
<script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
<script>
const gradesData = <?= json_encode($grades_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];

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

function archiveGrade(studentId, courseCode, year, sem) {
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
        if (!result.isConfirmed) {
            return;
        }

        const formData = new URLSearchParams();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'studentgrade.php';

        [['archive', '1'], ['student_id', studentId], ['course_code', courseCode], ['year', year], ['sem', sem]].forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });
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

document.addEventListener('DOMContentLoaded', function () {
    const gradesTabulatorEl = document.getElementById('gradesTabulator');
    if (!gradesTabulatorEl || typeof Tabulator === 'undefined') {
        return;
    }

    const gradesTable = new Tabulator(gradesTabulatorEl, {
        data: gradesData,
        layout: 'fitColumns',
        responsiveLayout: 'collapse',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50],
        placeholder: 'No grades found',
        height: '560px',
        rowFormatter: function (row) {
            const data = row.getData() || {};
            if (data.row_class) {
                row.getElement().classList.add(data.row_class);
            }
        },
        columns: [
            { title: 'Student ID', field: 'student_id', headerFilter: 'input', minWidth: 140 },
            { title: 'Course Code', field: 'course_code', headerFilter: 'input', minWidth: 140, formatter: 'plaintext' },
            { title: 'Course Title', field: 'course_title', headerFilter: 'input', minWidth: 220 },
            { title: 'Year', field: 'year', hozAlign: 'center', width: 100, headerFilter: 'input' },
            { title: 'Semester', field: 'sem', hozAlign: 'center', width: 110, headerFilter: 'input' },
            { title: 'Grade', field: 'display_grade', hozAlign: 'center', width: 110, headerFilter: 'input' },
            {
                title: 'Status',
                field: 'status_html',
                hozAlign: 'center',
                minWidth: 130,
                headerSort: false,
                formatter: function (cell) {
                    return cell.getValue() || '';
                }
            },
            {
                title: 'Actions',
                hozAlign: 'center',
                width: 150,
                headerSort: false,
                formatter: function () {
                    return '<div class="d-flex justify-content-center gap-2"><button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" title="Edit"><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-sm btn-outline-warning" data-action="archive" title="Archive"><i class="bi bi-archive"></i></button></div>';
                },
                cellClick: function (e, cell) {
                    const target = e.target.closest('button[data-action]');
                    if (!target) {
                        return;
                    }

                    const rowData = cell.getRow().getData() || [];
                    const action = target.dataset.action;

                    if (action === 'edit') {
                        editGrade(
                            rowData.student_id || '',
                            rowData.course_code || '',
                            rowData.year || '',
                            rowData.sem || '',
                            rowData.final_grade || '',
                            rowData.course_title || ''
                        );
                    } else if (action === 'archive') {
                        archiveGrade(
                            rowData.student_id || '',
                            rowData.course_code || '',
                            rowData.year || '',
                            rowData.sem || ''
                        );
                    }
                }
            },
        ],
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
