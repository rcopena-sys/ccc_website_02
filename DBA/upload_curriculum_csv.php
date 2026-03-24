<?php
// Correct DB path (adminpage → parent directory → db_connect.php)
require_once '../db_connect.php';

$message = '';
$parsedRows = [];
$inserted = 0;
$errors = [];
$selectedProgram = $_POST['program'] ?? '';
$selectedFiscalYear = $_POST['fiscal_year'] ?? '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');

            if ($handle) {
                // Read header row
                $header = fgetcsv($handle);

                $expectedCols = [
                    'year_semester',
                    'subject_code',
                    'course_title',
                    'lec_units',
                    'lab_units',
                    'total_units',
                    'prerequisites'
                ];

                $headerLower = array_map('strtolower', array_map('trim', $header));
                $expectedLower = array_map('strtolower', $expectedCols);

                // Validate dropdown selections
                if (!$selectedProgram || !$selectedFiscalYear) {
                    $message = '<span class="text-red-600">Please select both Program and Fiscal Year.</span>';
                }
                // Validate header format
                elseif ($headerLower !== $expectedLower) {
                    $message = '<span class="text-red-600">
                        CSV header does not match expected format.<br>
                        Expected: ' . implode(', ', $expectedCols) . '<br>
                        Found: ' . implode(', ', $header) . '
                    </span>';
                } else {
                    // Check whether curriculum table exists
                    $tableCheck = $conn->query("SHOW TABLES LIKE 'curriculum'");
                    
                    if ($tableCheck->num_rows === 0) {
                        $message = '<span class="text-red-600">The curriculum table does not exist. Run create_curriculum_table.php first.</span>';
                    } else {

                        // Clear old data for selected program + year
                        $deleteStmt = $conn->prepare("DELETE FROM curriculum WHERE program = ? AND fiscal_year = ?");
                        $deleteStmt->bind_param("ss", $selectedProgram, $selectedFiscalYear);
                        $deleteStmt->execute();
                        $deleteStmt->close();

                        // Prepare insert statement
                        $stmt = $conn->prepare("
                            INSERT INTO curriculum (
                                fiscal_year,
                                program,
                                year_semester,
                                subject_code,
                                course_title,
                                lec_units,
                                lab_units,
                                total_units,
                                prerequisites
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");

                        if (!$stmt) {
                            $message = '<span class="text-red-600">Database prepare failed: ' . htmlspecialchars($conn->error) . '</span>';
                        } else {

                            $rowNumber = 1;

                            while (($row = fgetcsv($handle)) !== false) {
                                $rowNumber++;

                                if (count($row) < 7) {
                                    $errors[] = "Row $rowNumber: Missing columns.";
                                    continue;
                                }

                                // Validate numeric fields
                                $lec_units   = is_numeric($row[3]) ? floatval($row[3]) : 0;
                                $lab_units   = is_numeric($row[4]) ? floatval($row[4]) : 0;
                                $total_units = is_numeric($row[5]) ? floatval($row[5]) : 0;

                                // Save row for preview
                                $parsedRows[] = array_merge([$selectedFiscalYear, $selectedProgram], $row);

                                // Bind parameters and execute
                                $stmt->bind_param(
                                    "ssss sddds",
                                    $selectedFiscalYear,
                                    $selectedProgram,
                                    $row[0],
                                    $row[1],
                                    $row[2],
                                    $lec_units,
                                    $lab_units,
                                    $total_units,
                                    $row[6]
                                );

                                if ($stmt->execute()) {
                                    $inserted++;
                                } else {
                                    $errors[] = "Row $rowNumber: " . htmlspecialchars($stmt->error);
                                }
                            }

                            $stmt->close();

                            if ($inserted > 0) {
                                $success = true;
                                $message = "<span class='text-green-600'>
                                    Successfully uploaded $inserted rows for $selectedProgram ($selectedFiscalYear).
                                </span>";

                                if ($errors) {
                                    $message .= "<br><span class='text-orange-600'>Warnings:<br>" . implode('<br>', $errors) . '</span>';
                                }
                            } else {
                                $message = "<span class='text-red-600'>No rows inserted. Check your CSV format.</span>";
                            }

                        }
                    }
                }

                fclose($handle);

            } else {
                $message = '<span class="text-red-600">Cannot open uploaded file.</span>';
            }

        } else {
            $message = '<span class="text-red-600">Please upload a valid CSV file.</span>';
        }

    } else {
        $message = '<span class="text-red-600">Upload error: ' . $file['error'] . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Curriculum CSV</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="dba-theme.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-xl mt-10">
        <h2 class="text-2xl font-bold mb-4">Upload Curriculum CSV</h2>
        <?php if ($success === true): ?>
            <div class="mb-4 p-4 rounded bg-green-100 text-green-800"> 
                <?= $message ?> 
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="mb-4 p-4 rounded bg-red-100 text-red-800"> 
                <?= $message ?> 
            </div>
        <?php endif; ?>
        
        <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded">
            <h3 class="font-semibold mb-2">CSV Format Requirements:</h3>
            <p class="text-sm">Your CSV file should have these columns in order:</p>
            <ul class="text-sm list-disc list-inside mt-1">
                <li>year_semester (e.g., "1-1", "1-2", "2-1")</li>
                <li>subject_code (e.g., "IT101")</li>
                <li>course_title (e.g., "Introduction to IT")</li>
                <li>lec_units (numeric, e.g., 3.0)</li>
                <li>lab_units (numeric, e.g., 1.0)</li>
                <li>total_units (numeric, e.g., 4.0)</li>
                <li>prerequisites (text, e.g., "None" or "IT101")</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="mb-6">
            <label class="block mb-2 font-semibold">Select Program</label>
            <select name="program" class="mb-4 w-full border rounded p-2" required>
                <option value="">-- Select Program --</option>
                <option value="BSCS" <?= $selectedProgram==='BSCS'?'selected':'' ?>>BSCS</option>
                <option value="BSIT" <?= $selectedProgram==='BSIT'?'selected':'' ?>>BSIT</option>
            </select>
            <label class="block mb-2 font-semibold">Select Fiscal Year</label>
            <select name="fiscal_year" class="mb-4 w-full border rounded p-2" required>
                <option value="">-- Select Fiscal Year --</option>
                <option value="2022-2023" <?= $selectedFiscalYear==='2022-2023'?'selected':'' ?>>2022-2023</option>
                <option value="2023-2024" <?= $selectedFiscalYear==='2023-2024'?'selected':'' ?>>2023-2024</option>
                <option value="2024-2025" <?= $selectedFiscalYear==='2024-2025'?'selected':'' ?>>2024-2025</option>
                <option value="2025-2026" <?= $selectedFiscalYear==='2025-2026'?'selected':'' ?>>2025-2026</option>
            </select>
            <input type="file" name="csv_file" accept=".csv" class="mb-4 block w-full border rounded p-2" required>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Upload</button>
            <a href="curi_it.php" class="ml-4 text-blue-600 hover:underline">Back to BSIT Curriculum</a>
            <br><br>
            <a href="create_curriculum_table.php" class="text-orange-600 hover:underline">Create Curriculum Table (if needed)</a>
        </form>
        
        <?php if ($parsedRows): ?>
            <div class="overflow-x-auto">
                <h3 class="font-semibold mb-2">Uploaded Data Preview:</h3>
                <table class="min-w-full bg-white border mt-4">
                    <thead class="bg-blue-800 text-white">
                        <tr>
                            <th class="p-2 border">Fiscal Year</th>
                            <th class="p-2 border">Program</th>
                            <th class="p-2 border">Year/Sem</th>
                            <th class="p-2 border">Code</th>
                            <th class="p-2 border">Course Title</th>
                            <th class="p-2 border">Lec</th>
                            <th class="p-2 border">Lab</th>
                            <th class="p-2 border">Units</th>
                            <th class="p-2 border">Pre-Req</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php foreach ($parsedRows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td class="border p-2"><?= htmlspecialchars($cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 