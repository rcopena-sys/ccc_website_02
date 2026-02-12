<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Simple auth check (adjust as needed)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Helper to escape output
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $label = trim($_POST['label'] ?? '');
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

        if ($label === '' || !$start_date || !$end_date) {
            $error = 'Please provide a fiscal year label, start date and end date.';
        } else {
            try {
                // if marking active, clear previous active
                if ($is_active) {
                    $conn->query("UPDATE fiscal_years SET is_active = 0");
                }

                $stmt = $conn->prepare("INSERT INTO fiscal_years (label, start_date, end_date, is_active) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('sssi', $label, $start_date, $end_date, $is_active);
                $stmt->execute();
                $message = 'Fiscal year created successfully.';
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'A fiscal year with this label already exists. Please use a different label.';
                } else {
                    $error = 'Error creating fiscal year: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'set_active') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE fiscal_years SET is_active = 0");
            $stmt = $conn->prepare("UPDATE fiscal_years SET is_active = 1 WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Fiscal year set active.';
            } else {
                $error = 'Error updating fiscal year: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM fiscal_years WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = 'Fiscal year deleted.';
            } else {
                $error = 'Error deleting fiscal year: ' . $conn->error;
            }
            $stmt->close();
        }
    }

}

// Fetch existing fiscal years
$fiscal_years = [];
$res = $conn->query("SELECT * FROM fiscal_years ORDER BY start_date DESC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) $fiscal_years[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fiscal Year Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
        .card { margin-top: 30px; }
        .active-badge { background: linear-gradient(90deg,#4285f4,#669df6); color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <div style="margin-top: 20px; margin-bottom: 20px;">
        <a href="dashboardr.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    <div class="row">
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Create Fiscal Year</h5>
                    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Fiscal Year Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. 2025-2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive">
                            <label class="form-check-label" for="isActive">Set as active fiscal year</label>
                        </div>
                        <button class="btn btn-primary">Create</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Existing Fiscal Years</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Label</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fiscal_years) === 0): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No fiscal years yet.</td></tr>
                                <?php else: foreach ($fiscal_years as $i => $fy): ?>
                                    <tr>
                                        <td><?= $i+1 ?></td>
                                        <td><?= e($fy['label']) ?></td>
                                        <td><?= e($fy['start_date']) ?></td>
                                        <td><?= e($fy['end_date']) ?></td>
                                        <td><?php if ($fy['is_active']): ?><span class="badge active-badge">Active</span><?php else: ?>-<?php endif; ?></td>
                                        <td>
                                            <form method="post" style="display:inline-block">
                                                <input type="hidden" name="action" value="set_active">
                                                <input type="hidden" name="id" value="<?= (int)$fy['id'] ?>">
                                                <button class="btn btn-sm btn-outline-primary">Set Active</button>
                                            </form>
                                            <form method="post" style="display:inline-block; margin-left:6px" onsubmit="return confirm('Delete this fiscal year?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$fy['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
