<?php
// First Year
$firstYearFirstSemesterCourses = ['IT 101', 'CS 101', 'MATH 101', 'US 101', 'IE 101', 'SEC 101', 'ALG 101', 'PATHFit 1', 'NSTP 101'];
$firstYearSecondSemesterCourses = ['CS 102', 'IT 102', 'NET 102', 'IT 201', 'PCOM 102', 'IT 231', 'CALC 102', 'PATHFit 2', 'NSTP 102'];

// Course units mapping for first year
$courseUnits = [
    'IT 101' => 3, 'CS 101' => 5, 'MATH 101' => 3, 'US 101' => 3, 'IE 101' => 3,
    'SEC 101' => 3, 'ALG 101' => 3, 'PATHFit 1' => 2, 'NSTP 101' => 3,
    'CS 102' => 5, 'IT 102' => 3, 'NET 102' => 5, 'IT 201' => 3, 'PCOM 102' => 3,
    'IT 231' => 3, 'CALC 102' => 3, 'PATHFit 2' => 2, 'NSTP 102' => 3
];

// Calculate first year units
$firstYearFirstSemesterUnits = 0;
foreach ($firstYearFirstSemesterCourses as $course) {
    $firstYearFirstSemesterUnits += $courseUnits[$course] ?? 0;
}

$firstYearSecondSemesterUnits = 0;
foreach ($firstYearSecondSemesterCourses as $course) {
    $firstYearSecondSemesterUnits += $courseUnits[$course] ?? 0;
}

// Second Year
$secondYearFirstSemesterCourses = [
    'CS 201' => ['units' => 5], 'CS 202' => ['units' => 3], 'CS 203' => ['units' => 5],
    'CS 204' => ['units' => 5], 'IT 201' => ['units' => 3], 'IT 202' => ['units' => 5],
    'PE 201' => ['units' => 2], 'GEC 201' => ['units' => 3]
];

$secondYearSecondSemesterCourses = [
    'CS 205' => ['units' => 3], 'CS 206' => ['units' => 5], 'CS 207' => ['units' => 5],
    'CS 208' => ['units' => 5], 'CS 209' => ['units' => 5], 'GEC 202' => ['units' => 3],
    'PE 202' => ['units' => 2], 'NSTP 201' => ['units' => 3]
];

// Third Year
$thirdYearFirstSemesterCourses = [
    'HCI 301' => ['units' => 3], 'CS 301' => ['units' => 5], 'SQA 301' => ['units' => 3],
    'CS 311' => ['units' => 5], 'CS 321' => ['units' => 5], 'CEEL 301' => ['units' => 5],
    'CEEL 311' => ['units' => 5], 'RIZAL 301' => ['units' => 3]
];

$thirdYearSecondSemesterCourses = [
    'CEEL 302' => ['units' => 3], 'Ethics 302' => ['units' => 3],
    'Tech 302' => ['units' => 3], 'CS 302' => ['units' => 3]
];

// Fourth Year
$fourthYearFirstSemesterCourses = [
    'CS 401' => ['units' => 3]
];

$fourthYearSecondSemesterCourses = [
    'PRAC 402' => ['units' => 6]
];

// Calculate total units for each semester
$totalFirstSemesterUnits = $firstYearFirstSemesterUnits +
    array_sum(array_column($secondYearFirstSemesterCourses, 'units')) +
    array_sum(array_column($thirdYearFirstSemesterCourses, 'units')) +
    array_sum(array_column($fourthYearFirstSemesterCourses, 'units'));

$totalSecondSemesterUnits = $firstYearSecondSemesterUnits +
    array_sum(array_column($secondYearSecondSemesterCourses, 'units')) +
    array_sum(array_column($thirdYearSecondSemesterCourses, 'units')) +
    array_sum(array_column($fourthYearSecondSemesterCourses, 'units'));

$totalUnits = $totalFirstSemesterUnits + $totalSecondSemesterUnits;
?>
