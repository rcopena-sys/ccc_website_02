<?php
require_once 'config.php';
// Handle CSV upload for grades_db

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['grades_csv'])) {
    $file = $_FILES['grades_csv']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            // Skip header row
            if ($row === 0 && preg_match('/student.?id/i', $data[0])) {
                $row++;
                continue;
            }
            $student_id = $conn->real_escape_string($data[0] ?? '');
            $course_code = $conn->real_escape_string($data[1] ?? '');
            $year = $conn->real_escape_string($data[2] ?? '');
            $sem = $conn->real_escape_string($data[3] ?? '');
            $final_grade = $conn->real_escape_string($data[4] ?? '');
            $course_title = $conn->real_escape_string($data[5] ?? '');

            if ($student_id && $course_code && $year && $sem && $final_grade) {
                $stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, year, sem, final_grade, course_title) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('ssssss', $student_id, $course_code, $year, $sem, $final_grade, $course_title);
                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $error = 'Failed to insert record: ' . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Failed to prepare statement: ' . $conn->error;
                }
            }
            $row++;
        }
        fclose($handle);
    } else {
        $error = 'Failed to open uploaded file.';
    }
} else {
    $error = 'No file uploaded.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Grades CSV</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Upload Grades CSV</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">CSV imported successfully!</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="grades_csv" class="form-label">CSV File</label>
            <input type="file" name="grades_csv" id="grades_csv" class="form-control" accept=".csv" required>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Upload</button>
            <a href="Grade_Template .csv" class="btn btn-outline-primary" download>
                <i class="bi bi-download me-1"></i>Download Sample CSV
            </a>
            <a href="stugra.php" class="btn btn-secondary ms-auto">Back</a>
        </div>
    </form>
    <div class="mt-4">
        <p><strong>CSV Format:</strong></p>
        <pre>student_id,course_code,year,semester,final_grade,course_title</pre>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
