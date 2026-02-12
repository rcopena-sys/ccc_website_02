<?php
require_once 'db.php';

// Initialize variables
$message = '';
$messageType = '';

// Fetch students
$students = $conn->query("
    SELECT id, CONCAT(firstname, ' ', lastname) as name, student_id 
    FROM signin_db 
    WHERE role_id IN (4, 5, 6) 
    ORDER BY lastname, firstname
")->fetch_all(MYSQLI_ASSOC);

// Fetch available curriculums
$curriculums = $conn->query("
    SELECT id, CONCAT(program, ' (', fiscal_year, ')') as name, program, fiscal_year
    FROM curriculum 
    ORDER BY program, fiscal_year DESC
")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $curriculumId = $_POST['curriculum_id'];
    
    // Check if assignment already exists using prepared statement
    $check = $conn->prepare("SELECT * FROM assign_curriculum 
                           WHERE program_id = ? 
                           AND curriculum_id = ?");
    $check->bind_param("ii", $studentId, $curriculumId);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $message = 'This curriculum is already assigned to the selected student';
        $messageType = 'warning';
    } else {
        // Get program and fiscal_year using prepared statement
        $getCurriculum = $conn->prepare("SELECT program, fiscal_year FROM curriculum WHERE id = ?");
        $getCurriculum->bind_param("i", $curriculumId);
        $getCurriculum->execute();
        $curriculumData = $getCurriculum->get_result()->fetch_assoc();
        
        // Insert using prepared statement
        $insert = $conn->prepare("INSERT INTO assign_curriculum (program_id, curriculum_id, program, fiscal_year) 
                                 VALUES (?, ?, ?, ?)");
        $insert->bind_param("iiss", $studentId, $curriculumId, $curriculumData['program'], $curriculumData['fiscal_year']);
        $success = $insert->execute();
        
        if ($success) {
            $message = 'Curriculum assigned successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error: ' . $insert->error;
            $messageType = 'error';
        }
    }
}

// Fetch current assignments
$assignments = $conn->query("
    SELECT ac.*, 
           CONCAT(s.firstname, ' ', s.lastname) as student_name,
           s.student_id,
           CONCAT(c.program, ' (', c.fiscal_year, ')') as program_year
    FROM assign_curriculum ac
    JOIN signin_db s ON ac.program_id = s.id
    JOIN curriculum c ON ac.curriculum_id = c.id
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Curriculum</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 45px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
        }
        .navy-bg {
            background-color: #001f3f;
        }
        .navy-light {
            background-color: #0a3d62;
        }
        .navy-text {
            color: #001f3f;
        }
        .navy-border {
            border-color: #001f3f;
        }
        .btn-navy {
            background-color: #001f3f;
            color: white;
            transition: all 0.3s;
        }
        .btn-navy:hover {
            background-color: #0a3d62;
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="navy-bg text-white shadow-lg">
            <div class="container mx-auto px-4 py-4">
                <h1 class="text-2xl font-bold">Curriculum Assignment System</h1>
                <p class="text-blue-100">Manage student curriculum assignments</p>
            </div>
        </header>

               <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="dashboard2.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?= 
                    $messageType === 'success' ? 'bg-green-100 text-green-800' : 
                    ($messageType === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')
                ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <div class="bg-white rounded-lg card-shadow p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4 navy-text">Assign New Curriculum</h2>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Student Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Student</label>
                            <select name="student_id" class="w-full" required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>">
                                        <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['student_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Curriculum Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Curriculum</label>
                            <select name="curriculum_id" class="w-full" required>
                                <option value="">-- Choose Curriculum --</option>
                                <?php foreach ($curriculums as $curriculum): ?>
                                    <option value="<?= $curriculum['id'] ?>">
                                        <?= htmlspecialchars($curriculum['program']) ?> (<?= htmlspecialchars($curriculum['fiscal_year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" 
                                class="btn-navy px-6 py-2.5 rounded-md font-medium text-white hover:shadow-lg transition duration-300">
                            Assign Curriculum
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Assignments -->
            <div class="bg-white rounded-lg card-shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold navy-text">Current Assignments</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                                             <thead class="navy-light">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Year</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($assignments) > 0): ?>
                                <?php foreach ($assignments as $assign): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($assign['student_name']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($assign['student_id']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($assign['program']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($assign['fiscal_year']) ?>
                                        </td>
                                       
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No curriculum assignments found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="container mx-auto px-4 py-6">
                <p class="text-center text-gray-600 text-sm">
                    &copy; <?= date('Y') ?> Curriculum Management System. All rights reserved.
                </p>
            </div>
        </footer>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('select').select2({
                theme: 'classic',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Delete assignment function
            window.deleteAssignment = function(id) {
                if (confirm('Are you sure you want to delete this assignment?')) {
                    $.ajax({
                        url: 'delete_assignment.php',
                        method: 'POST',
                        data: { id: id },
                        success: function(response) {
                            location.reload();
                        },
                        error: function() {
                            alert('Error deleting assignment');
                        }
                    });
                }
            };
        });
    </script>
</body>
</html>