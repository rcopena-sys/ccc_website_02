<?php
$servername = "localhost";
$username = "u353705507_ccc_curriculum";
$password = "RoZz_puGeCivic96Vti1";
$dbname = "u353705507_ccc_cureval";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("connection failed" . $conn->connect_error);
}

// CLEAN INPUT
function clean_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

// ✅ FIX: ADD PASSWORD VALIDATOR FUNCTION
function validate_password_strength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => implode(" ", $errors)
        ];
    }

    return [
        'valid' => true,
        'message' => 'Password is strong.'
    ];
}

$message = '';
$error = '';

// Enable errors (helps when debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate form data
        $firstname = clean_input($conn, $_POST['firstname'] ?? '');
        $lastname = clean_input($conn, $_POST['lastname'] ?? '');
        $email = clean_input($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Convert empty student_id to NULL
        $student_id = !empty(trim($_POST['student_id'] ?? '')) ? clean_input($conn, $_POST['student_id']) : null;

        $course = clean_input($conn, $_POST['course'] ?? '');
        $classification = clean_input($conn, $_POST['classification'] ?? 'regular');
        $role_id = intval($_POST['role_id'] ?? 6);

        // Determine role type
        $isStudentRole = false;
        $roleNameStmt = $conn->prepare("SELECT role_name FROM roles WHERE role_id = ? LIMIT 1");
        if ($roleNameStmt) {
            $roleNameStmt->bind_param('i', $role_id);
            $roleNameStmt->execute();
            $rres = $roleNameStmt->get_result();
            if ($rrow = $rres->fetch_assoc()) {
                $roleName = strtolower($rrow['role_name'] ?? '');
                if (strpos($roleName, 'student') !== false || $roleName === 'bscs' || $roleName === 'bsit') {
                    $isStudentRole = true;
                }
            }
            $roleNameStmt->close();
        }

        if (!$isStudentRole) {
            $student_id = null;
            $course = '';
            $classification = '';
        }

        if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }

        // Validate names
        $firstname = ucfirst(strtolower(trim($firstname)));
        if (!preg_match('/^(?=.*[A-ZÁÉÍÓÚÜÑ])[A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\'-]*$/u', $firstname)) {
            throw new Exception('Please enter a valid first name with at least one capital letter (e.g., "John")');
        }

        $lastname = ucfirst(strtolower(trim($lastname)));
        if (!preg_match('/^(?=.*[A-ZÁÉÍÓÚÜÑ])[A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s\'-]*$/u', $lastname)) {
            throw new Exception('Please enter a valid last name with at least one capital letter (e.g., "Doe")');
        }

        if ($isStudentRole) {
            if (empty($student_id) || empty($course)) {
                throw new Exception('Student ID and Course are required for student roles.');
            }

            if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
                throw new Exception('Student ID must be in the format YYYY-##### (e.g., 2022-10873)');
            }
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }

        if (strpos(strtolower($email), '@gmail.com') !== false) {
            throw new Exception('Gmail addresses are not accepted. Use @ccc.edu.ph');
        }

        if (!preg_match('/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i', $email)) {
            throw new Exception('Only @ccc.edu.ph email addresses are accepted.');
        }

        // PASSWORD VALIDATION FIX
        $password_validation = validate_password_strength($password);
        if (!$password_validation['valid']) {
            throw new Exception($password_validation['message']);
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Check existing email
        $stmt = $conn->prepare("SELECT id FROM signin_db WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already exists');
        }

        // Dean role restriction
        $deanRoleNames = ['DBA Dean', 'DAS Dean', 'DTE Dean', 'Dean'];
        if (in_array(strtolower($rrow['role_name'] ?? ''), array_map('strtolower', $deanRoleNames))) {
            $deanCheck = $conn->prepare("SELECT id FROM signin_db s JOIN roles r ON s.role_id = r.role_id WHERE r.role_name = ? LIMIT 1");
            $deanCheck->bind_param("s", $rrow['role_name']);
            $deanCheck->execute();
            if ($deanCheck->get_result()->num_rows > 0) {
                throw new Exception('A ' . htmlspecialchars($rrow['role_name']) . ' already exists. Only one allowed.');
            }
            $deanCheck->close();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);

        if ($hashed_password === false) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO signin_db 
            (firstname, lastname, email, password, student_id, course, classification, status, role_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $status = 'active';

        $stmt->bind_param(
            "sssssssss",
            $firstname,
            $lastname,
            $email,
            $hashed_password,
            $student_id,
            $course,
            $classification,
            $status,
            $role_id
        );

        if ($stmt->execute()) {
            $message = 'User added successfully!';
            $_POST = []; // reset form
        } else {
            throw new Exception('Failed to add user: ' . $conn->error);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get roles
$roles = [];
$result = $conn->query("SELECT * FROM roles ORDER BY role_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Add New User</h4>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="studentMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-graduate me-1"></i> Student Views
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="studentMenuButton">
                                <li><a class="dropdown-item" href="student_acc.php"><i class="fas fa-id-card me-2"></i>Student Accounts</a></li>
                                <li><a class="dropdown-item" href="list_students.php"><i class="fas fa-list me-2"></i>Student List</a></li>
                            </ul>
                        </div>
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

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstname" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" 
                                           pattern="(?=.*[A-ZÁÉÍÓÚÜÑ])[A-Za-zÁÉÍÓÚÜÑñÑáéíóúüÜ\s'-]+"
                                           title="First name must contain at least one capital letter and can include letters, spaces, or special characters only"
                                           required>
                                    <div class="invalid-feedback">
                                        First name must contain at least one capital letter and can include letters, spaces, or special characters only
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" 
                                           pattern="(?=.*[A-ZÁÉÍÓÚÜÑ])[A-Za-zÁÉÍÓÚÜÑñÑáéíóúüÜ\s'-]+"
                                           title="Last name must contain at least one capital letter and can include letters, spaces, or special characters only"
                                           required>
                                    <div class="invalid-feedback">
                                        Last name must contain at least one capital letter and can include letters, spaces, or special characters only
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           pattern="^[a-zA-Z0-9._%+-]+@ccc\.edu\.ph$"
                                           title="Email must end with @ccc.edu.ph (e.g., username@ccc.edu.ph)"
                                           required>
                                    <div class="invalid-feedback">
                                        Email must end with @ccc.edu.ph (e.g., username@ccc.edu.ph)
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="password" name="password" required
                                               minlength="8" 
                                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                                               title="Password must be at least 8 characters long with uppercase, lowercase, number, and special character"
                                               autocomplete="new-password">
                                        <span class="password-toggle" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        <div class="invalid-feedback">
                                            Password must be at least 8 characters long with uppercase, lowercase, number, and special character
                                        </div>
                                        <div class="mt-2">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" id="password-strength" role="progressbar" style="width: 0%;"></div>
                                            </div>
                                            <small class="form-text" id="password-strength-text">Enter a password to see strength</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required>
                                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        <div class="invalid-feedback">
                                            Passwords do not match
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 student-fields">
                                    <label for="student_id" class="form-label">Student ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_id" name="student_id" 
                                           value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                                           pattern="\d{4}-\d{5}"
                                           title="Student ID must be in format: YYYY-##### (e.g., 2022-10873)"
                                           placeholder="2022-10873"
                                           maxlength="10">
                                    <div class="invalid-feedback">
                                        Student ID must be in format: YYYY-##### (e.g., 2022-10873)
                                    </div>
                                </div>
                                
                                <div class="col-md-6 student-fields">
                                    <label for="course" class="form-label">Course</label>
                                    <input type="text" class="form-control" id="course" name="course" 
                                           value="<?php echo htmlspecialchars($_POST['course'] ?? ''); ?>">
                                    <div class="invalid-feedback">
                                        Please enter a valid course
                                    </div>
                                </div>
                                
                                <div class="col-md-6 student-fields">
                                    <label for="classification" class="form-label">Classification</label>
                                    <select class="form-select" id="classification" name="classification" required>
                                        <option value="regular" <?php echo (!isset($_POST['classification']) || $_POST['classification'] === 'regular') ? 'selected' : ''; ?>>Regular</option>
                                        <option value="irregular" <?php echo (isset($_POST['classification']) && $_POST['classification'] === 'irregular') ? 'selected' : ''; ?>>Irregular</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a classification
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role_id" name="role_id" required>
                                        <option value="">Select Role</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['role_id']; ?>" data-role-name="<?php echo htmlspecialchars($role['role_name']); ?>"
                                                <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a role
                                    </div>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-user-plus me-2"></i> Add User
                                    </button>
                                    <a href="homepage.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-arrow-left me-2"></i> Back to Users
                                    </a>
                                </div>
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
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    // Check passwords match
                    const password = document.getElementById('password');
                    const confirmPassword = document.getElementById('confirm_password');
                    const emailInput = document.getElementById('email');
                    const firstNameInput = document.getElementById('firstname');
                    const lastNameInput = document.getElementById('lastname');
                    
                    // Name validation - require capital letters
                    function validateNameField(input, fieldName) {
                        if (input.value.length > 0 && !/(?=.*[A-ZÁÉÍÓÚÜÑ])/.test(input.value)) {
                            input.setCustomValidity(fieldName + ' must contain at least one capital letter (e.g., "John" not "john")');
                        } else {
                            input.setCustomValidity('');
                        }
                    }
                    
                    validateNameField(firstNameInput, 'First name');
                    validateNameField(lastNameInput, 'Last name');
                    
                    // Email validation - block Gmail and only allow @ccc.edu.ph
                    if (emailInput.value.toLowerCase().includes('@gmail.com')) {
                        emailInput.setCustomValidity('Gmail addresses are not accepted. Please use your @ccc.edu.ph school email.');
                    } else if (!/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i.test(emailInput.value)) {
                        emailInput.setCustomValidity('Only @ccc.edu.ph email addresses are accepted.');
                    } else {
                        emailInput.setCustomValidity('');
                    }
                    
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            let feedback = [];
            
            // Length checks
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (password.length >= 16) score++;
            
            // Character variety
            if (/[a-z]/.test(password)) {
                score++;
                feedback.push('lowercase');
            }
            if (/[A-Z]/.test(password)) {
                score++;
                feedback.push('uppercase');
            }
            if (/[0-9]/.test(password)) {
                score++;
                feedback.push('numbers');
            }
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                score++;
                feedback.push('special characters');
            }
            
            // Calculate percentage
            let percentage = Math.min((score / 8) * 100, 100);
            
            // Determine strength level and color
            let strength, color, text;
            if (score < 3) {
                strength = 'weak';
                color = 'danger';
                text = 'Weak password - add more character types';
            } else if (score < 5) {
                strength = 'fair';
                color = 'warning';
                text = 'Fair password - could be stronger';
            } else if (score < 7) {
                strength = 'good';
                color = 'info';
                text = 'Good password';
            } else {
                strength = 'strong';
                color = 'success';
                text = 'Strong password!';
            }
            
            return {
                score: score,
                percentage: percentage,
                strength: strength,
                color: color,
                text: text,
                feedback: feedback
            };
        }
        
        // Real-time password strength checking
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('password-strength');
            const strengthText = document.getElementById('password-strength-text');
            
            if (password.length > 0) {
                const strength = checkPasswordStrength(password);
                strengthBar.style.width = strength.percentage + '%';
                strengthBar.className = 'progress-bar bg-' + strength.color;
                strengthText.textContent = strength.text;
                strengthText.className = 'form-text text-' + strength.color;
            } else {
                strengthBar.style.width = '0%';
                strengthBar.className = 'progress-bar';
                strengthText.textContent = 'Enter a password to see strength';
                strengthText.className = 'form-text';
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Real-time email validation
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('email');
            const firstNameInput = document.getElementById('firstname');
            const lastNameInput = document.getElementById('lastname');
            
            // Real-time name validation
            function validateName(input) {
                const name = input.value;
                input.setCustomValidity('');
                
                if (name.length > 0 && !/(?=.*[A-ZÁÉÍÓÚÜÑ])/.test(name)) {
                    input.setCustomValidity('Name must contain at least one capital letter (e.g., "John" not "john")');
                }
            }
            
            if (firstNameInput) {
                firstNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }
            
            if (lastNameInput) {
                lastNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }
            
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value;
                    
                    // Clear previous validation
                    this.setCustomValidity('');
                    
                    // Check if email contains Gmail
                    if (email.toLowerCase().includes('@gmail.com')) {
                        this.setCustomValidity('Gmail addresses are not accepted. Please use your @ccc.edu.ph school email.');
                    } 
                    // Check if email matches @ccc.edu.ph pattern
                    else if (email.length > 0 && !/^[A-Za-z0-9._%+-]+@ccc\.edu\.ph$/i.test(email)) {
                        this.setCustomValidity('Only @ccc.edu.ph email addresses are accepted.');
                    }
                });
            }

            const roleSelect = document.getElementById('role_id');
            const studentFields = document.querySelectorAll('.student-fields');

            function updateFieldVisibility() {
                if (!roleSelect) return;
                const opt = roleSelect.selectedOptions && roleSelect.selectedOptions[0];
                const roleName = opt ? (opt.dataset.roleName || '').toLowerCase() : '';
                // Treat specific roles as student roles
                const isStudent = roleName.includes('student') || 
                                roleName === 'bscs' || 
                                roleName === 'bsit';

                studentFields.forEach(function (el) {
                    // show for student, hide otherwise
                    el.style.display = isStudent ? '' : 'none';
                    // toggle required on inputs inside
                    el.querySelectorAll('input, select, textarea').forEach(function (inp) {
                        if (isStudent) inp.setAttribute('required', 'required');
                        else inp.removeAttribute('required');
                    });
                });
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', updateFieldVisibility);
                updateFieldVisibility();
            }

            // Auto-format student ID as YYYY-#####
            const studentIdInput = document.getElementById('student_id');
            if (studentIdInput) {
                studentIdInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove all non-digits
                    
                    // Add hyphen after 4 digits when there are more than 4 digits
                    if (value.length > 4) {
                        value = value.slice(0, 4) + '-' + value.slice(4, 9);
                    }
                    
                    e.target.value = value;
                    
                    // Set cursor position to end of input
                    setTimeout(() => {
                        e.target.setSelectionRange(e.target.value.length, e.target.value.length);
                    }, 0);
                });

                // Also handle paste events
                studentIdInput.addEventListener('paste', function(e) {
                    setTimeout(() => {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length > 4) {
                            value = value.slice(0, 4) + '-' + value.slice(4, 9);
                        }
                        e.target.value = value;
                    }, 10);
                });
            }
        });
    </script>
</body>
</html>