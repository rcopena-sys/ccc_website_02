<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: ../index.php");
    exit();
}
require_once '../db_connect.php';

// Initialize student data
$student = [
    'firstname' => '',
    'lastname' => '',
    'student_id' => '',
    'course' => 'BSIT',
    'academic_year' => '',
    'email' => '',
    'profile_image' => 'default-avatar.png' // Default avatar if none is set
];

// Fetch student info from the database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_id, academic_year, course, email, profile_image FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student = array_merge($student, $row); // Merge with defaults
    // Store student_id in session if not set
    if (!isset($_SESSION['student_id']) && !empty($row['student_id'])) {
        $_SESSION['student_id'] = $row['student_id'];
    }
}
$stmt->close();

// Get unread notifications count (defensive: handle missing table)
$unread_count = 0;
if (isset($_SESSION['student_id'])) {
    // Check whether the notifications table exists to avoid fatal errors
    $table_exists = false;
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check) {
        if ($check->num_rows > 0) {
            $table_exists = true;
        }
        $check->free();
    }

    if ($table_exists) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        if ($stmt) {
            $stmt->bind_param("s", $_SESSION['student_id']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $unread_count = isset($row['count']) ? (int)$row['count'] : 0;
            } else {
                error_log('Failed to execute notifications count statement: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log('Failed to prepare notifications count statement: ' . $conn->error);
        }
    } else {
        // Table missing — don't fatal; log and continue with zero
        error_log('Notifications table not found; unread_count defaulting to 0.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSIT Student Dashboard - City College of Calamba</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
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
            padding-top: 60px; /* Space for mobile header */
        }
        .sidebar {
            background-color: #0d6efd;
            color: white;
            min-height: 100vh;
            padding: 15px 10px 10px 10px;
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            transition: all 0.3s ease;
        }
        
        .sidebar.show {
            left: 0;
        }
        
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0d6efd;
            color: white;
            padding: 10px 15px;
            z-index: 1040;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 5px 10px;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        
        .overlay.show {
            display: block;
        }
        .sidebar .btn {
            width: 100%;
            margin: 5px 0;
        }
        .sidebar-clock {
            font-size: 1.1rem;
            color: #fff;
            background: rgba(0,0,0,0.09);
            padding: 8px 15px;
            border-radius: 5px;
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .sidebar-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        /* Notification bell styles */
        .notification-bell {
            position: relative;
            display: inline-block;
            color: #fff;
            font-size: 1.25rem;
            margin-left: 15px;
        }
        
        .notification-bell .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 0.6rem;
            padding: 0.25em 0.5em;
        }
        
        @media (max-width: 767.98px) {
            .notification-bell {
                margin-left: 0;
                margin-right: 10px;
            }
        }
        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background: #fff;
            margin-bottom: 10px;
        }
        
        @media (min-width: 768px) {
            .profile-image {
                width: 110px;
                height: 110px;
            }
            
            .sidebar {
                left: 0;
            }
            
            .main-content {
                margin-left: 250px;
            }
            
            .mobile-header {
                display: none !important;
            }
            
            .overlay {
                display: none !important;
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
            display: block;
            padding: 12px 15px;
            color: #fff;
            border-radius: 8px;
            margin: 5px 0;
            transition: background-color 0.2s;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .sidebar {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
            margin-top: 20px;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
                margin-top: 0;
            }
        }
        .container {
            max-width: 1200px;
        }
        .content-wrapper {
            background-color: rgba(255, 255, 255, 0.9);
            min-height: 40vh;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            padding: 30px 20px;
        }
        .year-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 16px;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .year-card .card-body {
            padding: 1.5rem;
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
            padding: 12px 20px;
            font-size: 1rem;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 10px;
        }
        
        @media (min-width: 768px) {
            .btn-gradient {
                width: auto;
                margin-bottom: 0;
            }
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
    <!-- Mobile Header -->
    <div class="mobile-header d-flex align-items-center justify-content-between" id="mobileHeader">
        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="mb-0">Student Dashboard</h5>
        <div class="d-flex align-items-center">
            <a href="notification.php" class="position-relative me-2 notification-bell" style="color: white; font-size: 1.25rem;">
                <i class="bi bi-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.25em 0.5em;">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column align-items-center" id="sidebar">
    <!-- Profile and Info -->
    <div class="w-100 text-center">
        <div id="profile-section" class="mb-4">
            <img src="its.png" 
                 alt="Profile" 
                 class="mb-2" 
                 id="profileImage" 
                 style="width: 110px; height: 110px; object-fit: cover; border-radius: 50%; cursor: pointer; border: 2px solid #fff;"
                 data-bs-toggle="modal" 
                 data-bs-target="#profileModal">
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
        <div class="mt-4">
            <a href="calenci.php" class="btn btn-gradient w-100" id="calendarButton">
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
        
    <a href="about_us.php" class="sidebar-link active">
        <i class="fas fa-info-circle mr-2"></i> About Us
    </a>
    <a href="notification.php" class="sidebar-link position-relative">
        <i class="bi bi-bell"></i> Notifications
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-50 end-0 translate-middle-y badge rounded-pill bg-danger" style="font-size: 0.6rem; margin-right: 15px;">
                <?php echo $unread_count; ?>
            </span>
        <?php endif; ?>
    </a>
    <a href="feedback.php" class="btn btn-outline-light fw-semibold sidebar-link">Feedback</a>
    <a href="../logout.php"  class="btn btn-danger fw-bold sidebar-link mt-2">Logout</a>
</div>
    </div>
</div>
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="profileForm" action="update_profile.php" method="POST">
                    <?php $csrfToken = bin2hex(random_bytes(32)); $_SESSION['csrf_token'] = $csrfToken; ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

                     
                   
                            
             
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="content-wrapper mt-4">
                <h1 class="text-center mb-5 d-none d-md-block">Student Dashboard</h1>
                <div class="row g-4 justify-content-center">
                    <?php
                    $years = [
                        ['year' => '1st Year', 'color' => 'primary', 'icon' => 'bi-1-circle', 'prospectus' => 'dcipros1st.php'],
                        ['year' => '2nd Year', 'color' => 'primary', 'icon' => 'bi-2-circle', 'prospectus' => 'dcipros2nd.php'],
                        ['year' => '3rd Year', 'color' => 'primary', 'icon' => 'bi-3-circle', 'prospectus' => 'dcipros3rd.php'],
                        ['year' => '4th Year', 'color' => 'primary', 'icon' => 'bi-4-circle', 'prospectus' => 'dcipros4th.php']
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

    <!-- Bootstrap JS and Bootstrap Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            });
            
            // Close menu when clicking on a link
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            });
            
            // Handle window resize
            function handleResize() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            }
            
            window.addEventListener('resize', handleResize);
        });
        
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

// Profile update functionality
const profileImage = document.getElementById('profileImage');
const profilePreview = document.getElementById('profilePreview');
const profileImageInput = document.getElementById('profileImageInput');
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
</script>
</body>
</html>