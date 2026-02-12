<?php
// Database connection
require_once 'db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if curriculum table exists, create if not
$tableCheck = $conn->query("SHOW TABLES LIKE 'curriculum'");
if ($tableCheck->num_rows === 0) {
    // Create the table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS curriculum (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fiscal_year VARCHAR(20) NOT NULL,
        program VARCHAR(10) NOT NULL,
        year_semester VARCHAR(10) NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        course_title VARCHAR(200) NOT NULL,
        lec_units DECIMAL(3,1) NOT NULL,
        lab_units DECIMAL(3,1) NOT NULL,
        total_units DECIMAL(3,1) NOT NULL,
        prerequisites TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
}

// Get form data
$program = $_POST['program'];
$year_semester = $_POST['year_semester'];
$fiscal_year = $_POST['fiscal_year'] ?? '2024-2025'; // Default fiscal year if not provided

// Prepare arrays for batch insertion
$subject_codes = $_POST['course_code'];
$course_titles = $_POST['course_title'];
$lec_units = $_POST['lec_units'];
$lab_units = $_POST['lab_units'];
$total_units = $_POST['total_units'];
$prerequisites = $_POST['prerequisites'];

// Initialize counters
$inserted = 0;
$errors = [];

// Begin transaction
$conn->begin_transaction();

try {
    // Insert each subject (check for duplicates first)
    for ($i = 0; $i < count($subject_codes); $i++) {
        // Check if this subject already exists for this program, fiscal year, and semester
        $check_sql = "SELECT id FROM curriculum WHERE fiscal_year = ? AND program = ? AND year_semester = ? AND subject_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssss", 
            $fiscal_year,
            $program,
            $year_semester,
            $subject_codes[$i]
        );
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // Subject doesn't exist, insert it
            $sql = "INSERT INTO curriculum (fiscal_year, program, year_semester, subject_code, course_title, lec_units, lab_units, total_units, prerequisites) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssiis", 
                $fiscal_year,
                $program,
                $year_semester,
                $subject_codes[$i],
                $course_titles[$i],
                $lec_units[$i],
                $lab_units[$i],
                $total_units[$i],
                $prerequisites[$i]
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = 'Row ' . ($i + 1) . ': ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            // Subject already exists, skip it
            $errors[] = 'Row ' . ($i + 1) . ': Subject ' . $subject_codes[$i] . ' already exists for this semester';
        }
        $check_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect based on program
    $redirect_page = ($program === 'BSCS') ? 'curi_cs.php' : 'curi_it.php';
    header("Location: " . $redirect_page . "?success=1");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: add_curriculum.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>