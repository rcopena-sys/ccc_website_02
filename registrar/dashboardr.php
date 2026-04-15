<?php
session_start();
// Check if user is logged in and is a registrar (role_id = 2) or admin (role_id = 3)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// --- Enrollment period save handler (AJAX from modal) ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'save_enrollment_period'
) {
    header('Content-Type: application/json');

    $start = trim($_POST['start_datetime'] ?? '');
    $end   = trim($_POST['end_datetime'] ?? '');

    if ($start === '' || $end === '') {
        echo json_encode(['success' => false, 'message' => 'Both start and end date/time are required.']);
        exit;
    }

    $startTs = strtotime($start);
    $endTs   = strtotime($end);
    if ($startTs === false || $endTs === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid date/time format.']);
        exit;
    }
    if ($endTs <= $startTs) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
        exit;
    }

    // Ensure table exists
    $createSql = "CREATE TABLE IF NOT EXISTS enrollment_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(50) NOT NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_page (page)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!$conn->query($createSql)) {
        echo json_encode(['success' => false, 'message' => 'DB error creating table: ' . $conn->error]);
        exit;
    }

    // Upsert enrollment period specifically for the admin evaluation page (stueval.php)
    $pageKey = 'stueval';
    $startDb = date('Y-m-d H:i:s', $startTs);
    $endDb   = date('Y-m-d H:i:s', $endTs);
    $createdBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $sql = "INSERT INTO enrollment_periods (page, start_datetime, end_datetime, created_by)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              start_datetime = VALUES(start_datetime),
              end_datetime   = VALUES(end_datetime),
              created_by     = VALUES(created_by)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('sssi', $pageKey, $startDb, $endDb, $createdBy);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Failed to save enrollment period.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Enrollment period saved successfully.']);
    }
    exit;
}

// --- Semestral lock handler (AJAX from modal) ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) && $_POST['action'] === 'toggle_sem_lock'
) {
    header('Content-Type: application/json');

    $semesterRaw = $_POST['semester'] ?? '';
    $semester    = (int)$semesterRaw;
    $isLocked    = isset($_POST['is_locked']) ? (int)$_POST['is_locked'] : 1;

    if (!in_array($semester, [1, 2], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid semester selected.']);
        exit;
    }

    $semesterLabel = $semester === 1 ? 'First Semester' : 'Second Semester';

    // Ensure table exists
    $createLockSql = "CREATE TABLE IF NOT EXISTS semester_locks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        semester TINYINT NOT NULL,
        is_locked TINYINT(1) NOT NULL DEFAULT 0,
        locked_by INT NULL,
        locked_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_semester (semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($createLockSql)) {
        echo json_encode(['success' => false, 'message' => 'DB error creating lock table: ' . $conn->error]);
        exit;
    }

    $lockedBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $sql = "INSERT INTO semester_locks (semester, is_locked, locked_by, locked_at)
            VALUES (?, ?, ?, IF(? = 1, NOW(), NULL))
            ON DUPLICATE KEY UPDATE
              is_locked = VALUES(is_locked),
              locked_by = VALUES(locked_by),
              locked_at = IF(VALUES(is_locked) = 1, NOW(), NULL)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('iiii', $semester, $isLocked, $lockedBy, $isLocked);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Failed to update semestral lock.']);
        exit;
    }

    $msg = $isLocked === 1
        ? $semesterLabel . ' locked successfully.'
        : $semesterLabel . ' unlocked successfully.';

    echo json_encode([
        'success'  => true,
        'message'  => $msg,
        'semester' => $semester,
        'is_locked'=> $isLocked,
    ]);
    exit;
}

// Load current enrollment period for stueval.php (if any) to prefill the modal
$currentEnrollment = null;
try {
    $checkSql = "SHOW TABLES LIKE 'enrollment_periods'";
    $res = $conn->query($checkSql);
    if ($res && $res->num_rows > 0) {
        $pageKey = 'stueval';
        $stmt = $conn->prepare("SELECT start_datetime, end_datetime FROM enrollment_periods WHERE page = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $pageKey);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $currentEnrollment = $row;
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    // Fail silently for dashboard if table is missing
}

// Load current semestral lock states (1st and 2nd semester)
$semesterLocks = [
    1 => ['is_locked' => 0],
    2 => ['is_locked' => 0],
];

try {
    $checkLockTable = "SHOW TABLES LIKE 'semester_locks'";
    $resLock = $conn->query($checkLockTable);
    if ($resLock && $resLock->num_rows > 0) {
        $lockRes = $conn->query("SELECT semester, is_locked FROM semester_locks");
        if ($lockRes) {
            while ($row = $lockRes->fetch_assoc()) {
                $sem = (int)$row['semester'];
                if ($sem === 1 || $sem === 2) {
                    $semesterLocks[$sem]['is_locked'] = (int)$row['is_locked'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Ignore lock loading issues on dashboard
}

// Initialize user data
$user = [
    'firstname' => '',
    'lastname' => '',
    'email' => '',
    'profile_image' => 'default-avatar.png'
];

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, profile_image FROM signin_db WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user = array_merge($user, $row);
}
$stmt->close();

// Count BSIT students
$bsit_query = "SELECT COUNT(*) as count FROM students_db WHERE programs = 'BSIT'";
$bsit_result = $conn->query($bsit_query);
$bsit_count = $bsit_result ? (int)$bsit_result->fetch_assoc()['count'] : 0;

// Count BSCS students
$bscs_query = "SELECT COUNT(*) as count FROM students_db WHERE programs = 'BSCS'";
$bscs_result = $conn->query($bscs_query);
$bscs_count = $bscs_result ? (int)$bscs_result->fetch_assoc()['count'] : 0;

// Count total students
$total_query = "SELECT COUNT(*) as count FROM students_db";
$total_result = $conn->query($total_query);
$total_count = $total_result ? (int)$total_result->fetch_assoc()['count'] : 0;

// Count Regular and Irregular students
$regular_query = "SELECT COUNT(*) as count FROM signin_db WHERE role_id IN (4, 5, 6) AND (classification IS NULL OR classification = '' OR classification = 'Regular')";
$regular_result = $conn->query($regular_query);
$regular_count = $regular_result ? (int)$regular_result->fetch_assoc()['count'] : 0;

$irregular_query = "SELECT COUNT(*) as count FROM signin_db WHERE role_id IN (4, 5, 6) AND classification = 'Irregular'";
$irregular_result = $conn->query($irregular_query);
$irregular_count = $irregular_result ? (int)$irregular_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            background: white;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            width: 220px;
            height: 100vh;
            color: #fff;
            padding: 30px 16px 16px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            border-top-right-radius: 32px;
            border-bottom-right-radius: 32px;
            box-shadow: 2px 0 16px rgba(31,38,135,0.10);
        }
        .profile-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #fff;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(31,38,135,0.10);
            border: 4px solid #fff;
        }
        .profile-circle img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .sidebar .role {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .sidebar .label {
            font-size: 0.98rem;
            color: #e0e0e0;
            font-weight: 400;
            margin-bottom: 18px;
        }
        .sidebar .nav-link {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin: 8px 0;
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-size: 1.08rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #fff;
            color: #2563eb;
            box-shadow: 0 2px 8px rgba(31,38,135,0.10);
        }
        .logout-btn {
            margin-top: auto;
            background: linear-gradient(90deg, #dc2626 0%, #f87171 100%);
            color: #fff;
            padding: 10px 0;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.08rem;
            letter-spacing: 0.5px;
            border: none;
            width: 100%;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: #b91c1c; color: #fff; }
        @media (max-width: 700px) {
            .sidebar { width: 100px; padding: 10px 4px; }
            .sidebar .nav-link, .logout-btn { font-size: 0.9rem; padding: 8px 0; }
            .profile-circle { width: 60px; height: 60px; }
            .profile-circle img { width: 52px; height: 52px; }
        }
        .content {
            flex: 1;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f3f4f6;
            width: 100%;
        }
        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            padding: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin: 24px 0;
        }
        .chart-area {
            width: 100%;
            max-width: 900px;
            height: 500px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
        <div class="profile-circle"><img src="regs.jpg" alt="My Small Photo"></div>
            <div class="role">Registrar</div>
            <div class="label">Registrar</div>
            <a href="dashboardr.php" class="nav-link active">Dashboard</a>
            <a href="registrar.php" class="nav-link">Student List</a>
            <a href="student_evaluation.php" class="nav-link"><i class="fas fa-clipboard-check"></i> Student Evaluation</a>
            <a href="studentgrade.php" class="nav-link">Student Grade</a>
            <a href="#" id="enrollmentPeriodLink" class="nav-link"><i class="fas fa-calendar"></i> Enrollment Period</a>
            <a href="#" id="semLockLink" class="nav-link"><i class="fas fa-lock"></i> Semestral Lock</a>
            <a href="feedbackr.php" class="nav-link">Feedback</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="content">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin-bottom: 20px;">
                <h2 style="color:#374151; margin: 0;">Registrar Dashboard</h2>
                <div class="notification-container" style="position: relative;">
                    <a href="notification_page.php" style="color: #4b5563; text-decoration: none; position: relative;">
                        <i class="fas fa-bell" style="font-size: 24px;"></i>
                        <?php
                        // Count unread notifications
                        $unread_query = "SELECT COUNT(*) as unread_count FROM notifications_db 
                                      WHERE user_id = ? AND is_read = 0";
                        $stmt = $conn->prepare($unread_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $unread_result = $stmt->get_result();
                        $unread_count = $unread_result->fetch_assoc()['unread_count'];
                        $stmt->close();
                        
                        if ($unread_count > 0): ?>
                            <span class="notification-badge" style="
                                position: absolute;
                                top: -8px;
                                right: -8px;
                                background-color: #ef4444;
                                color: white;
                                border-radius: 50%;
                                width: 20px;
                                height: 20px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 12px;
                                font-weight: bold;
                            "><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; width: 100%; max-width: 1200px;">
                <div class="chart-container" style="margin: 0;">
                    <div class="chart-area">
                        <canvas id="deptBarChart"></canvas>
                    </div>
                </div>
                <div class="chart-container" style="margin: 0;">
                    <div class="chart-area">
                        <canvas id="classificationBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Enrollment Period Modal -->
            <div id="enrollmentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:white; padding:20px 24px; border-radius:12px; width:100%; max-width:420px; box-shadow:0 10px 25px rgba(15,23,42,0.25);">
                    <h3 style="margin-top:0; margin-bottom:12px; color:#111827;">Set Enrollment Period</h3>
                    <p style="margin:0 0 16px; font-size:0.9rem; color:#4b5563;">
                        This period will be used as the allowed time window for evaluating students in the Dean evaluation.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <label style="font-size:0.9rem; color:#374151;">Start date & time</label>
                        <input type="datetime-local" id="enrollStart" style="padding:8px 10px; border-radius:6px; border:1px solid #d1d5db;" value="<?php
                            if ($currentEnrollment && !empty($currentEnrollment['start_datetime'])) {
                                echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($currentEnrollment['start_datetime'])));
                            }
                        ?>">
                        <label style="font-size:0.9rem; color:#374151; margin-top:8px;">End date & time</label>
                        <input type="datetime-local" id="enrollEnd" style="padding:8px 10px; border-radius:6px; border:1px solid #d1d5db;" value="<?php
                            if ($currentEnrollment && !empty($currentEnrollment['end_datetime'])) {
                                echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($currentEnrollment['end_datetime'])));
                            }
                        ?>">
                    </div>
                    <div id="enrollMessage" style="margin-top:10px; font-size:0.85rem; color:#b91c1c; display:none;"></div>
                    <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" id="enrollCancelBtn" style="padding:8px 14px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; cursor:pointer;">Cancel</button>
                        <button type="button" id="enrollSaveBtn" style="padding:8px 16px; border-radius:6px; border:none; background:#2563eb; color:white; font-weight:600; cursor:pointer;">Save</button>
                    </div>
                </div>
            </div>

            <!-- Semestral Lock Modal -->
            <div id="semLockModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1001; align-items:center; justify-content:center;">
                <div style="background:white; padding:20px 24px; border-radius:12px; width:100%; max-width:480px; box-shadow:0 10px 25px rgba(15,23,42,0.25);">
                    <h3 style="margin-top:0; margin-bottom:12px; color:#111827;">Semestral Lock</h3>
                    <p style="margin:0 0 16px; font-size:0.9rem; color:#4b5563;">
                        Lock or unlock actions for each semester. When a semester is locked, related operations in the system can be restricted.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <!-- First Semester -->
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;">
                            <div>
                                <div style="font-weight:600; color:#111827;">First Semester</div>
                                <div id="sem1Status" style="font-size:0.85rem; color:#6b7280;">
                                    Status: <?php echo $semesterLocks[1]['is_locked'] ? 'Locked' : 'Unlocked'; ?>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="sem-lock-btn"
                                data-sem="1"
                                data-locked="<?php echo $semesterLocks[1]['is_locked'] ? '1' : '0'; ?>"
                                style="padding:8px 14px; border-radius:6px; border:none; cursor:pointer; font-weight:600; color:white; background:<?php echo $semesterLocks[1]['is_locked'] ? '#6b7280' : '#2563eb'; ?>;">
                                <?php echo $semesterLocks[1]['is_locked'] ? 'Unlock' : 'Lock'; ?>
                            </button>
                        </div>

                        <!-- Second Semester -->
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;">
                            <div>
                                <div style="font-weight:600; color:#111827;">Second Semester</div>
                                <div id="sem2Status" style="font-size:0.85rem; color:#6b7280;">
                                    Status: <?php echo $semesterLocks[2]['is_locked'] ? 'Locked' : 'Unlocked'; ?>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="sem-lock-btn"
                                data-sem="2"
                                data-locked="<?php echo $semesterLocks[2]['is_locked'] ? '1' : '0'; ?>"
                                style="padding:8px 14px; border-radius:6px; border:none; cursor:pointer; font-weight:600; color:white; background:<?php echo $semesterLocks[2]['is_locked'] ? '#6b7280' : '#2563eb'; ?>;">
                                <?php echo $semesterLocks[2]['is_locked'] ? 'Unlock' : 'Lock'; ?>
                            </button>
                        </div>
                    </div>

                    <div id="semLockMessage" style="margin-top:10px; font-size:0.85rem; color:#b91c1c; display:none;"></div>

                    <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" id="semLockCloseBtn" style="padding:8px 14px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; cursor:pointer;">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </>
    <script>
        const ctx = document.getElementById('deptBarChart').getContext('2d');
        const deptBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['BSIT', 'BSCS', 'Total Students'],
                datasets: [{
                    label: 'No. of Students',
                    data: [<?php echo $bsit_count; ?>, <?php echo $bscs_count; ?>, <?php echo $total_count; ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)', // BSIT
                        'rgba(16, 185, 129, 0.8)', // BSCS
                        'rgba(139, 92, 246, 0.8)'  // Total
                    ],
                    borderRadius: 8,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Student Population by Department',
                        font: { size: 18 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Classification Chart - Regular vs Irregular Students
        const ctxClass = document.getElementById('classificationBarChart').getContext('2d');
        const classificationBarChart = new Chart(ctxClass, {
            type: 'bar',
            data: {
                labels: ['Regular Students', 'Irregular Students'],
                datasets: [{
                    label: 'No. of Students',
                    data: [<?php echo $regular_count; ?>, <?php echo $irregular_count; ?>],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',   // Regular - Green
                        'rgba(249, 115, 22, 0.8)'   // Irregular - Orange
                    ],
                    borderRadius: 8,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Student Classification Distribution',
                        font: { size: 18 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Enrollment Period modal logic
        const enrollmentLink = document.getElementById('enrollmentPeriodLink');
        const enrollmentModal = document.getElementById('enrollmentModal');
        const enrollCancelBtn = document.getElementById('enrollCancelBtn');
        const enrollSaveBtn = document.getElementById('enrollSaveBtn');
        const enrollMessage = document.getElementById('enrollMessage');

        function openEnrollmentModal() {
            if (enrollmentModal) {
                enrollmentModal.style.display = 'flex';
                enrollMessage.style.display = 'none';
                enrollMessage.textContent = '';
            }
        }

        function closeEnrollmentModal() {
            if (enrollmentModal) {
                enrollmentModal.style.display = 'none';
            }
        }

        if (enrollmentLink) {
            enrollmentLink.addEventListener('click', function (e) {
                e.preventDefault();
                openEnrollmentModal();
            });
        }
        if (enrollCancelBtn) {
            enrollCancelBtn.addEventListener('click', function () {
                closeEnrollmentModal();
            });
        }
        if (enrollmentModal) {
            enrollmentModal.addEventListener('click', function (e) {
                if (e.target === enrollmentModal) {
                    closeEnrollmentModal();
                }
            });
        }

        if (enrollSaveBtn) {
            enrollSaveBtn.addEventListener('click', function () {
                const startInput = document.getElementById('enrollStart');
                const endInput = document.getElementById('enrollEnd');
                const startVal = startInput.value;
                const endVal = endInput.value;

                if (!startVal || !endVal) {
                    enrollMessage.textContent = 'Please fill in both start and end date/time.';
                    enrollMessage.style.display = 'block';
                    return;
                }

                const formData = new URLSearchParams();
                formData.append('action', 'save_enrollment_period');
                formData.append('start_datetime', startVal);
                formData.append('end_datetime', endVal);

                fetch('dashboardr.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        enrollMessage.textContent = data.message || 'Failed to save enrollment period.';
                        enrollMessage.style.display = 'block';
                    } else {
                        // Close modal and show SweetAlert success message
                        closeEnrollmentModal();
                        enrollMessage.style.display = 'none';
                        enrollMessage.style.color = '#b91c1c';

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Enrollment period set',
                                text: data.message || 'Enrollment period saved successfully.',
                                confirmButtonText: 'OK'
                            });
                        }
                    }
                })
                .catch(err => {
                    enrollMessage.textContent = 'Error saving enrollment period.';
                    enrollMessage.style.display = 'block';
                    console.error(err);
                });
            });
        }

        // Semestral Lock modal logic
        const semLockLink = document.getElementById('semLockLink');
        const semLockModal = document.getElementById('semLockModal');
        const semLockCloseBtn = document.getElementById('semLockCloseBtn');
        const semLockMessage = document.getElementById('semLockMessage');

        function openSemLockModal() {
            if (semLockModal) {
                semLockModal.style.display = 'flex';
                if (semLockMessage) {
                    semLockMessage.style.display = 'none';
                    semLockMessage.textContent = '';
                }
            }
        }

        function closeSemLockModal() {
            if (semLockModal) {
                semLockModal.style.display = 'none';
            }
        }

        if (semLockLink) {
            semLockLink.addEventListener('click', function (e) {
                e.preventDefault();
                openSemLockModal();
            });
        }

        if (semLockCloseBtn) {
            semLockCloseBtn.addEventListener('click', function () {
                closeSemLockModal();
            });
        }

        if (semLockModal) {
            semLockModal.addEventListener('click', function (e) {
                if (e.target === semLockModal) {
                    closeSemLockModal();
                }
            });
        }

        function updateSemLockUI(sem, isLocked) {
            const statusEl = document.getElementById(sem === 1 ? 'sem1Status' : 'sem2Status');
            const btn = document.querySelector('.sem-lock-btn[data-sem="' + sem + '"]');
            if (!btn || !statusEl) return;

            btn.dataset.locked = isLocked ? '1' : '0';
            btn.textContent = isLocked ? 'Unlock' : 'Lock';
            btn.style.background = isLocked ? '#6b7280' : '#2563eb';
            statusEl.textContent = 'Status: ' + (isLocked ? 'Locked' : 'Unlocked');
        }

        const semLockButtons = document.querySelectorAll('.sem-lock-btn');
        semLockButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const sem = parseInt(btn.dataset.sem, 10);
                const currentlyLocked = btn.dataset.locked === '1';
                const newStatus = currentlyLocked ? 0 : 1;

                const formData = new URLSearchParams();
                formData.append('action', 'toggle_sem_lock');
                formData.append('semester', String(sem));
                formData.append('is_locked', String(newStatus));

                if (semLockMessage) {
                    semLockMessage.style.display = 'none';
                    semLockMessage.textContent = '';
                    semLockMessage.style.color = '#b91c1c';
                }

                fetch('dashboardr.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        if (semLockMessage) {
                            semLockMessage.textContent = data.message || 'Failed to update semestral lock.';
                            semLockMessage.style.display = 'block';
                        }
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update failed',
                                text: data.message || 'Failed to update semestral lock.',
                                confirmButtonText: 'OK'
                            });
                        }
                    } else {
                        const isLocked = data.is_locked === 1 || data.is_locked === '1';
                        updateSemLockUI(sem, isLocked);

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Semestral lock updated',
                                text: data.message || 'Semestral lock updated successfully.',
                                confirmButtonText: 'OK'
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (semLockMessage) {
                        semLockMessage.textContent = 'Error updating semestral lock.';
                        semLockMessage.style.display = 'block';
                    }
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error updating semestral lock.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
