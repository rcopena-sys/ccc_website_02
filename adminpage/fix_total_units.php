<?php
require_once 'db.php';

// Script to fix total_units in curriculum table
// This will allow you to manually set total_units values instead of calculated ones

echo "<h2>Fix Total Units in Curriculum</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $program = $_POST['program'] ?? '';
    $fiscal_year = $_POST['fiscal_year'] ?? '';
    
    if ($program && $fiscal_year) {
        // Get all curriculum records for this program and fiscal year
        $sql = "SELECT id, course_code, course_title, lec_units, lab_units, total_units 
                FROM curriculum 
                WHERE program = ? AND fiscal_year = ? 
                ORDER BY course_code";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $program, $fiscal_year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<form method='POST'>";
        echo "<input type='hidden' name='program' value='" . htmlspecialchars($program) . "'>";
        echo "<input type='hidden' name='fiscal_year' value='" . htmlspecialchars($fiscal_year) . "'>";
        echo "<input type='hidden' name='save_changes' value='1'>";
        echo "<table class='table table-bordered'>";
        echo "<tr><th>Course Code</th><th>Course Title</th><th>Lec Units</th><th>Lab Units</th><th>Current Total</th><th>New Total Units</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
            echo "<td>" . htmlspecialchars($row['course_title']) . "</td>";
            echo "<td>" . $row['lec_units'] . "</td>";
            echo "<td>" . $row['lab_units'] . "</td>";
            echo "<td>" . $row['total_units'] . "</td>";
            echo "<td><input type='number' name='total_units[" . $row['id'] . "]' value='" . $row['total_units'] . "' step='0.5' min='0'></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<button type='submit' class='btn btn-primary'>Update Total Units</button>";
        echo "</form>";
        
        $stmt->close();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    $program = $_POST['program'] ?? '';
    $fiscal_year = $_POST['fiscal_year'] ?? '';
    $total_units = $_POST['total_units'] ?? [];
    
    if ($program && $fiscal_year && !empty($total_units)) {
        $updateStmt = $conn->prepare("UPDATE curriculum SET total_units = ? WHERE id = ?");
        
        foreach ($total_units as $id => $total) {
            $updateStmt->bind_param("di", $total, $id);
            $updateStmt->execute();
        }
        
        $updateStmt->close();
        echo "<div class='alert alert-success'>Total units updated successfully!</div>";
    }
} else {
    // Show form to select program and fiscal year
    echo "<form method='POST'>";
    echo "<div class='mb-3'>";
    echo "<label>Program:</label>";
    echo "<select name='program' class='form-control' required>";
    echo "<option value=''>Select Program</option>";
    echo "<option value='BSCS'>BSCS</option>";
    echo "<option value='BSIT'>BSIT</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<div class='mb-3'>";
    echo "<label>Fiscal Year:</label>";
    echo "<select name='fiscal_year' class='form-control' required>";
    echo "<option value=''>Select Fiscal Year</option>";
    
    // Get fiscal years from database
    $result = $conn->query("SELECT DISTINCT fiscal_year FROM curriculum ORDER BY fiscal_year");
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . htmlspecialchars($row['fiscal_year']) . "'>" . htmlspecialchars($row['fiscal_year']) . "</option>";
    }
    
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' name='update' class='btn btn-primary'>Load Courses</button>";
    echo "</form>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.table { border-collapse: collapse; width: 100%; margin: 20px 0; }
.table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.table th { background-color: #f2f2f2; }
.btn { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
.btn-primary { background-color: #007bff; }
.alert { padding: 15px; margin: 20px 0; border: 1px solid transparent; border-radius: 4px; }
.alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
.form-control { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
</style>
