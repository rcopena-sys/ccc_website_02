<?php
require_once '../config/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Irregular Students</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            background: radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, rgba(29, 78, 216, 0.05) 0%, transparent 50%);
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
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            width: 220px;
            height: 100vh;
            color: white;
            padding: 30px 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: start;
            font-size: 1.1rem;
            font-weight: 500;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .profile-circle { 
            width: 90px; 
            height: 90px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
            margin-bottom: 25px; 
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        .profile-circle img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 50%; 
        }
        .add-btn, .bulk-update-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 12px;
            padding: 10px 16px;
            color: white;
            border: none;
            margin-right: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
            text-decoration: none;
            display: inline-block;
        }
        .add-btn:hover, .bulk-update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
        .content {
            flex-grow: 1;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 25px;
            border-radius: 24px;
            padding: 30px;
            overflow-y: auto;
            max-height: calc(100vh - 50px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .search-bar {
            margin-bottom: 25px;
            padding: 12px 16px;
            width: 280px;
            border-radius: 12px;
            border: 2px solid rgba(59, 130, 246, 0.2);
            background: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .search-bar:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        th, td {
            padding: 16px 12px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            text-align: center;
            font-size: 14px;
        }
        th { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: rgba(59, 130, 246, 0.02);
        }
        .student-row {
            transition: all 0.2s ease;
        }
        .student-row:hover {
            background: rgba(59, 130, 246, 0.05);
            transform: scale(1.01);
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 0;
        }
        #clock {
            position: fixed;
            top: 25px;
            right: 25px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            font-weight: 600;
            z-index: 1000;
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #1e3a8a;
            font-size: 14px;
        }
        .logout-btn {
            margin-top: auto;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 20px;
            cursor: pointer;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.2);
        }
        .logout-btn:hover { 
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
        }
        .student-name {
            color: #2563eb;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 2px 4px;
            border-radius: 4px;
        }
        .student-name:hover {
            color: #1d4ed8;
            background: rgba(59, 130, 246, 0.1);
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
            background: rgba(30, 58, 138, 0.4);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(20px);
            margin: 10% auto;
            padding: 30px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            width: 45%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(30, 58, 138, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .close {
            color: #64748b;
            float: right;
            font-size: 24px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(241, 245, 249, 0.8);
        }
        
        .close:hover {
            color: #1e3a8a;
            background: rgba(59, 130, 246, 0.1);
            transform: scale(1.1);
        }
        
        .modal h3 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .modal p {
            color: #475569;
            margin-bottom: 25px;
            font-size: 1rem;
        }
        
        .modal-actions {
            margin-top: 25px;
            text-align: right;
        }
        
        .add-curriculum-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
        }
        
        .add-curriculum-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }
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
                    <a href="regularstu.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none; border-bottom: 1px solid #c7d2fe;">Regular Students</a>
                    <a href="irregularstu.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none; border-bottom: 1px solid #c7d2fe; background: #c7d2fe;">Irregular Students</a>
                    <a href="stugra.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none; border-bottom: 1px solid #c7d2fe;">Student Grade</a>
                    <a href="stucuri.php" style="padding: 10px 16px; color: #3730a3; text-decoration: none;">Student Curriculum</a>
                </div>
            </div>
            <a href="stucuri.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin: 5px 0; background: #6366f1;">Curriculum</a>
            <a href="stueval.php" style="display: block; padding: 10px 0; color: white; text-decoration: none; border-radius: 8px; margin: 5px 0; background: #6366f1; transition: background-color 0.3s;">Student Evaluation</a>
        </nav>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
    <div class="content">
        <div id="clock"></div>
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

        // Base query - using only students_db, filtering for irregular students only
        $studentsWhere = ["(LOWER(st.classification) = 'irregular')"];
        
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
                    st.student_id as id,
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
            echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Student ID</th><th>Course</th><th>Classification</th><th>Role</th><th>Curriculum</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr class="student-row">';
                echo '<td>' . htmlspecialchars(formatStudentId($row['id'])) . '</td>';
                echo '<td><span class="student-name" onclick="showStudentModal(\'' . htmlspecialchars($row['student_id']) . '\', \'' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '\')">' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '</span></td>';
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
            echo '<p>No irregular students found.</p>';
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