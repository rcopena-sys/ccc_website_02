<?php
require_once 'db.php';

echo "<h2>E-Signature Setup</h2>";

// Check if evaluation_signatures table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'evaluation_signatures'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ evaluation_signatures table already exists!</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $result = $conn->query("DESCRIBE evaluation_signatures");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if any signatures exist
    $sigCheck = $conn->query("SELECT COUNT(*) as count FROM evaluation_signatures");
    if ($sigCheck) {
        $row = $sigCheck->fetch_assoc();
        echo "<h3>Signature Count:</h3>";
        echo "<p>Total signatures in database: " . $row['count'] . "</p>";
        
        if ($row['count'] > 0) {
            echo "<h3>Recent Signatures:</h3>";
            $recent = $conn->query("SELECT student_id, evaluator_id, year_semester, signature_filename, status, created_at 
                                   FROM evaluation_signatures ORDER BY created_at DESC LIMIT 5");
            if ($recent) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Student ID</th><th>Evaluator ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Date</th></tr>";
                while ($row = $recent->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['student_id'] . "</td>";
                    echo "<td>" . $row['evaluator_id'] . "</td>";
                    echo "<td>" . $row['year_semester'] . "</td>";
                    echo "<td>" . $row['signature_filename'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
} else {
    echo "<p style='color: red;'>❌ evaluation_signatures table does not exist!</p>";
    echo "<h3>Creating table...</h3>";
    
    // Create the table
    $createTableSQL = "CREATE TABLE evaluation_signatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        evaluator_id INT NOT NULL COMMENT 'ID of the admin/staff who evaluated',
        year_semester VARCHAR(10) NOT NULL COMMENT 'Format: 1-1, 1-2, 2-1, etc.',
        signature_filename VARCHAR(255) DEFAULT NULL COMMENT 'E-signature filename',
        evaluation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        comments TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_evaluation (student_id, evaluator_id, year_semester),
        INDEX idx_student_id (student_id),
        INDEX idx_evaluator_id (evaluator_id),
        INDEX idx_year_semester (year_semester)
    )";
    
    if ($conn->query($createTableSQL)) {
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

// Check upload directory
echo "<h3>Upload Directory Check:</h3>";
$uploadDir = 'uploads/evaluation_signatures/';
if (file_exists($uploadDir)) {
    echo "<p style='color: green;'>✅ Upload directory exists: " . $uploadDir . "</p>";
    if (is_writable($uploadDir)) {
        echo "<p style='color: green;'>✅ Directory is writable</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Directory exists but may not be writable</p>";
    }
    
    // List files
    $files = glob($uploadDir . '*');
    if ($files) {
        echo "<p>Files in directory: " . count($files) . "</p>";
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . " (" . filesize($file) . " bytes)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No files in directory</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Upload directory does not exist</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p style='color: green;'>✅ Created upload directory</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create upload directory</p>";
    }
}

$conn->close();
?>

<p><a href="stueval.php">← Go to Student Evaluation</a></p>
<p><a href="profile.php">← Go to Profile</a></p>
