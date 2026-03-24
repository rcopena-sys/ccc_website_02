<?php
require_once 'db.php';

// Get student ID from query parameter
$studentId = $_GET['student_id'] ?? '';
$program = '';
$searchTerm = $_GET['search'] ?? '';

// Search for students if search term is provided
$students = [];
if (!empty($searchTerm)) {
    $searchTerm = "%$searchTerm%";
    $query = "SELECT student_id, firstname, lastname, course FROM signin_db 
              WHERE student_id LIKE ? OR firstname LIKE ? OR lastname LIKE ? 
              ORDER BY lastname, firstname LIMIT 50";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Get student info
$studentInfo = [];
if (!empty($studentId)) {
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE student_id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $studentInfo = $stmt->get_result()->fetch_assoc();
        $program = $studentInfo['course'] ?? '';
        // Map firstname/lastname to first_name/last_name for compatibility
        if (isset($studentInfo['firstname'])) {
            $studentInfo['first_name'] = $studentInfo['firstname'];
        }
        if (isset($studentInfo['lastname'])) {
            $studentInfo['last_name'] = $studentInfo['lastname'];
        }
    }
}

// Get all grades for the student
$grades = [];
if (!empty($studentId)) {
    $stmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ? ORDER BY year, sem, course_code");
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
    }
}

// Get irregular subjects
$irregularSubjects = [];
if (!empty($studentId)) {
    $stmt = $conn->prepare("SELECT * FROM irregular_db WHERE student_id = ? ORDER BY year, sem, course_code");
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $irregularSubjects[] = $row;
        }
    }
}

// Function to get grade class for styling
function getGradeClass($grade) {
    if ($grade === 'INC' || $grade === 'DRP' || $grade === 'UD') {
        return 'table-warning';
    } elseif (is_numeric($grade)) {
        $grade = floatval($grade);
        if ($grade <= 3.0) {
            return 'table-success';
        } else {
            return 'table-danger';
        }
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Grades - <?= htmlspecialchars($studentId) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .table th { background-color: #f1f3f9; }
    .grade-col { width: 100px; text-align: center; }
    .semester-header { background-color: #e9ecef; font-weight: 600; }
    .student-info { background-color: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .search-box { margin-bottom: 20px; }
    .student-list { max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; }
    .student-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
    .student-item:hover { background-color: #f8f9fa; }
    .student-id { font-weight: bold; color: #0d6efd; }
    .student-name { margin-left: 10px; }
    .student-course { color: #6c757d; font-size: 0.9em; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="card search-box">
      <div class="card-body">
        <h4 class="mb-3">Search Student</h4>
        <form method="GET" class="row g-3">
          <div class="col-md-8">
            <div class="input-group">
              <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                     placeholder="Search by ID, first name, or last name" autocomplete="off">
              <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i> Search
              </button>
            </div>
          </div>
          <?php if (!empty($studentId)): ?>
          <div class="col-md-4">
            <a href="stueval_simple.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle"></i> Clear Search
            </a>
          </div>
          <?php endif; ?>
        </form>

        <?php if (!empty($searchTerm) && !empty($students)): ?>
          <div class="student-list mt-3">
            <?php foreach ($students as $student): ?>
              <a href="?student_id=<?= htmlspecialchars($student['student_id']) ?>" class="text-decoration-none">
                <div class="student-item">
                  <span class="student-id"><?= htmlspecialchars($student['student_id']) ?></span>
                  <span class="student-name">
                    <?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?>
                  </span>
                  <div class="student-course"><?= htmlspecialchars($student['course'] ?? 'N/A') ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($studentId)): ?>
    <div class="student-info mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Student Academic Record</h2>
        <div>
          <a href="javascript:window.print()" class="btn btn-outline-primary btn-sm me-2">
            <i class="bi bi-printer"></i> Print
          </a>
          <a href="stueval_simple.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Search
          </a>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <p><strong>Student ID:</strong> <?= htmlspecialchars($studentId) ?></p>
          <p><strong>Name:</strong> <?= htmlspecialchars($studentInfo['first_name'] ?? '') ?> <?= htmlspecialchars($studentInfo['last_name'] ?? '') ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Program:</strong> <?= htmlspecialchars($program) ?></p>
          <p><strong>Year Level:</strong> <?= $studentInfo['year_level'] ?? 'N/A' ?></p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Regular Subjects</h4>
      </div>
      <div class="card-body p-0">
        <?php if (empty($grades)): ?>
          <div class="p-4 text-center text-muted">No regular subjects found for this student.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Year</th>
                  <th>Semester</th>
                  <th>Course Code</th>
                  <th>Course Title</th>
                  <th class="grade-col">Grade</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $currentYear = null;
                $currentSem = null;
                foreach ($grades as $grade): 
                  $isNewSection = ($grade['year'] != $currentYear || $grade['sem'] != $currentSem);
                  $currentYear = $grade['year'];
                  $currentSem = $grade['sem'];
                ?>
                  <?php if ($isNewSection): ?>
                    <tr class="semester-header">
                      <td colspan="6" class="fw-bold">
                        Year <?= $grade['year'] ?> - Semester <?= $grade['sem'] ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td><?= $grade['year'] ?></td>
                    <td><?= $grade['sem'] ?></td>
                    <td><?= htmlspecialchars($grade['course_code']) ?></td>
                    <td><?= htmlspecialchars($grade['course_title'] ?? 'N/A') ?></td>
                    <td class="grade-col <?= getGradeClass($grade['final_grade']) ?>">
                      <?= htmlspecialchars($grade['final_grade'] ?? 'N/A') ?>
                    </td>
                    <td>
                      <?php 
                      if (!empty($grade['final_grade'])) {
                        if ($grade['final_grade'] === 'INC') {
                          echo 'Incomplete';
                        } elseif ($grade['final_grade'] === 'DRP') {
                          echo 'Dropped';
                        } elseif (is_numeric($grade['final_grade']) && floatval($grade['final_grade']) <= 3.0) {
                          echo 'Passed';
                        } elseif (is_numeric($grade['final_grade'])) {
                          echo 'Failed';
                        }
                      }
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($irregularSubjects)): ?>
    <div class="card">
      <div class="card-header bg-warning text-dark">
        <h4 class="mb-0">Irregular Subjects</h4>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Year</th>
                <th>Semester</th>
                <th>Course Code</th>
                <th>Course Title</th>
                <th class="grade-col">Grade</th>
                <th>Units</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($irregularSubjects as $subject): ?>
                <tr>
                  <td><?= $subject['year'] ?></td>
                  <td><?= $subject['sem'] ?></td>
                  <td><?= htmlspecialchars($subject['course_code']) ?></td>
                  <td><?= htmlspecialchars($subject['course_title'] ?? 'N/A') ?></td>
                  <td class="grade-col <?= getGradeClass($subject['grade']) ?>">
                    <?= htmlspecialchars($subject['grade'] ?? 'N/A') ?>
                  </td>
                  <td><?= $subject['total_units'] ?? 'N/A' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
      <a href="javascript:window.print()" class="btn btn-primary me-2">
        <i class="bi bi-printer"></i> Print Record
      </a>
      <a href="javascript:history.back()" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
