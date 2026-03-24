<?php
// remove_duplicates.php
require_once 'db.php';
/** @var mysqli $conn */

// Find and remove duplicates
$sql = "DELETE c1 FROM curriculum c1
    INNER JOIN curriculum c2 
    WHERE c1.id > c2.id 
    AND c1.fiscal_year = c2.fiscal_year 
    AND c1.program = c2.program 
    AND c1.year_semester = c2.year_semester 
    AND c1.course_code = c2.course_code";

$result = $conn->query($sql);

// Get how many rows were removed (0 if none or on error)
$removed = $result ? $conn->affected_rows : 0;

$conn->close();

// Decide where to go back: use referrer if available, otherwise fallback
$redirect = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])
    ? $_SERVER['HTTP_REFERER']
    : 'curi_it.php';

// Optionally pass back how many duplicates were removed
if (strpos($redirect, '?') === false) {
    $redirect .= '?';
} else {
    $redirect .= '&';
}
$redirect .= 'duplicates_removed=' . urlencode($removed);

header('Location: ' . $redirect);
exit;
?> 