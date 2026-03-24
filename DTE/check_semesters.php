<?php
require_once 'config.php';

// Query to get distinct year and semester combinations
$query = "SELECT DISTINCT year, sem, CONCAT(year, '-', sem) as semester 
          FROM grades_db 
          ORDER BY year, sem";
$result = $conn->query($query);

if (!$result) {
    die("Error: " . $conn->error);
}

echo "<h2>Semester Formats in Database</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Year</th><th>Semester</th><th>Combined</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['year']) . "</td>";
    echo "<td>" . htmlspecialchars($row['sem']) . "</td>";
    echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Get count of records for each semester
$countQuery = "SELECT year, sem, COUNT(*) as count 
               FROM grades_db 
               GROUP BY year, sem 
               ORDER BY year, sem";
$countResult = $conn->query($countQuery);

if ($countResult) {
    echo "<h2>Record Count by Semester</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Year</th><th>Semester</th><th>Record Count</th></tr>";
    
    while ($row = $countResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['year']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sem']) . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
