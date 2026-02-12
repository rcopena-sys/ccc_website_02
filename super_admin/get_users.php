<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get request parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 10;
$sorters = isset($_GET['sorters']) ? json_decode($_GET['sorters'], true) : [['field' => 'id', 'dir' => 'desc']];
$filters = isset($_GET['filters']) ? json_decode($_GET['filters'], true) : [];

// Build the base query
$query = "FROM signin_db u ";
$where = [];
$params = [];
$types = '';

// Map role IDs to names for display
$roleMap = [
    1 => 'Admin',
    2 => 'Dean',
    3 => 'Registrar',
    4 => 'Student (BSIT)',
    5 => 'Student (BSCS)'
];

// Initialize empty query parts
$where = [];
$params = [];
$types = '';

// Initialize response array
$response = [
    'last_page' => 1,
    'data' => []
];

// Apply filters
if (!empty($filters)) {
    foreach ($filters as $filter) {
        if (!empty($filter['value'])) {
            $field = $filter['field'];
            $value = $filter['value'];
            
            switch($field) {
                case 'name':
                    $where[] = "(u.firstname LIKE ? OR u.lastname LIKE ?)";
                    $params[] = "%$value%";
                    $params[] = "%$value%";
                    $types .= 'ss';
                    break;
                case 'email':
                    $where[] = "u.email LIKE ?";
                    $params[] = "%$value%";
                    $types .= 's';
                    break;
                case 'role':
                    $roleMap = [
                        'Admin' => 1,
                        'Dean' => 2,
                        'Registrar' => 3,
                        'Student' => [4, 5] // Both BSIT and BSCS students
                    ];
                    
                    if (isset($roleMap[$value])) {
                        $roleValue = $roleMap[$value];
                        if (is_array($roleValue)) {
                            $placeholders = implode(',', array_fill(0, count($roleValue), '?'));
                            $where[] = "u.role_id IN ($placeholders)";
                            $params = array_merge($params, $roleValue);
                            $types .= str_repeat('i', count($roleValue));
                        } else {
                            $where[] = "u.role_id = ?";
                            $params[] = $roleValue;
                            $types .= 'i';
                        }
                    }
                    break;
                case 'status':
                    // Assuming there's a status field in the database
                    $where[] = "u.status = ?";
                    $params[] = ($value === 'Active') ? 1 : 0;
                    $types .= 'i';
                    break;
            }
        }
    }
}

// Build WHERE clause
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) as total " . $query . $whereClause);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

// Apply sorting
$orderBy = "ORDER BY ";
if (!empty($sorters)) {
    $sortParts = [];
    foreach ($sorters as $sorter) {
        $field = $sorter['field'];
        $dir = strtoupper($sorter['dir']) === 'ASC' ? 'ASC' : 'DESC';
        $sortParts[] = "$field $dir";
    }
    $orderBy .= implode(', ', $sortParts);
} else {
    $orderBy .= "u.id DESC";
}

// Apply pagination
$offset = ($page - 1) * $size;
$limit = "LIMIT $offset, $size";

// Build a simpler query without complex HTML in SQL
$dataQuery = "SELECT 
    u.id,
    CONCAT(u.firstname, ' ', u.lastname) as name,
    u.email,
    u.role_id,
    u.status as user_status,
    u.id as user_id
    FROM signin_db u
    $whereClause
    $orderBy
    $limit";

$dataStmt = $conn->prepare($dataQuery);
if (!empty($params)) {
    $dataStmt->bind_param($types, ...$params);
}
$dataStmt->execute();
$result = $dataStmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    // Map role_id to role name
    $row['role'] = $roleMap[$row['role_id']] ?? 'Unknown';
    
    // Format status
    $row['status'] = !empty($row['user_status']);
    
    // Add action buttons
    $userId = $row['user_id'];
    $row['actions'] = "
        <button class='btn btn-sm btn-outline-primary edit-btn me-1' data-id='$userId'>
            <i class='fas fa-edit'></i> Edit
        </button>
        <button class='btn btn-sm btn-outline-danger delete-btn' data-id='$userId'>
            <i class='fas fa-trash'></i>
        </button>
    ";
    
    // Remove temporary fields
    unset($row['role_id'], $row['user_status'], $row['user_id']);
    
    $data[] = $row;
}

// Set last page
$response['last_page'] = ceil($total / $size);
$response['data'] = $data;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
