<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #clock {
            font-family: 'Arial', sans-serif;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .student-card {
            width: 150px;
            background: rgba(255, 255, 255, 0.30);
            backdrop-filter: blur(10px);
            padding: 10px;
            margin: 10px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
    
        .student-card:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
    
        .student-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }
    
        .student-card img:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="flex bg-blue-100 min-h-screen">
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
    <button id="studentDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
        Student
        <svg id="dropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </button>
    <div id="studentDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
        <a href="list.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t last:rounded-b">Student List</a>
        <a href="stugra.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100">Student Grade</a>
        <a href="stucuri.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">Student Curriculum</a>
    </div>
</div>
            <!-- Evaluation Dropdown -->
            <div class="relative">
                <button id="evaluationDropdownBtn" class="w-full flex justify-between items-center py-2 px-4 rounded bg-blue-500 hover:bg-blue-600 focus:outline-none">
                    Evaluation
                    <svg id="evaluationDropdownIcon" class="w-4 h-4 ml-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div id="evaluationDropdownMenu" class="hidden absolute left-0 w-full z-10 flex flex-col bg-white border border-blue-200 rounded shadow-lg mt-1">
                    <a href="stueval.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 border-b border-blue-100 first:rounded-t">Evaluate Student</a>
                    <a href="semeva.php" class="py-2 px-6 text-blue-700 hover:bg-blue-100 last:rounded-b">Semestral Evaluation</a>
                </div>
            </div>
            <a href="curi.php" class="block py-2 px-4 rounded hover:bg-blue-500">Curriculum</a>
        </nav>
        <script>
            // Student Dropdown
             const btn = document.getElementById('studentDropdownBtn');
            const menu = document.getElementById('studentDropdownMenu');
          const icon = document.getElementById('dropdownIcon');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            });
            // Evaluation Dropdown
            const evalBtn = document.getElementById('evaluationDropdownBtn');
            const evalMenu = document.getElementById('evaluationDropdownMenu');
            const evalIcon = document.getElementById('evaluationDropdownIcon');
            evalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                evalMenu.classList.toggle('hidden');
                evalIcon.classList.toggle('rotate-180');
            });
            // Optional: Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!btn.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
                if (!evalBtn.contains(event.target) && !evalMenu.contains(event.target)) {
                    evalMenu.classList.add('hidden');
                    evalIcon.classList.remove('rotate-180');
                }
            });
        </script>
        <div class="mt-auto">
            <a href="index.php" class="block py-2 px-4 bg-red-500 text-white rounded hover:bg-red-600 text-center">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 p-10 bg-cover bg-center" style="background-image: url('https://www.ccc.edu.ph/images/2022/01/02/ccc-vid-overlaay.jpg');">
   
             

          
    </div>

    
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>