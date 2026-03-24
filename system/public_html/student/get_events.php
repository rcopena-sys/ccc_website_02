<?php
session_start();
require_once 'db_connect.php';

// Get start and end dates from FullCalendar
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

// SQL query to fetch events from the calendar table
$query = "SELECT id, title, event_date as start, description FROM calendar";

// Add date range filter if provided
$where = [];
if ($start) {
    $where[] = "event_date >= '$start'";
}
if ($end) {
    $where[] = "event_date <= '$end'";
}

if (!empty($where)) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}

$query .= " ORDER BY event_date ASC";

$result = $conn->query($query);

$events = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'allDay' => true, // Mark as all-day event
            'description' => $row['description'] ?? '' // Handle NULL description
        ];
    }
}

// Close the connection
$conn->close();

// Return events as JSON
header('Content-Type: application/json');
echo json_encode($events);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON encode error: ' . json_last_error_msg());
    echo json_encode(['error' => 'Failed to encode events']);
}
