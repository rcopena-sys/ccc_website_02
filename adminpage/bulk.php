<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    set_time_limit(300); // Increase execution time to 5 minutes
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if ($handle) {
        // Skip header row
        fgetcsv($handle);
        $success = 0;
        $fail = 0;
        $conn->begin_transaction();
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Map CSV columns to variables
            // Adjust indexes if your CSV header order is different
            $student_id = trim($data[0]);
            $student_name = trim($data[1]);
            $curriculum = trim($data[2]);
            $classification = trim($data[3]);
            $category = trim($data[4]);
            $programs = trim($data[5]);
            $fiscal_year = trim($data[6]); // Added fiscal_year

            // Strict validation for student ID format (YYYY-XXXXX)
            if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
                $fail++;
                continue; // Skip this row if student ID format is invalid
            }

            // Check if student already exists
            $check_stmt = $conn->prepare("SELECT student_id FROM students_db WHERE student_id = ?");
            $check_stmt->bind_param("s", $student_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                // Student exists, update the record
                $stmt = $conn->prepare("UPDATE students_db SET student_name = ?, curriculum = ?, classification = ?, category = ?, programs = ?, fiscal_year = ? WHERE student_id = ?");
                $stmt->bind_param("sssssss", $student_name, $curriculum, $classification, $category, $programs, $fiscal_year, $student_id);
            } else {
                // Student does not exist, insert a new record
                $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, curriculum, classification, category, programs, fiscal_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $student_id, $student_name, $curriculum, $classification, $category, $programs, $fiscal_year);
            }
            $check_stmt->close();

            if ($stmt->execute()) {
                $success++;
            } else {
                $fail++;
            }
            $stmt->close();
        }
        fclose($handle);
        $conn->commit();
        $conn->close();
        echo "<script>alert('Upload complete: $success students added, $fail failed.'); window.location.href='list.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to open file.'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students</title>
    <style>
        body { background: #f9f9f9; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 500px; margin: 80px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 40px; }
        h2 { color: #2563eb; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 8px; }
        input[type="file"] { display: block; margin-bottom: 16px; }
        button { background: #2563eb; color: #fff; border: none; padding: 12px 30px; border-radius: 8px; font-size: 1rem; cursor: pointer; width: 100%; }
        button:hover { background: #1e40af; }
        .note { font-size: 0.95em; color: #555; margin: 20px 0; }
        .sample-link { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e0f2fe;
            color: #0369a1;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid #bae6fd;
        }
        .sample-link:hover {
            background: #bae6fd;
            border-color: #7dd3fc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bulk Upload Students</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Upload CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                <div class="note">Accepted format: CSV only. <br>Columns: student_id, student_name, curriculum, classification, category, programs, fiscal_year<br><strong>Student ID must be in format: YYYY-XXXXX (e.g., 2022-10873)</strong></div>
            </div>
            <button type="submit">Upload</button>
        </form>
        <div class="note" style="text-align: center;">
            <a href="add_student_template.csv" class="sample-link" download>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download Sample CSV Template
            </a>
        </div>
        <div style="margin-top: 20px; text-align:center;">
            <a href="list.php" style="color:#2563eb; text-decoration:underline;">Back to Student List</a>
        </div>
    </div>
</body>
</html>
