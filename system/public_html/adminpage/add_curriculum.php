<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Curriculum</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-600 text-white p-6 flex flex-col">
        <div class="flex flex-col items-center">
            <img src="dci.png.png" class="rounded-full border-4 border-white" alt="DCI Logo" />
            <h2 class="mt-4 font-semibold text-lg">DCI</h2>
            <p class="text-sm text-gray-200 mb-6">Dean</p>
        </div>
        <nav class="space-y-4 mt-4">
            <a href="dashboard2.php" class="block py-2 px-4 rounded hover:bg-blue-500">Dashboard</a>
            <div class="relative">
                <button id="studentDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded hover:bg-blue-500 focus:outline-none">
                    Student
                    <svg id="dropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="studentDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
                    <a href="list.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">Student List</a>
                    <a href="student_grade.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">Student Grade</a>
                    <a href="curi.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100">Student Curriculum</a>
                </div>
            </div>
            <div class="relative">
                <button id="curriculumDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
                    Curriculum
                    <svg id="curriculumDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="curriculumDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
                    <a href="curi_cs.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">BSCS Curriculum</a>
                    <a href="curi_it.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100">BSIT Curriculum</a>
                </div>
            </div>
        </nav>
        <div class="mt-auto">
            <a href="index.php" class="block py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600 text-center">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-6">Add New Curriculum</h2>
            <div class="flex gap-2 mb-4">
                <a href="upload_curriculum_csv.php" class="inline-block bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Add CSV File</a>
              <div class="mb-6 p-4 bg-blue-100 text-blue-800 rounded">
    <h3 class="font-semibold mb-2">CSV Template for Curriculum Upload</h3>
    <p class="text-sm mb-2">
        <strong>  <strong></strong> 
    </p>
   <a href="download_template.php" download class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
    </svg>
    Download Sample CSV
</a>
</div>
            </div>
            <form action="save_curriculum.php" method="POST" class="space-y-6">
                <!-- Program Selection -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="program">
                        Select Program
                    </label>
                    <select name="program" id="program" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select a program</option>
                        <option value="BSCS">BS Computer Science</option>
                        <option value="BSIT">BS Information Technology</option>
                    </select>
                </div>

                <!-- Fiscal Year Selection -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="fiscal_year">
                        Fiscal Year
                    </label>
                    <select name="fiscal_year" id="fiscal_year" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select fiscal year</option>
                        <?php 
                        $currentYear = (int)date('Y');
                        $startYear = $currentYear - 5; // Show 5 years in the past
                        $endYear = $currentYear + 5;   // And 5 years in the future
                        
                        for ($year = $startYear; $year <= $endYear; $year++) {
                            $nextYear = $year + 1;
                            $fiscalYear = "$year-$nextYear";
                            $selected = ($year == $currentYear) ? 'selected' : '';
                            echo "<option value='$fiscalYear' $selected>$fiscalYear</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Semester Selection -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="year_semester">
                        Year and Semester
                    </label>
                    <select name="year_semester" id="year_semester" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select year and semester</option>
                        <option value="1-1">First Year - First Semester</option>
                        <option value="1-2">First Year - Second Semester</option>
                        <option value="2-1">Second Year - First Semester</option>
                        <option value="2-2">Second Year - Second Semester</option>
                        <option value="3-1">Third Year - First Semester</option>
                        <option value="3-2">Third Year - Second Semester</option>
                        <option value="4-1">Fourth Year - First Semester</option>
                        <option value="4-2">Fourth Year - Second Semester</option>
                    </select>
                </div>

                <!-- Subject Details -->
                <div id="subjects" class="space-y-4">
                    <div class="subject-entry bg-gray-50 p-4 rounded-md">
                        <h3 class="font-semibold mb-3">Subject 1</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Code</label>
                                <input type="text" name="course_code[]" class="w-full p-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Course Title</label>
                                <input type="text" name="course_title[]" class="w-full p-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Lecture Units</label>
                                <input type="number" name="lec_units[]" class="w-full p-2 border rounded-md" min="0" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Lab Units</label>
                                <input type="number" name="lab_units[]" class="w-full p-2 border rounded-md" min="0" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Total Units</label>
                                <input type="number" name="total_units[]" class="w-full p-2 border rounded-md" min="0" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Pre-requisites</label>
                                <input type="text" name="prerequisites[]" class="w-full p-2 border rounded-md" placeholder="Separate with commas">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add More Subjects Button -->
                <button type="button" id="addSubject" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 flex items-center gap-2">
                    <span class="text-xl font-bold">+</span> Add Another Subject
                </button>

                <!-- Submit Button -->
                <div class="flex justify-end mt-6">
                    <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                        Save Curriculum
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Dropdown functionality
        function setupDropdown(btnId, menuId, iconId) {
            const btn = document.getElementById(btnId);
            const menu = document.getElementById(menuId);
            const icon = document.getElementById(iconId);

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });

            document.addEventListener('click', function(event) {
                if (!btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
            });
        }

        // Setup dropdowns
        setupDropdown('studentDropdownBtn', 'studentDropdownMenu', 'dropdownIcon');
        setupDropdown('curriculumDropdownBtn', 'curriculumDropdownMenu', 'curriculumDropdownIcon');

        // Add Subject functionality
        document.getElementById('addSubject').addEventListener('click', function() {
            const subjects = document.getElementById('subjects');
            const subjectCount = subjects.children.length + 1;
            
            const newSubject = document.createElement('div');
            newSubject.className = 'subject-entry bg-gray-50 p-4 rounded-md';
            newSubject.innerHTML = `
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold">Subject ${subjectCount}</h3>
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="this.parentElement.parentElement.remove()">Remove</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Code</label>
                        <input type="text" name="course_code[]" class="w-full p-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Course Title</label>
                        <input type="text" name="course_title[]" class="w-full p-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Lecture Units</label>
                        <input type="number" name="lec_units[]" class="w-full p-2 border rounded-md" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Lab Units</label>
                        <input type="number" name="lab_units[]" class="w-full p-2 border rounded-md" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Total Units</label>
                        <input type="number" name="total_units[]" class="w-full p-2 border rounded-md" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pre-requisites</label>
                        <input type="text" name="prerequisites[]" class="w-full p-2 border rounded-md" placeholder="Separate with commas">
                    </div>
                </div>
            `;
            
            subjects.appendChild(newSubject);
        });
    </script>
</body>
</html> 