<?php
require_once 'db.php';

echo "<h2>Student Classification Check</h2>";

// Check the specific student being viewed
$studentId = '2022-10873';

echo "<h3>Student: $studentId</h3>";

// Check in signin_db
$query = "SELECT student_id, firstname, lastname, classification FROM signin_db WHERE student_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo "<h4>Found in signin_db:</h4>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) . "</p>";
        echo "<p><strong>Classification:</strong> " . htmlspecialchars($student['classification']) . "</p>";
        echo "<p><strong>Is Irregular:</strong> " . (strtolower($student['classification']) === 'irregular' ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>Student not found in signin_db</p>";
    }
    $stmt->close();
}

// Check in students_db
$query = "SELECT student_id, student_name, classification FROM students_db WHERE student_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo "<h4>Found in students_db:</h4>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($student['student_name']) . "</p>";
        echo "<p><strong>Classification:</strong> " . htmlspecialchars($student['classification']) . "</p>";
        echo "<p><strong>Is Irregular:</strong> " . (strtolower($student['classification']) === 'irregular' ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>Student not found in students_db</p>";
    }
    $stmt->close();
}

// Also check the irregular student
$irregularStudentId = '2022-12346';
echo "<hr><h3>Irregular Student: $irregularStudentId</h3>";

// Check in signin_db
$query = "SELECT student_id, firstname, lastname, classification FROM signin_db WHERE student_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('s', $irregularStudentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo "<h4>Found in signin_db:</h4>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) . "</p>";
        echo "<p><strong>Classification:</strong> " . htmlspecialchars($student['classification']) . "</p>";
        echo "<p><strong>Is Irregular:</strong> " . (strtolower($student['classification']) === 'irregular' ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>Student not found in signin_db</p>";
    }
    $stmt->close();
}

$conn->close();
?>
