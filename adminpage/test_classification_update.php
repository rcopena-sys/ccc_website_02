<?php
require_once 'db.php';

echo "<h2>Test Automatic Classification Update</h2>";

// Test the updateClassificationBasedOnGrades function
function testUpdateClassificationBasedOnGrades($conn, $studentId) {
    echo "<h3>Testing Student: $studentId</h3>";
    
    // Get current classification
    $currentQuery = "SELECT classification FROM signin_db WHERE student_id = ?";
    $stmt = $conn->prepare($currentQuery);
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $currentClassification = $student['classification'];
            echo "<p><strong>Current Classification:</strong> " . htmlspecialchars($currentClassification) . "</p>";
        } else {
            echo "<p>Student not found in signin_db</p>";
            return;
        }
        $stmt->close();
    }
    
    // Check for failed grades
    $failedQuery = "SELECT COUNT(*) as failed_count FROM grades_db 
                    WHERE student_id = ? AND (final_grade >= 5.00 OR final_grade IS NULL)";
    $stmt = $conn->prepare($failedQuery);
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $failedData = $result->fetch_assoc();
        $failedCount = $failedData['failed_count'];
        $stmt->close();
        
        echo "<p><strong>Failed Grades Count:</strong> $failedCount</p>";
        
        if ($failedCount > 0) {
            echo "<p><strong>Should be classified as:</strong> Irregular</p>";
        } else {
            echo "<p><strong>Should be classified as:</strong> Regular</p>";
        }
    }
    
    // Show actual failed grades
    $gradesQuery = "SELECT course_code, final_grade FROM grades_db 
                   WHERE student_id = ? AND (final_grade >= 5.00 OR final_grade IS NULL)
                   LIMIT 5";
    $stmt = $conn->prepare($gradesQuery);
    if ($stmt) {
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p><strong>Failed Grades:</strong></p><ul>";
            while ($row = $result->fetch_assoc()) {
                $grade = $row['final_grade'] ?? 'NULL';
                echo "<li>" . htmlspecialchars($row['course_code']) . ": " . htmlspecialchars($grade) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>Failed Grades:</strong> None found</p>";
        }
        $stmt->close();
    }
    
    echo "<hr>";
}

// Test specific students
$testStudents = [
    '2022-10873', // The student we were looking at
    '2022-12346', // Known irregular student
];

foreach ($testStudents as $studentId) {
    testUpdateClassificationBasedOnGrades($conn, $studentId);
}

// Test the automatic update function
echo "<h3>Testing Automatic Update Function</h3>";

// Test one student
$testStudentId = '2022-10873';
echo "<p><strong>Running automatic update for $testStudentId...</strong></p>";

// Simulate the function
function updateClassificationBasedOnGrades($conn, $studentId) {
    // Get current classification
    $currentQuery = "SELECT classification FROM signin_db WHERE student_id = ?";
    $stmt = $conn->prepare($currentQuery);
    if (!$stmt) {
        echo "<p>Failed to prepare current classification query</p>";
        return false;
    }
    
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p>Student $studentId not found in signin_db</p>";
        $stmt->close();
        return false;
    }
    
    $student = $result->fetch_assoc();
    $currentClassification = strtolower(trim($student['classification']));
    $stmt->close();
    
    echo "<p>Current classification: $currentClassification</p>";
    
    // Check for failed grades
    $failedQuery = "SELECT COUNT(*) as failed_count FROM grades_db 
                    WHERE student_id = ? AND (final_grade >= 5.00 OR final_grade IS NULL)";
    $stmt = $conn->prepare($failedQuery);
    if (!$stmt) {
        echo "<p>Failed to prepare failed grades query</p>";
        return false;
    }
    
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $failedData = $result->fetch_assoc();
    $failedCount = $failedData['failed_count'];
    $stmt->close();
    
    echo "<p>Failed grades count: $failedCount</p>";
    
    // Determine if student should be irregular
    $shouldBeIrregular = ($failedCount > 0);
    
    if ($shouldBeIrregular && $currentClassification !== 'irregular') {
        echo "<p><strong>UPDATE NEEDED: Should change to Irregular</strong></p>";
        return true;
    } elseif (!$shouldBeIrregular && $currentClassification === 'irregular') {
        echo "<p><strong>UPDATE NEEDED: Should change to Regular</strong></p>";
        return true;
    } else {
        echo "<p>No update needed</p>";
        return true;
    }
}

$updateNeeded = updateClassificationBasedOnGrades($conn, $testStudentId);

if ($updateNeeded) {
    echo "<p><strong>Result:</strong> Classification update is needed!</p>";
} else {
    echo "<p><strong>Result:</strong> Classification is correct.</p>";
}

$conn->close();
?>
