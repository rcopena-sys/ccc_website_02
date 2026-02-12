<?php
require_once 'db_connect.php';

// Get student ID from barcode
$barcode = isset($_GET['code']) ? $_GET['code'] : '';

if (!empty($barcode)) {
    // Extract student ID from barcode (format: STUDENT-12345)
    $student_id = str_replace('STUDENT-', '', $barcode);
    
    // Fetch student data
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        // Redirect to student's prospectus with a flag to show it was scanned
        header("Location: dcipros1st.php?scanned=1&student_id=" . $student_id);
        exit();
    }
}

// If no valid barcode or student found, redirect to error page or home
header("Location: index.php");
exit();
?>
