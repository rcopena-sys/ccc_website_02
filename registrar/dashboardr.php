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
    $lockStartRaw = trim($_POST['lock_start_datetime'] ?? '');
    $lockEndRaw   = trim($_POST['lock_end_datetime'] ?? '');

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
        lock_start_datetime DATETIME NULL,
        lock_end_datetime DATETIME NULL,
        locked_by INT NULL,
        locked_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_semester (semester)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($createLockSql)) {
        echo json_encode(['success' => false, 'message' => 'DB error creating lock table: ' . $conn->error]);
        exit;
    }

    // Backfill columns for existing tables created before date-range support
    $hasStartColRes = $conn->query("SHOW COLUMNS FROM semester_locks LIKE 'lock_start_datetime'");
    if ($hasStartColRes && $hasStartColRes->num_rows === 0) {
        $conn->query("ALTER TABLE semester_locks ADD COLUMN lock_start_datetime DATETIME NULL AFTER is_locked");
    }
    $hasEndColRes = $conn->query("SHOW COLUMNS FROM semester_locks LIKE 'lock_end_datetime'");
    if ($hasEndColRes && $hasEndColRes->num_rows === 0) {
        $conn->query("ALTER TABLE semester_locks ADD COLUMN lock_end_datetime DATETIME NULL AFTER lock_start_datetime");
    }

    $lockStartDb = null;
    $lockEndDb = null;
    if ($isLocked === 1) {
        if ($lockStartRaw === '' || $lockEndRaw === '') {
            echo json_encode(['success' => false, 'message' => 'Lock start and end date/time are required when locking a semester.']);
            exit;
        }

        $lockStartTs = strtotime($lockStartRaw);
        $lockEndTs = strtotime($lockEndRaw);
        if ($lockStartTs === false || $lockEndTs === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid lock date/time format.']);
            exit;
        }
        if ($lockEndTs <= $lockStartTs) {
            echo json_encode(['success' => false, 'message' => 'Lock end date/time must be after lock start date/time.']);
            exit;
        }

        $lockStartDb = date('Y-m-d H:i:s', $lockStartTs);
        $lockEndDb = date('Y-m-d H:i:s', $lockEndTs);
    }

    $lockedBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $sql = "INSERT INTO semester_locks (semester, is_locked, lock_start_datetime, lock_end_datetime, locked_by, locked_at)
                        VALUES (?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL))
            ON DUPLICATE KEY UPDATE
              is_locked = VALUES(is_locked),
                            lock_start_datetime = VALUES(lock_start_datetime),
                            lock_end_datetime = VALUES(lock_end_datetime),
              locked_by = VALUES(locked_by),
              locked_at = IF(VALUES(is_locked) = 1, NOW(), NULL)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('iissii', $semester, $isLocked, $lockStartDb, $lockEndDb, $lockedBy, $isLocked);
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
        'lock_start_datetime' => $lockStartDb,
        'lock_end_datetime' => $lockEndDb,
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
    1 => ['is_locked' => 0, 'lock_start_datetime' => null, 'lock_end_datetime' => null],
    2 => ['is_locked' => 0, 'lock_start_datetime' => null, 'lock_end_datetime' => null],
];

try {
    $checkLockTable = "SHOW TABLES LIKE 'semester_locks'";
    $resLock = $conn->query($checkLockTable);
    if ($resLock && $resLock->num_rows > 0) {
        $hasStartColRes = $conn->query("SHOW COLUMNS FROM semester_locks LIKE 'lock_start_datetime'");
        if ($hasStartColRes && $hasStartColRes->num_rows === 0) {
            $conn->query("ALTER TABLE semester_locks ADD COLUMN lock_start_datetime DATETIME NULL AFTER is_locked");
        }
        $hasEndColRes = $conn->query("SHOW COLUMNS FROM semester_locks LIKE 'lock_end_datetime'");
        if ($hasEndColRes && $hasEndColRes->num_rows === 0) {
            $conn->query("ALTER TABLE semester_locks ADD COLUMN lock_end_datetime DATETIME NULL AFTER lock_start_datetime");
        }

        $lockRes = $conn->query("SELECT semester, is_locked, lock_start_datetime, lock_end_datetime FROM semester_locks");
        if ($lockRes) {
            while ($row = $lockRes->fetch_assoc()) {
                $sem = (int)$row['semester'];
                if ($sem === 1 || $sem === 2) {
                    $semesterLocks[$sem]['is_locked'] = (int)$row['is_locked'];
                    $semesterLocks[$sem]['lock_start_datetime'] = $row['lock_start_datetime'] ?? null;
                    $semesterLocks[$sem]['lock_end_datetime'] = $row['lock_end_datetime'] ?? null;
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

// Count Regular and Irregular students from students_db
$regular_query = "SELECT COUNT(*) as count FROM students_db WHERE LOWER(TRIM(COALESCE(classification, ''))) = 'regular'";
$regular_result = $conn->query($regular_query);
$regular_count = $regular_result ? (int)$regular_result->fetch_assoc()['count'] : 0;

$irregular_query = "SELECT COUNT(*) as count FROM students_db WHERE LOWER(TRIM(COALESCE(classification, ''))) = 'irregular'";
$irregular_result = $conn->query($irregular_query);
$irregular_count = $irregular_result ? (int)$irregular_result->fetch_assoc()['count'] : 0;

// Per-course classification counts for dashboard filtering
$bsit_regular_result = $conn->query("SELECT COUNT(*) AS count FROM students_db WHERE programs = 'BSIT' AND LOWER(TRIM(COALESCE(classification, ''))) = 'regular'");
$bsit_regular_count = $bsit_regular_result ? (int)$bsit_regular_result->fetch_assoc()['count'] : 0;

$bsit_irregular_result = $conn->query("SELECT COUNT(*) AS count FROM students_db WHERE programs = 'BSIT' AND LOWER(TRIM(COALESCE(classification, ''))) = 'irregular'");
$bsit_irregular_count = $bsit_irregular_result ? (int)$bsit_irregular_result->fetch_assoc()['count'] : 0;

$bscs_regular_result = $conn->query("SELECT COUNT(*) AS count FROM students_db WHERE programs = 'BSCS' AND LOWER(TRIM(COALESCE(classification, ''))) = 'regular'");
$bscs_regular_count = $bscs_regular_result ? (int)$bscs_regular_result->fetch_assoc()['count'] : 0;

$bscs_irregular_result = $conn->query("SELECT COUNT(*) AS count FROM students_db WHERE programs = 'BSCS' AND LOWER(TRIM(COALESCE(classification, ''))) = 'irregular'");
$bscs_irregular_count = $bscs_irregular_result ? (int)$bscs_irregular_result->fetch_assoc()['count'] : 0;

$dashboardRefreshedAt = date('M d, Y h:i A');
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
        :root {
            --bg: #f4f7fb;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #2563eb;
            --primary-soft: rgba(37, 99, 235, 0.12);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
        }
        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .container {
            display: flex;
            align-items: stretch;
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, #2563eb 0%, #60a5fa 100%);
            width: 220px;
            min-height: 100vh;
            height: auto;
            flex-shrink: 0;
            position: sticky;
            top: 0;
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
            overflow-y: auto;
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
            align-items: stretch;
            gap: 18px;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 32%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.06), transparent 28%),
                var(--bg);
            width: 100%;
            overflow-y: auto;
        }
        .dashboard-shell {
            display: flex;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            flex-direction: column;
            gap: 18px;
        }
        .dashboard-topbar {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 24px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: var(--shadow);
        }
        .dashboard-title h2 {
            font-size: 1.5rem;
            color: var(--text);
            margin-bottom: 6px;
        }
        .dashboard-title p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.4;
        }
        .dashboard-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .refresh-pill,
        .notification-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--surface);
            border: 1px solid var(--line);
            color: var(--muted);
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }
        .notification-pill {
            color: #334155;
        }
        .filter-strip,
        .panel,
        .stat-card,
        .mini-card {
            background: var(--surface);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: var(--shadow);
        }
        .filter-strip {
            border-radius: 22px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-group label {
            font-size: 0.92rem;
            color: var(--muted);
            font-weight: 700;
        }
        .filter-group select {
            min-width: 180px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            outline: none;
        }
        .course-count-pill {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            font-size: 0.92rem;
            font-weight: 700;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }
        .stat-card {
            border-radius: 22px;
            padding: 18px;
            min-height: 126px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            inset: auto -20px -20px auto;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.06);
        }
        .stat-card.is-primary::after {
            background: var(--primary-soft);
        }
        .stat-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .stat-label {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.08);
            color: var(--primary);
            z-index: 1;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
            margin-top: 8px;
            z-index: 1;
        }
        .stat-note {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.35;
            z-index: 1;
        }
        .panel-grid {
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 16px;
        }
        .panel {
            border-radius: 22px;
            padding: 18px;
        }
        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .panel-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text);
        }
        .panel-subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .panel-badge {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.08);
            color: #047857;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .chart-frame {
            width: 100%;
            height: 420px;
        }
        .aux-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .mini-stack {
            display: grid;
            gap: 16px;
        }
        .mini-card {
            border-radius: 18px;
            padding: 16px;
        }
        .mini-card h4 {
            font-size: 0.95rem;
            color: var(--text);
            margin-bottom: 6px;
        }
        .mini-card p {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.45;
        }
        .status-line {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            background: var(--surface-soft);
            border: 1px solid var(--line);
            color: #334155;
            font-size: 0.9rem;
        }
        .status-line strong {
            color: var(--text);
        }
        .status-line.is-locked {
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.18);
            color: #991b1b;
        }
        .status-line.is-open {
            background: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.18);
            color: #047857;
        }
        .chart-card {
            min-height: 100%;
        }
        .chart-area {
            width: 100%;
            height: 100%;
            min-height: 360px;
        }
        .chart-card canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .hidden-message {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #b91c1c;
            display: none;
        }
        @media (max-width: 1100px) {
            .stats-grid, .aux-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .panel-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 700px) {
            .content { padding: 14px; }
            .dashboard-topbar,
            .filter-strip,
            .panel,
            .stat-card,
            .mini-card {
                border-radius: 18px;
            }
            .stats-grid, .aux-grid {
                grid-template-columns: 1fr;
            }
            .chart-frame {
                height: 320px;
            }
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
            <div class="dashboard-shell">
                <div class="dashboard-topbar">
                    <div class="dashboard-title">
                        <h2>Registrar Dashboard</h2>
                        <p>Track student counts, filter by course, and manage enrollment windows and semestral locks.</p>
                    </div>
                    <div class="dashboard-meta">
                        <div class="refresh-pill"><i class="fas fa-sync-alt"></i><span>Data refreshed at <?php echo htmlspecialchars($dashboardRefreshedAt); ?></span></div>
                        <a href="notification_page.php" class="notification-pill" aria-label="Notifications">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                            <?php
                            $unread_query = "SELECT COUNT(*) as unread_count FROM notifications_db WHERE user_id = ? AND is_read = 0";
                            $stmt = $conn->prepare($unread_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $unread_result = $stmt->get_result();
                            $unread_count = $unread_result->fetch_assoc()['unread_count'];
                            $stmt->close();

                            if ($unread_count > 0): ?>
                                <strong style="color:#ef4444;">(<?php echo (int)$unread_count; ?>)</strong>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <div class="filter-strip">
                    <div class="filter-group">
                        <label for="courseFilter">Filter by course</label>
                        <select id="courseFilter">
                            <option value="ALL">All Courses</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                        </select>
                    </div>
                    <div id="courseCountText" class="course-count-pill">All Courses count: <?php echo $total_count; ?></div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card is-primary">
                        <div class="stat-head">
                            <div class="stat-label">Total Students</div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $total_count; ?></div>
                        <div class="stat-note">All enrolled students across BSIT and BSCS.</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">BSIT</div>
                            <div class="stat-icon"><i class="fas fa-laptop-code"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $bsit_count; ?></div>
                        <div class="stat-note">Department count for BSIT.</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">BSCS</div>
                            <div class="stat-icon"><i class="fas fa-microchip"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $bscs_count; ?></div>
                        <div class="stat-note">Department count for BSCS.</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Regular</div>
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $regular_count; ?></div>
                        <div class="stat-note">Students classified as regular.</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Irregular</div>
                            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                        </div>
                        <div class="stat-value"><?php echo $irregular_count; ?></div>
                        <div class="stat-note">Students classified as irregular.</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Enrollment Period</div>
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                        </div>
                        <div class="stat-value" style="font-size:1.4rem;">
                            <?php echo ($currentEnrollment && !empty($currentEnrollment['start_datetime']) && !empty($currentEnrollment['end_datetime'])) ? 'Set' : 'Pending'; ?>
                        </div>
                        <div class="stat-note" id="enrollmentCardStatus">
                            <?php
                            if ($currentEnrollment && !empty($currentEnrollment['start_datetime']) && !empty($currentEnrollment['end_datetime'])) {
                                echo 'From ' . htmlspecialchars(date('M d, Y h:i A', strtotime($currentEnrollment['start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($currentEnrollment['end_datetime'])));
                            } else {
                                echo 'No enrollment window has been saved yet.';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">First Semester Lock</div>
                            <div class="stat-icon"><i class="fas fa-lock"></i></div>
                        </div>
                        <div class="stat-value" style="font-size:1.4rem; color: <?php echo $semesterLocks[1]['is_locked'] ? '#b91c1c' : '#047857'; ?>;">
                            <?php echo $semesterLocks[1]['is_locked'] ? 'Locked' : 'Unlocked'; ?>
                        </div>
                        <div class="stat-note" id="sem1CardStatus">
                            <?php
                            if (!empty($semesterLocks[1]['is_locked']) && !empty($semesterLocks[1]['lock_start_datetime']) && !empty($semesterLocks[1]['lock_end_datetime'])) {
                                echo 'Active ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_end_datetime'])));
                            } else {
                                echo 'First semester is currently open.';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-head">
                            <div class="stat-label">Second Semester Lock</div>
                            <div class="stat-icon"><i class="fas fa-unlock-alt"></i></div>
                        </div>
                        <div class="stat-value" style="font-size:1.4rem; color: <?php echo $semesterLocks[2]['is_locked'] ? '#b91c1c' : '#047857'; ?>;">
                            <?php echo $semesterLocks[2]['is_locked'] ? 'Locked' : 'Unlocked'; ?>
                        </div>
                        <div class="stat-note" id="sem2CardStatus">
                            <?php
                            if (!empty($semesterLocks[2]['is_locked']) && !empty($semesterLocks[2]['lock_start_datetime']) && !empty($semesterLocks[2]['lock_end_datetime'])) {
                                echo 'Active ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_end_datetime'])));
                            } else {
                                echo 'Second semester is currently open.';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="panel-grid">
                    <div class="panel chart-card">
                        <div class="panel-header">
                            <div>
                                <div class="panel-title">Student Population by Department</div>
                                <div class="panel-subtitle">Click a bar to filter the dashboard by course.</div>
                            </div>
                            <div class="panel-badge">Interactive</div>
                        </div>
                        <div class="chart-frame">
                            <canvas id="deptBarChart"></canvas>
                        </div>
                    </div>

                    <div class="mini-stack">
                        <div class="panel chart-card">
                            <div class="panel-header">
                                <div>
                                    <div class="panel-title">Student Classification Distribution</div>
                                    <div class="panel-subtitle">Regular versus irregular students for the selected course.</div>
                                </div>
                                <div class="panel-badge" id="courseCountBadge">All Courses</div>
                            </div>
                            <div class="chart-frame" style="height: 280px; min-height: 280px;">
                                <canvas id="classificationBarChart"></canvas>
                            </div>
                        </div>

                        <div class="aux-grid">
                            <div class="mini-card">
                                <h4>Enrollment Window</h4>
                                <p>This controls the allowed time window for student evaluation in the Dean module.</p>
                                <div class="status-line <?php echo ($currentEnrollment && !empty($currentEnrollment['start_datetime']) && !empty($currentEnrollment['end_datetime'])) ? 'is-open' : ''; ?>" id="enrollmentCardSummary">
                                    <?php
                                    if ($currentEnrollment && !empty($currentEnrollment['start_datetime']) && !empty($currentEnrollment['end_datetime'])) {
                                        echo '<strong>Set</strong> from ' . htmlspecialchars(date('M d, Y h:i A', strtotime($currentEnrollment['start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($currentEnrollment['end_datetime'])));
                                    } else {
                                        echo '<strong>Pending</strong> no enrollment window is configured.';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="mini-card">
                                <h4>Semestral Access</h4>
                                <p>Lock or unlock semester access with a date range for controlled operations.</p>
                                <div class="status-line <?php echo $semesterLocks[1]['is_locked'] ? 'is-locked' : 'is-open'; ?>" id="semLockSummary">
                                    First semester: <strong><?php echo $semesterLocks[1]['is_locked'] ? 'Locked' : 'Open'; ?></strong>
                                    <?php
                                    if ($semesterLocks[1]['is_locked'] && !empty($semesterLocks[1]['lock_start_datetime']) && !empty($semesterLocks[1]['lock_end_datetime'])) {
                                        echo '<br>' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_end_datetime'])));
                                    }
                                    ?>
                                </div>
                                <div class="status-line <?php echo $semesterLocks[2]['is_locked'] ? 'is-locked' : 'is-open'; ?>" style="margin-top:8px;" id="semLockSummary2">
                                    Second semester: <strong><?php echo $semesterLocks[2]['is_locked'] ? 'Locked' : 'Open'; ?></strong>
                                    <?php
                                    if ($semesterLocks[2]['is_locked'] && !empty($semesterLocks[2]['lock_start_datetime']) && !empty($semesterLocks[2]['lock_end_datetime'])) {
                                        echo '<br>' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_start_datetime']))) . ' to ' . htmlspecialchars(date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_end_datetime'])));
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Enrollment Period Modal -->
            <div id="enrollmentModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); backdrop-filter: blur(4px); z-index:1000; align-items:center; justify-content:center; padding:18px;">
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
                    <div id="enrollMessage" class="hidden-message"></div>
                    <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" id="enrollCancelBtn" style="padding:8px 14px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; cursor:pointer;">Cancel</button>
                        <button type="button" id="enrollSaveBtn" style="padding:8px 16px; border-radius:6px; border:none; background:#2563eb; color:white; font-weight:600; cursor:pointer;">Save</button>
                    </div>
                </div>
            </div>

            <!-- Semestral Lock Modal -->
            <div id="semLockModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); backdrop-filter: blur(4px); z-index:1001; align-items:center; justify-content:center; padding:18px;">
                <div style="background:white; padding:20px 24px; border-radius:12px; width:100%; max-width:480px; box-shadow:0 10px 25px rgba(15,23,42,0.25);">
                    <h3 style="margin-top:0; margin-bottom:12px; color:#111827;">Semestral Lock</h3>
                    <p style="margin:0 0 16px; font-size:0.9rem; color:#4b5563;">
                        Lock or unlock actions for each semester. When a semester is locked, related operations in the system can be restricted.
                    </p>

                    <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:14px;">
                        <label style="font-size:0.9rem; color:#374151;">Lock start date & time</label>
                        <input type="datetime-local" id="semLockStart" style="padding:8px 10px; border-radius:6px; border:1px solid #d1d5db;" value="<?php
                            if (!empty($semesterLocks[1]['lock_start_datetime'])) {
                                echo htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[1]['lock_start_datetime'])));
                            }
                        ?>">
                        <label style="font-size:0.9rem; color:#374151; margin-top:4px;">Lock end date & time</label>
                        <input type="datetime-local" id="semLockEnd" style="padding:8px 10px; border-radius:6px; border:1px solid #d1d5db;" value="<?php
                            if (!empty($semesterLocks[1]['lock_end_datetime'])) {
                                echo htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[1]['lock_end_datetime'])));
                            }
                        ?>">
                    </div>

                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <!-- First Semester -->
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;">
                            <div>
                                <div style="font-weight:600; color:#111827;">First Semester</div>
                                <div id="sem1Status" style="font-size:0.85rem; color:#6b7280;">
                                    Status: <?php echo $semesterLocks[1]['is_locked'] ? 'Locked' : 'Unlocked'; ?><?php
                                        if (!empty($semesterLocks[1]['is_locked']) && !empty($semesterLocks[1]['lock_start_datetime']) && !empty($semesterLocks[1]['lock_end_datetime'])) {
                                            echo ' (' . date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_start_datetime'])) . ' - ' . date('M d, Y h:i A', strtotime($semesterLocks[1]['lock_end_datetime'])) . ')';
                                        }
                                    ?>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="sem-lock-btn"
                                data-sem="1"
                                data-locked="<?php echo $semesterLocks[1]['is_locked'] ? '1' : '0'; ?>"
                                data-lock-start="<?php echo !empty($semesterLocks[1]['lock_start_datetime']) ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[1]['lock_start_datetime']))) : ''; ?>"
                                data-lock-end="<?php echo !empty($semesterLocks[1]['lock_end_datetime']) ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[1]['lock_end_datetime']))) : ''; ?>"
                                style="padding:8px 14px; border-radius:6px; border:none; cursor:pointer; font-weight:600; color:white; background:<?php echo $semesterLocks[1]['is_locked'] ? '#6b7280' : '#2563eb'; ?>;">
                                <?php echo $semesterLocks[1]['is_locked'] ? 'Unlock' : 'Lock'; ?>
                            </button>
                        </div>

                        <!-- Second Semester -->
                        <div style="border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; display:flex; align-items:center; justify-content:space-between;">
                            <div>
                                <div style="font-weight:600; color:#111827;">Second Semester</div>
                                <div id="sem2Status" style="font-size:0.85rem; color:#6b7280;">
                                    Status: <?php echo $semesterLocks[2]['is_locked'] ? 'Locked' : 'Unlocked'; ?><?php
                                        if (!empty($semesterLocks[2]['is_locked']) && !empty($semesterLocks[2]['lock_start_datetime']) && !empty($semesterLocks[2]['lock_end_datetime'])) {
                                            echo ' (' . date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_start_datetime'])) . ' - ' . date('M d, Y h:i A', strtotime($semesterLocks[2]['lock_end_datetime'])) . ')';
                                        }
                                    ?>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="sem-lock-btn"
                                data-sem="2"
                                data-locked="<?php echo $semesterLocks[2]['is_locked'] ? '1' : '0'; ?>"
                                data-lock-start="<?php echo !empty($semesterLocks[2]['lock_start_datetime']) ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[2]['lock_start_datetime']))) : ''; ?>"
                                data-lock-end="<?php echo !empty($semesterLocks[2]['lock_end_datetime']) ? htmlspecialchars(date('Y-m-d\\TH:i', strtotime($semesterLocks[2]['lock_end_datetime']))) : ''; ?>"
                                style="padding:8px 14px; border-radius:6px; border:none; cursor:pointer; font-weight:600; color:white; background:<?php echo $semesterLocks[2]['is_locked'] ? '#6b7280' : '#2563eb'; ?>;">
                                <?php echo $semesterLocks[2]['is_locked'] ? 'Unlock' : 'Lock'; ?>
                            </button>
                        </div>
                    </div>

                    <div id="semLockMessage" class="hidden-message"></div>

                    <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" id="semLockCloseBtn" style="padding:8px 14px; border-radius:6px; border:1px solid #d1d5db; background:white; color:#374151; cursor:pointer;">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const classificationByCourse = {
            ALL: { regular: <?php echo $regular_count; ?>, irregular: <?php echo $irregular_count; ?> },
            BSIT: { regular: <?php echo $bsit_regular_count; ?>, irregular: <?php echo $bsit_irregular_count; ?> },
            BSCS: { regular: <?php echo $bscs_regular_count; ?>, irregular: <?php echo $bscs_irregular_count; ?> }
        };

        const courseCounts = {
            ALL: <?php echo $total_count; ?>,
            BSIT: <?php echo $bsit_count; ?>,
            BSCS: <?php echo $bscs_count; ?>
        };

        const populationByCourse = {
            ALL: {
                labels: ['BSIT', 'BSCS', 'Total Students'],
                data: [<?php echo $bsit_count; ?>, <?php echo $bscs_count; ?>, <?php echo $total_count; ?>],
                title: 'Student Population by Department'
            },
            BSIT: {
                labels: ['BSIT'],
                data: [<?php echo $bsit_count; ?>],
                title: 'Student Population by Department (BSIT)'
            },
            BSCS: {
                labels: ['BSCS'],
                data: [<?php echo $bscs_count; ?>],
                title: 'Student Population by Department (BSCS)'
            }
        };

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
                onClick: function (evt, elements) {
                    if (!elements || elements.length === 0) return;
                    const index = elements[0].index;
                    const clickedLabel = this.data.labels[index] || '';
                    if (clickedLabel === 'BSIT' || clickedLabel === 'BSCS') {
                        applyCourseFilter(clickedLabel);
                    } else {
                        applyCourseFilter('ALL');
                    }
                },
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

        const courseFilter = document.getElementById('courseFilter');

        function applyCourseFilter(course) {
            const selectedCourse = classificationByCourse[course] ? course : 'ALL';
            const selectedData = classificationByCourse[selectedCourse];
            const selectedPopulation = populationByCourse[selectedCourse] || populationByCourse.ALL;

            deptBarChart.data.labels = selectedPopulation.labels;
            deptBarChart.data.datasets[0].data = selectedPopulation.data;
            deptBarChart.options.plugins.title.text = selectedPopulation.title;
            deptBarChart.update();

            classificationBarChart.data.datasets[0].data = [selectedData.regular, selectedData.irregular];
            classificationBarChart.options.plugins.title.text =
                selectedCourse === 'ALL'
                    ? 'Student Classification Distribution'
                    : 'Student Classification Distribution (' + selectedCourse + ')';
            classificationBarChart.update();

            if (courseFilter) {
                courseFilter.value = selectedCourse;
            }

            if (courseCountText) {
                if (selectedCourse === 'ALL') {
                    courseCountText.textContent = 'All Courses count: ' + courseCounts.ALL;
                } else {
                    courseCountText.textContent = selectedCourse + ' count: ' + courseCounts[selectedCourse];
                }
            }

            if (courseCountBadge) {
                courseCountBadge.textContent = selectedCourse === 'ALL' ? 'All Courses' : selectedCourse;
            }
        }

        if (courseFilter) {
            courseFilter.addEventListener('change', function () {
                applyCourseFilter(this.value);
            });
        }

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
                        setEnrollmentCard(startVal, endVal);

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
        const semLockStartInput = document.getElementById('semLockStart');
        const semLockEndInput = document.getElementById('semLockEnd');

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

        function formatDateRangeForStatus(start, end) {
            if (!start || !end) return '';

            const startDate = new Date(start);
            const endDate = new Date(end);
            if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return '';

            const dateOptions = {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return ' (' + startDate.toLocaleString('en-US', dateOptions) + ' - ' + endDate.toLocaleString('en-US', dateOptions) + ')';
        }

        function updateSemLockUI(sem, isLocked, lockStart, lockEnd) {
            const statusEl = document.getElementById(sem === 1 ? 'sem1Status' : 'sem2Status');
            const btn = document.querySelector('.sem-lock-btn[data-sem="' + sem + '"]');
            if (!btn || !statusEl) return;

            btn.dataset.locked = isLocked ? '1' : '0';
            btn.dataset.lockStart = lockStart || '';
            btn.dataset.lockEnd = lockEnd || '';
            btn.textContent = isLocked ? 'Unlock' : 'Lock';
            btn.style.background = isLocked ? '#6b7280' : '#2563eb';
            statusEl.textContent = 'Status: ' + (isLocked ? 'Locked' : 'Unlocked') + (isLocked ? formatDateRangeForStatus(lockStart, lockEnd) : '');

            if (semLockStartInput && semLockEndInput) {
                semLockStartInput.value = lockStart || '';
                semLockEndInput.value = lockEnd || '';
            }
        }

        const semLockButtons = document.querySelectorAll('.sem-lock-btn');
        semLockButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const sem = parseInt(btn.dataset.sem, 10);
                const currentlyLocked = btn.dataset.locked === '1';
                const newStatus = currentlyLocked ? 0 : 1;
                const lockStartVal = semLockStartInput ? semLockStartInput.value : '';
                const lockEndVal = semLockEndInput ? semLockEndInput.value : '';

                if (newStatus === 1 && (!lockStartVal || !lockEndVal)) {
                    if (semLockMessage) {
                        semLockMessage.textContent = 'Please provide lock start and end date/time before locking.';
                        semLockMessage.style.display = 'block';
                    }
                    return;
                }

                const formData = new URLSearchParams();
                formData.append('action', 'toggle_sem_lock');
                formData.append('semester', String(sem));
                formData.append('is_locked', String(newStatus));
                formData.append('lock_start_datetime', newStatus === 1 ? lockStartVal : '');
                formData.append('lock_end_datetime', newStatus === 1 ? lockEndVal : '');

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
                        const lockStart = isLocked ? (data.lock_start_datetime || lockStartVal || '') : '';
                        const lockEnd = isLocked ? (data.lock_end_datetime || lockEndVal || '') : '';
                        updateSemLockUI(sem, isLocked, lockStart, lockEnd);
                        setSemCard(sem, isLocked, lockStart, lockEnd);

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

        const courseCountText = document.getElementById('courseCountText');
        const courseCountBadge = document.getElementById('courseCountBadge');
        const enrollmentCardSummary = document.getElementById('enrollmentCardSummary');
        const enrollmentCardStatus = document.getElementById('enrollmentCardStatus');
        const sem1CardStatus = document.getElementById('sem1CardStatus');
        const sem2CardStatus = document.getElementById('sem2CardStatus');
        const semLockSummary = document.getElementById('semLockSummary');
        const semLockSummary2 = document.getElementById('semLockSummary2');

        function formatCardRange(start, end) {
            if (!start || !end) return '';

            const startDate = new Date(start);
            const endDate = new Date(end);
            if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return '';

            const dateOptions = {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };

            return startDate.toLocaleString('en-US', dateOptions) + ' to ' + endDate.toLocaleString('en-US', dateOptions);
        }

        function setEnrollmentCard(start, end) {
            const formattedRange = formatCardRange(start, end);
            if (enrollmentCardSummary) {
                enrollmentCardSummary.className = 'status-line ' + (formattedRange ? 'is-open' : '');
                enrollmentCardSummary.innerHTML = formattedRange
                    ? '<strong>Set</strong> from ' + formattedRange
                    : '<strong>Pending</strong> no enrollment window is configured.';
            }
            if (enrollmentCardStatus) {
                enrollmentCardStatus.textContent = formattedRange
                    ? 'From ' + formattedRange
                    : 'No enrollment window has been saved yet.';
            }
        }

        function setSemCard(sem, isLocked, start, end) {
            const formattedRange = isLocked ? formatCardRange(start, end) : '';
            const summaryEl = sem === 1 ? semLockSummary : semLockSummary2;
            const statusEl = sem === 1 ? sem1CardStatus : sem2CardStatus;

            if (summaryEl) {
                summaryEl.className = 'status-line ' + (isLocked ? 'is-locked' : 'is-open');
                summaryEl.innerHTML = (sem === 1 ? 'First semester: ' : 'Second semester: ') + '<strong>' + (isLocked ? 'Locked' : 'Open') + '</strong>' + (formattedRange ? '<br>' + formattedRange : '');
            }
            if (statusEl) {
                statusEl.textContent = isLocked
                    ? ('Active ' + (formattedRange || ''))
                    : (sem === 1 ? 'First semester is currently open.' : 'Second semester is currently open.');
            }
        }

        setEnrollmentCard(
            <?php echo json_encode($currentEnrollment['start_datetime'] ?? ''); ?>,
            <?php echo json_encode($currentEnrollment['end_datetime'] ?? ''); ?>
        );
        setSemCard(1, <?php echo $semesterLocks[1]['is_locked'] ? 'true' : 'false'; ?>, <?php echo json_encode($semesterLocks[1]['lock_start_datetime'] ?? ''); ?>, <?php echo json_encode($semesterLocks[1]['lock_end_datetime'] ?? ''); ?>);
        setSemCard(2, <?php echo $semesterLocks[2]['is_locked'] ? 'true' : 'false'; ?>, <?php echo json_encode($semesterLocks[2]['lock_start_datetime'] ?? ''); ?>, <?php echo json_encode($semesterLocks[2]['lock_end_datetime'] ?? ''); ?>);
    </script>
</body>
</html>
