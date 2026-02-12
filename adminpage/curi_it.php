<?php
// Database connection
require_once 'db.php';

// Get selected fiscal year
$selectedFiscalYear = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : '';

// Improved normalization function for year_semester
function normalizeSemesterKey($raw) {
    $raw = strtolower(trim($raw));
    // Replace words with numbers
    $raw = str_replace(
        ['first', 'second', 'third', 'fourth', '1st', '2nd', '3rd', '4th'],
        ['1', '2', '3', '4', '1', '2', '3', '4'],
        $raw
    );
    // Remove 'year', 'semester', spaces, and dashes
    $raw = str_replace(['year', 'semester', ' ', '-'], '', $raw);
    // Now $raw should be like '11', '12', etc.
    if (preg_match('/^([1-4])([1-2])$/', $raw, $matches)) {
        return $matches[1] . '-' . $matches[2];
    }
    // If already in '1-2' format, return as is
    if (preg_match('/^[1-4]-[1-2]$/', $raw)) {
        return $raw;
    }
    return $raw; // fallback
}

// Fetch curriculum data for BSIT
$curriculumData = [];
if ($selectedFiscalYear) {
    $sql = "SELECT * FROM curriculum WHERE program = 'BSIT' AND fiscal_year = ? ORDER BY year_semester, course_code";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedFiscalYear);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $normKey = normalizeSemesterKey($row['year_semester']);
        $curriculumData[$normKey][] = $row;
    }
    $stmt->close();
}

// Check for success message
$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = '<div class="mb-4 p-4 bg-green-100 text-green-800 rounded">Curriculum added successfully!</div>';
}

// Function to generate table body content
function generateTableBody($semesterKey, $curriculumData) {
    if (isset($curriculumData[$semesterKey])) {
        foreach ($curriculumData[$semesterKey] as $subject) {
            echo '<tr class="border">';
            echo '<td>' . htmlspecialchars($subject['course_code']) . '</td>';
            echo '<td class="text-left p-2">' . htmlspecialchars($subject['course_title']) . '</td>';
            echo '<td>' . htmlspecialchars($subject['lec_units']) . '</td>';
            echo '<td>' . htmlspecialchars($subject['lab_units']) . '</td>';
            echo '<td>' . htmlspecialchars($subject['total_units']) . '</td>';
            echo '<td>' . htmlspecialchars($subject['prerequisites'] ?: 'None') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr class="border"><td colspan="6" class="text-gray-500">No curriculum data available for this semester</td></tr>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BSIT Curriculum</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      /* Hide the sidebar and other non-essential elements when printing */
      .no-print {
        display: none !important;
      }
      /* Ensure the main content takes up full width when printing */
      .main-content {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      /* Adjust font size for print readability */
      body {
        font-size: 10pt;
      }
    }
  </style>
</head>
<body class="flex h-screen bg-gray-100">

  <aside class="w-64 bg-blue-600 text-white p-6 flex flex-col no-print">
    <div class="flex flex-col items-center">
        <img src="dci.png.png" class="rounded-full border-4 border-white" alt="DCI Logo" />
        <h2 class="mt-4 font-semibold text-lg">DCI</h2>
        <p class="text-sm text-gray-200 mb-6">Dean</p>
    </div>
    <nav class="space-y-4 mt-4">
        <a href="dashboard2.php" class="block py-2 px-4 rounded hover:bg-blue-500">Dashboard</a>
        <div class="relative">
            <button id="studentDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
                Student
                <svg id="dropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="studentDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
                <a href="list.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t last:rounded-b">Student List</a>
                <a href="student_grade.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">Student Grade</a>
                <a href="curi.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">Student Curriculum</a>
            </div>
        </div>
        <div class="relative">
            <button id="curriculumDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded hover:bg-blue-500 focus:outline-none">
                Curriculum
                <svg id="curriculumDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="curriculumDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
                <a href="curi_cs.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t">BSCS Curriculum</a>
                <a href="curi_it.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b bg-blue-100">BSIT Curriculum</a>
            </div>
        </div>
    </nav>
    <script>
        const btn = document.getElementById('studentDropdownBtn');
        const menu = document.getElementById('studentDropdownMenu');
        const icon = document.getElementById('dropdownIcon');
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            menu.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        });
        document.addEventListener('click', function(event) {
            if (!btn.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        });

        const curriculumBtn = document.getElementById('curriculumDropdownBtn');
        const curriculumMenu = document.getElementById('curriculumDropdownMenu');
        const curriculumIcon = document.getElementById('curriculumDropdownIcon');
        curriculumBtn.addEventListener('click', function(e) {
            e.preventDefault();
            curriculumMenu.classList.toggle('hidden');
            curriculumIcon.classList.toggle('rotate-180');
        });
        document.addEventListener('click', function(event) {
            if (!curriculumBtn.contains(event.target) && !curriculumMenu.contains(event.target)) {
                curriculumMenu.classList.add('hidden');
                curriculumIcon.classList.remove('rotate-180');
            }
        });
    </script>
    
  </aside>

  <main class="flex-1 overflow-y-scroll p-8 space-y-12 main-content">
    
 
    
    <h2 class="text-3xl font-bold mb-4 no-print">BSIT Curriculum (Prospectus)</h2>
    <?php echo $successMessage; ?>
    <form method="GET" class="mb-6 no-print">
        <label class="block mb-2 font-semibold">Select Fiscal Year</label>
        <select name="fiscal_year" class="mb-4 w-full border rounded p-2 max-w-xs inline-block" onchange="this.form.submit()" required>
            <option value="">-- Select Fiscal Year --</option>
            <?php 
            $currentYear = (int)date('Y');
            $startYear = $currentYear - 5; 
            $endYear = $currentYear + 5;
            
            for ($year = $startYear; $year <= $endYear; $year++) {
                $nextYear = $year + 1;
                $fiscalYear = "$year-$nextYear";
                $selected = (isset($_GET['fiscal_year']) && $_GET['fiscal_year'] === $fiscalYear) ? 'selected' : '';
                echo "<option value='$fiscalYear' $selected>$fiscalYear</option>";
            }
            ?>
        </select>
    </form>
    <?php if (!isset($_GET['fiscal_year']) || !$_GET['fiscal_year']): ?>
        <div class="mb-8 p-4 bg-yellow-100 text-yellow-800 rounded no-print">Please select a fiscal year to view the curriculum.</div>
    <?php else: ?>
        <div class="mb-8 p-4 bg-blue-100 text-blue-800 rounded no-print">Showing curriculum for fiscal year <strong><?= htmlspecialchars($_GET['fiscal_year']) ?></strong>.</div>
    <?php endif; ?>
    
    <a href="add_curriculum.php" class="inline-block bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md no-print">
      + Add Curriculum
    </a>
    <a href="remove_duplicates.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md ml-2 no-print">
      Remove Duplicates
    </a>

    <section class="space-y-10">
      
     
      <div class="flex justify-between items-start">
        <div class="flex items-center space-x-4">
          <img src="logo ccc.png" alt="City College of Calamba Logo" class="w-20 h-20">
          <div>
            <h1 class="text-xl font-bold">CITY COLLEGE OF CALAMBA</h1>
            <h2 class="text-lg">OFFICE OF THE COLLEGE REGISTRAR</h2>
            <p class="text-sm">Old Municipal Site, Brgy. VII, Poblacion, Calamba City, Laguna 4027 Philippines</p>
          </div>
        </div>
        <div class="text-right">
          <img src="barcode.png" alt="Barcode" class="w-32 h-16 object-cover mb-2">
          <p>1 2 3 4 5 6 . 0 0</p>
        </div>
      </div>
      <div class="text-right mt-4">
        <h1 class="text-4xl font-extrabold tracking-widest">PROSPECTUS</h1>
      </div>
      <div class="mt-6">
        <h2 class="text-2xl font-bold">Bachelor of Science in Information Technology</h2>
      </div>
    </header>
        <h3 class="text-xl font-bold mb-4">First Year</h3>
        <div class="flex flex-wrap lg:flex-nowrap gap-8">
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">First Semester</h4>
                <table id="table-sem1" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('1-1', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">Second Semester</h4>
                <table id="table-sem2" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('1-2', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
      
     
        <h3 class="text-xl font-bold mb-4">Second Year</h3>
        <div class="flex flex-wrap lg:flex-nowrap gap-8">
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">First Semester</h4>
                <table id="table-sem3" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('2-1', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">Second Semester</h4>
                <table id="table-sem4" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('2-2', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
      
      
        <h3 class="text-xl font-bold mb-4">Third Year</h3>
        <div class="flex flex-wrap lg:flex-nowrap gap-8">
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">First Semester</h4>
                <table id="table-sem5" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('3-1', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">Second Semester</h4>
                <table id="table-sem6" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('3-2', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
      
    
        <h3 class="text-xl font-bold mb-4">Fourth Year</h3>
        <div class="flex flex-wrap lg:flex-nowrap gap-8">
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">First Semester</h4>
                <table id="table-sem7" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('4-1', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
            <div class="w-full lg:w-1/2">
                <h4 class="text-lg font-semibold mb-2">Second Semester</h4>
                <table id="table-sem8" class="w-full bg-white border">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php generateTableBody('4-2', $curriculumData); ?>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>