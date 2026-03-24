<?php
// remove_duplicates.php (DAS) — action-only endpoint
require_once 'db.php';
/** @var mysqli $conn */

// Delete duplicate curriculum rows based on fiscal_year, program, year_semester and course_code
$sql = "DELETE c1 FROM curriculum c1
        INNER JOIN curriculum c2 
          ON c1.fiscal_year = c2.fiscal_year 
         AND c1.program = c2.program 
         AND c1.year_semester = c2.year_semester 
         AND c1.course_code = c2.course_code
        WHERE c1.id > c2.id";

$result = $conn->query($sql);
$removed = $result ? mysqli_affected_rows($conn) : 0;

$conn->close();

// Redirect back to the referring page (or Psychology curriculum page as fallback)
$redirectUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'curi_it.php';

// Append the number of removed duplicates as a query parameter
$separator = (parse_url($redirectUrl, PHP_URL_QUERY) !== null) ? '&' : '?';
$redirectUrl .= $separator . 'duplicates_removed=' . urlencode($removed);

header("Location: $redirectUrl");
exit;
?>