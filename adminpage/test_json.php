<?php
// Test version to isolate JSON parsing issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test basic JSON output
    echo json_encode([
        'success' => true,
        'message' => 'Test response',
        'debug' => 'Basic test working'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
