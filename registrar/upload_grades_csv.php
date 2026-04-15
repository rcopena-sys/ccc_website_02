<?php
require_once __DIR__ . '/../db_connect.php';

// Helper: normalize semester value from CSV into 1 or 2 (or null if unknown)
function normalizeSemesterFromCsv($raw)
{
    $val = strtolower(trim((string)$raw));
    if ($val === '') {
        return null;
    }

    // Remove common words and punctuation
    $val = str_replace(['semester', 'sem', '.', '-', '_'], ' ', $val);
    $val = trim(preg_replace('/\s+/', ' ', $val));

    if (in_array($val, ['1', '1st', 'first'], true)) {
        return 1;
    }
    if (in_array($val, ['2', '2nd', 'second'], true)) {
        return 2;
    }

    return null;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['grades_csv'])) {
    $file = $_FILES['grades_csv']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $row = 0;
        $lockChecked = false;
        $semesterLocked = false;

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

            // On first data row, check if the semester is locked
            if (!$lockChecked) {
                $semNumber = normalizeSemesterFromCsv($sem);

                if ($semNumber !== null && in_array($semNumber, [1, 2], true)) {
                    try {
                        $checkLockTable = "SHOW TABLES LIKE 'semester_locks'";
                        $resLock = $conn->query($checkLockTable);
                        if ($resLock && $resLock->num_rows > 0) {
                            $stmtLock = $conn->prepare("SELECT is_locked FROM semester_locks WHERE semester = ? LIMIT 1");
                            if ($stmtLock) {
                                $stmtLock->bind_param('i', $semNumber);
                                $stmtLock->execute();
                                $lockRes = $stmtLock->get_result();
                                if ($lockRow = $lockRes->fetch_assoc()) {
                                    $semesterLocked = (int)$lockRow['is_locked'] === 1;
                                }
                                $stmtLock->close();
                            }
                        }
                    } catch (Exception $e) {
                        // If there is any issue checking the lock, treat as unlocked
                        $semesterLocked = false;
                    }

                    if ($semesterLocked) {
                        $label = $semNumber === 1 ? '1st Semester' : '2nd Semester';
                        $error = $label . ' is currently locked. Grade uploads for this semester are not allowed.';
                        break; // Stop processing further rows
                    }
                }

                $lockChecked = true;
            }

            if ($semesterLocked) {
                break;
            }

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
             <a href="download_grade_template.php" class="btn btn-outline-primary" download>
                <i class="bi bi-download me-1"></i>Download Sample CSV
            </a>
            <a href="studentgrade.php" class="btn btn-secondary ms-auto">Back</a>
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
