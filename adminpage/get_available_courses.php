<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header at the very beginning
header('Content-Type: application/json');

require_once 'db.php';

// Handle saving courses to irregular_db
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_courses') {
    $transactionStarted = false;

    try {
        // Log the received data for debugging
        error_log('Received save_courses request: ' . print_r($_POST, true));

        $student_id    = trim($_POST['student_id'] ?? '');
        $semesterKey   = trim($_POST['semester'] ?? '');
        $year_semester = trim($_POST['year_semester'] ?? '');
        $program       = trim($_POST['program'] ?? '');
        $coursesRaw    = $_POST['courses'] ?? '[]';
        $courses       = json_decode($coursesRaw, true);

        // Log decoded courses
        error_log('Decoded courses: ' . print_r($courses, true));

        if ($student_id === '' || $semesterKey === '' || $year_semester === '' || $program === '') {
            throw new Exception('Missing required parameters.');
        }

        if (!is_array($courses)) {
            throw new Exception('Invalid courses payload: ' . json_last_error_msg());
        }

        $semesterParts = array_map('intval', explode('-', $semesterKey));
        if (count($semesterParts) !== 2) {
            throw new Exception('Invalid semester format. Expected format: year-semester (e.g., 1-1)');
        }

        [$year_level, $semester] = $semesterParts;
        if ($year_level < 1 || $semester < 1) {
            throw new Exception('Invalid year or semester value. Both must be positive numbers.');
        }

        // Start transaction
        if (!$conn->begin_transaction()) {
            throw new Exception('Failed to start transaction: ' . $conn->error);
        }
        $transactionStarted = true;

        // First, delete existing courses for this student and semester
        $deleteStmt = $conn->prepare("DELETE FROM irregular_db WHERE student_id = ? AND year_level = ? AND semester = ?");
        if (!$deleteStmt) {
            throw new Exception('Failed to prepare delete statement: ' . $conn->error);
        }
        $deleteStmt->bind_param('sii', $student_id, $year_level, $semester);
        if (!$deleteStmt->execute()) {
            throw new Exception('Failed to delete existing courses: ' . $deleteStmt->error);
        }
        $deleteStmt->close();

        // Prepare insert statement
        $insertStmt = $conn->prepare("
            INSERT INTO irregular_db 
            (student_id, course_code, course_title, lec_units, lab_units, 
             units, year_level, semester, year_semester, program, prerequisites, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        if (!$insertStmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }

        $inserted = 0;
        foreach ($courses as $index => $course) {
            $code  = trim($course['course_code'] ?? $course['code'] ?? '');
            $title = trim($course['course_title'] ?? $course['title'] ?? '');

            $lecUnits = isset($course['lec_units']) ? floatval($course['lec_units']) : (isset($course['lec']) ? floatval($course['lec']) : 0.0);
            $labUnits = isset($course['lab_units']) ? floatval($course['lab_units']) : (isset($course['lab']) ? floatval($course['lab']) : 0.0);
            if (isset($course['units'])) {
                $units = floatval($course['units']);
            } else {
                $units = $lecUnits + $labUnits;
            }

            $prereq = trim($course['prerequisites'] ?? '');

            if ($code === '' || $title === '') {
                error_log("Skipping invalid course at index $index: " . print_r($course, true));
                continue;
            }

            $bindResult = $insertStmt->bind_param(
                'sssdddiisss',
                $student_id,
                $code,
                $title,
                $lecUnits,
                $labUnits,
                $units,
                $year_level,
                $semester,
                $year_semester,
                $program,
                $prereq
            );

            if (!$bindResult) {
                throw new Exception('Failed to bind parameters: ' . $insertStmt->error);
            }

            if (!$insertStmt->execute()) {
                throw new Exception("Failed to insert course $code: " . $insertStmt->error);
            }
            $inserted += $insertStmt->affected_rows;
        }

        $insertStmt->close();
        
        if (!$conn->commit()) {
            throw new Exception('Failed to commit transaction: ' . $conn->error);
        }
        
        $response = [
            'success' => true,
            'ok' => true,
            'inserted' => $inserted,
            'message' => 'Courses saved successfully!'
        ];
        echo json_encode($response);
        error_log('Response: ' . print_r($response, true));
        exit;
        
    } catch (Exception $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        http_response_code(400);
        $errorResponse = [
            'success' => false,
            'ok' => false,
            'message' => $e->getMessage(),
            'error_details' => $e->getFile() . ':' . $e->getLine()
        ];
        error_log('Error: ' . print_r($errorResponse, true));
        echo json_encode($errorResponse);
        exit;
    }
}

// If we reach here, it means the request wasn't handled above
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'Invalid request'
]);
exit;



