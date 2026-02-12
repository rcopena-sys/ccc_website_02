<?php
session_start();
require_once '../db_connect.php';
require_once '../config/global_func.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => ''];
        
        try {
            if ($_POST['action'] === 'add_role') {
                $role_name = clean_input($conn, $_POST['role_name']);
                $description = clean_input($conn, $_POST['description'] ?? '');
                $status = clean_input($conn, $_POST['status'] ?? 'Active');
                
                $stmt = $conn->prepare("INSERT INTO roles (role_name, description, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $role_name, $description, $status);
                $stmt->execute();
                
                $response = ['success' => true, 'message' => 'Role added successfully!'];
            }
            elseif ($_POST['action'] === 'update_role') {
                $role_id = intval($_POST['role_id']);
                $role_name = clean_input($conn, $_POST['role_name']);
                $description = clean_input($conn, $_POST['description'] ?? '');
                $status = clean_input($conn, $_POST['status'] ?? 'Active');
                
                $stmt = $conn->prepare("UPDATE roles SET role_name = ?, description = ?, status = ? WHERE role_id = ?");
                $stmt->bind_param("sssi", $role_name, $description, $status, $role_id);
                $stmt->execute();
                
                $response = ['success' => true, 'message' => 'Role updated successfully!'];
            }
            elseif ($_POST['action'] === 'delete_role') {
                $role_id = intval($_POST['role_id']);
                
                // Check if role is in use
                $check = $conn->prepare("SELECT COUNT(*) as count FROM signin_db WHERE role_id = ?");
                $check->bind_param("i", $role_id);
                $check->execute();
                $result = $check->get_result()->fetch_assoc();
                
                if ($result['count'] > 0) {
                    throw new Exception('Cannot delete role that is assigned to users');
                }
                
                $stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
                $stmt->bind_param("i", $role_id);
                $stmt->execute();
                
                $response = ['success' => true, 'message' => 'Role deleted successfully!'];
            }
            elseif ($_POST['action'] === 'archive_role') {
                $role_id = intval($_POST['role_id']);
                $new_status = $_POST['new_status'] ?? 'Archived';
                
                $stmt = $conn->prepare("UPDATE roles SET status = ? WHERE role_id = ?");
                $stmt->bind_param("si", $new_status, $role_id);
                $stmt->execute();
                
                $message = $new_status === 'Archived' ? 'Role archived successfully! Users with this role cannot login.' : 'Role restored successfully!';
                $response = ['success' => true, 'message' => $message];
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
}

// Get all roles
$roles = [];
$result = $conn->query("SELECT * FROM roles ORDER BY role_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Role Management - Admin Panel</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a1929 0%, #1e3a5f 25%, #2e5490 50%, #1e3a5f 75%, #0a1929 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            position: relative;
            overflow-x: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(66, 133, 244, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(66, 133, 244, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(20px) rotate(-1deg); }
        }
        
        .sidebar {
            width: 280px;
            background: rgba(30, 58, 95, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(66, 133, 244, 0.2);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .sidebar-header {
            padding: 30px 25px;
            text-align: center;
            border-bottom: 1px solid rgba(66, 133, 244, 0.2);
            position: relative;
        }
        
        .sidebar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(66, 133, 244, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .profile-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4285f4, #669df6);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .profile-circle i {
            color: white;
            font-size: 32px;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 5px 0 0;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(66, 133, 244, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        .nav-link:hover {
            background: rgba(66, 133, 244, 0.1);
            color: white;
            border-left-color: #4285f4;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(66, 133, 244, 0.2);
            color: white;
            border-left-color: #4285f4;
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            position: relative;
            z-index: 10;
        }
        
        .page-header {
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-header h1 {
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .page-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin: 0;
        }
        
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 
                0 20px 40px rgba(10, 25, 41, 0.3),
                0 10px 20px rgba(30, 58, 95, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-header {
            background: linear-gradient(135deg, #4285f4, #669df6) !important;
            color: white !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 20px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        .card-header h5 {
            color: white;
            font-weight: 600;
            margin: 0;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #4285f4, #669df6) !important;
            border: none !important;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
            color: white !important;
        }
        
        .btn-outline-secondary {
            border: 1px solid rgba(100, 116, 139, 0.3);
            color: #64748b;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            border-color: #4285f4;
            color: #4285f4;
            background: transparent;
        }
        
        .table {
            margin-bottom: 0;
            background: transparent;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            background: transparent !important;
            color: #1e3a5f !important;
            border-bottom: 2px solid rgba(66, 133, 244, 0.2) !important;
            padding: 15px 12px !important;
        }
        
        .table td {
            color: #1e3a5f;
            padding: 15px 12px !important;
            border-bottom: 1px solid rgba(66, 133, 244, 0.1) !important;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: rgba(66, 133, 244, 0.05) !important;
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .bg-success {
            background: rgba(52, 168, 83, 0.15) !important;
            color: #0f5132 !important;
        }
        
        .bg-secondary {
            background: rgba(100, 116, 139, 0.15) !important;
            color: #475569 !important;
        }
        
        .action-btns .btn {
            margin: 0 2px;
            border-radius: 6px;
            padding: 6px 10px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary {
            border: 1px solid rgba(66, 133, 244, 0.3) !important;
            color: #4285f4 !important;
        }
        
        .btn-outline-primary:hover {
            background: #4285f4 !important;
            color: white !important;
            border-color: #4285f4 !important;
        }
        
        .btn-outline-danger {
            border: 1px solid rgba(234, 67, 53, 0.3) !important;
            color: #ea4335 !important;
        }
        
        .btn-outline-danger:hover {
            background: #ea4335 !important;
            color: white !important;
            border-color: #ea4335 !important;
        }
        
        #message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            border-radius: 10px;
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
        }
        
        .alert-success {
            background: rgba(52, 168, 83, 0.1);
            color: #0f5132;
        }
        
        .alert-danger {
            background: rgba(234, 67, 53, 0.1);
            color: #842029;
        }
        
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(10, 25, 41, 0.3);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #4285f4, #669df6);
            color: white;
            border: none;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-header h5 {
            color: white;
            font-weight: 600;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .form-control, .form-select {
            border: 1px solid rgba(66, 133, 244, 0.2);
            border-radius: 10px;
            padding: 10px 15px;
            color: #1e3a5f;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
            color: #1e3a5f;
        }
        
        .form-label {
            color: #1e3a5f;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div style="display: flex; width: 100%; min-height: 100vh;">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile-circle">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES); ?></h3>
                <p>Administrator</p>
            </div>

            <div class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="homepage.php" class="nav-link">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </div>
                <div class="nav-item">
                    <a href="add_user.php" class="nav-link">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-user-shield"></i> User Roles
                    </a>
                </div>
                <div class="nav-item">
                    <a href="calendars.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </a>
                </div>
                <div class="nav-item">
                    <a href="activity.php" class="nav-link">
                        <i class="fas fa-clipboard-list"></i> Activity Logs
                    </a>
                </div>
                <div class="nav-item">
                    <a href="view_feedback.php" class="nav-link">
                        <i class="fas fa-comment-alt"></i> Feedback
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Role Management</h1>
                <p>Manage user roles and permissions</p>
            </div>

            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h2 style="color: #1e3a5f; font-weight: 700; margin-bottom: 5px; font-size: 1.5rem;"><i class="fas fa-user-shield me-2" style="color: #4285f4;"></i>User Roles</h2>
                        <p style="color: #64748b; margin: 0;">View and manage all system roles</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i> Add New Role
                    </button>
                </div>

                <div style="background: white; border-radius: 15px; overflow: hidden;">
                    <div class="table-responsive" style="margin-bottom: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $index => $role): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($role['role_name']); ?></td>
                                    <td><?= htmlspecialchars($role['description']); ?></td>
                                    <td>
                                        <?php if ($role['status'] === 'Active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($role['status'] === 'Archived'): ?>
                                            <span class="badge bg-danger">Archived</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($role['created_at'])); ?></td>
                                    <td class="action-btns">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editRole(<?= htmlspecialchars(json_encode($role), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($role['status'] !== 'Archived'): ?>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="confirmArchive(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name'], ENT_QUOTES) ?>')" 
                                                    title="Archive this role to prevent users from logging in">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-info" 
                                                    onclick="confirmRestore(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name'], ENT_QUOTES) ?>')" 
                                                    title="Restore this role">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="roleForm" onsubmit="saveRole(event)">
                    <input type="hidden" id="roleId" name="role_id">
                    <input type="hidden" name="action" id="formAction" value="add_role">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTitle">Add New Role</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="roleName" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="roleName" name="role_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <span class="spinner-border spinner-border-sm d-none" id="spinner" role="status" aria-hidden="true"></span>
                            Save Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Message Alert -->
    <div id="message" class="alert alert-dismissible fade show" role="alert">
        <span id="messageText"></span>
        <button type="button" class="btn-close" onclick="hideMessage()"></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMessage(message, type = 'success') {
            const messageEl = document.getElementById('message');
            const messageText = document.getElementById('messageText');
            messageEl.className = `alert alert-${type} alert-dismissible fade show`;
            messageText.textContent = message;
            messageEl.style.display = 'block';
            setTimeout(hideMessage, 5000);
        }
        function hideMessage() { document.getElementById('message').style.display = 'none'; }
        function resetForm() {
            document.getElementById('roleForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('formAction').value = 'add_role';
            document.getElementById('modalTitle').textContent = 'Add New Role';
        }
        function editRole(role) {
            document.getElementById('roleId').value = role.role_id;
            document.getElementById('roleName').value = role.role_name;
            document.getElementById('description').value = role.description || '';
            document.getElementById('status').value = role.status || 'Active';
            document.getElementById('formAction').value = 'update_role';
            document.getElementById('modalTitle').textContent = 'Edit Role';
            new bootstrap.Modal(document.getElementById('roleModal')).show();
        }
        function confirmArchive(id, name) {
            if (confirm(`Are you sure you want to archive the role "${name}"? Users with this role will not be able to login.`)) {
                archiveRole(id);
            }
        }
        function confirmRestore(id, name) {
            if (confirm(`Are you sure you want to restore the role "${name}"?`)) {
                restoreRole(id);
            }
        }
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete the role "${name}"?`)) {
                deleteRole(id);
            }
        }
        async function archiveRole(id) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=archive_role&role_id=${id}&new_status=Archived`
                });
                const result = await response.json();
                showMessage(result.message, result.success ? 'success' : 'danger');
                if (result.success) setTimeout(() => location.reload(), 1000);
            } catch (error) {
                showMessage('An error occurred', 'danger');
            }
        }
        async function restoreRole(id) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=archive_role&role_id=${id}&new_status=Active`
                });
                const result = await response.json();
                showMessage(result.message, result.success ? 'success' : 'danger');
                if (result.success) setTimeout(() => location.reload(), 1000);
            } catch (error) {
                showMessage('An error occurred', 'danger');
            }
        }
        async function deleteRole(id) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete_role&role_id=${id}`
                });
                const result = await response.json();
                showMessage(result.message, result.success ? 'success' : 'danger');
                if (result.success) setTimeout(() => location.reload(), 1000);
            } catch (error) {
                showMessage('An error occurred', 'danger');
            }
        }
        async function saveRole(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const saveBtn = form.querySelector('button[type="submit"]');
            const spinner = document.getElementById('spinner');
            saveBtn.disabled = true;
            spinner.classList.remove('d-none');
            try {
                const response = await fetch('', { method: 'POST', body: new URLSearchParams(formData) });
                const result = await response.json();
                if (result.success) {
                    showMessage(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showMessage(error.message, 'danger');
            } finally {
                saveBtn.disabled = false;
                spinner.classList.add('d-none');
            }
        }
    </script>
</body>
</html>
