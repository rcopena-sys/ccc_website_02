<?php
session_start();

// Check if user is logged in and is an admin (role_id = 2, 10, or 11)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 10, 11])) {
    header("Location: ../index.php");
    exit();
}


require_once '../db_connect.php';

if (!($conn instanceof mysqli)) {
  throw new RuntimeException('Expected mysqli connection in dashboard2.php');
}

/** @var mysqli $conn */

// Get unread count (NOW USING HOSTINGER CONNECTION)
$unread_count = 0;

if (!$conn->connect_error) {
    $role_id = $_SESSION['role_id'];
    $recipient_type = ($role_id == 2) ? 'registrar' : 'dean';

    $query = "SELECT COUNT(*) as count FROM messages_db WHERE recipient_type = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $recipient_type);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $unread_count = (int)$row['count'];
            }
        }
        $stmt->close();
    }
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

// Get the selected fiscal year from the URL, if any
$selected_fiscal_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : null;

// Prepare the WHERE clause for filtering
$where_clause = '';
if ($selected_fiscal_year) {
    $escaped_year = $conn->real_escape_string($selected_fiscal_year);
    $where_clause = " WHERE fiscal_year = '$escaped_year'";
}

// Get a list of all distinct fiscal years for the dropdown menu
$fiscal_years_list = [];
$fiscal_year_result = $conn->query("SELECT DISTINCT fiscal_year FROM students_db WHERE fiscal_year IS NOT NULL AND fiscal_year != '' ORDER BY fiscal_year DESC");
if ($fiscal_year_result) {
    while ($row = $fiscal_year_result->fetch_assoc()) {
        $fiscal_years_list[] = $row['fiscal_year'];
    }
}

// Initialize variables with default values
$feedback_count = 0;
$bscs_count = 0;
$bsit_count = 0;
$total_students = 0;
$curriculum_count = 0;
$regular_count = 0;
$irregular_count = 0;
$gender_counts = ['Male' => 0, 'Female' => 0];
$academic_years = [];
$year_level_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
$enrolled_per_year_sem = [];
$program_enrollment = [];
$total_population = 0;

try {
    // Check database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // 1. Get feedback count
    $feedback_result = $conn->query("SELECT COUNT(*) as total FROM feedback_db");
    if ($feedback_result) {
        $feedback_count = $feedback_result->fetch_assoc()['total'];
    }

    // 2. Get program-wise counts from both students_db and signin_db
    $bscs_count = 0;
    $bsit_count = 0;
    
    // Count from students_db
    $program_result = $conn->query("SELECT programs, COUNT(*) as total FROM students_db " . ($where_clause ? $where_clause . " AND programs IN ('BSCS', 'BSIT')" : " WHERE programs IN ('BSCS', 'BSIT')") . " GROUP BY programs");
    if ($program_result) {
        while ($row = $program_result->fetch_assoc()) {
            if ($row['programs'] === 'BSCS') {
                $bscs_count += (int)$row['total'];
            } elseif ($row['programs'] === 'BSIT') {
                $bsit_count += (int)$row['total'];
            }
        }
    }
    
    
    
    $total_students = $bscs_count + $bsit_count;

    // 3. Get curriculum count for BSIT and BSCS by fiscal year
    $curriculum_stats = [];
    $curriculum_result = $conn->query("SELECT fiscal_year, COUNT(DISTINCT program) as count 
        FROM curriculum 
        WHERE program IN ('BSIT', 'BSCS') 
        AND fiscal_year IS NOT NULL AND fiscal_year != '' 
        GROUP BY fiscal_year 
        ORDER BY fiscal_year");
    if ($curriculum_result && $curriculum_result->num_rows > 0) {
        while ($row = $curriculum_result->fetch_assoc()) {
            $curriculum_stats[] = $row;
        }
        $curriculum_count = array_sum(array_column($curriculum_stats, 'count'));
    } else {
        // Fallback to total distinct BSIT and BSCS programs
        $curriculum_fallback = $conn->query("SELECT COUNT(DISTINCT program) as count 
            FROM curriculum 
            WHERE program IN ('BSIT', 'BSCS')");
        if ($curriculum_fallback) {
            $curriculum_count = (int)$curriculum_fallback->fetch_assoc()['count'];
        } else {
            $curriculum_count = 0;
        }
    }

    // 4. Get enrollment by year and semester
    $enrollment_result = $conn->query("SELECT academic_year, semester, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY academic_year, semester ORDER BY academic_year, semester");
    if ($enrollment_result) {
        while ($row = $enrollment_result->fetch_assoc()) {
            $enrolled_per_year_sem[] = [
                'academic_year' => htmlspecialchars($row['academic_year']),
                'semester' => htmlspecialchars($row['semester']),
                'total' => (int)$row['total']
            ];
        }
    }

    // 5. Get total population and classification counts from both databases
    $total_population = 0;
    $regular_count = 0;
    $irregular_count = 0;

    // Get total population from students_db
    $total_result = $conn->query("SELECT COUNT(*) as total FROM students_db" . $where_clause);
    if ($total_result) {
        $total_population += (int)$total_result->fetch_assoc()['total'];
    }
    
    // Get total population from signin_db (BSCS and BSIT only)
    $signin_total_result = $conn->query("SELECT COUNT(*) as total FROM signin_db s LEFT JOIN roles r ON s.role_id = r.role_id WHERE r.role_name IN ('BSCS', 'BSIT')");
    if ($signin_total_result) {
        $total_population += (int)$signin_total_result->fetch_assoc()['total'];
    }
    
    // Get regular/irregular counts from students_db
    $classification_result = $conn->query("SELECT 
        SUM(CASE WHEN LOWER(classification) = 'regular' THEN 1 ELSE 0 END) as regular_count,
        SUM(CASE WHEN LOWER(classification) = 'irregular' THEN 1 ELSE 0 END) as irregular_count
        FROM students_db" . $where_clause);
        
    if ($classification_result && $row = $classification_result->fetch_assoc()) {
        $regular_count = (int)$row['regular_count'];
        $irregular_count = (int)$row['irregular_count'];
    }
    
    // Get regular/irregular counts from signin_db
   
        
    
    // 6. Get gender breakdown
    $gender_result = $conn->query("SELECT LOWER(gender) as gender, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY gender");
    if ($gender_result) {
        while ($row = $gender_result->fetch_assoc()) {
            $gender = ucfirst(strtolower($row['gender']));
            $gender_counts[$gender] = (int)$row['total'];
        }
    }

    // 7. Get academic year breakdown
    $year_result = $conn->query("SELECT academic_year, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY academic_year ORDER BY academic_year");
    if ($year_result) {
        while ($row = $year_result->fetch_assoc()) {
            $academic_years[htmlspecialchars($row['academic_year'])] = (int)$row['total'];
        }
    }

    // 8. Get total population (count distinct student IDs to avoid duplicates)
    $total_result = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM students_db" . $where_clause);
    if ($total_result) {
        $total_population = (int)$total_result->fetch_assoc()['total'];
    }

    // 9. Get fiscal year count
    $fiscal_year_count = 0;
    $fiscal_year_result = $conn->query("SELECT COUNT(DISTINCT fiscal_year) as total FROM students_db");
    if ($fiscal_year_result) {
        $fiscal_year_count = (int)$fiscal_year_result->fetch_assoc()['total'];
    }

    // 10. Get program enrollment for chart
    $program_enrollment_result = $conn->query("SELECT programs, COUNT(*) as count FROM students_db" . $where_clause . " GROUP BY programs");
    if ($program_enrollment_result) {
        while ($row = $program_enrollment_result->fetch_assoc()) {
            $program_enrollment[] = [
                'program' => htmlspecialchars($row['programs']),
                'count' => (int)$row['count']
            ];
        }
    }

} catch (Exception $e) {
    // Log the error (you might want to log this to a file in production)
    error_log("Database error: " . $e->getMessage());
    
    // Set a user-friendly error message
    $error_message = "Error loading dashboard data. Please try again later.";
}

// Total students is already calculated in the try block as $total_population
// and $bscs_count + $bsit_count = $total_students

// By curriculum
$curriculum = [];
$res = $conn->query("SELECT curriculum, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY curriculum");
while ($row = $res->fetch_assoc()) {
    $curriculum[$row['curriculum']] = $row['total'];
}

// By classification
$classifications = [];
$res = $conn->query("SELECT classification, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY classification");
while ($row = $res->fetch_assoc()) {
    $classifications[$row['classification']] = $row['total'];
}



// By programs
$programs = [];
$res = $conn->query("SELECT programs, COUNT(*) as total FROM students_db" . $where_clause . " GROUP BY programs");
while ($row = $res->fetch_assoc()) {
    $programs[$row['programs']] = $row['total'];
}

// Get 1st-4th year student counts from either academic_year or year_level
$yearLevelColumn = null;
$yearLevelColumnCheck = $conn->query("SHOW COLUMNS FROM students_db LIKE 'academic_year'");
if ($yearLevelColumnCheck && $yearLevelColumnCheck->num_rows > 0) {
  $yearLevelColumn = 'academic_year';
} else {
  $yearLevelColumnCheck = $conn->query("SHOW COLUMNS FROM students_db LIKE 'year_level'");
  if ($yearLevelColumnCheck && $yearLevelColumnCheck->num_rows > 0) {
    $yearLevelColumn = 'year_level';
  }
}

if ($yearLevelColumn !== null) {
  $yearLevelQuery = "SELECT {$yearLevelColumn} AS year_value FROM students_db" . $where_clause;
  $yearLevelResult = $conn->query($yearLevelQuery);
  if ($yearLevelResult) {
    while ($row = $yearLevelResult->fetch_assoc()) {
      $rawYear = trim((string)($row['year_value'] ?? ''));
      if ($rawYear === '') {
        continue;
      }

      $normalizedYear = strtolower($rawYear);
      if (preg_match('/\b(1|1st)\b/', $normalizedYear)) {
        $year_level_counts[1]++;
      } elseif (preg_match('/\b(2|2nd)\b/', $normalizedYear)) {
        $year_level_counts[2]++;
      } elseif (preg_match('/\b(3|3rd)\b/', $normalizedYear)) {
        $year_level_counts[3]++;
      } elseif (preg_match('/\b(4|4th)\b/', $normalizedYear)) {
        $year_level_counts[4]++;
      }
    }
  }
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Dean Dashboard</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --bg: #eef3fb;
      --surface: rgba(255, 255, 255, 0.92);
      --surface-strong: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --line: rgba(226, 232, 240, 0.9);
      --blue: #2563eb;
      --blue-soft: rgba(37, 99, 235, 0.12);
      --green: #059669;
      --orange: #f97316;
      --shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }

    html, body {
      min-height: 100%;
    }

    body {
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 28%),
        radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 22%),
        var(--bg);
    }

    #clock {
      font-family: 'Arial', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      color: var(--muted);
    }

    .dashboard-main {
      min-height: 100vh;
      padding: 18px;
    }

    .dashboard-shell {
      max-width: 1220px;
      margin: 0 auto;
      display: grid;
      gap: 18px;
    }

    .topbar {
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 26px;
      padding: 18px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(14px);
    }

    .topbar h1 {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.02em;
    }

    .topbar p {
      margin-top: 4px;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .pill-action {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 999px;
      background: #fff;
      border: 1px solid var(--line);
      color: var(--muted);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
      text-decoration: none;
      white-space: nowrap;
    }

    .pill-action strong {
      color: var(--text);
    }

    .metric-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .metric-card,
    .panel-card,
    .mini-card {
      background: var(--surface);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      border-radius: 24px;
    }

    .metric-card {
      padding: 18px;
      min-height: 122px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }

    .metric-card::after {
      content: '';
      position: absolute;
      right: -24px;
      bottom: -24px;
      width: 92px;
      height: 92px;
      border-radius: 50%;
      background: rgba(37, 99, 235, 0.06);
    }

    .metric-label {
      font-size: 0.83rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      color: #64748b;
      text-transform: uppercase;
    }

    .metric-value {
      font-size: 2rem;
      font-weight: 800;
      color: var(--text);
      line-height: 1;
      margin-top: 8px;
      position: relative;
      z-index: 1;
    }

    .metric-note {
      color: var(--muted);
      font-size: 0.9rem;
      line-height: 1.35;
      position: relative;
      z-index: 1;
    }

    .metric-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(37, 99, 235, 0.09);
      color: var(--blue);
      align-self: flex-end;
      position: relative;
      z-index: 1;
    }

    .metric-card.highlight .metric-icon {
      background: rgba(5, 150, 105, 0.10);
      color: var(--green);
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: 1.35fr 1fr;
      gap: 16px;
      align-items: start;
    }

    .panel-card {
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
      margin-top: 4px;
      font-size: 0.9rem;
      color: var(--muted);
    }

    .panel-badge {
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(5, 150, 105, 0.10);
      color: var(--green);
      font-size: 0.82rem;
      font-weight: 800;
      white-space: nowrap;
    }

    .chart-frame {
      width: 100%;
      height: 410px;
      position: relative;
    }

    .chart-frame.small {
      height: 250px;
    }

    .right-stack {
      display: grid;
      gap: 16px;
    }

    .mini-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .mini-card {
      padding: 16px;
    }

    .mini-card h3 {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 6px;
    }

    .mini-card p {
      color: var(--muted);
      font-size: 0.9rem;
      line-height: 1.45;
    }

    .stat-line {
      margin-top: 12px;
      padding: 10px 12px;
      border-radius: 14px;
      background: #f8fafc;
      border: 1px solid var(--line);
      color: #334155;
      font-size: 0.9rem;
    }

    .stat-line strong {
      color: var(--text);
    }

    .stat-line.open {
      background: rgba(5, 150, 105, 0.08);
      border-color: rgba(5, 150, 105, 0.18);
      color: #047857;
    }

    .stat-line.notice {
      background: rgba(37, 99, 235, 0.08);
      border-color: rgba(37, 99, 235, 0.18);
      color: #1d4ed8;
    }

    .dashboard-bg-card {
      background: rgba(255, 255, 255, 0.72);
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 30px;
      padding: 22px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(16px);
    }

    @media (max-width: 1100px) {
      .metric-grid,
      .mini-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .dashboard-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 700px) {
      .dashboard-main {
        padding: 12px;
      }

      .topbar {
        padding: 16px;
        border-radius: 22px;
        flex-direction: column;
        align-items: flex-start;
      }

      .metric-grid,
      .mini-grid {
        grid-template-columns: 1fr;
      }

      .chart-frame {
        height: 320px;
      }
    }
  </style>
</head>
<body>
  <div class="flex min-h-screen">
  <!-- Sidebar -->
 <aside id="sidebar" class="w-64 bg-blue-600 text-white p-6 flex flex-col transform transition-transform duration-300 ease-in-out">
    <div class="flex flex-col items-center">
      <img src="dci.png.png" class="rounded-full border-4 border-white" alt="DCI Logo" />
      <h2 class="mt-4 font-semibold text-lg">DCI</h2>
      <p class="text-sm text-gray-200 mb-6">Dean</p>
    </div>
    <nav class="space-y-4 mt-4">
      <a href="dashboard2.php" class="block py-2 px-4 rounded bg-blue-500">Dashboard</a>
      <a href="profile.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">
        <i class="fas fa-user mr-2"></i> Profile
      </a>
      <!-- <a href="feedback.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">Feedback</a> -->
      <a href="calendean.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">Calendar of Events</a>
      <a href="about_us.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">
        <i class="fas fa-info-circle mr-2"></i> About Us
      </a>
      <!-- Student Dropdown -->
      <div class="relative">
        <button id="studentDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Student
          <svg id="dropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="studentDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
          <a href="list.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t last:rounded-b">Student List</a>
          <a href="stugra.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">Student Grade</a>
          <a href="stucuri.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">Student Curriculum</a>
        </div>
      </div>
      
      <a href="stueval.php" class="block py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 transition-colors duration-200">Evaluation</a>
      
      <!-- Curriculum Dropdown -->
      <div class="relative">
        <button id="curriculumDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Curriculum
          <svg id="curriculumDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="curriculumDropdownMenu" class="hidden absolute left-0 w-full z-10 flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
          <a href="curi_it.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t">BSIT</a>
          <a href="curi_cs.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">BSCS</a>
        </div>
      </div>

      <!-- Fiscal Year Dropdown -->
      <div class="relative">
        <button id="fiscalYearDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Fiscal Year
          <svg id="fiscalYearDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="fiscalYearDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
          <a href="dashboard2.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">All Years</a>
          <?php foreach ($fiscal_years_list as $year): ?>
            <a href="?fiscal_year=<?php echo urlencode($year); ?>" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 last:border-b-0"><?php echo htmlspecialchars($year); ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </nav>
    <div class="mt-auto">
      <a href="../logout.php" class="block py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600 text-center">
        Logout
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-main flex-1">
    <div class="dashboard-shell">
      <section class="topbar">
        <div>
          <h1>Dean Dashboard</h1>
          <p>Department of Computing and Informatics</p>
        </div>
        <div class="topbar-actions">
          <div class="pill-action">
            <i class="fas fa-sync-alt"></i>
            <span>Data refreshed at <?php echo date('M d, Y h:i A'); ?></span>
          </div>
          <a href="notification_page.php" class="pill-action relative">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unread_count > 0): ?>
              <strong class="ml-1 text-red-500"><?php echo $unread_count; ?></strong>
            <?php endif; ?>
          </a>
          <button type="button" class="pill-action" aria-label="More options">
            <i class="fas fa-ellipsis-h"></i>
          </button>
        </div>
      </section>

      <section class="metric-grid">
        <div class="metric-card highlight">
          <div class="metric-label">Total Students</div>
          <div class="metric-value"><?php echo number_format($total_students); ?></div>
          <div class="metric-note">Combined BSIT and BSCS student count.</div>
          <div class="metric-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Curriculum</div>
          <div class="metric-value"><?php echo number_format($curriculum_count); ?></div>
          <div class="metric-note">Curriculum entries in operation.</div>
          <div class="metric-icon"><i class="fas fa-book-open"></i></div>
        </div>
        <a href="list.php?program=BSIT" class="metric-card block no-underline text-inherit">
          <div class="metric-label">BSIT</div>
          <div class="metric-value"><?php echo number_format($bsit_count); ?></div>
          <div class="metric-note">Students enrolled in BSIT.</div>
          <div class="metric-icon"><i class="fas fa-laptop-code"></i></div>
        </a>
        <a href="list.php?program=BSCS" class="metric-card block no-underline text-inherit">
          <div class="metric-label">BSCS</div>
          <div class="metric-value"><?php echo number_format($bscs_count); ?></div>
          <div class="metric-note">Students enrolled in BSCS.</div>
          <div class="metric-icon"><i class="fas fa-microchip"></i></div>
        </a>
        <a href="regularstu.php" class="metric-card highlight block no-underline text-inherit">
          <div class="metric-label">Regular Students</div>
          <div class="metric-value"><?php echo number_format($regular_count); ?></div>
          <div class="metric-note">Students classified as regular.</div>
          <div class="metric-icon"><i class="fas fa-user-check"></i></div>
        </a>
        <a href="irregularstu.php" class="metric-card block no-underline text-inherit">
          <div class="metric-label">Irregular Students</div>
          <div class="metric-value"><?php echo number_format($irregular_count); ?></div>
          <div class="metric-note">Students classified as irregular.</div>
          <div class="metric-icon"><i class="fas fa-user-clock"></i></div>
        </a>
      </section>

      <section class="dashboard-grid">
        <div class="panel-card">
          <div class="panel-header">
            <div>
              <div class="panel-title">Student Population by Program</div>
              <div class="panel-subtitle">Distribution of BSIT, BSCS, and total students.</div>
            </div>
            <div class="panel-badge">Interactive</div>
          </div>
          <div class="chart-frame">
            <canvas id="programChart"></canvas>
          </div>
        </div>

        <div class="right-stack">
          <div class="panel-card">
            <div class="panel-header">
              <div>
                <div class="panel-title">Classification Distribution</div>
                <div class="panel-subtitle">Regular versus irregular student count.</div>
              </div>
              <div class="panel-badge">Live</div>
            </div>
            <div class="chart-frame small">
              <canvas id="classificationChart"></canvas>
            </div>
          </div>

          <div class="mini-grid">
            <div class="mini-card">
              <h3>Gender Distribution</h3>
              <p>Breakdown of student gender count in the current filter scope.</p>
              <div class="chart-frame small">
                <canvas id="genderPieChart"></canvas>
              </div>
            </div>
            <div class="mini-card">
              <h3>Academic Summary</h3>
              <p>Quick figures for the current dashboard context.</p>
              <div class="stat-line notice"><strong>Total Population:</strong> <?php echo number_format($total_population); ?></div>
              <div class="stat-line open"><strong>Fiscal Years:</strong> <?php echo number_format($fiscal_year_count); ?></div>
              <div class="stat-line"><strong>Feedback:</strong> <?php echo number_format($feedback_count); ?></div>
              <div class="stat-line" style="margin-bottom:0;"><strong>Scope:</strong> <?php echo $selected_fiscal_year ? htmlspecialchars($selected_fiscal_year) : 'All Years'; ?></div>
            </div>
          </div>
        </div>
      </section>

      <section class="panel-card">
        <div class="panel-header">
          <div>
            <div class="panel-title">Year Level Breakdown</div>
            <div class="panel-subtitle">Counts of 1st, 2nd, 3rd, and 4th year students.</div>
          </div>
          <div class="panel-badge">Overview</div>
        </div>
        <div class="chart-frame">
          <canvas id="yearLevelChart"></canvas>
        </div>
      </section>
    </div>
  </main>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: true
        });

        const clock = document.getElementById('clock');
        if (clock) {
          clock.textContent = timeString;
        }
      }

      updateClock();
      setInterval(updateClock, 1000);

      const studentBtn = document.getElementById('studentDropdownBtn');
      const studentMenu = document.getElementById('studentDropdownMenu');
      const studentIcon = document.getElementById('dropdownIcon');

      if (studentBtn && studentMenu && studentIcon) {
        studentBtn.addEventListener('click', function (event) {
          event.preventDefault();
          studentMenu.classList.toggle('hidden');
          studentIcon.classList.toggle('rotate-180');
        });
      }

      const curriculumBtn = document.getElementById('curriculumDropdownBtn');
      const curriculumMenu = document.getElementById('curriculumDropdownMenu');
      const curriculumIcon = document.getElementById('curriculumDropdownIcon');

      if (curriculumBtn && curriculumMenu && curriculumIcon) {
        curriculumBtn.addEventListener('click', function (event) {
          event.preventDefault();
          curriculumMenu.classList.toggle('hidden');
          curriculumIcon.classList.toggle('rotate-180');
        });
      }

      const fiscalYearBtn = document.getElementById('fiscalYearDropdownBtn');
      const fiscalYearMenu = document.getElementById('fiscalYearDropdownMenu');
      const fiscalYearIcon = document.getElementById('fiscalYearDropdownIcon');

      if (fiscalYearBtn && fiscalYearMenu && fiscalYearIcon) {
        fiscalYearBtn.addEventListener('click', function (event) {
          event.preventDefault();
          fiscalYearMenu.classList.toggle('hidden');
          fiscalYearIcon.classList.toggle('rotate-180');
        });
      }

      document.addEventListener('click', function (event) {
        if (studentBtn && studentMenu && !studentBtn.contains(event.target) && !studentMenu.contains(event.target)) {
          studentMenu.classList.add('hidden');
          studentIcon.classList.remove('rotate-180');
        }

        if (curriculumBtn && curriculumMenu && !curriculumBtn.contains(event.target) && !curriculumMenu.contains(event.target)) {
          curriculumMenu.classList.add('hidden');
          curriculumIcon.classList.remove('rotate-180');
        }

        if (fiscalYearBtn && fiscalYearMenu && !fiscalYearBtn.contains(event.target) && !fiscalYearMenu.contains(event.target)) {
          fiscalYearMenu.classList.add('hidden');
          fiscalYearIcon.classList.remove('rotate-180');
        }
      });

      const programCanvas = document.getElementById('programChart');
      if (programCanvas) {
        new Chart(programCanvas, {
          type: 'bar',
          data: {
            labels: ['BSIT', 'BSCS', 'Total Students'],
            datasets: [{
              label: 'Students',
              data: [<?php echo (int)$bsit_count; ?>, <?php echo (int)$bscs_count; ?>, <?php echo (int)$total_students; ?>],
              backgroundColor: ['rgba(37, 99, 235, 0.82)', 'rgba(16, 185, 129, 0.82)', 'rgba(139, 92, 246, 0.82)'],
              borderRadius: 10,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { precision: 0 }
              }
            }
          }
        });
      }

      const classificationCanvas = document.getElementById('classificationChart');
      if (classificationCanvas) {
        new Chart(classificationCanvas, {
          type: 'bar',
          data: {
            labels: ['Regular', 'Irregular'],
            datasets: [{
              label: 'Students',
              data: [<?php echo (int)$regular_count; ?>, <?php echo (int)$irregular_count; ?>],
              backgroundColor: ['rgba(5, 150, 105, 0.82)', 'rgba(249, 115, 22, 0.82)'],
              borderRadius: 10,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { precision: 0 }
              }
            }
          }
        });
      }

      const genderCanvas = document.getElementById('genderPieChart');
      if (genderCanvas) {
        new Chart(genderCanvas, {
          type: 'doughnut',
          data: {
            labels: ['Male', 'Female'],
            datasets: [{
              data: [<?php echo (int)$gender_counts['Male']; ?>, <?php echo (int)$gender_counts['Female']; ?>],
              backgroundColor: ['rgba(37, 99, 235, 0.82)', 'rgba(244, 114, 182, 0.82)'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }

      const yearLevelCanvas = document.getElementById('yearLevelChart');
      if (yearLevelCanvas) {
        const yearLevelLabels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        const yearLevelCounts = [
          <?php echo (int)$year_level_counts[1]; ?>,
          <?php echo (int)$year_level_counts[2]; ?>,
          <?php echo (int)$year_level_counts[3]; ?>,
          <?php echo (int)$year_level_counts[4]; ?>
        ];

        new Chart(yearLevelCanvas, {
          type: 'bar',
          data: {
            labels: yearLevelLabels,
            datasets: [{
              label: 'Students',
              data: yearLevelCounts,
              backgroundColor: 'rgba(96, 165, 250, 0.82)',
              borderRadius: 10,
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { precision: 0 }
              }
            }
          }
        });
      }
    });
  </script>
</body>
</html>