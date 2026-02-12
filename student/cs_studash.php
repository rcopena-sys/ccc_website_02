<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 5) {
    header("Location: ../index.php");
    exit();
}
require_once '../db_connect.php';

// Initialize student data
$student = [
    'firstname' => '',
    'lastname' => '',
    'student_id' => '',
    'course' => 'BSCS',
    'academic_year' => '',
    'email' => '',
    'profile_image' => 'css.png' // Default avatar if none is set
];

// Always use css.png as the profile image
$student['profile_image'] = 'css.png';

// Fetch other student info from the database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_id, academic_year, course, email FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student = array_merge($student, $row);
    // Store student_id in session if not set
    if (!isset($_SESSION['student_id']) && !empty($row['student_id'])) {
        $_SESSION['student_id'] = $row['student_id'];
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BSCS Student Dashboard - City College of Calamba</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-image: url('schol.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            padding: 30px 10px 10px 10px;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            transition: transform 0.3s ease-in-out;
        }
        
        /* Mobile sidebar toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 1.2rem;
        }
        
        /* Mobile backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        @media (max-width: 992px) {
            .sidebar-backdrop.active {
                display: block;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .sidebar-toggle {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
            }
            .main-content.active {
                margin-left: 0;
            }
        }
        .profile-image {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: #fff;
            margin-bottom: 10px;
        }
        
        @media (max-width: 576px) {
            .profile-image {
                width: 90px;
                height: 90px;
            }
            .sidebar-name {
                font-size: 1rem !important;
            }
            .sidebar-label, .sidebar-value {
                font-size: 0.85rem !important;
            }
        }
        .sidebar-name {
            font-family: 'Segoe UI Semibold', 'Arial', sans-serif;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .sidebar-label {
            font-size: 0.98rem;
            color: #e0e0e0;
            font-weight: 500;
        }
        .sidebar-value {
            font-size: 1rem;
            color: #fff;
            font-weight: 600;
        }
        .btn-danger {
            background: #dc3545 !important;
            border: none !important;
            color: #fff !important;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .btn-danger:hover, .btn-danger:focus {
            background: #b02a37 !important;
            color: #fff !important;
        }
        .btn-outline-light {
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar-clock {
            font-size: 1.1rem;
            color: #fff;
            background: rgba(0,0,0,0.09);
            border-radius: 8px;
            padding: 4px 12px;
            display: inline-block;
            margin-top: 10px;
            font-family: 'Consolas', 'Courier New', monospace;
            letter-spacing: 1px;
        }
        .sidebar-link {
            text-decoration: none !important;
            font-size: 1.1rem;
        }
        .sidebar {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-top: 60px;
            }
        }
        .container {
            max-width: 1200px;
        }
        .content-wrapper {
            background-color: rgba(255, 255, 255, 0.95);
            min-height: 40vh;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 20px 15px;
        }
        
        @media (min-width: 768px) {
            .content-wrapper {
                padding: 30px 20px;
            }
        }
        .year-card {
            transition: transform 0.2s;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 16px;
            margin-bottom: 15px;
        }
        .year-card:hover {
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 6px 32px 0 rgba(31,38,135,0.18);
        }
        .sidebar .btn {
            font-weight: 500;
        }
        .sidebar .btn-danger {
            margin-top: 10px;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #4f8cff 0%, #1cefff 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }
        .btn-gradient:hover, .btn-gradient:focus {
            background: linear-gradient(90deg, #1cefff 0%, #4f8cff 100%);
            color: #fff;
            transform: scale(1.05);
            box-shadow: 0 4px 18px rgba(31,38,135,0.16);
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Mobile Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
   <!-- Sidebar -->
<div class="sidebar d-flex flex-column align-items-center">
    <!-- Profile and Info -->
    <div class="w-100 text-center">
        <div id="profile-section" class="mb-4">
            <img src="css.png" 
                 alt="Profile" 
                 class="profile-image mb-2" 
                 id="profileImage" 
                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                 data-bs-toggle="modal" 
                 data-bs-target="#editProfileModal">
            <h4 class="fw-bold mt-2 mb-1 sidebar-name">
                <?php
                    if (!empty($student['lastname']) && !empty($student['firstname'])) {
                        echo htmlspecialchars($student['lastname'] . ', ' . $student['firstname']);
                    } else {
                        echo "Student Name";
                    }
                ?>
            </h4>
            <div>
                <span class="sidebar-label">student id:</span>
                <span class="sidebar-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
            </div>
            <div id="sidebar-clock" class="sidebar-clock mt-3"></div>
        </div>
        <?php
        // Sidebar notification bell: show unread count if notifications table exists
        $sidebar_unread = 0;
        if (isset($_SESSION['student_id'])) {
            try {
                $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    $tmpStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                    if ($tmpStmt) {
                        $tmpStmt->bind_param("s", $_SESSION['student_id']);
                        $tmpStmt->execute();
                        $tmpRes = $tmpStmt->get_result();
                        if ($tmpRes) {
                            $sidebar_unread = (int)($tmpRes->fetch_assoc()['count'] ?? 0);
                        }
                        $tmpStmt->close();
                    }
                }
            } catch (Exception $e) {
                error_log('Notification badge check failed: ' . $e->getMessage());
                $sidebar_unread = 0;
            }
        }
        ?>
        <a href="notification.php" class="btn btn-light d-inline-flex align-items-center gap-2 mt-2 w-100 justify-content-center" id="sidebarNotificationButton" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($sidebar_unread > 0): ?>
                <span class="badge bg-danger"><?php echo $sidebar_unread; ?></span>
            <?php endif; ?>
        </a>
 <a href="aboutus_cs.php" class="sidebar-link active">
        <i class="fas fa-info-circle mr-2"></i> About Us
    </a>
        <div class="mt-4">
            <a href="calendar_events.php" class="btn btn-gradient w-100" id="calendarButton">
                <i class="bi bi-calendar3 me-2"></i>Calendar Events
            </a>
            <script>
                document.getElementById('calendarButton').addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'calendar_events.php';
                });
            </script>
        </div>

          <!-- Dashboard, Feedback and Logout Buttons -->
          <div class="d-grid gap-3 w-100 mt-4 px-2">
    <a href="feedback.php" class="btn btn-outline-light fw-semibold sidebar-link">Feedback</a>
    <a href="../logout.php"  class="btn btn-danger fw-bold sidebar-link mt-2">Logout</a>
</div>
    </div>
</div>
    <!-- Profile Update Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="profileModalLabel"><img src="css.png" me-2>Update Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Separate form for profile picture upload -->
                <form id="profileImageForm" action="update_profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                    <?php $csrfToken = bin2hex(random_bytes(32)); $_SESSION['csrf_token'] = $csrfToken; ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="file" id="profileImageInput" name="profile_image" accept="image/*">
                </form>

                <!-- Main profile form -->
                <form id="profileForm" img src="css.png" method="POST">
                    <?php $csrfToken = bin2hex(random_bytes(32)); $_SESSION['csrf_token'] = $csrfToken; ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="modal-body">
                        <!-- Alert Messages -->
                        <div id="alertContainer" class="mb-3" style="display: none;">
                            <div class="alert" id="alertMessage" role="alert"></div>
                        </div>
                 
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="firstname" name="firstname" 
                                           value="<?php echo htmlspecialchars($student['firstname']); ?>" readonly>
                                    <label for="firstname">First Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lastname" name="lastname" 
                                           value="<?php echo htmlspecialchars($student['lastname']); ?>" readonly>
                                    <label for="lastname">Last Name</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" readonly>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveChangesBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="content-wrapper mt-4">
                <h1 class="text-center mb-5">Student Dashboard</h1>
                <div class="row g-4 justify-content-center">
                    <?php
                    $years = [
                        ['year' => '1st Year', 'color' => 'primary', 'icon' => 'bi-1-circle', 'prospectus' => 'cs1st.php'],
                        ['year' => '2nd Year', 'color' => 'primary', 'icon' => 'bi-2-circle', 'prospectus' => 'cs2nd.php'],
                        ['year' => '3rd Year', 'color' => 'primary', 'icon' => 'bi-3-circle', 'prospectus' => 'cs3rd.php'],
                        ['year' => '4th Year', 'color' => 'primary', 'icon' => 'bi-4-circle', 'prospectus' => 'cs4th.php']
                    ];
                    foreach ($years as $yearData) {
                        echo '<div class="col-12 col-sm-6 col-lg-3">
                            <div class="card year-card h-100 bg-' . $yearData['color'] . ' text-white text-center shadow">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                    <i class="bi ' . $yearData['icon'] . ' display-3 mb-3"></i>
                                    <h3 class="card-title">' . $yearData['year'] . '</h3>
                                    <p class="card-text">Click to view your prospectus</p>
                                    <a href="' . $yearData['prospectus'] . '" class="btn btn-lg btn-gradient mt-2 w-75">
                                        <i class="bi bi-arrow-right-circle"></i> View Prospectus
                                    </a>
                                </div>
                            </div>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Bootstrap Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            
            sidebar.classList.toggle('active');
            backdrop.classList.toggle('active');
        });

        // DOM Elements
        const profileImage = document.getElementById('profileImage');
        const profilePreview = document.getElementById('profilePreview');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImageForm = document.getElementById('profileImageForm');
        const alertContainer = document.getElementById('alertContainer');
        const alertMessage = document.getElementById('alertMessage');
        const saveChangesBtn = document.getElementById('saveChangesBtn');
        const submitSpinner = document.getElementById('submitSpinner');
        const profileForm = document.getElementById('profileForm');

        // Show alert message
        function showAlert(message, type = 'danger') {
            alertMessage.textContent = message;
            alertMessage.className = `alert alert-${type} alert-dismissible fade show`;
            alertContainer.style.display = 'block';
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    alertContainer.style.display = 'none';
                }, 5000);
            }
        }

        // Handle profile image upload
        if (profileImageInput && profilePreview && profileImageForm) {
            profileImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showAlert('Please select a valid image file (JPEG, PNG, GIF)', 'danger');
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    showAlert('Image size should be less than 2MB', 'danger');
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                    
                    // Submit the form
                    const formData = new FormData(profileImageForm);
                    
                    // Show loading state
                    const submitBtn = document.querySelector('#saveChangesBtn');
                    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Uploading...';
                    }
                    
                    // Submit the form via AJAX
                    fetch('update_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Profile picture updated successfully!', 'success');
                            // Update all profile images on the page
                            const profileImages = document.querySelectorAll('.profile-image');
                            if (data.profile_image) {
                                const timestamp = new Date().getTime();
                                profileImages.forEach(img => {
                                    img.src = data.profile_image + '?t=' + timestamp;
                                });
                                // Also update the preview in the modal
                                if (profilePreview) {
                                    profilePreview.src = data.profile_image + '?t=' + timestamp;
                                }
                            }
                        } else {
                            showAlert(data.message || 'Failed to update profile picture', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred while updating your profile picture', 'danger');
                    })
                    .finally(() => {
                        // Restore button state
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnText;
                        }
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Update clock function
            function updateClock() {
                const now = new Date();
                const hours = now.getHours();
                const minutes = now.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const hours12 = hours % 12 || 12;
                const timeString = `${hours12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
                const clockElement = document.getElementById('sidebar-clock');
                if (clockElement) {
                    clockElement.textContent = timeString;
                }
            }

            // Set up clock
            setInterval(updateClock, 1000);
            updateClock();
            
            // Remove duplicate event listener since we already have one above
            // The sidebar toggle is already handled in the script above
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || 
                                    (sidebarToggle && sidebarToggle.contains(event.target));
                
                if (!isClickInside && window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    document.getElementById('sidebarBackdrop').classList.remove('active');
                }
            });
            
            // Close sidebar when clicking on backdrop
            document.getElementById('sidebarBackdrop').addEventListener('click', function() {
                sidebar.classList.remove('active');
                this.classList.remove('active');
            });
            
            // Close sidebar when a navigation link is clicked on mobile
            const navLinks = document.querySelectorAll('.sidebar a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 992) {
                        sidebar.classList.remove('active');
                        document.getElementById('sidebarBackdrop').classList.remove('active');
                    }
                });
            });
        });

// Handle profile image preview
if (profileImageInput && profilePreview) {
    profileImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                showAlert('Only JPG, PNG, and GIF images are allowed.');
                profileImageInput.value = ''; // Clear the input
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showAlert('Image size should be less than 2MB.');
                profileImageInput.value = ''; // Clear the input
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Create a temporary image to verify dimensions
                const img = new Image();
                img.onload = function() {
                    // Update both the preview in the modal and the sidebar image
                    const previewImage = document.getElementById('profilePreview');
                    const sidebarImage = document.getElementById('profileImage');
                    
                    if (previewImage) {
                        previewImage.src = e.target.result;
                        previewImage.classList.add('fade');
                        setTimeout(() => previewImage.classList.remove('fade'), 300);
                    }
                    
                    if (sidebarImage) {
                        sidebarImage.src = e.target.result;
                        sidebarImage.classList.add('fade');
                        setTimeout(() => sidebarImage.classList.remove('fade'), 300);
                    }
                };
                img.onerror = function() {
                    showAlert('Invalid image file. Please try another.');
                    profileImageInput.value = ''; // Clear the input
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                showAlert('Error reading file. Please try again.');
                profileImageInput.value = ''; // Clear the input
            };
            reader.readAsDataURL(file);
        }
    });
}

// Form submission with AJAX
if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        saveChangesBtn.disabled = true;
        submitSpinner.classList.remove('d-none');
        
        const formData = new FormData(this);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update the profile image in the sidebar
                if (data.profile_image) {
                    const sidebarImg = document.getElementById('profileImage');
                    if (sidebarImg) {
                        sidebarImg.src = data.profile_image + '?t=' + new Date().getTime(); // Cache buster
                    }
                }
                
                // Show success message
                showAlert('Profile updated successfully!', 'success');
                
                // Close the modal after a short delay
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
                    if (modal) {
                        modal.hide();
                    }
                    // Reload the page to reflect changes
                    window.location.reload();
                }, 1500);
            } else {
                showAlert(data.message || 'Error updating profile. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while updating the profile. Please try again.');
        })
        .finally(() => {
            // Reset loading state
            saveChangesBtn.disabled = false;
            submitSpinner.classList.add('d-none');
        });
    });
}

// Edit Profile Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const editProfileForm = document.getElementById('editProfileForm');
    
    if (editProfileForm) {
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
        
        // Form submission handler
        editProfileForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            const currentPassword = document.getElementById('current_password')?.value || '';
            
            // Only validate if any password field is filled
            if (newPassword || confirmPassword || currentPassword) {
                if (newPassword && newPassword.length < 8) {
                    e.preventDefault();
                    showAlert('New password must be at least 8 characters long', 'danger');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showAlert('New password and confirm password do not match', 'danger');
                    return false;
                }
                
                if (!currentPassword) {
                    e.preventDefault();
                    showAlert('Please enter your current password', 'danger');
                    return false;
                }
            }
            
            // If validation passes, submit the form via AJAX
            if (!e.defaultPrevented) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                
                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Profile updated successfully!', 'success');
                        // Update the displayed name if it was changed
                        if (data.firstname && data.lastname) {
                            const nameElement = document.querySelector('.sidebar-name');
                            if (nameElement) {
                                nameElement.textContent = `${data.lastname}, ${data.firstname}`;
                            }
                        }
                        // Close the modal after a short delay
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                            if (modal) modal.hide();
                            
                            // Reset form and hide any alerts
                            editProfileForm.reset();
                            const alerts = document.querySelectorAll('.alert');
                            alerts.forEach(alert => alert.remove());
                        }, 1500);
                    } else {
                        showAlert(data.message || 'Failed to update profile', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while updating your profile', 'danger');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            }
        });
    }
});

</script>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm" action="update_profile.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" 
                               value="<?php echo htmlspecialchars($student['firstname'] ?? ''); ?>"/>
                    </div>
                    <div class="mb-3">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" 
                               value="<?php echo htmlspecialchars($student['lastname'] ?? ''); ?>"/>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>"/>
                    </div>
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" 
                               value="<?php echo htmlspecialchars($student['student_id'] ?? ''); ?>" readonly/>
                    </div>
                    
                    <!-- Password Change Section -->
                    <div class="border-top pt-3 mt-3">
                        <h6 class="mb-3">Change Password</h6>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" />
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" />
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Password must be at least 8 characters long</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" />
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>