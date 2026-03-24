<?php
// Global helper functions

/**
 * Sanitizes and validates input data
 * @param mysqli $conn Database connection
 * @param string $data Input data to be cleaned
 * @return string Sanitized and validated data
 */
function clean_input($conn, $data) {
    if (empty($data)) {
        return '';
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // If a database connection is provided, escape the string for SQL
    if ($conn instanceof mysqli) {
        $data = $conn->real_escape_string($data);
    }
    
    return $data;
}

/**
 * Gets a formatted full name from name components
 * @param string $fname First name
 * @param string $mname Middle name
 * @param string $lname Last name
 * @param string $suffix Suffix (e.g., Jr., Sr., III)
 * @return string Formatted full name
 */
function get_full_name($fname, $mname, $lname, $suffix = '') {
    $full_name = trim($fname);
    if (!empty($mname)) {
        $full_name .= ' ' . trim($mname);
    }
    $full_name .= ' ' . trim($lname);
    if (!empty($suffix)) {
        $full_name .= ' ' . trim($suffix);
    }
    return $full_name;
}

/**
 * Validates password strength
 * @param string $password Password to validate
 * @return array with 'valid' boolean and 'message' string
 */
function validate_password_strength($password) {
    $result = ['valid' => true, 'message' => '', 'score' => 0];
    
    if (strlen($password) < 8) {
        $result['valid'] = false;
        $result['message'] = 'Password must be at least 8 characters long';
        return $result;
    }
    
    $score = 0;
    
    // Length bonus
    if (strlen($password) >= 8) $score++;
    if (strlen($password) >= 12) $score++;
    
    // Character variety
    if (preg_match('/[a-z]/', $password)) $score++;
    if (preg_match('/[A-Z]/', $password)) $score++;
    if (preg_match('/[0-9]/', $password)) $score++;
    if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $score++;
    
    $result['score'] = $score;
    
    // Require at least 3 points for valid password
    if ($score < 3) {
        $result['valid'] = false;
        $result['message'] = 'Password is too weak. Include uppercase, lowercase, numbers, and special characters.';
    }
    
    return $result;
}

/**
 * Outputs data as JSON
 * @param mixed $data Data to be encoded as JSON
 * @return string JSON-encoded string
 */
function output($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}
?>