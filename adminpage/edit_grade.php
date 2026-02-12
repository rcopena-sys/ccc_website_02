<?php
require_once 'db.php';

// Initialize variables
$message = '';
$messageType = '';
$studentId = $_GET['student_id'] ?? '';
$courseCode = $_GET['course_code'] ?? '';
$courseTitle = $_GET['course_title'] ?? '';
$grade = '';

// Fetch current grade if exists
if ($studentId && $courseCode) {
    $stmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ? AND course_code = ?");
    $stmt->bind_param("ss", $studentId, $courseCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentGrade = $result->fetch_assoc();
    $grade = $currentGrade['final_grade'] ?? '';
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newGrade = $_POST['grade'] ?? '';
    $newCourseCode = $_POST['course_code'] ?? '';
    $newCourseTitle = $_POST['course_title'] ?? '';
    
    // Validate input
    if (empty($newGrade) || empty($newCourseCode) || empty($newCourseTitle)) {
        $message = 'All fields are required';
        $messageType = 'danger';
    } else {
        // Check if grade exists (update) or needs to be inserted
        if ($currentGrade) {
            // Update existing grade
            $stmt = $conn->prepare("UPDATE grades_db SET final_grade = ?, course_code = ?, course_title = ? WHERE student_id = ? AND course_code = ?");
            $stmt->bind_param("sssss", $newGrade, $newCourseCode, $newCourseTitle, $studentId, $courseCode);
        } else {
            // Insert new grade
            $stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, course_title, final_grade) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $studentId, $newCourseCode, $newCourseTitle, $newGrade);
        }
        
        if ($stmt->execute()) {
            $message = 'Grade updated successfully';
            $messageType = 'success';
            // Update variables to show new values
            $courseCode = $newCourseCode;
            $courseTitle = $newCourseTitle;
            $grade = $newGrade;
        } else {
            $message = 'Error updating grade: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Grade - <?= htmlspecialchars($courseCode) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .grade-form { max-width: 600px; margin: 2rem auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Grade</h2>
            <a href="stueval.php?student_id=<?= urlencode($studentId) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Evaluation
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card grade-form">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" value="<?= htmlspecialchars($studentId) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" 
                               value="<?= htmlspecialchars($courseCode) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="course_title" class="form-label">Course Title</label>
                        <input type="text" class="form-control" id="course_title" name="course_title" 
                               value="<?= htmlspecialchars($courseTitle) ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="grade" class="form-label">Grade</label>
                        <input type="text" class="form-control" id="grade" name="grade" 
                               value="<?= htmlspecialchars($grade) ?>" required>
                        <div class="form-text">Enter the final grade (e.g., 1.0, 1.25, 2.5, 3.0, 5.0, INC, DRP, etc.)</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="stueval.php?student_id=<?= urlencode($studentId) ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>