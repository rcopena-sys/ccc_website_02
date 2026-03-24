<?php
// Add this code right after the database query in cs1st.php (around line 968)
// This will help us see what's in the courses array

// Debug output to check courses array
echo '<!-- Debug: Courses array after database query -->';
echo '<!-- 1-1 Courses: ' . (isset($courses['1-1']) ? count($courses['1-1']) : 0) . ' found -->';
if (isset($courses['1-1'])) {
    foreach ($courses['1-1'] as $course) {
        echo '<!-- 1-1 Course: ' . htmlspecialchars($course['subject_code']) . ' -->';
    }
}
echo '<!-- 1-2 Courses: ' . (isset($courses['1-2']) ? count($courses['1-2']) : 0) . ' found -->';
if (isset($courses['1-2'])) {
    foreach ($courses['1-2'] as $course) {
        echo '<!-- 1-2 Course: ' . htmlspecialchars($course['subject_code']) . ' -->';
    }
}

// Also add this right after the SQL query to see the raw query
echo '<!-- SQL Query: ' . htmlspecialchars($sql) . ' -->';

// And add this to check if we're connected to the right database
$db_check = $conn->query("SELECT DATABASE() as db");
if ($db_check) {
    $db_name = $db_check->fetch_assoc()['db'];
    echo '<!-- Connected to database: ' . htmlspecialchars($db_name) . ' -->';
}
?>
