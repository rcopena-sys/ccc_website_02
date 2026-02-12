<?php
session_start();
require_once '../config/connect.php';
require_once '../config/global_func.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Handle CSV file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Check for upload errors
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $_FILES['csv_file']['error']);
        }

        // Check file type
        $file_type = $_FILES['csv_file']['type'];
        if (!in_array($file_type, ['text/csv', 'application/vnd.ms-excel', 'text/plain'])) {
            throw new Exception('Please upload a valid CSV file');
        }

        // Read the CSV file
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($file === false) {
            throw new Exception('Failed to open uploaded file');
        }

        // Skip the header row
        $header = fgetcsv($file);
        
        // Process each row
        $success_count = 0;
        $error_messages = [];
        $conn->begin_transaction();

        while (($data = fgetcsv($file)) !== false) {
            if (count($data) < 7) continue; // Skip invalid rows

            // Map CSV columns to variables
            $firstname = clean_input($conn, $data[0] ?? '');
            $lastname = clean_input($conn, $data[1] ?? '');
            $email = clean_input($conn, $data[2] ?? '');
            $password = $data[3] ?? '';
            $student_id = !empty(trim($data[4] ?? '')) ? clean_input($conn, $data[4]) : null;
            $course = clean_input($conn, $data[5] ?? '');
            $classification = clean_input($conn, $data[6] ?? 'regular');
            $role_id = intval($data[7] ?? 6); // Default to student role

            // Basic validation
            if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
                $error_messages[] = "Skipped user $email: Missing required fields";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_messages[] = "Skipped user $email: Invalid email format";
                continue;
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM signin_db WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error_messages[] = "Skipped user $email: Email already exists";
                continue;
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user
            $stmt = $conn->prepare("INSERT INTO signin_db (firstname, lastname, email, password, student_id, course, classification, role_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssi", $firstname, $lastname, $email, $hashed_password, $student_id, $course, $classification, $role_id);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_messages[] = "Failed to add user $email: " . $conn->error;
            }
        }

        $conn->commit();
        fclose($file);

        if ($success_count > 0) {
            $message = "Successfully added $success_count users.";
            if (!empty($error_messages)) {
                $message .= " " . count($error_messages) . " users were not added due to errors.";
            }
        } else {
            throw new Exception("No users were added. Please check the CSV file format and try again.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Handle CSV template download
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_import_template.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add header row
    fputcsv($output, [
        'firstname', 
        'lastname', 
        'email', 
        'password', 
        'student_id', 
        'course', 
        'classification', 
        'role_id (1=admin, 2=registrar, 3=dean, 4=instructor, 5=student)'
    ]);
    
    // Add example rows
    fputcsv($output, [
        'John', 
        'Doe', 
        'john.doe@example.com', 
        'password123', 
        '2023-0001', 
        'BS Computer Science', 
        'regular', 
        '6'
    ]);
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk User Upload - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .btn-outline-secondary {
            border-color: #dee2e6;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover {
            border-color: #86b7fe;
            background-color: #f1f8ff;
        }
        .file-upload i {
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .file-upload p {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        .file-upload small {
            color: #6c757d;
        }
        .file-input {
            display: none;
        }
        .file-name {
            margin-top: 1rem;
            font-weight: 500;
        }
        .instructions {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        .instructions h6 {
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .instructions ul {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        .instructions li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Bulk User Upload</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="instructions mb-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Instructions</h6>
                            <ul>
                                <li>Download the CSV template below to ensure proper formatting</li>
                                <li>Fill in the required user information</li>
                                <li>Upload the completed CSV file using the form below</li>
                                <li>Required fields: First Name, Last Name, Email, Password</li>
                                <li>Role IDs: 1=Admin, 2=Registrar, 3=Dean, 4=Instructor, 5=Student</li>
                            </ul>
                        </div>

                        <div class="text-center mb-4">
                            <a href="?download_template=1" class="btn btn-primary">
                                <i class="fas fa-file-csv me-2"></i>Download CSV Template
                            </a>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="csv_file" class="form-label">Upload CSV File <span class="text-danger">*</span></label>
                                <div class="file-upload" id="file-upload-area" onclick="document.getElementById('csv_file').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-1">Click to upload or drag and drop</p>
                                    <small class="text-muted">CSV files only (max 5MB)</small>
                                    <input type="file" class="file-input" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>
                                <div id="file-name" class="file-name"></div>
                                <div class="invalid-feedback">Please select a CSV file to upload.</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="user_management.php" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Users
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i> Upload and Process
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload preview
        document.getElementById('csv_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('file-name').textContent = fileName;
            
            // Validate file type
            const fileInput = document.getElementById('csv_file');
            const filePath = fileInput.value;
            const allowedExtensions = /(\.csv)$/i;
            
            if (!allowedExtensions.exec(filePath)) {
                alert('Please upload a valid CSV file');
                fileInput.value = '';
                document.getElementById('file-name').textContent = '';
                return false;
            }
            
            return true;
        });

        // Enable file drop
        const fileUploadArea = document.getElementById('file-upload-area');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            fileUploadArea.classList.add('bg-light');
        }

        function unhighlight() {
            fileUploadArea.classList.remove('bg-light');
        }

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                const fileInput = document.getElementById('csv_file');
                fileInput.files = files;
                
                // Trigger change event
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        }

        // Form validation
        (function () {
            'use strict'
            
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>