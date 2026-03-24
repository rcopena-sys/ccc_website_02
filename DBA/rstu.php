<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    $student_name = trim($_POST['student_name']);
    $curriculum = trim($_POST['curriculum']);
    $classification = trim($_POST['classification']);
    $category = trim($_POST['category']);
    $programs = trim($_POST['programs']);

    // Validate required fields
    if (empty($student_id) || empty($student_name) || empty($curriculum) || empty($classification) || empty($category) || empty($programs)) {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }

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
    
    // Insert into students_db (students table)
    $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, curriculum, classification, category, programs) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $student_id, $student_name, $curriculum, $classification, $category, $programs);

    if ($stmt->execute()) {
        echo "<script>alert('Student registered successfully!'); window.location.href='registrar.php';</script>";
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body style="background: #f9f9f9; font-family: Arial, sans-serif;">
    <div class="container" style="max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.07);">
        <h2 style="text-align:center; margin-bottom:20px;">Add Student</h2>
        <form method="POST" action="">
            <label for="student_id">Student ID</label>
            <input type="text" id="student_id" name="student_id" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <label for="student_name">Student Name</label>
            <input type="text" id="student_name" name="student_name" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <label for="curriculum">Curriculum</label>
            <input type="text" id="curriculum" name="curriculum" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <label for="classification">Classification</label>
            <input type="text" id="classification" name="classification" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <label for="category">Category</label>
            <input type="text" id="category" name="category" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <label for="programs">Programs</label>
            <input type="text" id="programs" name="programs" required style="width:100%; padding:8px; margin-top:4px; border-radius:4px; border:1px solid #ccc;">

            <button type="submit" style="width:100%; padding:10px; margin-top:20px; background:#2563eb; color:#fff; border:none; border-radius:4px; font-size:16px; cursor:pointer;">Register Student</button>
        </form>
        <a href="registrar.php" style="display:block; text-align:center; margin-top:16px; color:#2563eb; text-decoration:underline;">Back to Student List</a>
    </div>
</body>
</html>
