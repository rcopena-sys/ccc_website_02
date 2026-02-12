<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Student List</title>
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
                        <input type="text" id="createStudentId" name="student_id" required>
                    </div>
                    <div class="form-group">
                        <label for="createStudentName">Student Name</label>
                        <input type="text" id="createStudentName" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="createEmail">Email</label>
                        <input type="email" id="createEmail" name="email" required>
                    </div>
                    <div class="form-group" style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 12px; border-radius: 8px;">
                        <div style="color:#1e40af; font-weight:600; margin-bottom:6px;">Password</div>
                        <div style="color:#374151; font-size: 14px;">Password will be auto-generated and emailed to the student.</div>
                    </div>
                    <div class="form-group">
                        <label for="createRole">Role</label>
                        <select id="createRole" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Student">Student</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                        </select>
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
        </style>
        <?php
       include 'db.php';

// Debug: Check current database and tables
echo "<!-- Current database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . " -->";
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);
if ($tables_result) {
    echo "<!-- Available tables: -->";
    while ($table = $tables_result->fetch_array()) {
        echo "<!-- - " . $table[0] . " -->";
    }
}

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
            echo '<table>';
            echo '<tr><th>Name</th><th>Email</th><th>Student ID</th><th>Course</th><th>Classification</th><th>Role</th><th>Curriculum</th><th>Action</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr class="student-row" onclick="createAccountFromRow(\'' . htmlspecialchars($row['student_id']) . '\', \'' . htmlspecialchars($row['student_name']) . '\', \'' . htmlspecialchars($row['email']) . '\', \'' . htmlspecialchars($row['course']) . '\')" style="cursor: pointer;">';
                echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars(formatStudentId($row['student_id'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['course']) . '</td>';
                echo '<td>' . htmlspecialchars($row['classification'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['role_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['curriculum'] ?? 'N/A') . '</td>';
                echo '<td><span style="color: #2563eb; font-weight: 600; cursor: pointer;" title="Click to create account with auto-generated password" onclick="event.stopPropagation(); openCreateAccountModalPrefill(\'' . htmlspecialchars($row['student_id']) . '\', \'' . htmlspecialchars($row['student_name']) . '\', \'' . htmlspecialchars($row['email']) . '\', \'' . htmlspecialchars($row['course']) . '\')">Create Account</span></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No students found.</p>';
        }

        $conn->close();
        ?>
    </div>
</div>
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
    // Search functionality
    function search() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toLowerCase();
        var rows = document.getElementsByClassName('student-row');

        for (var i = 0; i < rows.length; i++) {
            var name = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
            var id = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
            var email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
            var studentId = rows[i].getElementsByTagName('td')[3].textContent.toLowerCase();
            var course = rows[i].getElementsByTagName('td')[4].textContent.toLowerCase();
            
            if (name.indexOf(filter) > -1 || id.indexOf(filter) > -1 || 
                email.indexOf(filter) > -1 || studentId.indexOf(filter) > -1 || 
                course.indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
    // Modal functionality
    var modal = document.getElementById('studentModal');
    var span = document.getElementsByClassName('close')[0];
    var currentStudentId = '';
    
    // Create account directly from row click
    function createAccountFromRow(studentId, studentName, email, course) {
        // Test connection first
        fetch('debug_connection.php')
        .then(response => response.json())
        .then(debugData => {
            console.log('Connection Test Result:', debugData);
            
            if (!debugData.success) {
                alert('Server connection test failed. Please check server configuration.');
                return;
            }
            
            // Check if student already has an account
            fetch('check_student_account.php?student_id=' + encodeURIComponent(studentId) + '&email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.hasAccount) {
                    alert('Student already has an account in signin_db');
                    return;
                }
                
                // Get role from course
                let role = course;
                if (course.toLowerCase().includes('bsit')) {
                    role = 'BSIT';
                } else if (course.toLowerCase().includes('bscs')) {
                    role = 'BSCS';
                } else {
                    role = 'Student';
                }
                
                // Confirm account creation
                const confirmCreate = confirm('Create account for ' + studentName + ' with email: ' + email + '?\n\nUsername: ' + email.split('@')[0] + '\nRole: ' + role + '\n\nPassword will be auto-generated and sent to their email.');
                if (!confirmCreate) return;
                
                // Create account with auto-generated username and password
                const formData = new FormData();
                formData.append('student_id', studentId);
                formData.append('student_name', studentName);
                formData.append('email', email);
                formData.append('role', role);
                // Username and password will be auto-generated in handler
                
                console.log('Sending request to create_student_account_handler.php with data:', {
                    student_id: studentId,
                    student_name: studentName,
                    email: email,
                    role: role
                });
                
                fetch('create_student_account_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Server Response:', data);
                    if (data.success) {
                        let message = 'Account created successfully for ' + studentName + '!\n\n';
                        message += 'Username: ' + (data.username || '[Auto-generated and sent via email]') + '\n';
                        message += 'Password: ' + (data.password || '[Auto-generated and sent via email]') + '\n';
                        message += 'Role: ' + role + '\n\n';
                        message += data.message;
                        alert(message);
                        // Optionally refresh page to update list
                        location.reload();
                    } else {
                        alert('Error creating account: ' + data.message);
                        console.error('Server Response:', data);
                        console.error('Error Details:', {
                            studentId: studentId,
                            studentName: studentName,
                            email: email,
                            role: role,
                            response: data
                        });
                    }
                })
                .catch(error => {
                    console.error('Network Error:', error);
                    console.error('Error Details:', {
                        studentId: studentId,
                        studentName: studentName,
                        email: email,
                        role: role,
                        error: error
                    });
                    alert('Network error occurred while creating the account. Please check console for details.');
                });
            })
            .catch(error => {
                console.error('Error checking account:', error);
                // If check fails, proceed with account creation anyway
                createAccountDirectly(studentId, studentName, email, course);
            });
        })
        .catch(error => {
            console.error('Connection Test Failed:', error);
            alert('Failed to connect to server. Please check your internet connection and server status.');
        });
    }
    
    // Fallback function to create account directly
    function createAccountDirectly(studentId, studentName, email, course) {
        // Get role from course
        let role = course;
        if (course.toLowerCase().includes('bsit')) {
            role = 'BSIT';
        } else if (course.toLowerCase().includes('bscs')) {
            role = 'BSCS';
        } else {
            role = 'Student';
        }
        
        const confirmCreate = confirm('Create account for ' + studentName + ' with email: ' + email + '?\n\nUsername: ' + email.split('@')[0] + '\nRole: ' + role + '\n\nPassword will be auto-generated and sent to their email.');
        if (!confirmCreate) return;
        
        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('student_name', studentName);
        formData.append('email', email);
        formData.append('role', role);
        // Username and password will be auto-generated in handler
        
        fetch('create_student_account_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = 'Account created successfully for ' + studentName + '!\n\n';
                message += 'Username: ' + (data.username || '[Auto-generated and sent via email]') + '\n';
                message += 'Password: ' + (data.password || '[Auto-generated and sent via email]') + '\n';
                message += 'Role: ' + role + '\n\n';
                message += data.message;
                alert(message);
                location.reload();
            } else {
                alert('Error creating account: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the account.');
        });
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
    }
    
    function closeCreateAccountModal() {
        createAccountModal.style.display = 'none';
    }
    
    function createStudentAccount(event) {
        event.preventDefault();
        
        const formData = new FormData(document.getElementById('createAccountForm'));
        
        // Send data to server
        fetch('create_student_account_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Account created successfully!');
                closeCreateAccountModal();
                // Optionally refresh the page or update the student list
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while creating the account.');
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
    document.addEventListener('DOMContentLoaded', updateClock);
</script>
</body>
</html>
