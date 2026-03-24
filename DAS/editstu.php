<?php
include 'db.php';

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard2.php');
    exit();
}

$student_id = $_GET['id'];
$error = '';
$success = '';

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students_db WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Student not found.";
} else {
    $student = $result->fetch_assoc();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
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

    // Update the student record
    $update_sql = "UPDATE students_db SET 
                  student_name = ?, 
                  curriculum = ?, 
                  classification = ?, 
                  category = ?, 
                  programs = ?,
                  academic_year = ?,
                  fiscal_year = ?,
                  semester = ?,
                  status = ?,
                  gender = ?
                  WHERE student_id = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssssssssss", 
        $student_name, 
        $curriculum, 
        $classification, 
        $category, 
        $programs,
        $academic_year,
        $fiscal_year,
        $semester,
        $status,
        $gender,
        $student_id
    );

    if ($stmt->execute()) {
        $success = "Student record updated successfully!";
        // Refresh student data
        $stmt = $conn->prepare("SELECT * FROM students_db WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Registrar</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
            margin-top: 5px;
        }
        .btn { 
            background: #2563eb; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel { background: #6b7280; }
        .btn-delete { background: #dc2626; }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            color: white;
        }
        .alert-success { background: #10b981; }
        .alert-error { background: #ef4444; }
        .form-actions { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Student</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($student)): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id_display" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                <input type="hidden" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
            </div>
            
            <div class="form-group">
                <label for="student_name">Student Name</label>
                <input type="text" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="curriculum">Curriculum</label>
                <input type="text" id="curriculum" name="curriculum" value="<?php echo htmlspecialchars($student['curriculum']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="classification">Classification</label>
                <select id="classification" name="classification" required>
                    <option value="Regular" <?php echo ($student['classification'] === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                    <option value="Irregular" <?php echo ($student['classification'] === 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                    <option value="Transferee" <?php echo ($student['classification'] === 'Transferee') ? 'selected' : ''; ?>>Transferee</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($student['category']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($student['academic_year']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="fiscal_year">Fiscal Year</label>
                <input type="text" id="fiscal_year" name="fiscal_year" value="<?php echo htmlspecialchars($student['fiscal_year']); ?>" placeholder="e.g., 2024-2025" required>
            </div>
            
            <div class="form-group">
                <label for="programs">Program</label>
                <select id="programs" name="programs" required>
                    <option value="BSIT" <?php echo ($student['programs'] === 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                    <option value="BSCS" <?php echo ($student['programs'] === 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <select id="academic_year" name="academic_year" required>
                    <?php for($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($student['academic_year'] == $i) ? 'selected' : ''; ?>>Year <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="semester">Semester</label>
                <select id="semester" name="semester" required>
                    <option value="1" <?php echo ($student['semester'] == '1') ? 'selected' : ''; ?>>1st Semester</option>
                    <option value="2" <?php echo ($student['semester'] == '2') ? 'selected' : ''; ?>>2nd Semester</option>
                    <option value="3" <?php echo ($student['semester'] == '3') ? 'selected' : ''; ?>>Summer</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Regular" <?php echo ($student['status'] === 'Regular') ? 'selected' : ''; ?>>Regular</option>
                    <option value="Irregular" <?php echo ($student['status'] === 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Update Student</button>
                <a href="registrar.php" class="btn btn-cancel">Cancel</a>
                <a href="deletestu.php?id=<?php echo urlencode($student['student_id']); ?>" 
                   class="btn btn-delete" 
                   onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                    Delete Student
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>