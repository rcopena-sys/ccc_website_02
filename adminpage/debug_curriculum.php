<?php
require_once 'db.php';

echo "<h2>Curriculum Debug</h2>";

// Check what's in curriculum table
echo "<h3>All curriculum data:</h3>";
$result = $conn->query("SELECT program, year, semester, course_code, course_title FROM curriculum ORDER BY year, semester, course_code");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Program</th><th>Year</th><th>Semester</th><th>Code</th><th>Title</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['program']) . "</td>";
        echo "<td>" . htmlspecialchars($row['year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_title']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check specifically for BSIT program
echo "<h3>BSIT curriculum only:</h3>";
$result = $conn->query("SELECT year, semester, course_code, course_title FROM curriculum WHERE program = 'BSIT' ORDER BY year, semester, course_code");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Year</th><th>Semester</th><th>Code</th><th>Title</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_title']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check specifically for BSCS program
echo "<h3>BSCS curriculum only:</h3>";
$result = $conn->query("SELECT year, semester, course_code, course_title FROM curriculum WHERE program = 'BSCS' ORDER BY year, semester, course_code");

if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Year</th><th>Semester</th><th>Code</th><th>Title</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['course_title']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

// Check what semesters exist for BSIT
echo "<h3>BSIT semesters:</h3>";
$result = $conn->query("SELECT DISTINCT year, semester FROM curriculum WHERE program = 'BSIT' ORDER BY year, semester");

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>Year " . $row['year'] . ", Semester " . $row['semester'] . " ({$row['year']}-{$row['semester']})</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

// Check what semesters exist for BSCS
echo "<h3>BSCS semesters:</h3>";
$result = $conn->query("SELECT DISTINCT year, semester FROM curriculum WHERE program = 'BSCS' ORDER BY year, semester");

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>Year " . $row['year'] . ", Semester " . $row['semester'] . " ({$row['year']}-{$row['semester']})</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}
?>
