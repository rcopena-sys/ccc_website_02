<?php
require_once 'db.php';

echo "<h2>Debug: Regular Prerequisites Check</h2>";

// Get a sample irregular student
$studentQuery = "SELECT student_id, classification FROM students_db WHERE LOWER(classification) = 'irregular' LIMIT 1";
$studentResult = $conn->query($studentQuery);

if ($studentResult && $studentResult->num_rows > 0) {
    $student = $studentResult->fetch_assoc();
    $studentId = $student['student_id'];
    $classification = $student['classification'];
    
    echo "<h3>Student Info:</h3>";
    echo "<p>Student ID: " . htmlspecialchars($studentId) . "</p>";
    echo "<p>Classification: " . htmlspecialchars($classification) . "</p>";
    
    // Check the specific courses mentioned
    $courses = ['CAP 302', 'CAP 401', 'PRAC 401'];
    
    echo "<h3>Course Prerequisites:</h3>";
    
    foreach ($courses as $courseCode) {
        echo "<h4>Course: " . htmlspecialchars($courseCode) . "</h4>";
        
        // Get course info from curriculum
        $curriculumQuery = "SELECT course_code, course_title, prerequisites FROM curriculum WHERE course_code = ?";
        $stmt = $conn->prepare($curriculumQuery);
        if ($stmt) {
            $stmt->bind_param('s', $courseCode);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $course = $result->fetch_assoc();
                $prerequisites = $course['prerequisites'] ?? '';
                
                echo "<p><strong>Title:</strong> " . htmlspecialchars($course['course_title']) . "</p>";
                echo "<p><strong>Prerequisites:</strong> '" . htmlspecialchars($prerequisites) . "'</p>";
                echo "<p><strong>Prerequisites Length:</strong> " . strlen($prerequisites) . "</p>";
                echo "<p><strong>Prerequisites (lowercase):</strong> '" . htmlspecialchars(strtolower($prerequisites)) . "'</p>";
                
                // Test the blocking logic
                $blockingPrereqs = ['regular', 'regular 3rd year', 'regular 4th year'];
                $prereqLower = strtolower($prerequisites);
                $shouldBlock = false;
                
                echo "<p><strong>Blocking Check:</strong></p><ul>";
                foreach ($blockingPrereqs as $blockingPrereq) {
                    $contains = strpos($prereqLower, $blockingPrereq) !== false;
                    echo "<li>Contains '" . htmlspecialchars($blockingPrereq) . "': " . ($contains ? 'YES' : 'NO') . "</li>";
                    if ($contains) {
                        $shouldBlock = true;
                    }
                }
                echo "</ul>";
                
                echo "<p><strong>Should Block:</strong> " . ($shouldBlock ? 'YES' : 'NO') . "</p>";
                
                // Test isIrregularStudent function
                $isIrregular = false;
                
                // Check in students_db
                $query = "SELECT classification FROM students_db WHERE student_id = ? AND LOWER(classification) = 'irregular'";
                $stmt2 = $conn->prepare($query);
                if ($stmt2) {
                    $stmt2->bind_param('s', $studentId);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($result2->num_rows > 0) {
                        $isIrregular = true;
                    }
                    $stmt2->close();
                }
                
                // Check in signin_db if not found
                if (!$isIrregular) {
                    $query = "SELECT classification FROM signin_db WHERE student_id = ? AND LOWER(classification) = 'irregular'";
                    $stmt3 = $conn->prepare($query);
                    if ($stmt3) {
                        $stmt3->bind_param('s', $studentId);
                        $stmt3->execute();
                        $result3 = $stmt3->get_result();
                        if ($result3->num_rows > 0) {
                            $isIrregular = true;
                        }
                        $stmt3->close();
                    }
                }
                
                echo "<p><strong>Student is Irregular:</strong> " . ($isIrregular ? 'YES' : 'NO') . "</p>";
                echo "<p><strong>Final Result (should block grade):</strong> " . ($isIrregular && $shouldBlock ? 'YES' : 'NO') . "</p>";
                
            } else {
                echo "<p>Course not found in curriculum table.</p>";
            }
            
            $stmt->close();
        } else {
            echo "<p>Database error preparing curriculum query.</p>";
        }
        
        echo "<hr>";
    }
    
} else {
    echo "<p>No irregular students found in database.</p>";
}

// Also check what's actually in the curriculum table for all courses with "Regular" prerequisites
echo "<h3>All Courses with 'Regular' in Prerequisites:</h3>";

$query = "SELECT course_code, course_title, prerequisites FROM curriculum WHERE prerequisites LIKE '%Regular%'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Course Code</th><th>Title</th><th>Prerequisites</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['prerequisites']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No courses found with 'Regular' in prerequisites.</p>";
}

$conn->close();
?>
