<?php
// Path to the template file
$templateFile = __DIR__ . '/BSIT_Curriculum_template.csv';

// Check if the file exists
if (!file_exists($templateFile)) {
    die('Template file not found');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=BSIT_Curriculum_Template.csv');

// Create output stream
$output = fopen('php://output', 'w');
if (!$output) {
    die('Error creating output stream');
}

// Add BOM for Excel
fputs($output, "\xEF\xBB\xBF");

// Read and process the template file
$handle = fopen($templateFile, 'r');
if (!$handle) {
    fclose($output);
    die('Error opening template file');
}

$isFirstLine = true;
while (($data = fgetcsv($handle)) !== false) {
    if ($isFirstLine) {
        // Output the header line as is
        fputcsv($output, $data);
        $isFirstLine = false;
        continue;
    }
    
    // Format the Year_Semester column to be treated as text in Excel
    if (isset($data[0])) {
        $data[0] = '="' . trim($data[0]) . '"';
    }
    
    // Clean up any potential whitespace in other columns
    foreach ($data as &$value) {
        $value = trim($value);
    }
    
    fputcsv($output, $data);
}

// Close file handles
fclose($handle);
fclose($output);
exit;