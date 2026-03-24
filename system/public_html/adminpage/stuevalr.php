<?php
require_once 'db.php';

// Helpers
function columnExists(mysqli $conn, string $table, string $column): bool {
    // SHOW statements cannot use parameter markers in MySQL/MariaDB.
    // Safely interpolate identifiers and values.
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}

function normalizeSemesterKey(string $raw): string {
    $raw = strtolower(trim($raw));
    $raw = str_replace(['first','second','third','fourth','1st','2nd','3rd','4th'], ['1','2','3','4','1','2','3','4'], $raw);
    $raw = str_replace(['year','yr','semester','sem',' ', '-'], '', $raw);
    if (preg_match('/^([1-4])([1-2])$/', $raw, $m)) {
        return $m[1] . '-' . $m[2];
    }
    if (preg_match('/^[1-4]-[1-2]$/', $raw)) return $raw;
    return $raw;
}

function normalizeCourseCode(string $code): string {
    // Uppercase and strip all non-alphanumeric to match 'ALG 101' with 'ALG101' etc.
    $upper = strtoupper(trim($code));
    return preg_replace('/[^A-Z0-9]/', '', $upper);
}

function latestFiscalYear(mysqli $conn, string $program): ?string {
    if (!columnExists($conn, 'curriculum', 'fiscal_year') || !columnExists($conn, 'curriculum', 'program')) {
        return null;
    }
    $stmt = $conn->prepare("SELECT fiscal_year FROM curriculum WHERE program = ? ORDER BY fiscal_year DESC LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $program);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $fy = $row['fiscal_year'] ?? null;
    $stmt->close();
    return $fy ?: null;
}

// Add back inline grade edit support
function gradeColumn(mysqli $conn): string {
    return columnExists($conn, 'grades_db', 'final_grade') ? 'final_grade' : 'grade';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_grade') {
    header('Content-Type: application/json');

    $student_id = trim($_POST['student_id'] ?? '');
    $course_code = trim($_POST['course_code'] ?? '');
    $course_title = trim($_POST['course_title'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $sem = trim($_POST['sem'] ?? '');
    $grade = trim($_POST['grade'] ?? '');

    if ($student_id === '' || $course_code === '' || $year === '' || $sem === '' || $grade === '') {
        echo json_encode(['ok' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    if (!is_numeric($grade) || $grade < 1.0 || $grade > 5.0) {
        echo json_encode(['ok' => false, 'message' => 'Grade must be a number between 1.0 and 5.0']);
        exit;
    }

    $gradeCol = gradeColumn($conn);

    $existsStmt = $conn->prepare("SELECT 1 FROM grades_db WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ? LIMIT 1");
    if (!$existsStmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $existsStmt->bind_param('ssss', $student_id, $course_code, $year, $sem);
    $existsStmt->execute();
    $exists = $existsStmt->get_result()->num_rows > 0;
    $existsStmt->close();

    if ($exists) {
        $sql = "UPDATE grades_db SET {$gradeCol} = ?, course_title = ? WHERE student_id = ? AND course_code = ? AND year = ? AND sem = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['ok'=>false,'message'=>$conn->error]); exit; }
        $stmt->bind_param('ssssss', $grade, $course_title, $student_id, $course_code, $year, $sem);
        $ok = $stmt->execute();
        $stmt->close();
    } else {
        $sql = "INSERT INTO grades_db (student_id, course_code, year, sem, {$gradeCol}, course_title) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { echo json_encode(['ok'=>false,'message'=>$conn->error]); exit; }
        $stmt->bind_param('ssssss', $student_id, $course_code, $year, $sem, $grade, $course_title);
        $ok = $stmt->execute();
        $stmt->close();
    }

    if ($ok) {
        echo json_encode(['ok' => true, 'grade' => $grade]);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Failed to save grade']);
    }
    exit;
}

$studentId = trim($_GET['student_id'] ?? $_POST['student_id'] ?? '');
$program = trim($_GET['program'] ?? $_POST['program'] ?? '');
$fiscalYear = trim($_GET['fiscal_year'] ?? $_POST['fiscal_year'] ?? '');

$student = null;
if ($studentId !== '') {
    if (columnExists($conn, 'students_db', 'student_id')) {
        $stmt = $conn->prepare("SELECT student_id, student_name, programs, curriculum, classification, category FROM students_db WHERE student_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $studentId);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}

if ($program === '' && $student && !empty($student['programs'])) {
    $program = strtoupper($student['programs']);
}
if ($program !== 'BSCS' && $program !== 'BSIT') {
    $program = 'BSCS';
}

if ($fiscalYear === '') {
    $fy = latestFiscalYear($conn, $program);
    if ($fy) $fiscalYear = $fy;
}

// Fetch curriculum for program (optionally fiscal year)
$hasYearSem = columnExists($conn, 'curriculum', 'year_semester');
$codeCol = columnExists($conn, 'curriculum', 'subject_code') ? 'subject_code' : (columnExists($conn, 'curriculum', 'course_code') ? 'course_code' : 'subject_code');
$titleCol = columnExists($conn, 'curriculum', 'course_title') ? 'course_title' : (columnExists($conn, 'curriculum', 'subject_title') ? 'subject_title' : 'course_title');
$lecCol = columnExists($conn, 'curriculum', 'lec_units') ? 'lec_units' : (columnExists($conn, 'curriculum', 'lecture_units') ? 'lecture_units' : 'lec_units');
$labCol = columnExists($conn, 'curriculum', 'lab_units') ? 'lab_units' : 'lab_units';
$totalCol = columnExists($conn, 'curriculum', 'total_units') ? 'total_units' : (columnExists($conn, 'curriculum', 'units') ? 'units' : 'total_units');
$preCol = columnExists($conn, 'curriculum', 'prerequisites') ? 'prerequisites' : (columnExists($conn, 'curriculum', 'pre_req') ? 'pre_req' : 'prerequisites');

$curriculum = [];
$sql = "SELECT $codeCol AS code, $titleCol AS title, $lecCol AS lec, $labCol AS lab, $totalCol AS units, $preCol AS prereq";
if ($hasYearSem) {
    $sql .= ", year_semester AS ys";
} else {
    $sql .= ", year, semester";
}
$sql .= " FROM curriculum WHERE 1=1";
$params = [];
$types = '';
if (columnExists($conn, 'curriculum', 'program')) {
    $sql .= " AND program = ?";
    $params[] = $program;
    $types .= 's';
}
if ($fiscalYear !== '' && columnExists($conn, 'curriculum', 'fiscal_year')) {
    $sql .= " AND fiscal_year = ?";
    $params[] = $fiscalYear;
    $types .= 's';
}
$sql .= $hasYearSem ? " ORDER BY ys, code" : " ORDER BY year, semester, code";
$stmt = $conn->prepare($sql);
if ($stmt && ($types === '' || $stmt->bind_param($types, ...$params))) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ys = $hasYearSem ? normalizeSemesterKey($row['ys']) : (string)($row['year'] . '-' . (int)$row['semester']);
        if (!isset($curriculum[$ys])) $curriculum[$ys] = [];
        $curriculum[$ys][] = $row;
    }
    $stmt->close();
}

// Fetch grades for the student with normalization and prefix-year-sem fallback
$gradesByCode = [];
$gradesByPrefixYearSem = [];
if ($studentId !== '') {
    $gstmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ?");
    if ($gstmt) {
        $gstmt->bind_param('s', $studentId);
        $gstmt->execute();
        $gres = $gstmt->get_result();
        while ($g = $gres->fetch_assoc()) {
            $code = $g['course_code'] ?? $g['subject_code'] ?? '';
            if ($code === '') continue;
            $norm = normalizeCourseCode($code);
            $gradeValue = $g['final_grade'] ?? $g['grade'] ?? '';
            $gradesByCode[$norm] = $gradeValue;

            // Build prefix-year-sem alternative key, e.g., 'NSTP-1-1'
            if (preg_match('/^[A-Z]+/', $norm, $m)) {
                $prefix = $m[0];
                $y = intval($g['year'] ?? 0);
                $s = intval($g['sem'] ?? 0);
                if ($prefix && $y && $s) {
                    $gradesByPrefixYearSem["{$prefix}-{$y}-{$s}"] = $gradeValue;
                }
            }
        }
        $gstmt->close();
    }
}

$ysOrder = ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Evaluation</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
  <style>
    body { background:#f6f8fa; }
    .prospectus { max-width: 1100px; margin: 24px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .heading { border-bottom: 2px solid #e5e7eb; margin-bottom: 16px; padding-bottom: 8px; }
    .semester-title { background:#0d6efd; color:#fff; padding:8px 12px; border-radius:6px; font-weight:600; }
    .table thead th { background:#f3f4f6; }
    .badge-program { font-size:.85rem; }
    .passed-row { background-color: #d4edda !important; }
    .failed-row { background-color: #f8d7da !important; }
  </style>
</head>
<body>
  <div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Student Evaluation</h2>
      <a href="dashboard2.php" class="btn btn-outline-secondary">
        <i class="bi bi-house-door"></i> Back to Dashboard
      </a>
    </div>
  <div class="container my-4">
    <form method="GET" class="row g-2 align-items-end mb-3">
      <div class="col-sm-4">
        <label class="form-label">Student ID</label>
        <input type="text" name="student_id" class="form-control" value="<?= htmlspecialchars($studentId) ?>" required>
      </div>
      <div class="col-sm-3">
        <label class="form-label">Program</label>
        <select name="program" class="form-select">
          <option value="BSCS" <?= $program==='BSCS'?'selected':'' ?>>BSCS</option>
          <option value="BSIT" <?= $program==='BSIT'?'selected':'' ?>>BSIT</option>
        </select>
      </div>
      <?php if (columnExists($conn, 'curriculum', 'fiscal_year')): ?>
      <div class="col-sm-3">
        <label class="form-label">Fiscal Year</label>
        <input type="text" name="fiscal_year" class="form-control" value="<?= htmlspecialchars($fiscalYear) ?>" placeholder="e.g. 2024-2025">
      </div>
      <?php endif; ?>
      <div class="col-sm-2">
        <button class="btn btn-primary w-100" type="submit">Evaluate</button>
      </div>
    </form>
  </div>

<?php if ($student): ?>
  <div class="prospectus">
    <div class="d-flex justify-content-between align-items-center heading">
      <div>
        <h5 class="mb-0">CITY COLLEGE OF CALAMBA</h5>
        <small>Office of the College Registrar</small>
      </div>
      <div class="text-end">
        <div><span class="badge bg-primary badge-program"><?= htmlspecialchars($program) ?></span></div>
        <?php if ($fiscalYear): ?><div class="text-muted small">Curriculum <?= htmlspecialchars($fiscalYear) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6"><strong>Name:</strong> <?= htmlspecialchars($student['student_name'] ?? 'N/A') ?></div>
      <div class="col-md-3"><strong>Student No.:</strong> <?= htmlspecialchars($student['student_id']) ?></div>
      <div class="col-md-3"><strong>Generated:</strong> <?= date('m/d/Y h:i A') ?></div>
    </div>

    <?php
    $labels = [
      '1-1' => 'FIRST YEAR • FIRST SEMESTER',
      '1-2' => 'FIRST YEAR • SECOND SEMESTER',
      '2-1' => 'SECOND YEAR • FIRST SEMESTER',
      '2-2' => 'SECOND YEAR • SECOND SEMESTER',
      '3-1' => 'THIRD YEAR • FIRST SEMESTER',
      '3-2' => 'THIRD YEAR • SECOND SEMESTER',
      '4-1' => 'FOURTH YEAR • FIRST SEMESTER',
      '4-2' => 'FOURTH YEAR • SECOND SEMESTER',
    ];
    ?>

    <?php foreach ($ysOrder as $ysKey): ?>
      <div class="mb-4">
        <div class="semester-title mb-2"><?= $labels[$ysKey] ?></div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr class="text-center">
                <th style="width:10%">CODE</th>
                <th>COURSE TITLE</th>
                <th style="width:7%">Lec</th>
                <th style="width:7%">Lab</th>
                <th style="width:8%">Units</th>
                <th style="width:15%">Pre-Req</th>
                <th style="width:12%">Final Grade</th>
                <th style="width:8%">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rows = $curriculum[$ysKey] ?? [];
              if (empty($rows)):
              ?>
                <tr><td colspan="8" class="text-center text-muted">No curriculum data for this semester.</td></tr>
              <?php else:
                [$yr, $sm] = array_map('intval', explode('-', $ysKey));
                foreach ($rows as $subj):
                  $code = $subj['code'];
                  $normCode = normalizeCourseCode($code);
                  $grade = trim((string)($gradesByCode[$normCode] ?? ''));
                  // Fallback: if curriculum code has no digits (e.g., 'NSTP'), try prefix-year-sem key
                  if ($grade === '' && preg_match('/^[A-Z]+$/', $normCode)) {
                      $prefixKey = preg_replace('/[^A-Z]/', '', $normCode) . "-{$yr}-{$sm}";
                      if (isset($gradesByPrefixYearSem[$prefixKey])) {
                          $grade = trim((string)$gradesByPrefixYearSem[$prefixKey]);
                      }
                  }
                  $gradeNum = is_numeric($grade) ? floatval($grade) : null;
                  $rowClass = '';
                  $displayGrade = '—';
                  if ($gradeNum !== null) {
                    if ($gradeNum <= 3.0) { $rowClass = 'passed-row'; $displayGrade = $grade; }
                    else { $rowClass = 'failed-row'; $displayGrade = 'INC'; }
                  }
              ?>
                <tr class="<?= $rowClass ?>">
                  <td class="text-center"><code><?= htmlspecialchars($code) ?></code></td>
                  <td><?= htmlspecialchars($subj['title']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($subj['lec']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($subj['lab']) ?></td>
                  <td class="text-center"><?= htmlspecialchars($subj['units']) ?></td>
                  <td><?= htmlspecialchars($subj['prereq'] ?: 'None') ?></td>
                  <td class="text-center"><strong class="grade-cell" data-code="<?= htmlspecialchars(normalizeCourseCode($code)) ?>"><?= htmlspecialchars($displayGrade) ?></strong></td>
                  <td class="text-center">
                    <?php if ($studentId !== ''): ?>
                    <a href="edit_grade.php?student_id=<?= urlencode($studentId) ?>
                      &course_code=<?= urlencode($code) ?>
                      &course_title=<?= urlencode($subj['title']) ?>" 
                       class="btn btn-sm btn-outline-primary" 
                       title="Edit <?= htmlspecialchars($code) ?> - <?= htmlspecialchars($subj['title']) ?>">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php endforeach; ?>

    <div class="text-end text-muted small">Printed on <?= date('F j, Y h:i A') ?></div>
  </div>
<?php elseif ($studentId !== ''): ?>
  <div class="container"><div class="alert alert-warning">No student found for ID: <?= htmlspecialchars($studentId) ?>.</div></div>
<?php else: ?>
  <div class="container"><div class="alert alert-info">Enter a student ID to view the evaluation.</div></div>
<?php endif; ?>

<?php if ($student): ?>
<!-- Grade Edit Modal -->
<div class="modal fade" id="editGradeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Grade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editGradeForm">
          <input type="hidden" name="action" value="save_grade">
          <input type="hidden" name="student_id" id="eg_student">
          <input type="hidden" name="course_code" id="eg_code">
          <input type="hidden" name="year" id="eg_year">
          <input type="hidden" name="sem" id="eg_sem">
          <div class="mb-3">
            <label class="form-label">Course</label>
            <input type="text" class="form-control" id="eg_title" name="course_title" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Grade</label>
            <input type="number" step="0.25" min="1" max="5" class="form-control" id="eg_grade" name="grade" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="eg_save">Save</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('editGradeModal');
  if (!modalEl) return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('editGradeForm');
  const btnSave = document.getElementById('eg_save');

  document.querySelectorAll('.btn-edit-grade').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('eg_student').value = btn.dataset.student;
      document.getElementById('eg_code').value = btn.dataset.code;
      document.getElementById('eg_title').value = btn.dataset.title;
      document.getElementById('eg_year').value = btn.dataset.year;
      document.getElementById('eg_sem').value = btn.dataset.sem;
      document.getElementById('eg_grade').value = btn.dataset.grade || '';
      modal.show();
    });
  });

  btnSave.addEventListener('click', async () => {
    const fd = new FormData(form);
    try {
      const res = await fetch(window.location.href, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        const code = document.getElementById('eg_code').value;
        const norm = code.replace(/[^A-Z0-9]/gi,'').toUpperCase();
        const cell = document.querySelector('.grade-cell[data-code="' + CSS.escape(norm) + '"]');
        if (cell) {
          // match dcipros1st logic: <=3.0 show grade, else show INC and red row
          const g = parseFloat(data.grade);
          const row = cell.closest('tr');
          row.classList.remove('passed-row','failed-row');
          if (!isNaN(g)) {
            if (g <= 3.0) { cell.textContent = g; row.classList.add('passed-row'); }
            else { cell.textContent = 'INC'; row.classList.add('failed-row'); }
          } else {
            cell.textContent = '—';
          }
        }
        modal.hide();
      } else {
        alert(data.message || 'Failed to save grade');
      }
    } catch (e) {
      alert('Network error while saving grade');
    }
  });
})();
</script>
</body>
</html>
