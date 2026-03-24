<?php
require_once __DIR__ . '/../db.php';

/**
 * Get curriculum data for a specific program and year level
 * 
 * @param string $program Program code (e.g., 'BSIT', 'BSCS')
 * @param string $year_level Year level (e.g., '1st Year', '2nd Year')
 * @param string $fiscal_year Fiscal year (e.g., '2024-2025')
 * @return array Array of curriculum data
 */
function getCurriculumData($program, $year_level, $fiscal_year) {
    global $conn;
    
    $curriculum = [
        'first_sem' => [],
        'second_sem' => [],
        'midyear' => []
    ];
    
    // Map year level to database format
    $year_mapping = [
        '1st Year' => '1st Year',
        '2nd Year' => '2nd Year',
        '3rd Year' => '3rd Year',
        '4th Year' => '4th Year'
    ];
    
    $year = $year_mapping[$year_level] ?? '';
    
    if (empty($year)) {
        return $curriculum;
    }
    
    // First Semester
    $stmt = $conn->prepare("
        SELECT * FROM curriculum 
        WHERE program = ? 
        AND fiscal_year = ?
        AND year_semester = ?
        ORDER BY subject_code
    ");
    
    $first_sem = $year . ' 1st Semester';
    $stmt->bind_param("sss", $program, $fiscal_year, $first_sem);
    $stmt->execute();
    $result = $stmt->get_result();
    $curriculum['first_sem'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Second Semester
    $stmt = $conn->prepare("
        SELECT * FROM curriculum 
        WHERE program = ? 
        AND fiscal_year = ?
        AND year_semester = ?
        ORDER BY subject_code
    ");
    
    $second_sem = $year . ' 2nd Semester';
    $stmt->bind_param("sss", $program, $fiscal_year, $second_sem);
    $stmt->execute();
    $result = $stmt->get_result();
    $curriculum['second_sem'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Midyear (if any)
    $stmt = $conn->prepare("
        SELECT * FROM curriculum 
        WHERE program = ? 
        AND fiscal_year = ?
        AND year_semester = ?
        ORDER BY subject_code
    ");
    
    $midyear = $year . ' Midyear';
    $stmt->bind_param("sss", $program, $fiscal_year, $midyear);
    $stmt->execute();
    $result = $stmt->get_result();
    $curriculum['midyear'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $curriculum;
}

/**
 * Calculate total units for a semester
 */
function calculateTotalUnits($courses) {
    $total = 0;
    foreach ($courses as $course) {
        $total += (float)$course['total_units'];
    }
    return $total;
}

/**
 * Get student grades for a specific student
 */
function getStudentGrades($student_id) {
    global $conn;
    
    $grades = [];
    $stmt = $conn->prepare("SELECT course_code, final_grade FROM grades_db WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $grades[$row['course_code']] = $row['final_grade'];
    }
    
    $stmt->close();
    return $grades;
}

/**
 * Get student information
 */
function getStudentInfo($student_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM signin_db WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    return $student;
}

/**
 * Get current fiscal year
 */
function getCurrentFiscalYear() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT DISTINCT fiscal_year FROM curriculum ORDER BY fiscal_year DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['fiscal_year'] ?? date('Y') . '-' . (date('Y') + 1);
}
?>
