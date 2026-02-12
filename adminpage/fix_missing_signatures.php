<?php
require_once 'db.php';

echo "<h2>Fix Missing Program Director Signatures</h2>";

// Step 1: Check what students have subjects but no signatures
echo "<h3>🔍 Step 1: Find Students with Subjects but No Signatures</h3>";

// Get all students who have irregular subjects
$studentsQuery = "SELECT DISTINCT student_id FROM irregular_db ORDER BY student_id";
$studentsResult = $conn->query($studentsQuery);

if ($studentsResult && $studentsResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Student ID</th><th>Has Subjects</th><th>Has Signatures</th><th>Action</th></tr>";
    
    while ($student = $studentsResult->fetch_assoc()) {
        $student_id = $student['student_id'];
        
        // Check if student has subjects
        $subjectCount = 0;
        $subjectQuery = "SELECT COUNT(*) as count FROM irregular_db WHERE student_id = ?";
        $subjectStmt = $conn->prepare($subjectQuery);
        if ($subjectStmt) {
            $subjectStmt->bind_param('s', $student_id);
            $subjectStmt->execute();
            $subjectResult = $subjectStmt->get_result();
            if ($subjectResult) {
                $subjectRow = $subjectResult->fetch_assoc();
                $subjectCount = $subjectRow['count'];
            }
            $subjectStmt->close();
        }
        
        // Check if student has signatures
        $signatureCount = 0;
        $sigQuery = "SELECT COUNT(*) as count FROM evaluation_signatures WHERE student_id = ?";
        $sigStmt = $conn->prepare($sigQuery);
        if ($sigStmt) {
            $sigStmt->bind_param('s', $student_id);
            $sigStmt->execute();
            $sigResult = $sigStmt->get_result();
            if ($sigResult) {
                $sigRow = $sigResult->fetch_assoc();
                $signatureCount = $sigRow['count'];
            }
            $sigStmt->close();
        }
        
        echo "<tr>";
        echo "<td>" . $student_id . "</td>";
        echo "<td style='text-align: center;'>" . ($subjectCount > 0 ? "✅ " . $subjectCount : "❌") . "</td>";
        echo "<td style='text-align: center;'>" . ($signatureCount > 0 ? "✅ " . $signatureCount : "❌") . "</td>";
        echo "<td>";
        if ($subjectCount > 0 && $signatureCount == 0) {
            echo "<a href='?add_signature=" . urlencode($student_id) . "' style='background: #28a745; color: white; padding: 3px 8px; text-decoration: none; border-radius: 3px;'>Add Signature</a>";
        } else {
            echo "OK";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No students found with irregular subjects</p>";
}

// Step 2: Add signature if requested
if (isset($_GET['add_signature'])) {
    $student_id = urldecode($_GET['add_signature']);
    $evaluator_id = $_SESSION['user_id'] ?? 1;
    
    echo "<h3>📝 Adding Signature for: " . htmlspecialchars($student_id) . "</h3>";
    
    // Get the student's subjects to determine year/semester
    $yearSemQuery = "SELECT DISTINCT year_level, semester FROM irregular_db WHERE student_id = ? ORDER BY year_level, semester LIMIT 1";
    $yearSemStmt = $conn->prepare($yearSemQuery);
    if ($yearSemStmt) {
        $yearSemStmt->bind_param('s', $student_id);
        $yearSemStmt->execute();
        $yearSemResult = $yearSemStmt->get_result();
        if ($yearSemResult && $yearSemResult->num_rows > 0) {
            $yearSemRow = $yearSemResult->fetch_assoc();
            $year_level = $yearSemRow['year_level'];
            $semester = $yearSemRow['semester'];
            $year_semester = $year_level . '-' . $semester;
            
            echo "<p>Detected Year-Semester: " . $year_semester . "</p>";
            
            // Add signature record (without file for now)
            $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, status) 
                    VALUES (?, ?, ?, 'approved') 
                    ON DUPLICATE KEY UPDATE status = 'approved', updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sis', $student_id, $evaluator_id, $year_semester);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Signature record added successfully!</p>";
                    echo "<p><a href='?add_file_signature=" . urlencode($student_id) . "'>📷 Click here to upload a signature file</a></p>";
                } else {
                    echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Could not determine year/semester for this student</p>";
        }
        $yearSemStmt->close();
    }
}

// Step 3: Add file signature if requested
if (isset($_GET['add_file_signature'])) {
    $student_id = urldecode($_GET['add_file_signature']);
    
    echo "<h3>📷 Upload Signature File for: " . htmlspecialchars($student_id) . "</h3>";
    
    // Create a simple signature file
    $upload_dir = 'uploads/evaluation_signatures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Create a simple test signature
    $filename = 'signature_' . $student_id . '_' . time() . '.png';
    $filepath = $upload_dir . $filename;
    
    // Create a simple image with text
    $img = imagecreatetruecolor(300, 80);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $text = imagecolorallocate($img, 0, 0, 139); // Dark blue
    imagefill($img, 0, 0, $bg);
    
    // Add signature text
    $font_size = 20;
    $text = "Program Director";
    imagettftext($img, $font_size, 0, 20, 45, $text, 'arial.ttf', $text);
    imagettftext($img, 12, 0, 20, 65, $text, 'arial.ttf', $student_id);
    
    imagepng($img, $filepath);
    imagedestroy($img);
    
    // Update database with filename
    $sql = "UPDATE evaluation_signatures SET signature_filename = ? WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $filename, $student_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Signature file created and linked!</p>";
            echo "<p>Filename: " . $filename . "</p>";
            echo "<img src='" . $filepath . "' style='border: 1px solid #ddd; padding: 5px;' alt='Generated Signature'>";
        } else {
            echo "<p style='color: red;'>❌ Database error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

// Step 4: Test prospectus display
echo "<h3>🧪 Test Prospectus Display</h3>";
echo "<p>Click these links to test if signatures appear in prospectus pages:</p>";
echo "<ul>";
echo "<li><a href='../student/dcipros1st.php?student_id=" . urlencode($student_id ?? 'SAMPLE-001') . "' target='_blank'>📄 1st Year Prospectus</a></li>";
echo "<li><a href='../student/dcipros2nd.php?student_id=" . urlencode($student_id ?? 'SAMPLE-001') . "' target='_blank'>📄 2nd Year Prospectus</a></li>";
echo "<li><a href='../student/dcipros3rd.php?student_id=" . urlencode($student_id ?? 'SAMPLE-001') . "' target='_blank'>📄 3rd Year Prospectus</a></li>";
echo "<li><a href='../student/dcipros4th.php?student_id=" . urlencode($student_id ?? 'SAMPLE-001') . "' target='_blank'>📄 4th Year Prospectus</a></li>";
echo "</ul>";

$conn->close();
?>

<p><a href="stueval.php">← Back to Student Evaluation</a></p>
