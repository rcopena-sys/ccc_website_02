<?php
require_once 'db.php';

echo "<h2>🧪 Test Subject Assignment & E-Signatures</h2>";

// Check evaluation_signatures table
echo "<h3>📊 Database Status</h3>";
/** @var mysqli_result|false $tableCheck */
$tableCheck = $conn->query("SHOW TABLES LIKE 'evaluation_signatures'");
echo "<p><strong>Table Status:</strong> " . ($tableCheck && $tableCheck->num_rows > 0 ? "✅ Exists" : "❌ Missing") . "</p>";

if ($tableCheck && $tableCheck->num_rows > 0) {
    /** @var mysqli_result|false $countResult */
    $countResult = $conn->query("SELECT COUNT(*) as count FROM evaluation_signatures");
    if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        echo "<p><strong>Total Signatures:</strong> " . $countRow['count'] . "</p>";
    }
    
    // Show recent entries
    /** @var mysqli_result|false $recentResult */
    $recentResult = $conn->query("SELECT * FROM evaluation_signatures ORDER BY created_at DESC LIMIT 5");
    if ($recentResult && $recentResult->num_rows > 0) {
        echo "<h4>Recent Entries:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Student ID</th><th>Evaluator ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Created</th></tr>";
        while ($row = $recentResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['student_id'] . "</td>";
            echo "<td>" . $row['evaluator_id'] . "</td>";
            echo "<td>" . $row['year_semester'] . "</td>";
            echo "<td>" . ($row['signature_filename'] ?: 'NULL') . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check upload directory
echo "<h3>📁 File System Status</h3>";
$uploadDir = 'uploads/evaluation_signatures/';
if (file_exists($uploadDir)) {
    echo "<p><strong>Upload Directory:</strong> ✅ Exists</p>";
    $files = glob($uploadDir . '*');
    echo "<p><strong>Files in directory:</strong> " . count($files) . "</p>";
    if ($files) {
        echo "<ul>";
        foreach (array_slice($files, 0, 5) as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
        }
        if (count($files) > 5) {
            echo "<li>... and " . (count($files) - 5) . " more</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p><strong>Upload Directory:</strong> ❌ Missing</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created upload directory</p>";
    }
}

// Test manual signature insertion
echo "<h3>🔧 Manual Test</h3>";
if (isset($_GET['add_test'])) {
    $testStudent = 'TEST-STUDENT-' . time();
    $testEvaluator = 1;
    $testYearSem = '1-1';
    $testFilename = 'test_signature_' . time() . '.png';
    
    // Create test file
    $img = imagecreatetruecolor(200, 60);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $text = imagecolorallocate($img, 0, 0, 139);
    imagefill($img, 0, 0, $bg);
    imagettftext($img, 16, 0, 10, 35, $text, 'arial.ttf', 'Test Signature');
    imagepng($img, $uploadDir . $testFilename);
    imagedestroy($img);
    
    // Insert into database
    $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, signature_filename, status) 
            VALUES (?, ?, ?, ?, 'approved')";
    /** @var mysqli_stmt|false $stmt */
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('siss', $testStudent, $testEvaluator, $testYearSem, $testFilename);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Test signature added successfully!</p>";
            echo "<p>Student: " . $testStudent . "</p>";
            echo "<p>File: " . $testFilename . "</p>";
            echo "<img src='" . $uploadDir . $testFilename . "' style='border: 1px solid #ddd; padding: 5px;' alt='Test Signature'>";
        } else {
            echo "<p style='color: red;'>❌ Database error: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "<p><a href='?add_test=1' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>🧪 Add Test Signature</a></p>";
}

// Check recent PHP errors
echo "<h3>🔍 Recent Error Logs</h3>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -10); // Last 10 lines
    echo "<p><strong>Log file:</strong> " . $errorLog . "</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
    foreach ($recentLines as $line) {
        if (strpos($line, 'E-signature') !== false || strpos($line, 'evaluation_signature') !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ Error log not found or not accessible</p>";
}

if ($conn) {
    $conn->close();
}
?>

<p><a href="stueval.php">← Go to Student Evaluation</a></p>
<p><a href="find_my_signatures.php">← Find My Signatures</a></p>

<h3>📋 How to Test:</h3>
<ol>
    <li>Go to <a href="stueval.php">Student Evaluation</a></li>
    <li>Select a student and click "Add Subject"</li>
    <li>Fill in subject details</li>
    <li>Upload an e-signature file (PNG/JPG)</li>
    <li>Click "Add Subject"</li>
    <li>Come back to this page to check results</li>
    <li>Check error logs if no signature appears</li>
</ol>
