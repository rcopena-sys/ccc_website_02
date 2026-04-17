<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Student List</title>
    <link href="https://unpkg.com/tabulator-tables@6.2.5/dist/css/tabulator.min.css" rel="stylesheet">
    <script src="https://unpkg.com/tabulator-tables@6.2.5/dist/js/tabulator.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', sans-serif;
        }
        
        body { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%);
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.03"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.03"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.02"/><circle cx="20" cy="60" r="0.5" fill="white" opacity="0.02"/><circle cx="80" cy="40" r="0.5" fill="white" opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }
        
        .container { 
            display: flex; 
            height: 100vh;
            position: relative;
            z-index: 2;
        }
        
        .sidebar {
            background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 100%);
            backdrop-filter: blur(20px);
            width: 260px;
            height: 100vh;
            color: #1a1a2e;
            padding: 30px 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        
        .profile-circle { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            margin-bottom: 25px; 
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(30, 58, 138, 0.3);
            border: 4px solid white;
        }
        
        .profile-circle img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 50%;
        }
        
        .sidebar > div:first-of-type {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }
        
        .sidebar nav {
            width: 100%;
            flex-grow: 1;
        }
        
        .sidebar nav a {
            display: block; 
            padding: 14px 20px; 
            color: #4a5568; 
            text-decoration: none; 
            border-radius: 12px; 
            margin-bottom: 8px;
            background: rgba(255,255,255,0.5);
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar nav a:hover {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.3);
        }
        
        .add-btn, .bulk-update-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 0.95rem;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(72, 187, 120, 0.4);
        }
        
        .bulk-update-btn {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        .bulk-update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.4);
        }
        
        .content {
            flex-grow: 1;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            margin: 20px;
            border-radius: 24px;
            padding: 30px;
            overflow-y: auto;
            max-height: calc(100vh - 40px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
        }
        
        .search-bar {
            margin-bottom: 30px;
            padding: 16px 20px;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            border: 2px solid rgba(30, 58, 138, 0.2);
            background: rgba(255,255,255,0.8);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        th {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: rgba(30, 58, 138, 0.05);
        }
        
        tr.selected {
            background: rgba(30, 58, 138, 0.15) !important;
            border-left: 4px solid #1e3a8a;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 0;
            filter: grayscale(100%);
        }
        
        #clock {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(255,255,255,0.95);
            color: #4a5568;
            padding: 12px 20px;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            backdrop-filter: blur(20px);
        }
        
        .logout-btn {
            margin-top: auto;
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
        }
        
        .logout-btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 101, 101, 0.4);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
            backdrop-filter: blur(20px);
            margin: 10% auto;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #718096;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #2d3748;
        }
        
        .add-curriculum-btn {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }
        
        .add-curriculum-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(72, 187, 120, 0.4);
        }

        #studentsTable {
            margin-top: 10px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            background: rgba(255,255,255,0.9);
        }

        .tabulator {
            border: 1px solid rgba(30, 58, 138, 0.15);
            background: rgba(255,255,255,0.9);
        }

        .tabulator .tabulator-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            border-bottom: none;
        }

        .tabulator .tabulator-header .tabulator-col {
            background: transparent;
            color: #fff;
        }

        .tabulator .tabulator-footer {
            background: rgba(255,255,255,0.95);
        }
        
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
       
        <div>SUPER ADMIN</div>
        <nav style="width: 100%; margin-top: 20px;">
            <a href="dashboard.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin-bottom: 5px; background: #6366f1;">Dashboard</a>
        </nav>
      
    </div>
    <div class="content">
        <div id="clock"></div>
        <img class="watermark" src="dci.png.png" alt="Watermark Logo" width="300">
        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 30px;">
            <input type="text" id="searchInput" class="search-bar" placeholder="Search by name, ID, email, or course..." onkeyup="search()" style="flex: 1; max-width: 400px;">
            <button onclick="openCreateAccountModal()" class="add-btn">Create Student Account</button>
        </div>
        
        <!-- Popup Modal -->
        <div id="studentModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="modalStudentName"></h3>
                <p>ID: <span id="modalStudentId"></span></p>
                <div class="modal-actions">
                    <button onclick="addCurriculum()" class="add-curriculum-btn">Add Curriculum</button>
                </div>
            </div>
        </div>
        
        <!-- Create Account Modal -->
        <div id="createAccountModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeCreateAccountModal()">&times;</span>
                <h3>Create Student Account</h3>
                <form id="createAccountForm" onsubmit="createStudentAccount(event)">
                    <div class="form-group">
                        <label for="createStudentId">Student ID</label>
                        <input type="text" id="createStudentId" name="student_id" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="createStudentName">Student Name</label>
                        <input type="text" id="createStudentName" name="student_name" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="createEmail">Email</label>
                        <input type="email" id="createEmail" name="email" required readonly>
                    </div>
                    <div class="form-group" style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 12px; border-radius: 8px;">
                        <div style="color:#1e40af; font-weight:600; margin-bottom:6px;">Password</div>
                        <div style="color:#374151; font-size: 14px;">Password will be auto-generated and emailed to the student.</div>
                    </div>
                    <div class="form-group">
                        <label for="createRole">Role</label>
                        <input type="hidden" id="createRole" name="role" required>
                        <input type="text" id="createRoleDisplay" value="" readonly style="background: #f8fafc; cursor: not-allowed;">
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="add-curriculum-btn">Create Account</button>
                        <button type="button" onclick="closeCreateAccountModal()" class="btn-secondary" style="background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <style>
            /* Modal Styles */
            .modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
            }
            
            .modal-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 40%;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            
            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            
            .close:hover {
                color: black;
            }
            
            .modal-actions {
                margin-top: 20px;
                text-align: right;
            }
            
            .add-curriculum-btn {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            
            .add-curriculum-btn:hover {
                background-color: #45a049;
            }
            
            .student-name {
                color: #2563eb;
                cursor: pointer;
                text-decoration: underline;
            }
            
            .student-name:hover {
                color: #1d4ed8;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 5px;
                color: #4a5568;
                font-weight: 500;
            }
            
            .form-group input,
            .form-group select {
                width: 100%;
                padding: 10px;
                border: 2px solid rgba(30, 58, 138, 0.2);
                border-radius: 8px;
                font-size: 14px;
                transition: border-color 0.3s ease;
            }
            
            .form-group input:focus,
            .form-group select:focus {
                outline: none;
                border-color: #1e3a8a;
            }

            .form-group input[readonly] {
                background: #f8fafc;
                cursor: not-allowed;
            }
        </style>
        <?php
        require_once __DIR__ . '/../db_connect.php';

        // Function to format student ID as YYYY-XXXXX
        function formatStudentId($id) {
            // If already in correct format, return as is
            if (preg_match('/^\d{4}-\d{5}$/', $id)) {
                return $id;
            }
            // If it's just numbers, add the dash after the first 4 digits
            if (preg_match('/^(\d{4})(\d+)$/', $id, $matches)) {
                return $matches[1] . '-' . $matches[2];
            }
            // If it's in a different format, try to extract numbers and format
            preg_match_all('/\d+/', $id, $numbers);
            if (!empty($numbers[0])) {
                $digits = implode('', $numbers[0]);
                if (strlen($digits) >= 9) { // At least 9 digits for YYYY-XXXXX
                    return substr($digits, 0, 4) . '-' . substr($digits, 4, 5);
                }
            }
            // If all else fails, return the original ID
            return $id;
        }

        // Handle search
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $course = isset($_GET['course']) ? $_GET['course'] : '';

        // Base query - using UNION to combine signin_db and students_db
        $signinWhere = ["(r.role_name IN ('BSIT', 'BSCS'))"];
        $studentsWhere = [];
        
        // Add search condition if search term exists
        if (!empty($search)) {
            $sanitized_search = $conn->real_escape_string($search);
            $signinWhere[] = "(u.student_id LIKE '%$sanitized_search%' OR 
                              u.firstname LIKE '%$sanitized_search%' OR 
                              u.lastname LIKE '%$sanitized_search%' OR 
                              u.email LIKE '%$sanitized_search%')";
            $studentsWhere[] = "(s.student_id LIKE '%$sanitized_search%' OR 
                                s.firstname LIKE '%$sanitized_search%' OR 
                                s.lastname LIKE '%$sanitized_search%' OR 
                                s.email LIKE '%$sanitized_search%')";
        }

        // Add course filter if specified
        if (!empty($course)) {
            $sanitized_course = $conn->real_escape_string($course);
            $signinWhere[] = "u.course = '$sanitized_course'";
            // Don't filter students_db by course since it doesn't have course/program field
        }

        $sql = "SELECT 
                    '' as id,
                    '' as firstname,
                    '' as lastname,
                    s.student_name,
                    s.email,
                    s.student_id,
                    s.programs as course,
                    s.classification,
                    'Student' as role_name,
                    s.curriculum,
                    '' as category,
                    '' as fiscal_year,
                    'students' as source_table
                FROM students_db s
                WHERE " . (empty($studentsWhere) ? "1=1" : implode(' AND ', $studentsWhere)) . "
                ORDER BY student_id, student_name";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $studentsData = [];
            while($row = $result->fetch_assoc()) {
                $studentsData[] = [
                    'student_name' => (string)($row['student_name'] ?? ''),
                    'email' => (string)($row['email'] ?? ''),
                    'student_id' => (string)($row['student_id'] ?? ''),
                    'student_id_formatted' => formatStudentId((string)($row['student_id'] ?? '')),
                    'course' => (string)($row['course'] ?? ''),
                    'classification' => (string)($row['classification'] ?? 'N/A'),
                    'role_name' => (string)($row['role_name'] ?? 'Student'),
                    'curriculum' => (string)($row['curriculum'] ?? 'N/A')
                ];
            }

            echo '<div id="studentsTable"></div>';
            echo '<script>window.studentsTableData = ' . json_encode($studentsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        } else {
            echo '<p>No students found.</p>';
            echo '<script>window.studentsTableData = [];</script>';
        }

        $conn->close();
        ?>
    </div>
</div>
<script>
    var studentsTabulator = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function initializeStudentsTable() {
        var tableEl = document.getElementById('studentsTable');
        if (!tableEl || typeof Tabulator === 'undefined') return;

        var tableData = Array.isArray(window.studentsTableData) ? window.studentsTableData : [];
        studentsTabulator = new Tabulator('#studentsTable', {
            data: tableData,
            layout: 'fitColumns',
            responsiveLayout: 'collapse',
            pagination: true,
            paginationMode: 'local',
            paginationSize: 10,
            paginationSizeSelector: [10, 20, 50, 100],
            movableColumns: true,
            columns: [
                { title: 'Name', field: 'student_name', minWidth: 180 },
                { title: 'Email', field: 'email', minWidth: 220 },
                { title: 'Student ID', field: 'student_id_formatted', minWidth: 130 },
                { title: 'Course', field: 'course', minWidth: 110 },
                { title: 'Classification', field: 'classification', minWidth: 140 },
                { title: 'Role', field: 'role_name', minWidth: 100 },
                { title: 'Curriculum', field: 'curriculum', minWidth: 130 },
                {
                    title: 'Action',
                    field: 'action',
                    hozAlign: 'center',
                    headerHozAlign: 'center',
                    minWidth: 140,
                    formatter: function() {
                        return '<span style="color: #2563eb; font-weight: 600; cursor: pointer;">Create Account</span>';
                    },
                    cellClick: function(e, cell) {
                        e.stopPropagation();
                        var row = cell.getRow().getData();
                        openCreateAccountModalPrefill(row.student_id, row.student_name, row.email, row.course);
                    }
                }
            ],
            rowClick: function(e, row) {
                var data = row.getData();
                createAccountFromRow(data.student_id, data.student_name, data.email, data.course);
            }
        });
    }

    // Dropdown functionality
    const btn = document.getElementById('studentDropdownBtn');
    const menu = document.getElementById('studentDropdownMenu');
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        });
    }
    document.addEventListener('click', function(event) {
        if (btn && menu && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
    });

    // Search functionality
    function search() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toLowerCase();
        if (!studentsTabulator) {
            return;
        }

        if (!filter) {
            studentsTabulator.clearFilter(true);
            return;
        }

        studentsTabulator.setFilter(function(data) {
            var searchable = [
                data.student_name,
                data.email,
                data.student_id,
                data.student_id_formatted,
                data.course,
                data.classification,
                data.role_name,
                data.curriculum
            ].join(' ').toLowerCase();

            return searchable.indexOf(filter) !== -1;
        });
    }
    // Modal functionality
    var modal = document.getElementById('studentModal');
    var span = document.getElementsByClassName('close')[0];
    var currentStudentId = '';
    
    // Create account directly from row click
    function createAccountFromRow(studentId, studentName, email, course) {
        const role = getRoleFromCourse(course);
        const username = email.split('@')[0];

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Create account?',
                html: `
                    <div style="text-align:left; line-height:1.6;">
                        <div><strong>Name:</strong> ${escapeHtml(studentName)}</div>
                        <div><strong>Email:</strong> ${escapeHtml(email)}</div>
                        <div><strong>Username:</strong> ${escapeHtml(username)}</div>
                        <div><strong>Role:</strong> ${escapeHtml(role)}</div>
                        <div class="mt-2">Password will be auto-generated and sent to the student.</div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Create Account',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) return;
                submitCreateStudentAccount(studentId, studentName, email, role);
            });
            return;
        }

        if (confirm('Create account for ' + studentName + ' with email: ' + email + '?\n\nUsername: ' + username + '\nRole: ' + role + '\n\nPassword will be auto-generated and sent to their email.')) {
            submitCreateStudentAccount(studentId, studentName, email, role);
        }
    }
    
    // Fallback function to create account directly
    function createAccountDirectly(studentId, studentName, email, course) {
        const role = getRoleFromCourse(course);
        submitCreateStudentAccount(studentId, studentName, email, role);
    }
    
    // Show modal with student info
    function showStudentModal(studentId, studentName) {
        // Toggle selection - if already selected, unselect it
        const clickedRow = event.currentTarget;
        const wasSelected = clickedRow.classList.contains('selected');
        
        // Remove selection from all rows
        const allSelected = document.querySelectorAll('tr.selected');
        allSelected.forEach(row => row.classList.remove('selected'));
        
        // If it wasn't selected before, select it now and show modal
        if (!wasSelected) {
            clickedRow.classList.add('selected');
            
            document.getElementById('modalStudentId').textContent = studentId;
            document.getElementById('modalStudentName').textContent = studentName;
            currentStudentId = studentId;
            modal.style.display = 'block';
        }
        // If it was already selected, just unselect it (don't show modal)
    }
    
    // Close modal when clicking the X
    span.onclick = function() {
        modal.style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
        if (event.target == createAccountModal) {
            createAccountModal.style.display = 'none';
        }
    }
    
    // Create Account Modal functions
    var createAccountModal = document.getElementById('createAccountModal');
    
    function openCreateAccountModal() {
        createAccountModal.style.display = 'block';
        // Clear form
        document.getElementById('createAccountForm').reset();
    }

    // Prefill and open Create Account Modal from a table row
    function openCreateAccountModalPrefill(studentId, studentName, email, course) {
        createAccountModal.style.display = 'block';
        document.getElementById('createStudentId').value = studentId || '';
        document.getElementById('createStudentName').value = studentName || '';
        document.getElementById('createEmail').value = email || '';
        // Guess role from course
        let roleGuess = 'Student';
        if ((course || '').toLowerCase().includes('bsit')) roleGuess = 'BSIT';
        else if ((course || '').toLowerCase().includes('bscs')) roleGuess = 'BSCS';
        document.getElementById('createRole').value = roleGuess;
        document.getElementById('createRoleDisplay').value = roleGuess;
    }
    
    function closeCreateAccountModal() {
        createAccountModal.style.display = 'none';
    }
    
    function createStudentAccount(event) {
        event.preventDefault();

        var formData = new FormData(document.getElementById('createAccountForm'));
        submitCreateAccountForm(formData);
    }

    function getRoleFromCourse(course) {
        var value = String(course || '');
        if (value.toLowerCase().includes('bsit')) {
            return 'BSIT';
        }
        if (value.toLowerCase().includes('bscs')) {
            return 'BSCS';
        }
        return 'Student';
    }

    function submitCreateStudentAccount(studentId, studentName, email, role) {
        var formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('student_name', studentName);
        formData.append('email', email);
        formData.append('role', role);
        submitCreateAccountForm(formData, studentName);
    }

    function submitCreateAccountForm(formData, studentName) {
        fetch('create_student_account_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeCreateAccountModal();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Account Created',
                        text: studentName ? ('Account created successfully for ' + studentName + '.') : 'Account created successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(studentName ? ('Account created successfully for ' + studentName + '!') : 'Account created successfully!');
                    location.reload();
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Create Failed',
                        text: data.message || 'Error creating account.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'An error occurred while creating the account.',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('An error occurred while creating the account.');
            }
        });
    }
    
    // Handle Add Curriculum button click
    function addCurriculum() {
        if (currentStudentId) {
            // Redirect to the curriculum page with the student ID
            window.location.href = 'student_curriculum.php?student_id=' + encodeURIComponent(currentStudentId);
        }
    }
    // Clock functionality
    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('clock');
        clock.textContent = now.toLocaleTimeString();
    }

    // Update clock every second
    setInterval(updateClock, 1000);

    // Initial clock update
    document.addEventListener('DOMContentLoaded', function() {
        updateClock();
        initializeStudentsTable();
    });
</script>
</body>
</html>
