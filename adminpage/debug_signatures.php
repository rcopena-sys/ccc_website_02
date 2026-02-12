<?php
require_once 'db.php';

echo "<h2>Debug E-Signature System</h2>";

// Check table contents
echo "<h3>1. Table Contents:</h3>";
$result = $conn->query("SELECT * FROM evaluation_signatures ORDER BY created_at DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Evaluator ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . $row['evaluator_id'] . "</td>";
        echo "<td>" . $row['year_semester'] . "</td>";
        echo "<td>" . ($row['signature_filename'] ?: 'NULL') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No records found in evaluation_signatures table</p>";
}

// Check upload directory
echo "<h3>2. Upload Directory:</h3>";
$uploadDir = 'uploads/evaluation_signatures/';
if (file_exists($uploadDir)) {
    echo "<p style='color: green;'>✅ Directory exists: " . $uploadDir . "</p>";
    if (is_writable($uploadDir)) {
        echo "<p style='color: green;'>✅ Directory is writable</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Directory may not be writable</p>";
    }
    
    $files = glob($uploadDir . '*');
    if ($files) {
        echo "<p>Files found: " . count($files) . "</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No files in upload directory</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Upload directory does not exist</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created upload directory</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create directory</p>";
    }
}

// Test with a sample student ID
echo "<h3>3. Test Prospectus Integration:</h3>";
$testStudentId = 'TEST-001'; // Change this to a real student ID
echo "<p>Testing with Student ID: " . $testStudentId . "</p>";

// Test 1st year query
$testQuery = "SELECT signature_filename FROM evaluation_signatures 
             WHERE student_id = ? AND year_semester IN ('1-1', '1-2') 
             ORDER BY evaluation_date DESC LIMIT 1";
$stmt = $conn->prepare($testQuery);
if ($stmt) {
    $stmt->bind_param('s', $testStudentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filename = $row['signature_filename'];
        echo "<p style='color: green;'>✅ 1st Year signature found: " . $filename . "</p>";
        
        if (!empty($filename) && file_exists($uploadDir . $filename)) {
            echo "<p style='color: green;'>✅ File exists on disk</p>";
            echo "<img src='" . $uploadDir . $filename . "' style='max-height:50px;border:1px solid #ddd;' alt='Test Signature'>";
        } else {
            echo "<p style='color: red;'>❌ File not found on disk</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No 1st year signature found for test student</p>";
    }
    $stmt->close();
}

// Quick fix: Add a test signature
echo "<h3>4. Quick Fix - Add Test Signature:</h3>";
if (isset($_GET['add_test'])) {
    $testStudent = 'TEST-001';
    $testEvaluator = 1;
    $testYearSem = '1-1';
    $testFilename = 'test_signature.png';
    
    // Create a simple test image
    $imagePath = $uploadDir . $testFilename;
    $img = imagecreatetruecolor(200, 60);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textColor = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $bg);
    imagettftext($img, 20, 0, 10, 40, $textColor, 'arial.ttf', 'Test Signature');
    imagepng($img, $imagePath);
    imagedestroy($img);
    
    // Add to database
    $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, signature_filename, status) 
            VALUES (?, ?, ?, ?, 'approved') 
            ON DUPLICATE KEY UPDATE signature_filename = VALUES(signature_filename), status = 'approved', updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('siss', $testStudent, $testEvaluator, $testYearSem, $testFilename);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Test signature added successfully!</p>";
            echo "<p>Student: " . $testStudent . ", Year-Sem: " . $testYearSem . "</p>";
            echo "<img src='" . $imagePath . "' style='max-height:50px;border:1px solid #ddd;' alt='Test Signature'>";
        } else {
            echo "<p style='color: red;'>❌ Database error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "<p><a href='?add_test=1'>Click here to add a test signature</a></p>";
}

$conn->close();
?>

<p><a href="stueval.php">← Test in Student Evaluation</a></p>
<p><a href="../student/dcipros1st.php">← Test in 1st Year Prospectus</a></p>
