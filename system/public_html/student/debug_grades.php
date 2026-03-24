<?php
session_start();
require_once 'db_connect.php';

// Check if we're logged in
if (!isset($_SESSION['student_id'])) {
    die("Please log in first");
}

$student_id = $_SESSION['student_id'];
echo "<h2>Debug Information</h2>";
echo "<p>Student ID: " . htmlspecialchars($student_id) . "</p>";

// 1. Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'grades_db'");
if ($result->num_rows === 0) {
    die("Error: grades_db table does not exist");
}

// 2. Show table structure
echo "<h3>Table Structure (grades_db)</h3>";
$columns = $conn->query("SHOW COLUMNS FROM grades_db");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($col = $columns->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Show sample data
echo "<h3>Sample Data (First 5 Rows)</h3>";
$sample = $conn->query("SELECT * FROM grades_db LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<table border='1'><tr>";
    // Header row
    $fields = $sample->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr>";
    
    // Data rows
    $sample->data_seek(0); // Reset pointer
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found in grades_db table or error: " . htmlspecialchars($conn->error) . "</p>";
}

// 4. Check for this student's grades
echo "<h3>Grades for Student ID: " . htmlspecialchars($student_id) . "</h3>";
$stmt = $conn->prepare("SELECT * FROM grades_db WHERE student_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr>";
        // Header row
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        $result->data_seek(0); // Reset pointer
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No grades found for this student.</p>";
    }
    $stmt->close();
} else {
    echo "<p>Error preparing statement: " . htmlspecialchars($conn->error) . "</p>";
}

// 5. Show all tables in the database
echo "<h3>All Tables in Database</h3>";
$tables = $conn->query("SHOW TABLES");
if ($tables) {
    echo "<ul>";
    while ($table = $tables->fetch_array()) {
        echo "<li>" . htmlspecialchars($table[0]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Error getting tables: " . htmlspecialchars($conn->error) . "</p>";
}

$conn->close();
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 5px 10px; border: 1px solid #ccc; }
th { background-color: #f0f0f0; }
</style>
