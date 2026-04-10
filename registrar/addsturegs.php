<?php
include 'db.php';

// Function to format student ID as YYYY-NNNNN
function formatStudentId($id) {
    // Remove all non-digit characters
    $digits = preg_replace('/\D/', '', $id);
    
    // Accept two kinds of IDs:
    // 1) New format: YYYY-NNNNN (9 digits total, stored as 2022-10873)
    // 2) Old numeric IDs like 290015 (6 digits, stored as-is)

    if ($digits === '') {
        return '';
    }

    // Old-style 6-digit ID: keep as-is
    if (preg_match('/^\d{6}$/', $digits)) {
        return $digits;
    }

    // If we have at least 9 digits, format first 9 as YYYY-NNNNN
    if (strlen($digits) >= 9) {
        $digits = substr($digits, 0, 9);
        return substr($digits, 0, 4) . '-' . substr($digits, 4, 5);
    }

    // Fallback: return digits unchanged
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
    if (!$check_stmt) {
        error_log('addsturegs.php: Prepare failed for student_id check: ' . $conn->error);
        echo "<script>alert('Server error while checking student ID. Please contact the administrator.'); window.history.back();</script>";
        exit();
    }
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('A student with this ID already exists.'); window.history.back();</script>";
        exit();
    }
    $check_stmt->close();

    // Check if student name already exists (allow only one record per name)
    $check_name_stmt = $conn->prepare("SELECT student_id FROM students_db WHERE student_name = ?");
    if (!$check_name_stmt) {
        error_log('addsturegs.php: Prepare failed for student_name check: ' . $conn->error);
        echo "<script>alert('Server error while checking student name. Please contact the administrator.'); window.history.back();</script>";
        exit();
    }
    $check_name_stmt->bind_param("s", $student_name);
    $check_name_stmt->execute();
    $check_name_stmt->store_result();

    if ($check_name_stmt->num_rows > 0) {
        echo "<script>alert('A student with this name already exists.'); window.history.back();</script>";
        exit();
    }
    $check_name_stmt->close();

    // Insert into students_db
    $stmt = $conn->prepare("INSERT INTO students_db (student_id, student_name, email, curriculum, classification, programs, academic_year, semester, status, gender, fiscal_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('addsturegs.php: Prepare failed for students_db insert: ' . $conn->error);
        echo "<script>alert('Server error while registering student. Please contact the administrator.'); window.history.back();</script>";
        exit();
    }
    $stmt->bind_param("sssssssssss", $student_id, $student_name, $email, $curriculum, $classification, $programs, $academic_year, $semester, $status, $gender, $fiscal_year);

    if ($stmt->execute()) {
        // After successful student registration, try to auto-insert into assign_curriculum
        // 1) Parse curriculum label like "BSIT (2022-2023)" into program and fiscal_year
        $programForAssign = null;
        $fiscalForAssign = null;
        if (preg_match('/^(.*?)\s*\(([^)]+)\)$/', $curriculum, $matches)) {
            $programForAssign = trim($matches[1]);
            $fiscalForAssign = trim($matches[2]);
        }

        if ($programForAssign && $fiscalForAssign) {
            // 2) Find matching curriculum record to get curriculum_id
            $curStmt = $conn->prepare("SELECT id, program, fiscal_year FROM curriculum WHERE program = ? AND fiscal_year = ? LIMIT 1");
            $curStmt->bind_param("ss", $programForAssign, $fiscalForAssign);
            if ($curStmt->execute()) {
                $curResult = $curStmt->get_result();
                if ($curRow = $curResult->fetch_assoc()) {
                    $curriculumId = (int)$curRow['id'];

                    // 3) Find corresponding signin_db record for this student_id
                    $signStmt = $conn->prepare("SELECT id FROM signin_db WHERE student_id = ? LIMIT 1");
                    $signStmt->bind_param("s", $student_id);
                    if ($signStmt->execute()) {
                        $signResult = $signStmt->get_result();
                        if ($signRow = $signResult->fetch_assoc()) {
                            $signinId = (int)$signRow['id'];

                            // 4) Check if assignment already exists
                            $checkAssign = $conn->prepare("SELECT 1 FROM assign_curriculum WHERE program_id = ? AND curriculum_id = ? LIMIT 1");
                            $checkAssign->bind_param("ii", $signinId, $curriculumId);
                            if ($checkAssign->execute()) {
                                $checkAssign->store_result();
                                if ($checkAssign->num_rows === 0) {
                                    // 5) Insert new assignment
                                    $insertAssign = $conn->prepare("INSERT INTO assign_curriculum (program_id, curriculum_id, program, fiscal_year) VALUES (?, ?, ?, ?)");
                                    $insertAssign->bind_param("iiss", $signinId, $curriculumId, $programForAssign, $fiscalForAssign);
                                    $insertAssign->execute();
                                    $insertAssign->close();
                                }
                            }
                            $checkAssign->close();
                        }
                    }
                    $signStmt->close();
                }
            }
            $curStmt->close();
        }

        echo "<script>alert('Student registered successfully!'); window.location.href='registrar.php';</script>";
    } else {
        // Extra safety: if a UNIQUE index exists on student_id,
        // catch database duplicate-key errors and show a clear message.
        if ($stmt->errno === 1062 || (isset($stmt->error) && stripos($stmt->error, 'duplicate') !== false)) {
            echo "<script>alert('A student with this ID already exists.'); window.history.back();</script>";
        } else {
            echo "<script>alert('Error registering student.'); window.history.back();</script>";
        }
    }
    $stmt->close();
    $conn->close();
}

// Fetch distinct curriculum options based on existing assignments (assign_curriculum table)
$curriculumOptions = [];
if ($conn && $conn->connect_errno === 0) {
    // Use assign_curriculum so options reflect actually assigned curricula
    $curriculumSql = "SELECT DISTINCT curriculum_id, program, fiscal_year
                      FROM assign_curriculum
                      WHERE fiscal_year IS NOT NULL AND fiscal_year != ''
                        AND program IS NOT NULL AND program != ''
                      ORDER BY program, fiscal_year DESC";

    if ($curriculumResult = $conn->query($curriculumSql)) {
        while ($row = $curriculumResult->fetch_assoc()) {
            $curriculumOptions[] = $row;
        }
        $curriculumResult->free();
    }
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
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="e.g., 202210873 or 2022-10873 or 290015" pattern="([0-9]{4}-?[0-9]{5}|[0-9]{6})" maxlength="10" required>
                <small class="text-muted">You can type 9 digits (e.g., 202210873) and the dash will be added automatically, or use a 6-digit ID like 290015.</small>
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
                <select class="form-control" id="curriculum" name="curriculum" required>
                    <option value="">Select Curriculum</option>
                    <?php
                    if (!empty($curriculumOptions)) {
                        foreach ($curriculumOptions as $c) {
                            $label = $c['program'] . ' (' . $c['fiscal_year'] . ')';
                            $value = $label;
                            $selected = (isset($_POST['curriculum']) && $_POST['curriculum'] === $value) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                    }
                    ?>
                </select>
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
                <select class="form-control" id="programs" name="programs" required>
                    <option value="">Select Program</option>
                    <option value="BSCS">BSCS</option>
                    <option value="BSIT">BSIT</option>
                </select>
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
                <select class="form-control" id="semester" name="semester" required>
                    <option value="">Select Semester</option>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                </select>
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
        // Student ID handling
        const studentIdInput = document.getElementById('student_id');
        const submitBtn = document.getElementById('submitBtn');
        const manualForm = document.getElementById('manualAddForm');
        const studentIdError = document.getElementById('studentIdError');

        // Allow typing digits and an optional dash; keep user-entered dash
        studentIdInput.addEventListener('input', function(e) {
            let value = e.target.value;
            // Keep only digits and '-'
            value = value.replace(/[^0-9-]/g, '');
            // Limit to max 10 characters (YYYY-NNNNN)
            if (value.length > 10) {
                value = value.substring(0, 10);
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
            const studentIdField = document.getElementById('student_id');
            let studentId = studentIdField.value.trim();

            // Normalize: if user typed 9 digits without a dash, auto-insert the dash (YYYYNNNNN -> YYYY-NNNNN)
            let digitsOnly = studentId.replace(/\D/g, '');
            if (digitsOnly.length === 9 && studentId.indexOf('-') === -1) {
                studentId = digitsOnly.substring(0, 4) + '-' + digitsOnly.substring(4);
                studentIdField.value = studentId; // update field so this is what gets submitted
            }

            // Accept: YYYY-NNNNN or YYYYNNNNN, or old 6-digit ID
            const pattern = /^(\d{4}-?\d{5}|\d{6})$/;
            
            if (!pattern.test(studentId)) {
                e.preventDefault();
                alert('Student ID must be either in format YYYY-NNNNN (e.g., 2022-10890) or a 6-digit ID (e.g., 290015).');
                studentIdField.focus();
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
