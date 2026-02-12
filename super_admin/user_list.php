<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

// Pagination
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$start_from = ($page - 1) * $results_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR student_id LIKE ?";
    $search_term = "%$search%";
    $params = array_fill(0, 4, $search_term);
    $types = str_repeat('s', count($params));
}

// Get total number of users for pagination
$count_sql = "SELECT COUNT(*) as total FROM signin_db $where";
$stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$total_rows = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);
$stmt->close();

// Fetch users with pagination
$sql = "SELECT * FROM signin_db $where ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $types .= 'ii'; // Add types for limit parameters
    $params[] = $start_from;
    $params[] = $results_per_page;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $start_from, $results_per_page);
}

$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get role names for display
$roles = [
    1 => 'Administrator',
    2 => 'Dean',
    3 => 'Registrar',
    4 => 'Student',
    5 => 'Student'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #4e73df;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .status-active { color: var(--success-color); }
        .status-inactive { color: var(--danger-color); }
        .status-pending { color: var(--warning-color); }
        .status-suspended { color: var(--secondary-color); }
    .badge-admin { background-color: var(--primary-color); color: #fff; }
    .badge-dean { background-color: #6f42c1; color: #fff; } /* purple */
    .badge-registrar { background-color: #17a2b8; color: #fff; } /* teal */
    .badge-faculty { background-color: var(--info-color); color: #fff; }
    .badge-student { background-color: var(--success-color); color: #fff; }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Include Sidebar -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_user.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New User
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
                        <form class="d-flex" method="GET" action="">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" name="search" 
                                       placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="user_list.php" class="btn btn-outline-danger">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Student ID</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                                </td>
                                                <td>
    <?php 
    $email = htmlspecialchars($user['email']);
    $isCCCEmail = preg_match('/@ccc\\.edu\\.ph$/i', $user['email']);
    echo $email;
    if (!$isCCCEmail): ?>
        <span class="badge bg-warning text-dark ms-2" title="Non-CCC email address">
            <i class="fas fa-exclamation-triangle"></i> Non-CCC
        </span>
    <?php endif; ?>
</td>
                                                <td><?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php 
                                                    $role_class = '';
                                                    switch((int)$user['role_id']) {
                                                        case 1:
                                                            $role_class = 'badge-admin';
                                                            break;
                                                        case 2:
                                                            $role_class = 'badge-dean';
                                                            break;
                                                        case 3:
                                                            $role_class = 'badge-registrar';
                                                            break;
                                                        case 4:
                                                        case 5:
                                                            $role_class = 'badge-student';
                                                            break;
                                                        default:
                                                            $role_class = 'badge-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $role_class; ?>">
                                                        <?php echo $roles[$user['role_id']] ?? 'Unknown'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-<?php echo strtolower($user['status']); ?>">
                                                        <i class="fas fa-circle me-1"></i>
                                                        <?php echo $user['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-user" 
                                                            data-id="<?php echo $user['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Previous
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete user <strong id="userToDelete"></strong>? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline-block;">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="user_id" id="deleteUserId" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#dataTable').DataTable({
                paging: false, // Disable DataTables pagination (using custom pagination)
                searching: false, // Disable DataTables search (using custom search)
                info: false, // Hide "Showing X of Y entries"
                order: [[0, 'desc']], // Default sort by ID descending
                columnDefs: [
                    { orderable: false, targets: [6] } // Disable sorting on Actions column
                ]
            });

            // Delete user modal
            $('.delete-user').on('click', function() {
                const userId = $(this).data('id');
                const userName = $(this).data('name');
                
                $('#userToDelete').text(userName);
                $('#deleteUserId').val(userId);
                $('#deleteForm').attr('action', 'delete_user.php');
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>
