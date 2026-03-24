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
        .card { border-radius: 10px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .card-header { background: linear-gradient(135deg, #008B8B, #0d47a1); color: white; border-radius: 10px 10px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #008B8B, #0d47a1); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #0d47a1, #008B8B); }
        .table th { font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        .action-btns .btn { margin: 0 2px; }
        #message { display: none; position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px; }
    </style>
</head>
<body>
<div class="card-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
    <a href="homepage.php" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>User Role Management</h5>
    </div>
    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="resetForm()">
        <i class="fas fa-plus me-1"></i> Add New Role
    </button>
</div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
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
        function confirmDelete(id, name) {
            if (confirm(`Are you sure you want to delete the role "${name}"?`)) {
                deleteRole(id);
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
