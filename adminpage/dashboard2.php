<?php
session_start();
// Check if user is logged in and is an admin (role_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

// Get unread count
$unread_count = 0;
$db = new mysqli('localhost', 'root', '', 'ccc_curriculum_evaluation');
if (!$db->connect_error) {
    $role_id = $_SESSION['role_id'];
    $recipient_type = ($role_id == 2) ? 'registrar' : 'dean';
    $query = "SELECT COUNT(*) as count FROM messages_db WHERE recipient_type = ?";
    
    if ($stmt = $db->prepare($query)) {
        $stmt->bind_param("s", $recipient_type);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $unread_count = (int)$row['count'];
            }
            $result->free();
        }
        $stmt->close();
    }
    $db->close();
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

// Get year-level and classification breakdown
$year_levels = ['1st', '2nd', '3rd', '4th'];
$year_level_data = [];
foreach ($year_levels as $level) {
    $year_level_data[$level] = ['Regular' => 0, 'Irregular' => 0];
    $res = $conn->query("SELECT classification, COUNT(*) as total FROM students_db " . ($where_clause ? $where_clause . " AND programs = 'BSIT'" : " WHERE programs = 'BSIT'") . " GROUP BY classification");
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
    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
    </svg>
  </button>

  <!-- User Profile and Navigation -->
  <div class="fixed top-4 right-4 flex items-center space-x-4">
    <!-- Notification Bell -->
    <div class="relative">
      <a href="notification_page.php" class="text-blue-600 hover:text-blue-800 focus:outline-none relative">
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
        <span class="text-blue-700 font-medium"><?php echo htmlspecialchars($user['firstname']); ?></span>
        <i class="fas fa-chevron-down text-blue-600"></i>
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
      <img src="dci.png.png" class="rounded-full border-4 border-white" alt="DCI Logo" />
      <h2 class="mt-4 font-semibold text-lg">DCI</h2>
      <p class="text-sm text-gray-200 mb-6">Dean</p>
    </div>
    <nav class="space-y-4 mt-4">
      <a href="dashboard2.php" class="block py-2 px-4 rounded bg-blue-500">Dashboard</a>
      <a href="profile.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">
        <i class="fas fa-user mr-2"></i> Profile
      </a>
      <a href="feedback.php" class="block py-2 px-4 rounded hover:bg-blue-500 transition-colors duration-200">Feedback</a>
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
      
      <!-- Evaluation Dropdown -->
      <div class="relative">
        <button id="evaluationDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
          Evaluation
          <svg id="evaluationDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        <div id="evaluationDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
          <a href="stueval.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t">Evaluate Student</a>
          <a href="semeva.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">Semestral Evaluation</a>
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
  <main class="flex-1 p-10 relative" style="background-image: url('cccd.jpg'); background-size: cover; background-position: center; background-attachment: fixed; background-repeat: no-repeat;">
  <div class="absolute top-4 right-48 flex items-center space-x-4">
    <div class="flex items-center space-x-4">
      <a href="notification_page.php" class="bg-white bg-opacity-70 p-2 rounded-full shadow hover:bg-opacity-100 transition-all duration-200 relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
      </a>
      <div id="clock" class="bg-white bg-opacity-70 px-4 py-2 rounded shadow"></div>
    </div>
  </div>
  </div>

  <div class="bg-white bg-opacity-90 rounded-lg p-6 shadow-md">
    <div class="flex justify-center mb-6">
      <img src="dci.png.png" alt="DCI Logo" class="h-32 w-auto">
    </div>
    <h1 class="text-3xl font-bold text-center text-blue-600 mb-8">Department of Computing and Informatics</h1>

    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-10">
      <div class="bg-white text-blue-600 border-2 border-blue-600 p-6 rounded-lg shadow-lg">
        <div class="text-xl font-semibold">Total Students</div>
        <div class="text-3xl font-bold mt-2"><?php echo $total_students; ?></div>
      </div>
      <div class="bg-white text-blue-600 border-2 border-blue-600 p-6 rounded-lg shadow-lg">
        <div class="text-xl font-semibold">Curriculum</div>
        <div class="text-3xl font-bold mt-2"><?php echo $curriculum_count; ?></div>
      </div>
      <a href="list.php?program=BSIT" class="bg-white text-blue-600 border-2 border-blue-600 p-6 rounded-lg shadow-lg block hover:bg-blue-50 transition">
        <div class="text-xl font-semibold">BSIT</div>
        <div class="text-3xl font-bold mt-2">
          <?php echo $bsit_count ?? 0; ?>
        </div>
      </a>
      <a href="list.php?program=BSCS" class="bg-white text-blue-600 border-2 border-blue-600 p-6 rounded-lg shadow-lg block hover:bg-blue-50 transition">
        <div class="text-xl font-semibold">BSCS</div>
        <div class="text-3xl font-bold mt-2">
          <?php echo $bscs_count ?? 0; ?>
        </div>
      </a>
    </div>

    <!-- Additional Statistics Section -->
    <div class="bg-white bg-opacity-90 rounded-lg p-6 shadow-md mt-10" style="position: relative; z-index: 10;">
      <h2 class="text-2xl font-bold text-blue-600 mb-6">Statistics Overview</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-50 p-4 rounded shadow">
          <div class="text-lg font-semibold text-blue-700">Curriculum in Operation</div>
          <div class="text-2xl font-bold mt-2"><?php echo $curriculum_count; ?></div>
        </div>
        <div class="bg-blue-50 p-4 rounded shadow">
          <div class="text-lg font-semibold text-blue-700">Total Population</div>
          <div class="text-2xl font-bold mt-2"><?php echo number_format($total_population); ?></div>
        </div>
         <a href="regularstu.php" class="block bg-blue-50 p-4 rounded shadow hover:bg-blue-100 transition duration-200 transform hover:scale-105">
          <div class="text-lg font-semibold text-blue-700">Regular Students</div>
          <div class="text-2xl font-bold mt-2">
            <?php echo number_format($regular_count); ?>
          </div>
        </a>
        <a href="irregularstu.php" class="block bg-blue-50 p-4 rounded shadow hover:bg-blue-100 transition duration-200 transform hover:scale-105">
          <div class="text-lg font-semibold text-blue-700">Irregular Students</div>
          <div class="text-2xl font-bold mt-2">
            <?php echo number_format($irregular_count); ?>
          </div>
        </a>
       
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div>
         
          
              
            </tbody>
          </table>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-blue-700 mb-2">Gender Distribution</h3>
          <canvas id="genderPieChart" width="300" height="300"></canvas>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
       
            </thead>
           
          </table>
        </div>
      </div>
    </div>
    <div class="bg-white bg-opacity-90 rounded-lg p-6 shadow-md mt-10 sticky top-0">
      <h2 class="text-2xl font-bold text-blue-600 mb-6">Year Level Regular/Irregular Pie Charts</h2>
      <div class="flex flex-wrap gap-8 justify-center">
        <?php foreach ($year_levels as $level): ?>
          <div class="bg-white p-4 rounded shadow" style="position: relative;">
            <h3 class="font-semibold text-center mb-2"><?php echo $level; ?> Year</h3>
            <div class="h-48" style="position: relative;">
              <canvas id="pie_<?php echo $level; ?>"></canvas>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- End Additional Statistics Section -->
  </div>
</main>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <!-- Feedback Notifications -->
  <
  
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

      // Initialize charts when the DOM is fully loaded
      // Gender Distribution Chart
      const genderCtx = document.getElementById('genderChart').getContext('2d');
      new Chart(genderCtx, {
        type: 'pie',
        data: {
          labels: [
            'Male (<?php echo $gender_counts['Male']; ?>)', 
            'Female (<?php echo $gender_counts['Female']; ?>)'
          ],
          datasets: [{
            data: [
              <?php echo $gender_counts['Male']; ?>, 
              <?php echo $gender_counts['Female']; ?>
            ],
            backgroundColor: [
              'rgba(54, 162, 235, 0.7)',
              'rgba(255, 99, 132, 0.7)'
            ],
            borderColor: [
              'rgba(54, 162, 235, 1)',
              'rgba(255, 99, 132, 1)'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });

      // Program Enrollment Chart
      const programCtx = document.getElementById('programChart').getContext('2d');
      new Chart(programCtx, {
        type: 'pie',
        data: {
          labels: [
            'BSCS (<?php echo $bscs_count; ?>)', 
            'BSIT (<?php echo $bsit_count; ?>)'
          ],
          datasets: [{
            data: [
              <?php echo $bscs_count; ?>, 
              <?php echo $bsit_count; ?>
            ],
            backgroundColor: [
              'rgba(75, 192, 192, 0.7)',
              'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: [
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });

      // Student Status Chart
      const statusCtx = document.getElementById('statusChart').getContext('2d');
      new Chart(statusCtx, {
        type: 'pie',
        data: {
          labels: [
            'Regular (<?php echo $regular_count; ?>)', 
            'Irregular (<?php echo $irregular_count; ?>)'
          ],
          datasets: [{
            data: [
              <?php echo $regular_count; ?>, 
              <?php echo $irregular_count; ?>
            ],
            backgroundColor: [
              'rgba(75, 192, 192, 0.7)',
              'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 159, 64, 1)'],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });
    });

    function updateClock() {
      const now = new Date();
      const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: true 
      });
      document.getElementById('clock').textContent = timeString;
    }
    
    // Update clock immediately and then every second
    updateClock();
    setInterval(updateClock, 1000);

    // Student Dropdown
    const btn = document.getElementById('studentDropdownBtn');
    const menu = document.getElementById('studentDropdownMenu');
    const icon = document.getElementById('dropdownIcon');
    
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      menu.classList.toggle('hidden');
      icon.classList.toggle('rotate-180');
    });

    // Evaluation Dropdown
    const evalBtn = document.getElementById('evaluationDropdownBtn');
    const evalMenu = document.getElementById('evaluationDropdownMenu');

    // Curriculum Dropdown
    const curriculumBtn = document.getElementById('curriculumDropdownBtn');
    const curriculumMenu = document.getElementById('curriculumDropdownMenu');
    const curriculumIcon = document.getElementById('curriculumDropdownIcon');

    if (curriculumBtn) {
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

    fiscalYearBtn.addEventListener('click', function(e) {
      e.preventDefault();
      fiscalYearMenu.classList.toggle('hidden');
      fiscalYearIcon.classList.toggle('rotate-180');
    });
    const evalIcon = document.getElementById('evaluationDropdownIcon');
    
    evalBtn.addEventListener('click', function(e) {
      e.preventDefault();
      evalMenu.classList.toggle('hidden');
      evalIcon.classList.toggle('rotate-180');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
      if (!btn.contains(event.target) && !menu.contains(event.target)) {
        menu.classList.add('hidden');
        icon.classList.remove('rotate-180');
      }
      if (!evalBtn.contains(event.target) && !evalMenu.contains(event.target)) {
        evalMenu.classList.add('hidden');
        evalIcon.classList.remove('rotate-180');
      }
      if (curriculumBtn && !curriculumBtn.contains(event.target) && !curriculumMenu.contains(event.target)) {
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
    // Gender Pie Chart
    const ctx = document.getElementById('genderPieChart').getContext('2d');
    const genderPieChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ['Male', 'Female'],
        datasets: [{
          data: [<?php echo $gender_counts['Male']; ?>, <?php echo $gender_counts['Female']; ?>],
          backgroundColor: ['#3b82f6', '#f472b6'],
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
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
              backgroundColor: ['#3b82f6', '#f472b6'],
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