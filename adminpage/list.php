<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
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
        }
        .search-bar {
            margin-bottom: 30px;
            padding: 10px;
            width: 250px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th { background-color: #f2f2f2; }
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
            
        </div>
        <img class="watermark" src="dci.png.png" alt="Watermark Logo" width="300">
        <input type="text" id="searchInput" class="search-bar" placeholder="Search by name, ID, email, or course..." onkeyup="search()">
        
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
        </style>
        <?php
        include 'db.php';

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

        // Base query - using only students_db
        $studentsWhere = [];
        
        // Add search condition if search term exists
        if (!empty($search)) {
            $sanitized_search = $conn->real_escape_string($search);
            $studentsWhere[] = "(st.student_id LIKE '%$sanitized_search%' OR 
                               st.student_name LIKE '%$sanitized_search%')";
        }

        // Add course filter if specified
        if (!empty($course)) {
            $sanitized_course = $conn->real_escape_string($course);
            $studentsWhere[] = "st.programs = '$sanitized_course'";
        }

        $sql = "SELECT 
                    st.student_name as firstname,
                    '' as lastname,
                    '' as email,
                    st.student_id,
                    st.programs as course,
                    st.classification,
                    'Student' as role_name,
                    st.curriculum,
             
                    st.fiscal_year,
                    'students_db' as source_table
                FROM students_db st" . 
                (!empty($studentsWhere) ? " WHERE " . implode(' AND ', $studentsWhere) : "") . "
                ORDER BY st.student_id, st.student_name";

        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>Name</th><th>Email</th><th>Student ID</th><th>Course</th><th>Classification</th><th>Role</th><th>Curriculum</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr class="student-row">';
         
                echo '<td>' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars(formatStudentId($row['student_id'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['course']) . '</td>';
                echo '<td>' . htmlspecialchars($row['classification'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['role_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['curriculum'] ?? 'N/A') . '</td>';
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
    
    // Show modal with student info
    function showStudentModal(studentId, studentName) {
        document.getElementById('modalStudentId').textContent = studentId;
        document.getElementById('modalStudentName').textContent = studentName;
        currentStudentId = studentId;
        modal.style.display = 'block';
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
