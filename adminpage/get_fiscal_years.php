<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['program'])) {
    echo json_encode([]);
    exit;
}

$program = trim($_GET['program']);
$fiscalYears = [];

// Prepare and execute the query
$stmt = $conn->prepare("SELECT DISTINCT fiscal_year FROM curriculum WHERE program = ? ORDER BY fiscal_year DESC");
$stmt->bind_param("s", $program);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fiscalYears[] = $row['fiscal_year'];
}

echo json_encode($fiscalYears);

$stmt->close();
$conn->close();
?>
