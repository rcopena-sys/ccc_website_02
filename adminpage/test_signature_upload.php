<?php
session_start();
require_once 'db.php';

echo "<h2>Test E-Signature Upload</h2>";

// Check if evaluation_signatures table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'evaluation_signatures'");

if (!$tableCheck || $tableCheck->num_rows == 0) {
    echo "<p style='color: red;'>❌ evaluation_signatures table does not exist. Please run setup first.</p>";
    echo "<p><a href='setup_evaluation_signatures.php'>← Run Setup</a></p>";
    exit;
}

// Get current user info
$evaluator_id = $_SESSION['user_id'] ?? 1;
echo "<p>Current User ID: " . $evaluator_id . "</p>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $year_semester = trim($_POST['year_semester'] ?? '');
    
    if (empty($student_id) || empty($year_semester)) {
        echo "<p style='color: red;'>❌ Student ID and Year-Semester are required</p>";
    } else {
        echo "<p>Processing: Student ID = " . htmlspecialchars($student_id) . ", Year-Semester = " . htmlspecialchars($year_semester) . "</p>";
        // Handle file upload
        $signature_filename = null;
        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_type = $_FILES['signature']['type'];
            $file_size = $_FILES['signature']['size'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                // Create upload directory if it doesn't exist
                $upload_dir = 'uploads/evaluation_signatures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
                $signature_filename = 'test_' . $student_id . '_' . $year_semester . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $signature_filename;
                
                // Upload signature
                if (move_uploaded_file($_FILES['signature']['tmp_name'], $upload_path)) {
                    echo "<p style='color: green;'>✅ File uploaded successfully: " . $signature_filename . "</p>";
                } else {
                    echo "<p style='color: red;'>❌ File upload failed</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Invalid file type or size too large</p>";
            }
        }
        
        // Store in database
        if (!empty($signature_filename)) {
            $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, signature_filename, status) 
                    VALUES (?, ?, ?, ?, 'approved') 
                    ON DUPLICATE KEY UPDATE signature_filename = VALUES(signature_filename), status = 'approved', updated_at = CURRENT_TIMESTAMP";
            
            /** @var mysqli_stmt|false $stmt */
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('siss', $student_id, $evaluator_id, $year_semester, $signature_filename);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Signature saved to database!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Database error: " . $conn->error . "</p>";
                }
                $stmt->close();
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No file uploaded, but database entry created</p>";
            
            // Create entry without file
            $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, status) 
                    VALUES (?, ?, ?, 'pending') 
                    ON DUPLICATE KEY UPDATE status = 'pending', updated_at = CURRENT_TIMESTAMP";
            
            /** @var mysqli_stmt|false $stmt */
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sis', $student_id, $evaluator_id, $year_semester);
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Database entry created (no file)</p>";
                } else {
                    echo "<p style='color: red;'>❌ Database error: " . $conn->error . "</p>";
                }
                $stmt->close();
            }
        }
    }
}

// Show existing signatures
echo "<h3>Current Signatures:</h3>";
$result = $conn->query("SELECT * FROM evaluation_signatures ORDER BY created_at DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['student_id'] . "</td>";
        echo "<td>" . $row['year_semester'] . "</td>";
        echo "<td>" . ($row['signature_filename'] ?: 'None') . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No signatures found in database</p>";
}

// Quick test: Add sample signature
if (isset($_GET['add_sample'])) {
    $sampleStudent = 'SAMPLE-001';
    $sampleYearSem = '1-1';
    
    $sql = "INSERT INTO evaluation_signatures (student_id, evaluator_id, year_semester, status) 
            VALUES (?, ?, ?, 'approved') 
            ON DUPLICATE KEY UPDATE status = 'approved', updated_at = CURRENT_TIMESTAMP";
    
    /** @var mysqli_stmt|false $stmt */
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sis', $sampleStudent, $evaluator_id, $sampleYearSem);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Sample signature added for " . $sampleStudent . " (" . $sampleYearSem . ")</p>";
        } else {
            echo "<p style='color: red;'>❌ Database error: " . $conn->error . "</p>";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<p><a href="?add_sample=1" style="background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">🚀 Add Sample Signature</a></p>

<h3>Add Test Signature:</h3>
<form method="POST" enctype="multipart/form-data">
    <div style="margin-bottom: 10px;">
        <label>Student ID:</label><br>
        <input type="text" name="student_id" value="TEST-001" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Year-Semester:</label><br>
        <select name="year_semester" required>
            <option value="1-1">1st Year - 1st Sem</option>
            <option value="1-2">1st Year - 2nd Sem</option>
            <option value="2-1">2nd Year - 1st Sem</option>
            <option value="2-2">2nd Year - 2nd Sem</option>
            <option value="3-1">3rd Year - 1st Sem</option>
            <option value="3-2">3rd Year - 2nd Sem</option>
            <option value="4-1">4th Year - 1st Sem</option>
            <option value="4-2">4th Year - 2nd Sem</option>
        </select>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Signature (Optional):</label><br>
        <input type="file" name="signature" accept="image/*">
    </div>
    <button type="submit" style="background: #1e3c72; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Add Test Signature</button>
</form>

<p><a href="setup_evaluation_signatures.php">← Back to Setup</a></p>
<p><a href="stueval.php">← Student Evaluation</a></p>
