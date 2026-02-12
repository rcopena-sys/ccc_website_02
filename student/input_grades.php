<?php
require_once 'db_connect.php';

// Set the student number
$studentNumber = '2022-10873';

// Sample grades for each course
$grades = [
    // First Semester
    ['course_code' => 'CS 101', 'grade' => '1.5', 'course_title' => 'Introduction to Computing', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'IT 101', 'grade' => '1.75', 'course_title' => 'Introduction to Computing', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'MATH 101', 'grade' => '2.0', 'course_title' => 'Mathematics in the Modern World', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'US 101', 'grade' => '1.25', 'course_title' => 'Understanding the Self', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'IE 101', 'grade' => '1.5', 'course_title' => 'Euthenics 1', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'SEC 101', 'grade' => '1.75', 'course_title' => 'Living in the IT Era', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'ALG 101', 'grade' => '2.0', 'course_title' => 'College Algebra', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'PE 101', 'grade' => '1.5', 'course_title' => 'Movement Enhancement', 'year' => '1', 'sem' => '1'],
    ['course_code' => 'NSTP 101', 'grade' => '1.25', 'course_title' => 'National Service Training Program 1', 'year' => '1', 'sem' => '1'],
    
    // Second Semester
    ['course_code' => 'CS 102', 'grade' => '1.75', 'course_title' => 'Object Oriented Programming', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'IT 102', 'grade' => '1.5', 'course_title' => 'Information Management', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'NET 102', 'grade' => '2.0', 'course_title' => 'Computer Networking 1', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'IT 201', 'grade' => '1.75', 'course_title' => 'Data Structures and Algorithms with Laboratory', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'PCOM 102', 'grade' => '1.5', 'course_title' => 'Purposive Communication', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'IT 231', 'grade' => '2.0', 'course_title' => 'Operating System', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'CALC 102', 'grade' => '1.75', 'course_title' => 'Mechanics', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'PE 102', 'grade' => '1.5', 'course_title' => 'Rhythm Activities', 'year' => '1', 'sem' => '2'],
    ['course_code' => 'NSTP 102', 'grade' => '1.25', 'course_title' => 'National Service Training Program 2', 'year' => '1', 'sem' => '2']
];

// Prepare the SQL statement
$stmt = $conn->prepare("INSERT INTO grades_db (student_id, course_code, course_title, grade, year, sem) 
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     grade = VALUES(grade), 
                     course_title = VALUES(course_title), 
                     year = VALUES(year), 
                     sem = VALUES(sem)");

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("ssssss", $studentId, $courseCode, $courseTitle, $grade, $year, $sem);

$successCount = 0;
$errorCount = 0;

echo "<h2>Inserting Grades for Student: $studentNumber</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Course Code</th><th>Course Title</th><th>Grade</th><th>Year</th><th>Sem</th><th>Status</th></tr>";

foreach ($grades as $course) {
    $studentId = $studentNumber;
    $courseCode = $course['course_code'];
    $courseTitle = $course['course_title'];
    $grade = $course['grade'];
    $year = $course['year'];
    $sem = $course['sem'];
    
    if ($stmt->execute()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($courseCode) . "</td>";
        echo "<td>" . htmlspecialchars($courseTitle) . "</td>";
        echo "<td>" . htmlspecialchars($grade) . "</td>";
        echo "<td>" . htmlspecialchars($year) . "</td>";
        echo "<td>" . htmlspecialchars($sem) . "</td>";
        echo "<td style='color: green;'>Success</td>";
        echo "</tr>";
        $successCount++;
    } else {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($courseCode) . "</td>";
        echo "<td>" . htmlspecialchars($courseTitle) . "</td>";
        echo "<td>" . htmlspecialchars($grade) . "</td>";
        echo "<td>" . htmlspecialchars($year) . "</td>";
        echo "<td>" . htmlspecialchars($sem) . "</td>";
        echo "<td style='color: red;'>Error: " . $stmt->error . "</td>";
        echo "</tr>";
        $errorCount++;
    }
}

echo "</table>";
echo "<p>Total grades inserted/updated: $successCount</p>";
echo "<p>Errors: $errorCount</p>";

if ($errorCount === 0) {
    echo "<p>All grades have been successfully inserted/updated. <a href='dcipros1st.php'>View Grades</a></p>";
} else {
    echo "<p>There were some errors while inserting/updating grades.</p>";
}

$stmt->close();
$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 20px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>
