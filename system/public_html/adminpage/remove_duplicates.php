<?php
// remove_duplicates.php
require_once 'db.php';

echo "<h2>Removing Duplicate Curriculum Entries</h2>";

// Find and remove duplicates
$sql = "DELETE c1 FROM curriculum c1
        INNER JOIN curriculum c2 
        WHERE c1.id > c2.id 
        AND c1.fiscal_year = c2.fiscal_year 
        AND c1.program = c2.program 
        AND c1.year_semester = c2.year_semester 
        AND c1.subject_code = c2.subject_code";

$result = $conn->query($sql);

if ($result) {
    $affected_rows = $conn->affected_rows;
    echo "<p style='color: green;'>Successfully removed $affected_rows duplicate entries.</p>";
} else {
    echo "<p style='color: red;'>Error removing duplicates: " . $conn->error . "</p>";
}

// Show current curriculum count
$count_sql = "SELECT program, fiscal_year, year_semester, COUNT(*) as count 
              FROM curriculum 
              GROUP BY program, fiscal_year, year_semester 
              ORDER BY program, fiscal_year, year_semester";
$count_result = $conn->query($count_sql);

echo "<h3>Current Curriculum Count:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Program</th><th>Fiscal Year</th><th>Year/Semester</th><th>Count</th></tr>";

while ($row = $count_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['program']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fiscal_year']) . "</td>";
    echo "<td>" . htmlspecialchars($row['year_semester']) . "</td>";
    echo "<td>" . htmlspecialchars($row['count']) . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();

echo "<br><a href='curi_cs.php'>Go to BSCS Curriculum</a> | ";
echo "<a href='curi_it.php'>Go to BSIT Curriculum</a>";
?> 