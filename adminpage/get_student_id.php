<?php
include 'db.php';

echo "Searching for students in signin_db...\n\n";

// Get all students with BSIT and BSCS roles
$sql = "SELECT s.id, s.firstname, s.lastname, s.student_id, s.course, s.email, r.role_name 
        FROM signin_db s 
        LEFT JOIN roles r ON s.role_id = r.role_id 
        WHERE r.role_name IN ('BSIT', 'BSCS')
        ORDER BY s.course, s.lastname, s.firstname";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Students found:\n";
    echo "ID\tName\t\t\tStudent ID\tCourse\tEmail\n";
    echo "------------------------------------------------------------\n";
    
    while($row = $result->fetch_assoc()) {
        printf("%d\t%-20s\t%s\t%s\t%s\n", 
            $row['id'], 
            $row['firstname'] . ' ' . $row['lastname'], 
            $row['student_id'], 
            $row['course'], 
            $row['email']
        );
    }
} else {
    echo "No students found with BSIT or BSCS roles.\n";
}

$conn->close();
?>
