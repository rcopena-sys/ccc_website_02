<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Keep students_db.classification in sync with signin_db.classification (same as registrar view)
$syncClassificationSql = "UPDATE students_db s
    INNER JOIN signin_db si ON s.student_id = si.student_id
    SET s.classification = si.classification
    WHERE si.classification IS NOT NULL
      AND (s.classification IS NULL OR s.classification <> si.classification)";
$conn->query($syncClassificationSql);

// Fetch students data for Tabulator (same structure as registrar/registrar.php)
$students_query = "SELECT * FROM students_db ORDER BY student_name ASC";
$students_result = $conn->query($students_query);

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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: white; }
        .container { display: flex; height: 100vh; }
        .sidebar {
            background-color: rgb(80, 77, 218);
            width: 180px;
            height: 100vh;
            color: black;
            padding: 20px 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: start;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .profile-circle { width: 100px; height: 100px; border-radius: 50%; background-color: gray; margin-bottom: 30px; overflow: hidden; }
        .profile-circle img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .add-btn {
            background-color: blue;
            border-radius: 10px;
            padding: 7px;
            color: white;
            border: none;
            margin-right: 10px;
            cursor: pointer;
        }
        .bulk-update-btn {
            background-color: #2563eb;
            color: white;
            border-radius: 10px;
            padding: 7px 14px;
            border: none;
            cursor: pointer;
        }
        .content {
            flex-grow: 1;
            background-color: transparent;
            margin: 20px;
            border-radius: 20px;
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 40px);
            border: 2px solid purple;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .search-bar {
            margin-bottom: 10px;
            padding: 10px;
            width: 300px;
            border-radius: 10px;
            border: 1px solid #ddd;
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
            margin-top: 10px;
        }
        #studentsTabulator {
            width: 100%;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.3;
            z-index: -1;
        }
        #clock {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: white;
            padding: 10px 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-weight: bold;
            z-index: 1000;
            border: 1px solid #ddd;
        }
        .logout-btn {
            margin-top: auto;
            background-color: #dc2626;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
        }
        .logout-btn:hover { background-color: #b91c1c; }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="profile-circle"><img src="dci.png.png" alt="DCI Logo"></div>
        <div>DCI</div>
        <nav style="width: 100%; margin-top: 20px;">
            <a href="dashboard2.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin-bottom: 5px; background: #6366f1;">Dashboard</a>
            <div style="position: relative;">
                <button id="studentDropdownBtn" style="width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 10px 0; background: #6366f1; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Student list
                    <span style="margin-left: 8px;">▼</span>
                </button>
                <div id="studentDropdownMenu" style="display: none; flex-direction: column; background: #e0e7ff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); position: absolute; left: 0; top: 100%; width: 100%; z-index: 10;">
                    <a href="list.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none; border-bottom: 1px solid #c7d2fe;">Student List</a>
                    <a href="stugra.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none; border-bottom: 1px solid #c7d2fe;">Student Grade</a>
                    <a href="stucuri.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none;">Student Curriculum</a>
                </div>
            </div>
            <a href="stucuri.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin: 5px 0; background: #6366f1;">Curriculum</a>
            <a href="stueval.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin: 5px 0; background: #6366f1; transition: background-color 0.3s;">Student Evaluation</a>
        </nav>
      
    </div>
    <div class="content">
        <div id="clock"></div>
        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <button type="button" class="bulk-update-btn" onclick="downloadCSV()">Export CSV</button>
        </div>
        <img class="watermark" src="dci.png.png" alt="Watermark Logo" width="300">

        <div class="students-container">
            <div id="studentsTabulator"></div>
        </div>
    </div>
    </div>
</div>

<!-- Prospectus modal (loads stueval.php in an iframe, same style as registrar view) -->
<div id="prospectusOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:#fff; width:95%; max-width:1200px; height:90%; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:10px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
            <h3 style="margin:0; font-size:1rem; color:#111827;">Student Prospectus (Admin View)</h3>
            <button type="button" onclick="closeProspectusModal()" style="border:none; background:transparent; font-size:1.25rem; cursor:pointer; line-height:1;">&times;</button>
        </div>
        <iframe id="prospectusFrame" src="" style="border:0; width:100%; flex:1; background:#f3f4f6;"></iframe>
    </div>
</div>

<!-- Tabulator JS -->
<script src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

<script>
    // Dropdown functionality
    const btn = document.getElementById('studentDropdownBtn');
    const menu = document.getElementById('studentDropdownMenu');
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    });
    document.addEventListener('click', function(event) {
        if (!btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
    });
    
    // Expose PHP data to JS for Tabulator
    const studentsData = <?php echo json_encode($students_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [];
    let studentsTable = null;

    // Download CSV using Tabulator's downloader
    function downloadCSV() {
        if (!studentsTable) return;
        const filename = 'students_list_' + new Date().toISOString().slice(0, 10) + '.csv';
        studentsTable.download('csv', filename);
    }

    // Prospectus modal helpers
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

    // Open prospectus for a Tabulator row (only for irregular classification)
    function openProspectusForStudent(rowData) {
        if (!rowData) return;
        const classification = (rowData.classification || '').toLowerCase();
        if (classification !== 'irregular') return;

        const studentId = rowData.student_id || '';
        if (!studentId) return;

        const program = rowData.programs || '';
        const fiscalYear = rowData.fiscal_year || '';

        const params = new URLSearchParams();
        params.set('student_id', studentId);
        if (program) params.set('program', program);
        if (fiscalYear) params.set('fiscal_year', fiscalYear);
        params.set('from_modal', '1');

        const url = 'stueval.php?' + params.toString();
        openProspectusModal(url);
    }

    // Initialize Tabulator
    document.addEventListener('DOMContentLoaded', function() {
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
                { title: 'Program', field: 'programs', headerFilter: 'input', minWidth: 160 },
                { title: 'Year Level', field: 'academic_year', minWidth: 110 },
                { title: 'Semester', field: 'semester', minWidth: 110 },
                { title: 'Classification', field: 'classification', minWidth: 130 },
                { title: 'Gender', field: 'gender', minWidth: 100 },
                { title: 'Fiscal Year', field: 'fiscal_year', minWidth: 130 },
                {
                    title: 'Action',
                    field: 'action',
                    hozAlign: 'center',
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        const classification = (data.classification || '').toLowerCase();
                        const isIrregular = classification === 'irregular';
                        const btnClass = isIrregular ? 'btn btn-primary' : 'btn btn-secondary';
                        const disabledAttr = isIrregular ? '' : 'disabled';
                        const title = isIrregular ? 'View Prospectus' : 'Prospectus only for irregular students';
                        return `<button type="button" class="${btnClass}" ${disabledAttr} title="${title}">View</button>`;
                    },
                    width: 120,
                    cellClick: function(e, cell) {
                        const rowData = cell.getRow().getData();
                        openProspectusForStudent(rowData);
                    }
                }
            ],
            rowClick: function(e, row) {
                openProspectusForStudent(row.getData());
            }
        });
    });
    // Clock functionality
    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('clock');
        clock.textContent = now.toLocaleTimeString();
    }

    // Update clock every second
    setInterval(updateClock, 1000);

    // Initial clock update
    document.addEventListener('DOMContentLoaded', updateClock);
</script>
</body>
</html>
