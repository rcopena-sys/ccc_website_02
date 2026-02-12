<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$success = false;

// Fetch user data with role name and e-signature (excluding contact if not available)
$stmt = $conn->prepare("SELECT s.id, s.firstname, s.lastname, s.email, s.password, s.role_id, s.profile_image, s.esignature, r.role_name 
                      FROM signin_db s 
                      LEFT JOIN roles r ON s.role_id = r.role_id 
                      WHERE s.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log the start of form processing
    error_log('=== FORM SUBMISSION START ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));
    error_log('User ID: ' . $user_id);
    
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact'] ?? ''); // Make contact optional
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Handle e-signature upload
    $esignature_filename = $user['esignature'] ?? '';
    
    // Debug: Check if file was uploaded
    error_log('Complete FILES array: ' . print_r($_FILES, true));
    error_log('POST data: ' . print_r($_POST, true));
    
    if (isset($_FILES['esignature'])) {
        error_log('E-signature upload detected: ' . print_r($_FILES['esignature'], true));
        
        if ($_FILES['esignature']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_type = $_FILES['esignature']['type'];
            $file_size = $_FILES['esignature']['size'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            error_log('File type: ' . $file_type . ', Size: ' . $file_size);
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                // Create e-signatures directory if it doesn't exist
                $upload_dir = 'uploads/esignatures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                    error_log('Created upload directory: ' . $upload_dir);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['esignature']['name'], PATHINFO_EXTENSION);
                $esignature_filename = 'esign_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $esignature_filename;
                
                error_log('Generated filename: ' . $esignature_filename);
                error_log('Upload path: ' . $upload_path);
                
                // Delete old signature if exists
                if (!empty($user['esignature']) && file_exists($upload_dir . $user['esignature'])) {
                    unlink($upload_dir . $user['esignature']);
                    error_log('Deleted old signature: ' . $user['esignature']);
                }
                
                // Upload new signature
                if (move_uploaded_file($_FILES['esignature']['tmp_name'], $upload_path)) {
                    error_log('Signature uploaded successfully: ' . $esignature_filename);
                    $signature_upload_success = true;
                    // Don't set message here, let database update set it
                } else {
                    error_log('Failed to upload signature file');
                    $message = 'Error uploading signature. Please try again.';
                }
            } else {
                error_log('Invalid file type or size');
                $message = 'Invalid signature file. Please upload a PNG, JPG, or GIF file under 2MB.';
            }
        } else {
            error_log('Upload error code: ' . $_FILES['esignature']['error']);
        }
    } else {
        error_log('No e-signature file uploaded');
    }
    
    // Basic validation - but don't overwrite signature success messages
    if (empty($firstname) || empty($lastname) || empty($email)) {
        // Only set validation error if there's no signature success message
        if (strpos($message, 'signature') === false) {
            $message = 'First name, last name and email are required';
        }
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM signin_db WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            // Only set email error if there's no signature success message
            if (strpos($message, 'signature') === false) {
                $message = 'Email is already taken';
            }
        } else {
            // Debug: Log update attempt
            error_log('Profile update attempt - User ID: ' . $user_id);
            error_log('E-signature filename: ' . $esignature_filename);
            
            // Handle password change first - if there are any password errors, set message and skip database update
            $password_update_sql = "";
            $password_params = [];
            $password_types = "";
            
            if (!empty($current_password) && !empty($new_password)) {
                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 8) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $password_update_sql = ", password = ?";
                            $password_params[] = $hashed_password;
                            $password_types = "s";
                        } else {
                            $message = 'New password must be at least 8 characters long';
                        }
                    } else {
                        $message = 'New passwords do not match';
                    }
                } else {
                    $message = 'Current password is incorrect';
                }
            }
            
            // Only proceed with database update if there are no errors
            if (empty($message)) {
                // Build the SQL statement
                $update_sql = "UPDATE signin_db SET firstname = ?, lastname = ?, email = ?, esignature = ?";
                $params = [$firstname, $lastname, $email, $esignature_filename];
                $types = "ssss";
                
                // Add password if needed
                if (!empty($password_update_sql)) {
                    $update_sql .= $password_update_sql;
                    $params = array_merge($params, $password_params);
                    $types .= $password_types;
                }
                
                error_log('Final SQL: ' . $update_sql);
                error_log('Final params: ' . print_r($params, true));
                
                $update_sql .= " WHERE id = ?";
                $params[] = $user_id;
                $types .= "i";
                
                $stmt = $conn->prepare($update_sql);
                if ($stmt) {
                    error_log('Statement prepared successfully');
                    error_log('Binding parameters with types: ' . $types);
                    error_log('Actual SQL: ' . $update_sql);
                    error_log('Actual params: ' . print_r($params, true));
                    error_log('Number of params: ' . count($params));
                    
                    // Create references for bind_param
                    $bind_params = [$types];
                    foreach ($params as $key => $value) {
                        $bind_params[] = &$params[$key];
                    }
                    error_log('Bind params array: ' . print_r($bind_params, true));
                    
                    call_user_func_array([$stmt, 'bind_param'], $bind_params);
                    
                    error_log('Parameters bound successfully');
                    
                    if ($stmt->execute()) {
                        error_log('Profile update successful');
                        // Set appropriate message based on whether signature was uploaded
                        if (isset($signature_upload_success) && $signature_upload_success) {
                            $message = 'Profile updated successfully with new signature!';
                        } else {
                            $message = 'Profile updated successfully';
                        }
                        $success = true;
                        
                        // Refresh user data
                        $refreshStmt = $conn->prepare("SELECT * FROM signin_db WHERE id = ?");
                        $refreshStmt->bind_param("i", $user_id);
                        $refreshStmt->execute();
                        $result = $refreshStmt->get_result();
                        $user = $result->fetch_assoc();
                        $refreshStmt->close();
                        
                        // TEMPORARY DEBUG: Show what's actually in the database
                        echo "<div style='background: #d1ecf1; padding: 10px; margin: 10px; border: 1px solid #bee5eb; border-radius: 4px;'>";
                        echo "<h5 style='color: #0c5460; margin: 0 0 10px 0;'>DATABASE VERIFICATION:</h5>";
                        echo "<p style='margin: 5px 0;'><strong>User ID:</strong> " . $user_id . "</p>";
                        echo "<p style='margin: 5px 0;'><strong>E-signature in database:</strong> '" . ($user['esignature'] ?: 'NULL') . "'</p>";
                        echo "<p style='margin: 5px 0;'><strong>Expected filename:</strong> '" . $esignature_filename . "'</p>";
                        echo "<p style='margin: 5px 0;'><strong>File exists:</strong> " . (file_exists('uploads/esignatures/' . $user['esignature']) ? 'YES' : 'NO') . "</p>";
                        echo "</div>";
                    } else {
                        error_log('Profile update error: ' . $stmt->error);
                        error_log('SQL that failed: ' . $update_sql);
                        error_log('Parameters used: ' . print_r($params, true));
                        // Only overwrite message if it's not a signature success message
                        if (strpos($message, 'signature') === false) {
                            $message = 'Error updating profile: ' . $stmt->error;
                        }
                    }
                    $stmt->close();
                } else {
                    error_log('Failed to prepare update statement: ' . $conn->error);
                    if (strpos($message, 'signature') === false) {
                        $message = 'Database error. Please try again.';
                    }
                }
            } else {
                // There was a validation error, message is already set
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #1e3c72 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 40px;
            background: linear-gradient(145deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(30, 60, 114, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e3c72, #2a5298, #4a69bd, #1e3c72);
            background-size: 300% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid rgba(30, 60, 114, 0.1);
            position: relative;
        }
        
        .profile-picture {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 6px solid #1e3c72;
            box-shadow: 0 8px 32px rgba(30, 60, 114, 0.3), 0 0 0 3px rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(30, 60, 114, 0.4), 0 0 0 3px rgba(255, 255, 255, 0.3);
        }
        
        .profile-header h2 {
            color: #1e3c72;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header p {
            color: #4a69bd;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        .form-label {
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 8px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            border: 2px solid rgba(30, 60, 114, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.15);
            background: #ffffff;
            transform: translateY(-1px);
        }
        
        .btn-update {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            padding: 14px 32px;
            font-weight: 600;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.4);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            padding: 14px 32px;
            font-weight: 600;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .password-section, .esign-section {
            background: linear-gradient(145deg, #f8f9ff 0%, #ffffff 100%);
            padding: 30px;
            border-radius: 16px;
            margin-top: 25px;
            border: 2px solid rgba(30, 60, 114, 0.1);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.1);
        }
        
        .password-section h5, .esign-section h5 {
            color: #1e3c72;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .esign-preview {
            border: 2px dashed rgba(30, 60, 114, 0.3);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.5);
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .esign-preview img {
            max-width: 100%;
            max-height: 100px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .esign-preview.has-signature {
            border-color: rgba(30, 60, 114, 0.5);
            background: rgba(30, 60, 114, 0.05);
        }
        
        .input-group {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .input-group .form-control {
            border-right: none;
        }
        
        .input-group .btn {
            border: 2px solid rgba(30, 60, 114, 0.2);
            border-left: none;
            background: rgba(30, 60, 114, 0.1);
            color: #1e3c72;
            transition: all 0.3s ease;
        }
        
        .input-group .btn:hover {
            background: rgba(30, 60, 114, 0.2);
            color: #1e3c72;
        }
        
        .row {
            margin-bottom: 20px;
        }
        
        .text-muted {
            color: #6c757d !important;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <img src="dci.png.png" 
                     alt="Profile Picture" class="profile-picture">
                <h2><?= htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?></h2>
                <p class="text-muted"><?= htmlspecialchars($user['role_name'] ?? 'User') ?></p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" 
                               value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" 
                               value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <!-- Contact field commented out until column is added to database
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label for="contact" class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" id="contact" name="contact" 
                               value="<?= htmlspecialchars($user['contact'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Account Type</label>
                        <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($user['role_name'] ?? 'User')) ?>" readonly>
                    </div>
                </div>
-->
                
                <div class="password-section">
                    <h5 class="mb-4">Change Password</h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- E-Signature Section -->
                <div class="esign-section">
                    <h5><i class="fas fa-signature me-2"></i>Electronic Signature</h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="esignature" class="form-label">Upload E-Signature</label>
                            <input type="file" class="form-control" id="esignature" name="esignature" accept="image/*">
                            <small class="text-muted">Upload your electronic signature (PNG, JPG, or GIF format)</small>
                            
                            <div class="esign-preview <?= !empty($user['esignature']) ? 'has-signature' : '' ?>" id="esignPreview">
                                <?php if (!empty($user['esignature'])): ?>
                                    <img src="uploads/esignatures/<?= htmlspecialchars($user['esignature']) ?>" alt="E-Signature">
                                <?php else: ?>
                                    <div>
                                        <i class="fas fa-signature fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No signature uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="dashboard2.php" class="btn btn-secondary me-md-2">Back to Dashboard</a>
                    <button type="submit" class="btn btn-primary btn-update">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // E-signature preview
        document.getElementById('esignature').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('esignPreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="E-Signature Preview">';
                    preview.classList.add('has-signature');
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '<div><i class="fas fa-signature fa-3x text-muted mb-2"></i><p class="text-muted mb-0">No signature uploaded</p></div>';
                preview.classList.remove('has-signature');
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirm password do not match');
                return false;
            }
            
            if (newPassword.length > 0 && newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>