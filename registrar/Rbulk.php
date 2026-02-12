<?php
include 'db.php';

// Function to format student ID as YYYY-NNNNN
function formatStudentId($id) {
    // Remove all non-digit characters
    $digits = preg_replace('/\D/', '', $id);
    
    // If we have at least 9 digits, format as YYYY-NNNNN
    if (strlen($digits) >= 9) {
        return substr($digits, 0, 4) . '-' . substr($digits, 4, 5);
    }
    
    // If less than 9 digits, return as is or format what we have
    if (strlen($digits) >= 4) {
        return substr($digits, 0, 4) . '-' . substr($digits, 4);
    }
    
    return $digits;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    if ($handle) {
        // Skip header row
        fgetcsv($handle);
        $success = 0;
        $fail = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Map CSV columns to variables
            // Adjust indexes if your CSV header order is different
            $student_id = trim($data[0]);
            $student_name = trim($data[1]);  // Combined name field
            $email = trim($data[2]);
            $curriculum = trim($data[3]);
            $classification = trim($data[4]);
            $programs = trim($data[5]);

            // Validate email if provided
            if (!empty($email)) {
                // Check if email ends with @ccc.edu.ph
                if (!preg_match('/@ccc\.edu\.ph$/', $email)) {
                    $fail++;
                    continue;
                }
                // Validate general email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fail++;
                    continue;
                }
            }

            // Format student ID using the consistent formatting function
            $student_id = formatStudentId($student_id);

            // First check if student already exists
            $checkStmt = $conn->prepare("SELECT student_id FROM students_db WHERE student_id = ?");
            $checkStmt->bind_param("s", $student_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            
            if ($checkStmt->num_rows > 0) {
                // Student exists, update the record
                $checkStmt->close();
                $stmt = $conn->prepare("UPDATE students_db SET student_name = ?, email = ?, curriculum = ?, classification = ?, programs = ? WHERE student_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $student_name, $email, $curriculum, $classification, $programs, $student_id);
                    if ($stmt->execute()) {
                        $success++;
                    } else {
                        $fail++;
                    }
                } else {
                    $fail++;
                }
            } else {
                // Student doesn't exist, insert new record
                $checkStmt->close();
                $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, email, curriculum, classification, programs) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $student_id, $student_name, $email, $curriculum, $classification, $programs);
                    if ($stmt->execute()) {
                        $success++;
                    } else {
                        $fail++;
                    }
                } else {
                    $fail++;
                }
            }
                if (isset($stmt)) {
                    $stmt->close();
                }
        }
        fclose($handle);
        $conn->close();
        echo "<script>alert('Upload complete: $success students added, $fail failed.'); window.location.href='registrar.php';</script>";
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
    <title>Bulk Upload Students</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
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
                <div class="note">Accepted format: CSV only. <br>Columns: student_id, student_name, email, curriculum, classification, category, programs<br><strong>Note:</strong> Student IDs will be automatically formatted to YYYY-NNNNN (e.g., 2022-10873)<br><strong>Email:</strong> Optional, must end with @ccc.edu.ph if provided</div>
            </div>
            <button type="submit">Upload</button>
        </form>
        <div class="note" style="text-align: center;">
            <a href="download_template.php" class="sample-link" download>
                <i class="bi bi-download"></i> Download Sample CSV Template
            </a>
        </div>
        <div style="margin-top: 20px; text-align:center;">
            <a href="registrar.php" style="color:#2563eb; text-decoration:underline;">Back to Student List</a>
        </div>
    </div>
</body>
</html>
