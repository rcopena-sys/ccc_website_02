<?php
require_once 'db.php';

echo "<h2>🔍 Find Your Signatures</h2>";

// Get current user
$current_user_id = $_SESSION['user_id'] ?? 0;
echo "<p>Current User ID: " . $current_user_id . "</p>";

// Check 1: Profile signature in signin_db
echo "<h3>📝 Profile Signature (signin_db table)</h3>";
$profileQuery = "SELECT id, firstname, lastname, email, esignature FROM signin_db WHERE id = ?";
$profileStmt = $conn->prepare($profileQuery);
if ($profileStmt) {
    $profileStmt->bind_param('i', $current_user_id);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    if ($profileResult && $profileResult->num_rows > 0) {
        $profile = $profileResult->fetch_assoc();
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . $profile['id'] . "</td></tr>";
        echo "<tr><td>Name</td><td>" . $profile['firstname'] . " " . $profile['lastname'] . "</td></tr>";
        echo "<tr><td>Email</td><td>" . $profile['email'] . "</td></tr>";
        echo "<tr><td>E-Signature</td><td>" . ($profile['esignature'] ?: 'NULL') . "</td></tr>";
        echo "</table>";
        
        if (!empty($profile['esignature'])) {
            $profile_file = 'uploads/esignatures/' . $profile['esignature'];
            if (file_exists($profile_file)) {
                echo "<p style='color: green;'>✅ Profile signature file exists!</p>";
                echo "<img src='" . $profile_file . "' style='max-height: 60px; border: 1px solid #ddd; padding: 5px;' alt='Profile Signature'>";
            } else {
                echo "<p style='color: red;'>❌ Profile signature file not found: " . $profile_file . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No profile signature in signin_db</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ User not found in signin_db</p>";
    }
    $profileStmt->close();
}

// Check 2: Evaluation signatures in evaluation_signatures table
echo "<h3>📋 Evaluation Signatures (evaluation_signatures table)</h3>";

// As evaluator (Program Director signatures you uploaded)
echo "<h4>Signatures You Uploaded (as Evaluator):</h4>";
$evaluatorQuery = "SELECT student_id, year_semester, signature_filename, status, created_at 
                    FROM evaluation_signatures WHERE evaluator_id = ? ORDER BY created_at DESC";
$evaluatorStmt = $conn->prepare($evaluatorQuery);
if ($evaluatorStmt) {
    $evaluatorStmt->bind_param('i', $current_user_id);
    $evaluatorStmt->execute();
    $evaluatorResult = $evaluatorStmt->get_result();
    if ($evaluatorResult && $evaluatorResult->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Student ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Date</th><th>File</th></tr>";
        while ($row = $evaluatorResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['student_id'] . "</td>";
            echo "<td>" . $row['year_semester'] . "</td>";
            echo "<td>" . ($row['signature_filename'] ?: 'None') . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td>";
            if (!empty($row['signature_filename'])) {
                $file_path = 'uploads/evaluation_signatures/' . $row['signature_filename'];
                if (file_exists($file_path)) {
                    echo "<img src='" . $file_path . "' style='max-height: 30px; border: 1px solid #ddd;' alt='Signature'>";
                } else {
                    echo "❌ Missing";
                }
            } else {
                echo "No file";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No evaluation signatures found as evaluator</p>";
    }
    $evaluatorStmt->close();
}

// As student (signatures for your student evaluations)
echo "<h4>Signatures for Your Student Evaluations:</h4>";
$studentQuery = "SELECT evaluator_id, year_semester, signature_filename, status, created_at 
                 FROM evaluation_signatures WHERE student_id = ? ORDER BY created_at DESC";
$studentStmt = $conn->prepare($studentQuery);
if ($studentStmt) {
    // Get student_id from signin_db
    $studentIdQuery = "SELECT student_id FROM signin_db WHERE id = ?";
    $studentIdStmt = $conn->prepare($studentIdQuery);
    if ($studentIdStmt) {
        $studentIdStmt->bind_param('i', $current_user_id);
        $studentIdStmt->execute();
        $studentIdResult = $studentIdStmt->get_result();
        if ($studentIdResult && $studentIdResult->num_rows > 0) {
            $studentIdRow = $studentIdResult->fetch_assoc();
            $student_id = $studentIdRow['student_id'];
            
            $studentStmt->bind_param('s', $student_id);
            $studentStmt->execute();
            $studentResult = $studentStmt->get_result();
            if ($studentResult && $studentResult->num_rows > 0) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Evaluator ID</th><th>Year-Sem</th><th>Filename</th><th>Status</th><th>Date</th><th>File</th></tr>";
                while ($row = $studentResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['evaluator_id'] . "</td>";
                    echo "<td>" . $row['year_semester'] . "</td>";
                    echo "<td>" . ($row['signature_filename'] ?: 'None') . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td>";
                    if (!empty($row['signature_filename'])) {
                        $file_path = 'uploads/evaluation_signatures/' . $row['signature_filename'];
                        if (file_exists($file_path)) {
                            echo "<img src='" . $file_path . "' style='max-height: 30px; border: 1px solid #ddd;' alt='Signature'>";
                        } else {
                            echo "❌ Missing";
                        }
                    } else {
                        echo "No file";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>⚠️ No evaluation signatures found for your student ID</p>";
            }
        }
        $studentIdStmt->close();
    }
    $studentStmt->close();
}

// Check 3: All signatures in database
echo "<h3>📊 All Signatures in Database</h3>";
$allQuery = "SELECT COUNT(*) as total FROM evaluation_signatures";
$allResult = $conn->query($allQuery);
if ($allResult) {
    $row = $allResult->fetch_assoc();
    echo "<p><strong>Total evaluation signatures:</strong> " . $row['total'] . "</p>";
}

// Check upload directories
echo "<h3>📁 Upload Directories</h3>";
$directories = [
    'uploads/esignatures/' => 'Profile Signatures',
    'uploads/evaluation_signatures/' => 'Evaluation Signatures'
];

foreach ($directories as $dir => $desc) {
    echo "<p><strong>" . $desc . ":</strong> ";
    if (file_exists($dir)) {
        echo "✅ Exists";
        $files = glob($dir . '*');
        echo " (" . count($files) . " files)";
        if ($files && count($files) > 0) {
            echo "<ul>";
            foreach (array_slice($files, 0, 5) as $file) {
                echo "<li>" . basename($file) . "</li>";
            }
            if (count($files) > 5) {
                echo "<li>... and " . (count($files) - 5) . " more</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "❌ Missing";
    }
    echo "</p>";
}

$conn->close();
?>

<p><a href="profile.php">← Back to Profile</a></p>
<p><a href="stueval.php">← Student Evaluation</a></p>
