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

// Lightweight endpoint to check if a Student ID already exists (AJAX)
if (isset($_GET['check_student_id'])) {
    header('Content-Type: application/json');
    $candidate = trim($_GET['check_student_id']);
    $student_id = formatStudentId($candidate);
    $exists = false;

    if ($stmt = $conn->prepare("SELECT 1 FROM students_db WHERE student_id = ? LIMIT 1")) {
        $stmt->bind_param("s", $student_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            $exists = ($stmt->num_rows > 0);
        }
        $stmt->close();
    }

    echo json_encode([ 'exists' => $exists, 'student_id' => $student_id ]);
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = formatStudentId(trim($_POST['student_id']));
    $student_name = trim($_POST['student_name']);
    
    // Validate student name (only letters and spaces allowed)
    if (!preg_match("/^[a-zA-Z\s]+$/", $student_name)) {
        echo "<script>alert('Student name can only contain letters and spaces.'); window.history.back();</script>";
        exit();
    }
    
    $email = trim($_POST['email']);
    $curriculum = trim($_POST['curriculum']);
    $classification = trim($_POST['classification']);
    $programs = trim($_POST['programs']);
    $academic_year = trim($_POST['academic_year']);
    $semester = trim($_POST['semester']);
    $status = trim($_POST['status']);
    $gender = trim($_POST['gender']);
    $fiscal_year = trim($_POST['fiscal_year']);

    // Validate required fields (email is optional)
    if (empty($student_id) || empty($student_name) || empty($curriculum) || empty($classification) || empty($programs) || empty($academic_year) || empty($semester) || empty($status) || empty($gender) || empty($fiscal_year)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    // Validate email format if provided
    if (!empty($email)) {
        // Check if email ends with @ccc.edu.ph
        if (!preg_match('/@ccc\.edu\.ph$/', $email)) {
            echo "<script>alert('Email must end with @ccc.edu.ph'); window.history.back();</script>";
            exit();
        }
        // Validate general email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Please enter a valid email address.'); window.history.back();</script>";
            exit();
        }
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

    // Insert into students_db
    $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, email, curriculum, classification, programs, academic_year, semester, status, gender, fiscal_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $student_id, $student_name, $email, $curriculum, $classification, $programs, $academic_year, $semester, $status, $gender, $fiscal_year);

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
        <form id="manualAddForm" method="POST" action="">
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID (Format: YYYY-NNNNN)</label>
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="e.g., 2022-10873" pattern="[0-9]{4}-[0-9]{5}" maxlength="10" required>
                <small class="text-muted">Enter 9 digits (year + student number). Format will be applied automatically.</small>
                <div id="studentIdError" class="text-danger small mt-1"></div>
            </div>
            <div class="mb-3">
                <label for="student_name" class="form-label">Student Name</label>
                <input type="text" class="form-control" id="student_name" name="student_name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="student@ccc.edu.ph" pattern="^[^@]+@ccc\.edu\.ph$">
                <small class="text-muted">Optional: Must end with @ccc.edu.ph</small>
            </div>
            <div class="mb-3">
                <label for="curriculum" class="form-label">Curriculum</label>
                <input type="text" class="form-control" id="curriculum" name="curriculum" required>
            </div>
            <div class="mb-3">
                <label for="classification" class="form-label">Classification</label>
                <select class="form-control" id="classification" name="classification" required>
                    <option value="">Select Classification</option>
                    <option value="Regular">Regular</option>
                    <option value="Irregular">Irregular</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="programs" class="form-label">Programs</label>
                <input type="text" class="form-control" id="programs" name="programs" required>
            </div>
            <div class="mb-3">
                <label for="academic_year" class="form-label">Year</label>
                <select class="form-control" id="academic_year" name="academic_year" required>
                    <option value="">Select Year</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                    <option value="5th Year">5th Year</option>
                </select>
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
            <div class="mb-3">
                <label for="fiscal_year" class="form-label">Fiscal Year</label>
                <select class="form-control" id="fiscal_year" name="fiscal_year" required>
                    <option value="">Select Fiscal Year</option>
                    <?php
                    // Test fiscal years table
                    $test_query = "SELECT COUNT(*) as count FROM fiscal_years";
                    $test_result = $conn->query($test_query);
                    $count = $test_result->fetch_assoc()['count'];
                    
                    echo "<!-- Fiscal years count: $count -->";
                    
                    if ($count > 0) {
                        // Simple fiscal year query
                        $fiscal_query = "SELECT label FROM fiscal_years ORDER BY start_date DESC, id DESC";
                        $fiscal_result = $conn->query($fiscal_query);
                        
                        if ($fiscal_result) {
                            while ($row = $fiscal_result->fetch_assoc()) {
                                $fy = $row['label'];
                                $selected = (isset($_POST['fiscal_year']) && $_POST['fiscal_year'] == $fy) ? 'selected' : '';
                                echo "<option value=\"$fy\" $selected>$fy</option>";
                            }
                        }
                    } else {
                        echo "<!-- No fiscal years found in table -->";
                    }
                    ?>
                </select>
            </div>
            <button id="submitBtn" type="submit" class="btn btn-primary w-100">Register Student</button>
        </form>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Upload Students via CSV</h2>
            <a href="download_template.php" class="btn btn-outline-primary" download>
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
        <a href="registrar.php" class="d-block text-center mt-4">Back to Student List</a>
    </div>
    <script>
        // Auto-format student ID as user types
        const studentIdInput = document.getElementById('student_id');
        const submitBtn = document.getElementById('submitBtn');
        const manualForm = document.getElementById('manualAddForm');
        const studentIdError = document.getElementById('studentIdError');

        studentIdInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove all non-digit characters
            
            // Add dash after 4 digits
            if (value.length >= 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 9);
            }
            
            e.target.value = value;
        });

        // Debounced check for duplicate Student ID
        let checkTimer = null;
        function triggerDupCheck() {
            const raw = studentIdInput.value.trim();
            if (!raw) { studentIdError.textContent = ''; submitBtn.disabled = false; return; }
            fetch('addsturegs.php?check_student_id=' + encodeURIComponent(raw))
                .then(res => res.json())
                .then(data => {
                    if (data && data.exists) {
                        studentIdError.textContent = 'This Student ID already exists.';
                        submitBtn.disabled = true;
                    } else {
                        studentIdError.textContent = '';
                        submitBtn.disabled = false;
                    }
                })
                .catch(() => {
                    // On error, do not block submission
                    studentIdError.textContent = '';
                    submitBtn.disabled = false;
                });
        }
        studentIdInput.addEventListener('input', function() {
            clearTimeout(checkTimer);
            checkTimer = setTimeout(triggerDupCheck, 400);
        });
        studentIdInput.addEventListener('blur', triggerDupCheck);
        
        // Validate format on form submission
        manualForm.addEventListener('submit', function(e) {
            const studentId = document.getElementById('student_id').value;
            const pattern = /^\d{4}-\d{5}$/;
            
            if (!pattern.test(studentId)) {
                e.preventDefault();
                alert('Student ID must be in format YYYY-NNNNN (e.g., 2022-10873)');
                document.getElementById('student_id').focus();
                return;
            }
            
            // Validate email if provided
            const email = document.getElementById('email').value;
            if (email && !email.endsWith('@ccc.edu.ph')) {
                e.preventDefault();
                alert('Email must end with @ccc.edu.ph');
                document.getElementById('email').focus();
            }

            // Block submission if duplicate detected
            if (submitBtn.disabled) {
                e.preventDefault();
                alert('Duplicate Student ID detected. Please use a unique ID.');
                document.getElementById('student_id').focus();
            }
        });
    </script>
</body>
</html>
