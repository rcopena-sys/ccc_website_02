<?php
session_start();

// Check if user is logged in and is an admin (role_id = 2, 10, or 11)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [2, 10, 11])) {
  header("Location: ../index.php");
  exit();
}


require_once '../db_connect.php';
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
/** @var mysqli_stmt|false $stmt */
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

    // 2. Get program-wise counts for Teacher Education programs only
    $bse_english_count = 0;
    $beed_count = 0;
    $bse_math_count = 0;
    $bse_science_count = 0;

    $teProgramFilter = "programs IN ('Bachelor Of Elementary Education', 'Bachelor Of Secondary Education Major In English', 'Bachelor Of Secondary Education Major In Mathematics', 'Bachelor Of Secondary Education Major In Science')";

    // Count from students_db for each Teacher Education program
    $program_result = $conn->query("SELECT programs, COUNT(*) as total FROM students_db " . ($where_clause ? $where_clause . " AND " . $teProgramFilter : " WHERE " . $teProgramFilter) . " GROUP BY programs");
    if ($program_result) {
      while ($row = $program_result->fetch_assoc()) {
        switch ($row['programs']) {
          case 'Bachelor Of Elementary Education':
            $beed_count += (int)$row['total'];
            break;
          case 'Bachelor Of Secondary Education Major In English':
            $bse_english_count += (int)$row['total'];
            break;
          case 'Bachelor Of Secondary Education Major In Mathematics':
            $bse_math_count += (int)$row['total'];
            break;
          case 'Bachelor Of Secondary Education Major In Science':
            $bse_science_count += (int)$row['total'];
            break;
        }
      }
    }

    $total_students = $beed_count + $bse_english_count + $bse_math_count + $bse_science_count;

    // 3. Get curriculum count for Teacher Education programs by fiscal year
    // Count distinct (program, fiscal_year) combinations for the four DTE programs
    $curriculum_count = 0;

    if ($selected_fiscal_year) {
      // For a specific fiscal year, count how many TE programs have curriculum rows
      // Support both legacy long names and new short codes (BEE, BSEME, BSEMM, BSEMS)
      $curriculum_sql = "SELECT COUNT(DISTINCT program) AS count
        FROM curriculum
        WHERE program IN (
          'Bachelor Of Elementary Education',
          'Bachelor Of Secondary Education Major In English',
          'Bachelor Of Secondary Education Major In Mathematics',
          'Bachelor Of Secondary Education Major In Science',
          'BEE', 'BSEME', 'BSEMM', 'BSEMS'
        )
        AND fiscal_year = '" . $escaped_year . "'";

      $curriculum_result = $conn->query($curriculum_sql);
      if ($curriculum_result && $row = $curriculum_result->fetch_assoc()) {
        $curriculum_count = (int)$row['count'];
      }
    } else {
      // Across all years, sum distinct TE programs per fiscal year
      // Support both legacy long names and new short codes (BEE, BSEME, BSEMM, BSEMS)
      $curriculum_stats = [];
      $curriculum_result = $conn->query("SELECT fiscal_year, COUNT(DISTINCT program) AS count
        FROM curriculum
        WHERE program IN (
          'Bachelor Of Elementary Education',
          'Bachelor Of Secondary Education Major In English',
          'Bachelor Of Secondary Education Major In Mathematics',
          'Bachelor Of Secondary Education Major In Science',
          'BEE', 'BSEME', 'BSEMM', 'BSEMS'
        )
        AND fiscal_year IS NOT NULL AND fiscal_year != ''
        GROUP BY fiscal_year
        ORDER BY fiscal_year");

      if ($curriculum_result && $curriculum_result->num_rows > 0) {
        while ($row = $curriculum_result->fetch_assoc()) {
          $curriculum_stats[] = $row;
        }
        $curriculum_count = array_sum(array_column($curriculum_stats, 'count'));
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

    // Base WHERE for Teacher Education programs
    $teWhere = $where_clause ? $where_clause . " AND " . $teProgramFilter : " WHERE " . $teProgramFilter;

    // Get total population from students_db (Teacher Education programs only)
    $total_result = $conn->query("SELECT COUNT(*) as total FROM students_db" . $teWhere);
    if ($total_result) {
        $total_population += (int)$total_result->fetch_assoc()['total'];
    }
    
    // Get regular/irregular counts from students_db (Teacher Education programs only)
    $classification_result = $conn->query("SELECT 
        SUM(CASE WHEN LOWER(classification) = 'regular' THEN 1 ELSE 0 END) as regular_count,
        SUM(CASE WHEN LOWER(classification) = 'irregular' THEN 1 ELSE 0 END) as irregular_count
      FROM students_db" . $teWhere);
        
    if ($classification_result && $row = $classification_result->fetch_assoc()) {
        $regular_count = (int)$row['regular_count'];
        $irregular_count = (int)$row['irregular_count'];
    }
    
    // Get regular/irregular counts from signin_db
   
        
    
    // 6. Get gender breakdown (Teacher Education programs only)
    $gender_result = $conn->query("SELECT LOWER(gender) as gender, COUNT(*) as total FROM students_db" . $teWhere . " GROUP BY gender");
    if ($gender_result) {
        while ($row = $gender_result->fetch_assoc()) {
            $gender = ucfirst(strtolower($row['gender']));
            $gender_counts[$gender] = (int)$row['total'];
        }
    }

    // 7. Get academic year breakdown (Teacher Education programs only)
    $year_result = $conn->query("SELECT academic_year, COUNT(*) as total FROM students_db" . $teWhere . " GROUP BY academic_year ORDER BY academic_year");
    if ($year_result) {
        while ($row = $year_result->fetch_assoc()) {
            $academic_years[htmlspecialchars($row['academic_year'])] = (int)$row['total'];
        }
    }

    // 8. Get total population (count distinct student IDs to avoid duplicates, Teacher Education only)
    $total_result = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM students_db" . $teWhere);
    if ($total_result) {
        $total_population = (int)$total_result->fetch_assoc()['total'];
    }

    // 9. Get fiscal year count
    $fiscal_year_count = 0;
    $fiscal_year_result = $conn->query("SELECT COUNT(DISTINCT fiscal_year) as total FROM students_db");
    if ($fiscal_year_result) {
        $fiscal_year_count = (int)$fiscal_year_result->fetch_assoc()['total'];
    }

    // 10. Get program enrollment for chart (Teacher Education programs only)
    $program_enrollment_result = $conn->query("SELECT programs, COUNT(*) as count FROM students_db" . $teWhere . " GROUP BY programs");
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

// By curriculum (Teacher Education programs only)
$curriculum = [];
$res = $conn->query("SELECT curriculum, COUNT(*) as total FROM students_db" . ($where_clause ? $where_clause . " AND " . $teProgramFilter : " WHERE " . $teProgramFilter) . " GROUP BY curriculum");
while ($row = $res->fetch_assoc()) {
    $curriculum[$row['curriculum']] = $row['total'];
}

// By classification (Teacher Education programs only)
$classifications = [];
$res = $conn->query("SELECT classification, COUNT(*) as total FROM students_db" . ($where_clause ? $where_clause . " AND " . $teProgramFilter : " WHERE " . $teProgramFilter) . " GROUP BY classification");
while ($row = $res->fetch_assoc()) {
    $classifications[$row['classification']] = $row['total'];
}



// By programs (Teacher Education programs only)
$programs = [];
$res = $conn->query("SELECT programs, COUNT(*) as total FROM students_db" . ($where_clause ? $where_clause . " AND " . $teProgramFilter : " WHERE " . $teProgramFilter) . " GROUP BY programs");
while ($row = $res->fetch_assoc()) {
    $programs[$row['programs']] = $row['total'];
}

// Get year-level and classification breakdown (using academic_year column)
$year_levels = ['1st', '2nd', '3rd', '4th'];
$year_level_data = [];
// Map display labels to stored academic_year values
$year_value_map = [
  '1st' => '1',
  '2nd' => '2',
  '3rd' => '3',
  '4th' => '4',
];

foreach ($year_levels as $level) {
  $year_level_data[$level] = ['Regular' => 0, 'Irregular' => 0];
  $dbYearValue = $conn->real_escape_string($year_value_map[$level] ?? $level);
  // Use the same Teacher Education program filter and break down by academic_year
  $yearLevelWhere = $teWhere . " AND academic_year = '" . $dbYearValue . "'";
  $res = $conn->query("SELECT classification, COUNT(*) as total FROM students_db" . $yearLevelWhere . " GROUP BY classification");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $classification = ucfirst(strtolower($row['classification'] ?? ''));
      if (isset($year_level_data[$level][$classification])) {
        $year_level_data[$level][$classification] = (int)$row['total'];
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
  <link rel="stylesheet" href="../dashboard-theme.css">
  <style>
    #clock {
      font-family: 'Arial', sans-serif;
      font-size: 1.2rem;
      font-weight: bold;
    }
    </style>
  </head>
  <body class="flex bg-blue-100 min-h-screen">
  <!-- Sidebar Toggle Button -->
  <button id="sidebarToggle" class="fixed top-4 left-4 z-50 bg-transparent p-2 focus:outline-none hover:bg-white hover:bg-opacity-20 rounded transition-colors">
    <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
    </svg>
  </button>

  <!-- User Profile and Navigation -->
  <div class="fixed top-4 right-4 flex items-center space-x-4">
    <!-- Notification Bell -->
    <div class="relative">
      <a href="notification_page.php" class="text-blue-700 hover:text-blue-900 focus:outline-none relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <?php if ($unread_count > 0): ?>
          <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
            <?php echo $unread_count; ?>
          </span>
        <?php endif; ?>
      </a>
    </div>
    <div class="relative group">
      <button class="flex items-center space-x-2 focus:outline-none">
        <img src="<?php echo !empty($user['profile_image']) ? 'uploads/' . htmlspecialchars($user['profile_image']) : 'default-avatar.png'; ?>" 
               alt="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>"
               class="w-10 h-10 rounded-full border-2 border-blue-500">
             <span class="text-blue-800 font-medium"><?php echo htmlspecialchars($user['firstname']); ?></span>
             <i class="fas fa-chevron-down text-blue-700"></i>
      </button>
      <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
          <i class="fas fa-user mr-2"></i> Profile
        </a>
        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
          <i class="fas fa-cog mr-2"></i> Settings
        </a>
        <a href="../logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    
      </div>
    </div>
  </div>
  <!-- Sidebar -->
 <aside id="sidebar" class="w-64 bg-blue-600 text-white p-6 flex flex-col transform transition-transform duration-300 ease-in-out">
    <div class="flex flex-col items-center">
      <img src="dte.png" class="rounded-full border-4 border-blue-300 bg-white" alt="DCI Logo" />
      <h2 class="mt-4 font-semibold text-lg">DTE</h2>
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
          <a href="list.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100 first:rounded-t last:rounded-b">Student List</a>
          <a href="stugra.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100">Student Grade</a>
          <a href="stucuri.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 last:rounded-b">Student Curriculum</a>
        </div>
      </div>
      
      <!-- Evaluation Dropdown -->
      <div class="relative">
        <button id="evaluationDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Evaluation
          <svg id="evaluationDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="evaluationDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
          <a href="stueval.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100 first:rounded-t">Evaluate Student</a>
          <a href="semeva.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 last:rounded-b">Semestral Evaluation</a>
        </div>
      </div>
      
      <!-- Curriculum Dropdown -->
      <div class="relative">
        <button id="curriculumDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Curriculum
          <svg id="curriculumDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="curriculumDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
		  <a href="bee.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100 first:rounded-t">Bachelor Of Elementary Education</a>
		  <a href="bseme.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100">Bachelor Of Secondary Education Major In English</a>
		  <a href="bsemm.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100">Bachelor Of Secondary Education Major In Mathematics</a>
     <a href="bsems.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 last:rounded-b">Bachelor Of Secondary Education Major In Science</a>
        
      
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
          <a href="dashboard2.php" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100">All Years</a>
          <?php foreach ($fiscal_years_list as $year): ?>
            <a href="?fiscal_year=<?php echo urlencode($year); ?>" class="py-2 px-6 text-blue-800 hover:bg-blue-50 border-b border-blue-100 last:border-b-0"><?php echo htmlspecialchars($year); ?></a>
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
      <section class="dashboard-topbar topbar">
        <div>
          <div class="dashboard-kicker">Dean Dashboard</div>
          <h1>Department of Teacher Education</h1>
          <p>Live academic snapshot for the teacher education programs.</p>
        </div>
        <div class="topbar-actions">
          <div class="pill-action">
            <i class="fas fa-sync-alt"></i>
            <span>Updated <?php echo date('M d, Y h:i A'); ?></span>
          </div>
          <a href="notification_page.php" class="pill-action relative">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unread_count > 0): ?>
              <strong class="ml-1 text-red-500"><?php echo $unread_count; ?></strong>
            <?php endif; ?>
          </a>
        </div>
      </section>

      <section class="dashboard-stats">
        <div class="metric-card">
          <div class="metric-label">Total Students</div>
          <div class="metric-value"><?php echo number_format($total_students); ?></div>
          <div class="metric-note">Combined enrollment across programs.</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Curriculum</div>
          <div class="metric-value"><?php echo number_format($curriculum_count); ?></div>
          <div class="metric-note">Curriculum entries in operation.</div>
        </div>
        <a href="list.php?program=BEED" class="metric-card link-card block no-underline text-inherit">
          <div class="metric-label">BEED</div>
          <div class="metric-value"><?php echo number_format($beed_count); ?></div>
          <div class="metric-note">Bachelor of Elementary Education.</div>
        </a>
        <a href="list.php?program=BSEME" class="metric-card link-card block no-underline text-inherit">
          <div class="metric-label">BSE English</div>
          <div class="metric-value"><?php echo number_format($bse_english_count); ?></div>
          <div class="metric-note">BSE major in English.</div>
        </a>
      </section>

      <section class="dashboard-grid">
        <div class="chart-card panel-card">
          <div class="panel-header">
            <div>
              <div class="panel-title">Program Enrollment</div>
              <div class="panel-subtitle">Enrollment distribution across the four teacher education programs.</div>
            </div>
            <div class="panel-badge">Live</div>
          </div>
          <div class="chart-frame">
            <canvas id="programChart"></canvas>
          </div>
        </div>

        <div class="right-stack">
          <div class="chart-card panel-card">
            <div class="panel-header">
              <div>
                <div class="panel-title">Classification Distribution</div>
                <div class="panel-subtitle">Regular versus irregular students.</div>
              </div>
              <div class="panel-badge">Live</div>
            </div>
            <div class="chart-frame small">
              <canvas id="statusChart"></canvas>
            </div>
          </div>

          <div class="mini-grid">
            <div class="mini-card">
              <div class="panel-title" style="margin-bottom:6px;">Gender Distribution</div>
              <div class="panel-subtitle" style="margin-bottom:12px;">Breakdown by gender.</div>
              <div class="chart-frame small">
                <canvas id="genderChart"></canvas>
              </div>
            </div>

            <div class="summary-card">
              <div class="panel-title" style="margin-bottom:12px;">Academic Summary</div>
              <div class="summary-stack">
                <div class="summary-chip"><strong>Total Population</strong><span><?php echo number_format($total_population); ?></span></div>
                <div class="summary-chip"><strong>BSE Math</strong><span><?php echo number_format($bse_math_count); ?></span></div>
                <div class="summary-chip"><strong>BSE Science</strong><span><?php echo number_format($bse_science_count); ?></span></div>
                <div class="summary-chip"><strong>Scope</strong><span><?php echo $selected_fiscal_year ? htmlspecialchars($selected_fiscal_year) : 'All Years'; ?></span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="chart-card panel-card">
        <div class="panel-header">
          <div>
            <div class="panel-title">Year Level Breakdown</div>
            <div class="panel-subtitle">Regular and irregular counts for each year level.</div>
          </div>
          <div class="panel-badge">Overview</div>
        </div>
        <div class="flex flex-wrap gap-4 justify-center">
          <?php foreach ($year_levels as $level): ?>
            <div class="bg-white p-4 rounded shadow" style="position: relative; min-width: 240px; flex: 1 1 240px;">
              <h3 class="font-semibold text-center mb-2"><?php echo $level; ?> Year</h3>
              <div class="h-48" style="position: relative;">
                <canvas id="pie_<?php echo $level; ?>"></canvas>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </main>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <!-- Feedback Notifications -->
  
  
  <style>
    /* Smooth transitions for the sidebar and main content */
    #sidebar {
      transition: transform 0.3s ease-in-out;
    }
    #sidebar.collapsed {
      transform: translateX(-100%);
    }
    main {
      transition: margin 0.3s ease-in-out;
    }
    main.expanded {
      margin-left: 0;
    }
  </style>
  
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  
  <!-- Clock Script -->
  <script>
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const mainContent = document.querySelector('main');
      
      // Toggle sidebar on button click
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
      });

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(event) {
        const isClickInside = sidebar.contains(event.target) || sidebarToggle.contains(event.target);
        if (!isClickInside && window.innerWidth <= 768) {
          sidebar.classList.add('collapsed');
          mainContent.classList.add('expanded');
        }
      });

      // Handle window resize
      function handleResize() {
        if (window.innerWidth > 768) {
          sidebar.classList.remove('collapsed');
          mainContent.classList.remove('expanded');
        } else {
          sidebar.classList.add('collapsed');
          mainContent.classList.add('expanded');
        }
      }
      
      // Initial check on load
      handleResize();
      window.addEventListener('resize', handleResize);

      // Charts are initialized in the dedicated dashboard script below.
    });

    function updateClock() {
      const now = new Date();
      const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: true 
      });
      const clockElement = document.getElementById('clock');
      if (clockElement) {
        clockElement.textContent = timeString;
      }
    }
    
    // Update clock immediately and then every second
    updateClock();
    if (document.getElementById('clock')) {
      setInterval(updateClock, 1000);
    }

    // Student Dropdown
    const btn = document.getElementById('studentDropdownBtn');
    const menu = document.getElementById('studentDropdownMenu');
    const icon = document.getElementById('dropdownIcon');
    if (btn && menu && icon) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        menu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
      });
    }

    // Evaluation Dropdown
    const evalBtn = document.getElementById('evaluationDropdownBtn');
    const evalMenu = document.getElementById('evaluationDropdownMenu');

    // Curriculum Dropdown
    const curriculumBtn = document.getElementById('curriculumDropdownBtn');
    const curriculumMenu = document.getElementById('curriculumDropdownMenu');
    const curriculumIcon = document.getElementById('curriculumDropdownIcon');

    if (curriculumBtn && curriculumMenu && curriculumIcon) {
      curriculumBtn.addEventListener('click', function(e) {
        e.preventDefault();
        curriculumMenu.classList.toggle('hidden');
        curriculumIcon.classList.toggle('rotate-180');
      });
    }

    // Fiscal Year Dropdown
    const fiscalYearBtn = document.getElementById('fiscalYearDropdownBtn');
    const fiscalYearMenu = document.getElementById('fiscalYearDropdownMenu');
    const fiscalYearIcon = document.getElementById('fiscalYearDropdownIcon');

    if (fiscalYearBtn && fiscalYearMenu && fiscalYearIcon) {
      fiscalYearBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fiscalYearMenu.classList.toggle('hidden');
        fiscalYearIcon.classList.toggle('rotate-180');
      });
    }
    const evalIcon = document.getElementById('evaluationDropdownIcon');
    
    if (evalBtn && evalMenu && evalIcon) {
      evalBtn.addEventListener('click', function(e) {
        e.preventDefault();
        evalMenu.classList.toggle('hidden');
        evalIcon.classList.toggle('rotate-180');
      });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
      if (btn && menu && !btn.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.add('hidden');
        icon.classList.remove('rotate-180');
      }
      if (evalBtn && evalMenu && !evalBtn.contains(event.target) && !evalMenu.contains(event.target)) {
        evalMenu.classList.add('hidden');
        evalIcon.classList.remove('rotate-180');
      }
      if (curriculumBtn && curriculumMenu && curriculumIcon && !curriculumBtn.contains(event.target) && !curriculumMenu.contains(event.target)) {
        curriculumMenu.classList.add('hidden');
        curriculumIcon.classList.remove('rotate-180');
      }
    });

    // Initialize the feedback notification system
  
      
      {
        // This will be populated by feedback-notifications.js
      }
    ;
  </script>

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const programCanvas = document.getElementById('programChart');
      if (programCanvas) {
        new Chart(programCanvas.getContext('2d'), {
          type: 'pie',
          data: {
            labels: [
              'BEED (<?php echo $beed_count; ?>)',
              'BSE English (<?php echo $bse_english_count; ?>)',
              'BSE Math (<?php echo $bse_math_count; ?>)',
              'BSE Science (<?php echo $bse_science_count; ?>)'
            ],
            datasets: [{
              data: [
                <?php echo $beed_count; ?>,
                <?php echo $bse_english_count; ?>,
                <?php echo $bse_math_count; ?>,
                <?php echo $bse_science_count; ?>
              ],
              backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd'],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }

      const statusCanvas = document.getElementById('statusChart');
      if (statusCanvas) {
        new Chart(statusCanvas.getContext('2d'), {
          type: 'pie',
          data: {
            labels: [
              'Regular (<?php echo $regular_count; ?>)',
              'Irregular (<?php echo $irregular_count; ?>)'
            ],
            datasets: [{
              data: [<?php echo $regular_count; ?>, <?php echo $irregular_count; ?>],
              backgroundColor: ['#2563eb', '#f59e0b'],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }

      const genderCanvas = document.getElementById('genderChart');
      if (genderCanvas) {
        new Chart(genderCanvas.getContext('2d'), {
          type: 'pie',
          data: {
            labels: ['Male', 'Female'],
            datasets: [{
              data: [<?php echo $gender_counts['Male']; ?>, <?php echo $gender_counts['Female']; ?>],
              backgroundColor: ['#2563eb', '#3b82f6'],
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }
    });

    // Initialize year level charts
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($year_levels) && is_array($year_levels)): ?>
      <?php foreach ($year_levels as $level): ?>
      const ctx_<?php echo $level; ?> = document.getElementById('pie_<?php echo $level; ?>');
      if (ctx_<?php echo $level; ?>) {
        new Chart(ctx_<?php echo $level; ?>, {
          type: 'pie',
          data: {
            labels: ['Regular', 'Irregular'],
            datasets: [{
              data: [<?php echo $year_level_data[$level]['Regular'] ?? 0; ?>, <?php echo $year_level_data[$level]['Irregular'] ?? 0; ?>],
              backgroundColor: ['#2563eb', '#3b82f6'],
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
              legend: { position: 'bottom' }
            }
          }
        });
      }
      <?php endforeach; ?>
      <?php endif; ?>
    });
  </script>
  
  <!-- Feedback Notifications -->
  
</body>
</html>