<?php
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'], $_POST['curriculum'])) {
    $student_id = trim($_POST['student_id']);
    $curriculum = trim($_POST['curriculum']);

    if (empty($student_id) || empty($curriculum)) {
        $message = "Please fill in all fields.";
    } else {
        // Check if the student exists
        $stmt_check = $conn->prepare("SELECT student_id FROM list_db WHERE student_id = ?");
        $stmt_check->bind_param("s", $student_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Update the curriculum for the student
            $stmt_update = $conn->prepare("UPDATE list_db SET curriculum = ? WHERE student_id = ?");
            $stmt_update->bind_param("ss", $curriculum, $student_id);

            if ($stmt_update->execute()) {
                $message = "Curriculum for student " . htmlspecialchars($student_id) . " has been updated to " . htmlspecialchars($curriculum) . " successfully!";
            } else {
                $message = "Error updating curriculum.";
            }
            $stmt_update->close();
        } else {
            $message = "Student with ID " . htmlspecialchars($student_id) . " not found.";
        }
        $stmt_check->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Student Curriculum</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 50px auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Set Student Curriculum</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="curriculum" class="form-label">Select Curriculum</label>
                <select class="form-select" id="curriculum" name="curriculum" required>
                    <option value="">Choose a curriculum...</option>
                    <option value="BSIT 2022">BSIT 2022 Curriculum</option>
                    <option value="BSCS 2022">BSCS 2022 Curriculum</option>
                    <!-- Add other curricula as needed -->
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Set Curriculum</button>
        </form>
    </div>
</body>
</html> 