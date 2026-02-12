<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Initialize variables
$error = '';
$success = '';

// Fetch user for editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: user_list.php");
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: user_list.php");
    exit();
}

// Update user info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        // Get and sanitize input
        $id = intval($_POST['id']);
        $firstname = trim($conn->real_escape_string($_POST['firstname']));
        $lastname = trim($conn->real_escape_string($_POST['lastname']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $student_id = trim($conn->real_escape_string($_POST['student_id']));
        $academic_year = trim($conn->real_escape_string($_POST['academic_year']));
        $course = trim($conn->real_escape_string($_POST['course']));
        $role_id = intval($_POST['role_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $classification = $conn->real_escape_string($_POST['classification']);
        
        // Basic validation
        if (empty($firstname) || empty($lastname) || empty($email)) {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM signin_db WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already in use by another account.";
                $stmt->close();
            } elseif ($_POST['role_id'] == 2) { 
                $deanCheck = $conn->prepare("SELECT id FROM signin_db WHERE role_id = 2 AND id != ?");
                $deanCheck->bind_param("i", $id);
                $deanCheck->execute();
                $deanResult = $deanCheck->get_result();
                
                if ($deanResult->num_rows > 0) {
                    $error = "A dean already exists in the system. Only one dean account is allowed.";
                    $deanCheck->close();
                } else {
                    $deanCheck->close();
                }
            }
            
            if (empty($error)) {
                // Prepare update query
                $update_fields = [];
                $update_fields[] = "firstname = ?";
                $update_fields[] = "lastname = ?";
                $update_fields[] = "email = ?";
                $update_fields[] = "student_id = ?";
                $update_fields[] = "academic_year = ?";
                $update_fields[] = "course = ?";
                $update_fields[] = "role_id = ?";
                $update_fields[] = "status = ?";
                $update_fields[] = "classification = ?";
                $update_fields[] = "updated_at = NOW()";
                
                // Initialize types and params arrays
                $types = "ssssssiss"; // 9 parameters: ssssssiss
                $params = [
                    &$firstname, 
                    &$lastname, 
                    &$email, 
                    &$student_id, 
                    &$academic_year, 
                    &$course, 
                    &$role_id, 
                    &$status, 
                    &$classification
                ];

                // Validate student_id: if empty, skip updating it to avoid inserting empty unique key;
                // if non-empty, ensure it's unique (except for this user)
                if ($student_id === '') {
                    // If empty, set the student_id column to NULL in the UPDATE
                    // update_fields order: firstname, lastname, email, student_id, academic_year, course, role_id, status, classification
                    $update_fields[3] = "student_id = NULL";
                    // Remove the corresponding param for student_id
                    array_splice($params, 3, 1);
                    // Remove the corresponding type character at position 3
                    $types = substr($types, 0, 3) . substr($types, 4);
                } else {
                    $checkSid = $conn->prepare("SELECT id FROM signin_db WHERE student_id = ? AND id != ?");
                    if ($checkSid) {
                        $checkSid->bind_param('si', $student_id, $id);
                        $checkSid->execute();
                        $resSid = $checkSid->get_result();
                        if ($resSid && $resSid->num_rows > 0) {
                            $error = "Student ID already in use by another account.";
                        }
                        $checkSid->close();
                    }
                }

                // Handle password update if provided
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 8) {
                        $error = "Password must be at least 8 characters long.";
                    } else {
                        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        $update_fields[] = "password = ?";
                        $types .= "s";
                        $params[] = &$password;
                    }
                }
                
                if (empty($error)) {
                    $sql = "UPDATE signin_db SET " . implode(", ", $update_fields) . " WHERE id = ?";
                    $types .= "i";
                    $params[] = &$id;
                    
                    $stmt = $conn->prepare($sql);
                    array_unshift($params, $types);
                    call_user_func_array([$stmt, 'bind_param'], $params);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'User updated successfully!';
                        header("Location: user_list.php");
                        exit();
                    } else {
                        $error = "Error updating user: " . $conn->error;
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #4e73df;
        }
        .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.3rem;
        }
        .form-control, .form-select {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus, .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }
        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6e707e;
        }
        .password-strength {
            height: 4px;
            margin-top: 5px;
            border-radius: 2px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        .strength-0 { width: 20%; background-color: #e74a3b; }
        .strength-1 { width: 40%; background-color: #f6c23e; }
        .strength-2 { width: 60%; background-color: #f6c23e; }
        .strength-3 { width: 80%; background-color: #1cc88a; }
        .strength-4 { width: 100%; background-color: #1cc88a; }
        .password-hint {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit User</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="user_list.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Users
                        </a>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="editUserForm" novalidate>
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                                    <div class="invalid-feedback">Please enter first name</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                                    <div class="invalid-feedback">Please enter last name</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="student_id" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           value="<?php echo htmlspecialchars($user['student_id']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <select class="form-select" id="academic_year" name="academic_year">
                                        <option value="">-- Select Academic Year --</option>
                                        <?php
                                        $current_year = date('Y');
                                        for ($i = $current_year - 5; $i <= $current_year + 5; $i++) {
                                            $year_range = $i . '-' . ($i + 1);
                                            $selected = ($user['academic_year'] == $year_range) ? 'selected' : '';
                                            echo "<option value=\"$year_range\" $selected>$year_range</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="course" class="form-label">Course</label>
                                    <input type="text" class="form-control" id="course" name="course" 
                                           value="<?php echo htmlspecialchars($user['course']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 mb-3">
                                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role_id" name="role_id" required>
                                        <option value="">-- Select Role --</option>
                                        <?php
                                        // Static role list (used here instead of a DB lookup)
                                        $roles = [
                                            1 => 'Administrator',
                                            2 => 'Faculty',
                                            3 => 'registrar',
                                            4 => 'BSIT',
                                            5 => 'BSCS',
                                        ];
                                        foreach ($roles as $id => $name) {
                                            $selected = ($user['role_id'] == $id) ? 'selected' : '';
                                            echo "<option value=\"$id\" $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a role</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Active" <?php echo ($user['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($user['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Pending" <?php echo ($user['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Suspended" <?php echo ($user['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="classification" class="form-label">Classification</label>
                                    <select class="form-select" id="classification" name="classification">
                                        <option value="">-- Select Classification --</option>
                                        <option value="Regular" <?php echo ($user['classification'] == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Irregular" <?php echo ($user['classification'] == 'Irregular') ? 'selected' : ''; ?>>Irregular</option>
                                        <option value="Transferee" <?php echo ($user['classification'] == 'Transferee') ? 'selected' : ''; ?>>Transferee</option>
                                        <option value="Returning" <?php echo ($user['classification'] == 'Returning') ? 'selected' : ''; ?>>Returning</option>
                                        <option value="Graduating" <?php echo ($user['classification'] == 'Graduating') ? 'selected' : ''; ?>>Graduating</option>
                                    </select>
                                </div>
                            </div>

                            <div class="card mb-4 border-left-warning">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning">Change Password</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">New Password</label>
                                        <div class="password-toggle">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Leave blank to keep current password">
                                            <span class="toggle-password" onclick="togglePassword('password')">
                                                <i class="far fa-eye"></i>
                                            </span>
                                        </div>
                                        <div class="password-strength mt-2" id="passwordStrength"></div>
                                        <div class="password-hint">
                                            Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <div class="password-toggle">
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   placeholder="Confirm new password">
                                            <span class="toggle-password" onclick="togglePassword('confirm_password')">
                                                <i class="far fa-eye"></i>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="passwordMatchError">
                                            Passwords do not match.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="user_list.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch the form we want to apply custom Bootstrap validation styles to
            var form = document.getElementById('editUserForm')
            
            // Password strength meter
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    updatePasswordStrengthMeter(strength);
                });
            }
            
            // Check password strength
            function checkPasswordStrength(password) {
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength++;
                
                // Contains lowercase letter
                if (/[a-z]/.test(password)) strength++;
                
                // Contains uppercase letter
                if (/[A-Z]/.test(password)) strength++;
                
                // Contains number
                if (/[0-9]/.test(password)) strength++;
                
                // Contains special character
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                return Math.min(strength, 5); // Cap at 5 for strength classes
            }
            
            // Update password strength meter
            function updatePasswordStrengthMeter(strength) {
                passwordStrength.className = 'strength-' + (strength - 1);
                passwordStrength.style.display = strength > 0 ? 'block' : 'none';
            }
            
            // Toggle password visibility
            window.togglePassword = function(fieldId) {
                const input = document.getElementById(fieldId);
                const icon = input.nextElementSibling.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            };
            
            // Password match validation
            const confirmPassword = document.getElementById('confirm_password');
            const password = document.getElementById('password');
            const passwordMatchError = document.getElementById('passwordMatchError');
            
            function validatePasswordMatch() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords do not match");
                    confirmPassword.classList.add('is-invalid');
                    passwordMatchError.style.display = 'block';
                    return false;
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.classList.remove('is-invalid');
                    passwordMatchError.style.display = 'none';
                    return true;
                }
            }
            
            if (confirmPassword && password) {
                confirmPassword.addEventListener('input', validatePasswordMatch);
                password.addEventListener('input', validatePasswordMatch);
            }
            
            // Form submission
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                } else if (password && password.value && !validatePasswordMatch()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })()
    </script>
</body>
</html>