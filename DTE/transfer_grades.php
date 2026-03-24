<?php
require_once 'config.php';

$success = false;
$error = '';
$student_grades = [];

// Get all students with their grades
$result = $conn->query("SELECT DISTINCT student_id FROM grades_db ORDER BY student_id");
$students = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row['student_id'];
    }
}

// If a student is selected, get their grades
if (isset($_GET['student_id'])) {
    $student_id = $conn->real_escape_string($_GET['student_id']);
    $result = $conn->query("SELECT * FROM grades_db WHERE student_id = '$student_id' ORDER BY course_code");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $student_grades[] = $row;
        }
    }
}

// Handle grade transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_student = $conn->real_escape_string($_POST['source_student'] ?? '');
    $target_student = $conn->real_escape_string($_POST['target_student'] ?? '');
    
    if ($source_student && $target_student && $source_student !== $target_student) {
        // Get all grades for the source student
        $result = $conn->query("SELECT * FROM grades_db WHERE student_id = '$source_student' ORDER BY course_code");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Insert the grade for the target student
                $stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, year, sem, grade, course_title) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('ssssss', 
                        $target_student,
                        $row['course_code'],
                        $row['year'],
                        $row['sem'],
                        $row['grade'],
                        $row['course_title']
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $success = true;
        } else {
            $error = 'No grades found for the selected student.';
        }
    } else {
        $error = 'Please select different students.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Student Grades</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Transfer Student Grades</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success">Grades transferred successfully!</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <h4 class="mb-3">Select Students</h4>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Source Student (Grades to Transfer)</label>
                    <select name="source_student" class="form-select" required>
                        <option value="">Select source student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student) ?>" 
                                <?php if (isset($_POST['source_student']) && $_POST['source_student'] === $student): ?>
                                    selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($student) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Student (Receive Grades)</label>
                    <select name="target_student" class="form-select" required>
                        <option value="">Select target student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student) ?>" 
                                <?php if (isset($_POST['target_student']) && $_POST['target_student'] === $student): ?>
                                    selected
                                <?php endif; ?>>
                                <?= htmlspecialchars($student) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Transfer Grades</button>
                <a href="stugra.php" class="btn btn-secondary">Back</a>
            </form>
        </div>

        <div class="col-md-6">
            <?php if (isset($_GET['student_id'])): ?>
                <h4 class="mb-3">Grades for Student: <?= htmlspecialchars($_GET['student_id']) ?></h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Year</th>
                                <th>Sem</th>
                                <th>Grade</th>
                                <th>Course Title</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_grades as $grade): ?>
                                <tr>
                                    <td><?= htmlspecialchars($grade['course_code']) ?></td>
                                    <td><?= htmlspecialchars($grade['year']) ?></td>
                                    <td><?= htmlspecialchars($grade['sem']) ?></td>
                                    <td><?= htmlspecialchars($grade['grade']) ?></td>
                                    <td><?= htmlspecialchars($grade['course_title']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
