<?php
require_once '../db_connect.php';

echo "<h2>Test Program Query from assign_curriculum</h2>";

// Test the query for signin_db students
echo "<h3>Test signin_db query:</h3>";
$sql = "SELECT 
            s.student_id as id,
            s.firstname,
            s.lastname,
            s.email,
            s.student_id,
            COALESCE(ac.program, r.role_name, 'N/A') as course,
            s.classification,
            r.role_name,
            'N/A' as curriculum,
            '' as category,
            '' as fiscal_year,
            'signin_db' as source_table
        FROM signin_db s 
        LEFT JOIN roles r ON s.role_id = r.role_id 
        LEFT JOIN assign_curriculum ac ON r.role_id = ac.program_id
        LIMIT 5";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Student ID</th><th>Course</th><th>Classification</th><th>Role</th><th>Source</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course']) . "</td>";
        echo "<td>" . htmlspecialchars($row['classification'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['source_table']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found for signin_db query</p>";
}

// Test the query for students_db students
echo "<h3>Test students_db query:</h3>";
$sql = "SELECT 
            st.student_id as id,
            st.student_name as firstname,
            '' as lastname,
            '' as email,
            st.student_id,
            COALESCE(ac.program, st.programs, 'N/A') as course,
            st.classification,
            'Student' as role_name,
            st.curriculum,
            st.category,
            st.fiscal_year,
            'students_db' as source_table
        FROM students_db st 
        LEFT JOIN assign_curriculum ac ON st.programs = ac.program
        LIMIT 5";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Student ID</th><th>Course</th><th>Classification</th><th>Role</th><th>Source</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course']) . "</td>";
        echo "<td>" . htmlspecialchars($row['classification'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['role_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['source_table']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results found for students_db query</p>";
}

$conn->close();
?>
