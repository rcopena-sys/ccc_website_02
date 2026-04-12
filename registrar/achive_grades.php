<?php
require_once 'config.php';

$success = false;
$error = '';

// Ensure archive table exists with same structure as grades_db
if (!$conn->query("CREATE TABLE IF NOT EXISTS grades_archive LIKE grades_db")) {
	$error = 'Failed to ensure archive table exists: ' . $conn->error;
}

// Handle restore request (move record back to grades_db)
if (isset($_POST['restore']) && isset($_POST['id'])) {
	$id = (int)$_POST['id'];

	// Insert back into active grades without copying the id to avoid PK conflicts
	$restoreSql = "INSERT INTO grades_db (student_id, course_code, year, sem, final_grade, course_title, created_at, updated_at)
				   SELECT student_id, course_code, year, sem, final_grade, course_title, created_at, updated_at
				   FROM grades_archive WHERE id = ?";
	if ($stmt = $conn->prepare($restoreSql)) {
		$stmt->bind_param('i', $id);
		if ($stmt->execute() && $stmt->affected_rows > 0) {
			$stmt->close();
			// Remove from archive
			if ($del = $conn->prepare('DELETE FROM grades_archive WHERE id = ?')) {
				$del->bind_param('i', $id);
				if ($del->execute()) {
					$success = true;
					$success_message = 'Grade record restored successfully!';
				} else {
					$error = 'Restored but failed to remove from archive: ' . $conn->error;
				}
				$del->close();
			} else {
				$error = 'Restored but failed to prepare archive delete: ' . $conn->error;
			}
		} else {
			$error = 'No matching archive record found to restore.';
			$stmt->close();
		}
	} else {
		$error = 'Failed to prepare restore statement: ' . $conn->error;
	}
}

// Handle permanent delete request from archive
if (isset($_POST['delete']) && isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	if ($del = $conn->prepare('DELETE FROM grades_archive WHERE id = ?')) {
		$del->bind_param('i', $id);
		if ($del->execute()) {
			$success = true;
			$success_message = 'Archived grade permanently deleted.';
		} else {
			$error = 'Failed to delete archived grade: ' . $conn->error;
		}
		$del->close();
	} else {
		$error = 'Failed to prepare delete from archive: ' . $conn->error;
	}
}

// Fetch archived grades
$archived = [];
if (empty($error)) {
	$res = $conn->query('SELECT * FROM grades_archive ORDER BY student_id, course_code');
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			$archived[] = $row;
		}
		$res->free();
	} else {
		$error = 'Failed to load archived grades: ' . $conn->error;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Archived Grades</title>
	<link rel="icon" type="image/x-icon" href="favicon.ico">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<style>
		body {
			background: #f8fafc; /* light background */
			color: #0f172a; /* dark navy text */
		}
		.navbar-custom {
			background: #1e3a8a; /* medium navy */
		}
		.navbar-custom .navbar-brand,
		.navbar-custom .nav-link {
			color: #e5e7eb !important;
		}
		.card-navy {
			background: #ffffff;
			border: 1px solid #dbeafe;
			box-shadow: 0 10px 25px rgba(15,23,42,0.15);
		}
		.table thead {
			background: linear-gradient(90deg, #1d4ed8, #2563eb);
			color: #ffffff;
		}
		.badge-archived {
			background-color: #1d4ed8;
		}
		.btn-outline-light {
			border-color: #1d4ed8;
			color: #1e3a8a;
		}
		.btn-outline-light:hover {
			background-color: #1d4ed8;
			color: #fff;
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-custom mb-4">
		<div class="container-fluid">
			<a class="navbar-brand" href="#"><i class="bi bi-archive"></i> Archived Grades</a>
			<div class="d-flex">
				<a href="studentgrade.php" class="btn btn-light btn-sm me-2">
					<i class="bi bi-arrow-left"></i> Back to Grades
				</a>
			</div>
		</div>
	</nav>

	<div class="container mb-5">
		<?php if ($success === true): ?>
			<div class="alert alert-success alert-dismissible fade show">
				<i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message ?? 'Operation completed successfully!') ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
			</div>
		<?php elseif (!empty($error)): ?>
			<div class="alert alert-danger alert-dismissible fade show">
				<i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
				<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
			</div>
		<?php endif; ?>

		<div class="card card-navy">
			<div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0"><i class="bi bi-archive"></i> Archived Grades List</h5>
				<span class="badge bg-primary">Total: <?= count($archived) ?></span>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover table-bordered mb-0 align-middle">
						<thead>
							<tr>
								<th>ID</th>
								<th>Student ID</th>
								<th>Course Code</th>
								<th>Course Title</th>
								<th>Year</th>
								<th>Sem</th>
								<th>Final Grade</th>
								<th>Archived At</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php if (!empty($archived)): ?>
							<?php foreach ($archived as $row): ?>
								<tr>
									<td><?= htmlspecialchars($row['id']) ?></td>
									<td><strong><?= htmlspecialchars($row['student_id']) ?></strong></td>
									<td><code><?= htmlspecialchars($row['course_code']) ?></code></td>
									<td><?= htmlspecialchars($row['course_title']) ?></td>
									<td><?= htmlspecialchars($row['year']) ?></td>
									<td><?= htmlspecialchars($row['sem']) ?></td>
									<td><?= htmlspecialchars($row['final_grade']) ?></td>
									<td><span class="badge badge-archived"><?= htmlspecialchars($row['updated_at'] ?? $row['created_at']) ?></span></td>
									<td class="text-nowrap">
										<form method="POST" action="" style="display:inline;" onsubmit="return confirm('Restore this archived grade back to active records?');">
											<input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
											<button type="submit" name="restore" class="btn btn-sm btn-success" title="Restore">
												<i class="bi bi-arrow-counterclockwise"></i>
											</button>
										</form>
										<form method="POST" action="" style="display:inline;" onsubmit="return confirm('Permanently delete this archived grade? This cannot be undone.');">
											<input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
											<button type="submit" name="delete" class="btn btn-sm btn-outline-danger" title="Delete Permanently">
												<i class="bi bi-trash"></i>
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="9" class="text-center py-4">
									<i class="bi bi-inbox" style="font-size:3rem; color:#64748b;"></i>
									<h5 class="mt-2 mb-0">No archived grades</h5>
									<p class="text-muted mb-0">Archived grade records will appear here.</p>
								</td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Trigger SweetAlert popup after actions on this page
if ($success === true || !empty($error)):
    $type = $success === true ? 'success' : 'error';
    $msg = $success === true ? ($success_message ?? 'Operation completed successfully!') : $error;
    $title = $success === true ? 'Success' : 'Error';
    $msgJs = json_encode($msg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $titleJs = json_encode($title, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			Swal.fire({
				icon: '<?php echo $type; ?>',
				title: <?php echo $titleJs; ?>,
				text: <?php echo $msgJs; ?>
			});
		});
	</script>
<?php endif; ?>
</body>
</html>

