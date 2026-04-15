<?php
session_start();
// Check if user is logged in and is a registrar (role_id = 2) or admin (role_id = 3)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 3)) {
    header("Location: ../index.php");
    exit();
}

require_once '../db_connect.php';

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

// Ensure classification in students_db matches classification in signin_db
// (especially for "irregular" students) based on student_id
$syncClassificationSql = "UPDATE students_db s
        INNER JOIN signin_db si ON s.student_id = si.student_id
        SET s.classification = si.classification
        WHERE si.classification IS NOT NULL
            AND (s.classification IS NULL OR s.classification <> si.classification)";
$conn->query($syncClassificationSql);

// Fetch students data
$students_query = "SELECT * FROM students_db ORDER BY student_name ASC";
$students_result = $conn->query($students_query);

// Prepare data for Tabulator
$students_data = [];
if ($students_result && $students_result->num_rows > 0) {
    while ($student = $students_result->fetch_assoc()) {
        $classification = isset($student['classification']) ? strtolower(trim($student['classification'])) : '';

        $students_data[] = [
            'student_id'    => $student['student_id'] ?? '',
            'student_name'  => $student['student_name'] ?? '',
            'email'         => $student['email'] ?? '',
            'programs'      => $student['programs'] ?? '',
            'academic_year' => $student['academic_year'] ?? '',
            'semester'      => $student['semester'] ?? '',
            'status'        => $student['status'] ?? '',
            'gender'        => $student['gender'] ?? '',
            'fiscal_year'   => $student['fiscal_year'] ?? '',
            'classification'=> $classification,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Tabulator (Bootstrap 5 theme) -->
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        body {
            background: white;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
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
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
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
            align-items: center;
            background: #f3f4f6;
            width: 100%;
            margin-left: 220px;
            height: 100vh;
            overflow-y: auto;
        }
        .students-container {
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
        /* Tabulator container will live inside students-container */
        #studentsTabulator {
            width: 100%;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="profile-circle"><img src="regs.jpg" alt="My Small Photo"></div>
            <div class="role">Registrar</div>
            <div class="label">Registrar</div>
            <a href="dashboardr.php" class="nav-link">Dashboard</a>
            <a href="registrar.php" class="nav-link active">Student List</a>
           
            <a href="student_evaluation.php" class="nav-link"><i class="fas fa-clipboard-check"></i> Student Evaluation</a>
            <a href="studentgrade.php" class="nav-link">Student Grade</a>
            <a href="fiscal_year.php" class="nav-link"><i class="fas fa-calendar"></i> Fiscal Year</a>
            <a href="feedbackr.php" class="nav-link">Feedback</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
        </div>
        <div class="content">
            <div class="header-actions">
                <h2 style="color:#374151; margin: 0;">Student List</h2>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-success" onclick="downloadCSV()">
                        <i class="fas fa-download"></i> Download CSV
                    </button>
                    <a href="addsturegs.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Student
                    </a>
                </div>
            </div>
            
            <div class="header-actions" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; align-items: center; width: 100%;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by Student ID, Name, Email, or Program..." style="flex: 1; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <button type="button" class="btn btn-primary" onclick="searchStudents()" style="padding: 10px 20px;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="students-container">
                <div id="studentsTabulator"></div>
            </div>
        </div>
    </div>
    <!-- Prospectus modal (loads student_evaluation.php in an iframe) -->
    <div id="prospectusOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center;">
        <div style="background:#fff; width:95%; max-width:1200px; height:90%; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); display:flex; flex-direction:column; overflow:hidden;">
            <div style="padding:10px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:1rem; color:#111827;">Student Prospectus (Registrar View)</h3>
                <button type="button" onclick="closeProspectusModal()" style="border:none; background:transparent; font-size:1.25rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
            <iframe id="prospectusFrame" src="" style="border:0; width:100%; flex:1; background:#f3f4f6;"></iframe>
        </div>
    </div>

    <!-- Tabulator JS -->
    <script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

    <script>
        // Expose PHP data to JS for Tabulator
        const studentsData = <?php echo json_encode($students_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
        let studentsTable = null;

        function searchStudents() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            if (!studentsTable) return;

            if (!searchTerm) {
                studentsTable.clearFilter();
                return;
            }

            studentsTable.setFilter(function (data, params) {
                const t = params.term;
                if (!t) return true;
                const rowText = [
                    data.student_id,
                    data.student_name,
                    data.email,
                    data.programs,
                    data.academic_year,
                    data.semester,
                    data.classification,
                    data.gender,
                    data.fiscal_year,
                ].map(v => (v || '').toString().toLowerCase()).join(' ');
                return rowText.includes(t);
            }, { term: searchTerm });
        }
        
        // Add search on Enter key
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchStudents();
            }
        });

        // Download CSV function using Tabulator's built-in downloader
        function downloadCSV() {
            if (!studentsTable) return;
            const filename = 'students_list_' + new Date().toISOString().slice(0, 10) + '.csv';
            studentsTable.download('csv', filename);
        }

        // Open/close prospectus modal helpers
        function openProspectusModal(url) {
            const overlay = document.getElementById('prospectusOverlay');
            const frame = document.getElementById('prospectusFrame');
            if (!overlay || !frame) return;
            frame.src = url;
            overlay.style.display = 'flex';
        }

        function closeProspectusModal() {
            const overlay = document.getElementById('prospectusOverlay');
            const frame = document.getElementById('prospectusFrame');
            if (!overlay || !frame) return;
            frame.src = '';
            overlay.style.display = 'none';
        }

        // Helper to open prospectus for a given Tabulator row data
        function openProspectusForStudent(rowData) {
            if (!rowData) return;
            const classification = (rowData.classification || '').toLowerCase();
            if (classification !== 'irregular') return; // Only irregular students

            const studentId = rowData.student_id || '';
            if (!studentId) return;

            const program = rowData.programs || '';
            const fiscalYear = rowData.fiscal_year || '';

            const params = new URLSearchParams();
            params.set('student_id', studentId);
            if (program) params.set('program', program);
            if (fiscalYear) params.set('fiscal_year', fiscalYear);
            params.set('from_modal', '1');

            const url = 'student_evaluation.php?' + params.toString();
            openProspectusModal(url);
        }

        // Initialize Tabulator after DOM is ready
        document.addEventListener('DOMContentLoaded', function () {
            const tableEl = document.getElementById('studentsTabulator');
            if (!tableEl) return;

            studentsTable = new Tabulator(tableEl, {
                data: studentsData,
                layout: 'fitColumns',
                pagination: 'local',
                paginationSize: 25,
                paginationSizeSelector: [25, 50, 100],
                placeholder: 'No students found',
                height: '600px',
                columns: [
                    { title: 'Student ID', field: 'student_id', headerFilter: 'input', minWidth: 130 },
                    { title: 'Student Name', field: 'student_name', headerFilter: 'input', minWidth: 200 },
                    { title: 'Email', field: 'email', headerFilter: 'input', minWidth: 200 },
                    { title: 'Program', field: 'programs', headerFilter: 'input', minWidth: 150 },
                    { title: 'Year Level', field: 'academic_year', hozAlign: 'center', width: 110 },
                    { title: 'Semester', field: 'semester', hozAlign: 'center', width: 110 },
                    { title: 'Classification', field: 'classification', minWidth: 120 },
                    { title: 'Gender', field: 'gender', width: 100 },
                    { title: 'Fiscal Year', field: 'fiscal_year', width: 130 },
                    {
                        title: 'Action',
                        field: 'student_id',
                        hozAlign: 'center',
                        width: 140,
                        headerSort: false,
                        formatter: function (cell) {
                            const data = cell.getRow().getData() || {};
                            const classification = (data.classification || '').toLowerCase();
                            const isIrregular = classification === 'irregular';
                            const btnClass = isIrregular ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-secondary disabled';
                            const title = isIrregular ? 'View prospectus' : 'Prospectus available for irregular students only';
                            return '<button type="button" class="' + btnClass + '" title="' + title + '"><i class="fas fa-book-open"></i> View</button>';
                        },
                        cellClick: function (e, cell) {
                            const data = cell.getRow().getData() || {};
                            openProspectusForStudent(data);
                        }
                    },
                ],
                rowClick: function (e, row) {
                    const data = row.getData() || {};
                    openProspectusForStudent(data);
                },
            });
        });
    </script>
</body>
</html>