<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $curriculum = trim($_POST['curriculum']);
    $classification = trim($_POST['classification']);
    $category = trim($_POST['category']);
    $programs = trim($_POST['programs']);
    $academic_year = trim($_POST['academic_year']);
    $fiscal_year = trim($_POST['fiscal_year']);
    $semester = trim($_POST['semester']);
    $status = trim($_POST['status']);
    $gender = trim($_POST['gender']);

    // Validate required fields
    if (empty($student_id) || empty($student_name) || empty($curriculum) || empty($classification) || empty($category) || empty($programs) || empty($academic_year) || empty($fiscal_year) || empty($semester) || empty($status) || empty($gender)) {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }

    // Check if student ID already exists
    $check_stmt = $conn->prepare("SELECT student_id FROM students_db WHERE student_id = ?");
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('A student with this ID already exists.'); window.history.back();</script>";
        exit();
    }
    $check_stmt->close();

    // Format student ID to YYYY-XXXXX format if not already
    if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
        // Extract all digits
        preg_match_all('/\d+/', $student_id, $matches);
        $digits = implode('', $matches[0]);
        
        // Format as YYYY-XXXXX (first 4 digits as year, next 5 as ID)
        if (strlen($digits) >= 9) {
            $student_id = substr($digits, 0, 4) . '-' . substr($digits, 4, 5);
        }
    }
    
    // Insert into students_db
    $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, curriculum, classification, category, programs, academic_year, fiscal_year, semester, status, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $student_id, $student_name, $curriculum, $classification, $category, $programs, $academic_year, $fiscal_year, $semester, $status, $gender);

    if ($stmt->execute()) {
        echo "<script>alert('Student registered successfully!'); window.location.href='list.php';</script>";
    } else {
        echo "<script>alert('Error registering student.'); window.history.back();</script>";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
        h2 { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Student Manually</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="student_name" class="form-label">Student Name</label>
                <input type="text" class="form-control" id="student_name" name="student_name" required>
            </div>
            <div class="mb-3">
                <label for="curriculum" class="form-label">Curriculum</label>
                <input type="text" class="form-control" id="curriculum" name="curriculum" required>
            </div>
            <div class="mb-3">
                <label for="classification" class="form-label">Classification</label>
                <input type="text" class="form-control" id="classification" name="classification" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" class="form-control" id="category" name="category" required>
            </div>
            <div class="mb-3">
                <label for="programs" class="form-label">Programs</label>
                <input type="text" class="form-control" id="programs" name="programs" required>
            </div>
            <div class="mb-3">
                <label for="academic_year" class="form-label">Academic Year</label>
                <input type="text" class="form-control" id="academic_year" name="academic_year" required>
            </div>
            <div class="mb-3">
                <label for="fiscal_year" class="form-label">Fiscal Year</label>
                <input type="text" class="form-control" id="fiscal_year" name="fiscal_year" placeholder="e.g., 2024-2025" required>
            </div>
            <div class="mb-3">
                <label for="semester" class="form-label">Semester</label>
                <input type="text" class="form-control" id="semester" name="semester" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="">Select Status</option>
                    <option value="Regular">Regular</option>
                    <option value="Irregular">Irregular</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register Student</button>
        </form>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Upload Students via CSV</h2>
            <a href="add_student_template.csv" class="btn btn-outline-primary" download>
                <i class="bi bi-download me-2"></i>Download Sample CSV
            </a>
        </div>
        <form action="bulk.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="csv_file" class="form-label">Select CSV File</label>
                <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Upload and Add Students</button>
        </form>
        <a href="list.php" class="d-block text-center mt-4">Back to Student List</a>
    </div>
</body>
</html>
