<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['studentnumber'])) {
    header("Location: index.php");
    exit();
}
require_once 'db_connect.php';

// Initialize student data
$student = [
    'firstname' => '',
    'lastname' => '',
    'studentnumber' => ''
];

// Fetch student info from the database
$studentnumber = $_SESSION['studentnumber'];
$stmt = $conn->prepare("SELECT firstname, lastname, student_number FROM signin_db WHERE student_number = ?");
$stmt->bind_param("s", $studentnumber);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student['firstname'] = $row['firstname'];
    $student['lastname'] = $row['lastname'];
    $student['studentnumber'] = $row['student_number'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"] rel="stylesheet">
    <link rel="stylesheet" href="[https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background-image: url('http://localhost/website/student/schol.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
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
            z-index: 10;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            background: #fff;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
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
            transition: transform 0.2s;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 16px;
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
        .sidebar {
    background-color: #0d6efd;
    color: #fff;
    min-height: 100vh;
    padding: 30px 10px 10px 10px;
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    z-index: 10;
    font-family: 'Segoe UI', 'Arial', sans-serif;
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

.sidebar-link {
    text-decoration: none !important;
    font-size: 1.1rem;
}
    </style>
</head>
<body>
   <!-- Sidebar -->
<div class="sidebar d-flex flex-column align-items-center">
    <!-- Profile and Info -->
    <div class="w-100 text-center">
        <div id="profile-section" class="mb-4">
            <form id="profileForm" action="upload_profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" name="profile_picture" id="profileInput" accept="image/*" onchange="document.getElementById('profileForm').submit();">
            </form>
            <img src="profile_pictures/default.jpg" alt="Student Profile" class="profile-image mb-2" id="profileImage" oncontextmenu="triggerImageUpload(event)">
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
                <span class="sidebar-label">Student Number:</span>
                <span class="sidebar-value"><?php echo htmlspecialchars($student['studentnumber']); ?></span>
            </div>
            <div id="sidebar-clock" class="sidebar-clock mt-3"></div>
        </div>

         <!-- Dashboard and Logout Buttons -->
         <div class="d-grid gap-3 w-100 mt-4 px-2">
   <a href="dashboardstu.php" class="btn btn-outline-light fw-semibold sidebar-link">Dashboard</a>
   <a href="index.php" class="btn btn-danger fw-bold sidebar-link mt-2">Logout</a>
</div>
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
                        ['year' => '1st Year', 'color' => 'primary', 'icon' => 'bi-1-circle', 'prospectus' => 'dcipros1st.php'],
                        ['year' => '2nd Year', 'color' => 'success', 'icon' => 'bi-2-circle', 'prospectus' => 'dcipros2nd.php'],
                        ['year' => '3rd Year', 'color' => 'warning', 'icon' => 'bi-3-circle', 'prospectus' => 'dcipros3rd.php'],
                        ['year' => '4th Year', 'color' => 'danger', 'icon' => 'bi-4-circle', 'prospectus' => 'dcipros4th.php']
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
    <script>
function updateClock() {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    const timeString = `${hours12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
    document.getElementById('sidebar-clock').textContent = timeString;
}

setInterval(updateClock, 1000);
updateClock();

function triggerImageUpload(event) {
    event.preventDefault(); // Prevent right-click menu
    document.getElementById('profileInput').click();
}
</script>
</body>
</html>