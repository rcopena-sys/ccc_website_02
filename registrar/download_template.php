<?php
// Set headers to force download as Excel-compatible CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="add_student_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Pragma: no-cache');

// Read and output the CSV file
$file_path = 'add_student_template.csv';
if (file_exists($file_path)) {
    // Add BOM for proper Excel compatibility
    echo "\xEF\xBB\xBF";
    
    // Output the file contents
    readfile($file_path);
} else {
    echo "Error: Template file not found.";
}
exit();
?>
