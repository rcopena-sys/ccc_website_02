<?php
// Script to update all prospectus pages with responsive design

// Files to update
$files = [
    'dcipros1st.php',
    'dcipros2nd.php',
    'dcipros3rd.php',
    'dcipros4th.php'
];

// Create backup directory if it doesn't exist
if (!is_dir('backups')) {
    mkdir('backups', 0755, true);
}

// Common CSS that will be added to each file
$commonCSS = <<<'EOT'
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/prospectus.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        /* Additional styles specific to this page */
        .curriculum-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 600px; /* Minimum width before scrolling */
        }
        
        /* Responsive adjustments specific to this page */
        @media (max-width: 768px) {
            .student-info .row > div {
                margin-bottom: 0.5rem;
            }
            
            .student-info .text-md-end {
                text-align: left !important;
            }
        }
    </style>
EOT;

// Common JavaScript that will be added to each file
$commonJS = <<<'EOT'
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            // Close sidebar when clicking on a link
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            });
            
            // Generate barcode
            try {
                const studentId = document.querySelector('.barcode-text')?.textContent.replace('ID: ', '').trim();
                if (studentId) {
                    JsBarcode('#barcode', studentId, {
                        format: 'CODE128',
                        width: 2,
                        height: 50,
                        displayValue: true,
                        fontSize: 12,
                        margin: 5,
                        lineColor: '#000',
                        background: 'transparent'
                    });
                }
            } catch (e) {
                console.error('Barcode generation failed:', e);
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            const overlay = document.getElementById('overlay');
            
            if (sidebar && menuToggle && overlay) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && !overlay.contains(event.target)) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        });
    </script>
EOT;

// Common sidebar HTML
$sidebarHTML = <<<'EOT'
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle d-lg-none" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="d-flex flex-column align-items-center mb-3">
            <img src="<?php echo !empty($student['profile_image']) ? htmlspecialchars($student['profile_image']) : 'default_profile.jpg'; ?>" 
                 alt="Profile" 
                 class="rounded-circle mb-3" 
                 style="width: 80px; height: 80px; cursor: pointer;"
                 data-bs-toggle="modal" 
                 data-bs-target="#profileModal">
            <h5 class="text-center"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h5>
            <p class="text-center"><?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>
        <ul class="nav flex-column w-100">
            <li class="nav-item mb-2">
                <a href="dci_page.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros1st.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> First Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros2nd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Second Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros3rd.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Third Year
                </a>
            </li>
            <li class="nav-item mb-2">
                <a href="dcipros4th.php" class="nav-link text-white">
                    <i class="bi bi-book"></i> Fourth Year
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <!-- Print Button -->
        <button onclick="window.print()" class="print-button d-print-none">
            <i class="bi bi-printer"></i>
        </button>
EOT;

// Process each file
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Skipping $file - File not found.<br>";
        continue;
    }
    
    // Create backup
    $backupFile = 'backups/' . $file . '.bak.' . date('YmdHis');
    if (copy($file, $backupFile)) {
        echo "Created backup: $backupFile<br>";
    } else {
        echo "Failed to create backup for $file<br>";
        continue;
    }
    
    // Read the file
    $content = file_get_contents($file);
    
    // Update CSS and JS includes
    $content = preg_replace(
        '/<link[^>]+bootstrap\.min\.css[^>]*>\s*<link[^>]+bootstrap-icons[^>]*>\s*<script[^>]+jsbarcode[^>]*>\s*<\/script>/is',
        $commonCSS,
        $content
    );
    
    // Add mobile menu toggle and sidebar
    if (strpos($content, 'class="prospectus-container"') !== false) {
        $content = str_replace(
            '<div class="prospectus-container">',
            '<div class="prospectus-container">' . "\n" . str_replace('class="nav-link text-white"', 'class="nav-link text-white' . (basename($file, '.php') === 'dcipros' . substr($file, 7, 1) ? ' active' : '') . '"', $sidebarHTML),
            $content
        );
    }
    
    // Update JavaScript
    $content = preg_replace(
        '/<script[^>]+bootstrap\.bundle\.min\.js[^>]*>[\s\S]*?<\/script>/is',
        $commonJS,
        $content
    );
    
    // Add closing div for main-content if not exists
    if (strpos($content, '<div class="main-content">') !== false && 
        strpos($content, '</div><!-- End main-content -->') === false) {
        $content = str_replace(
            '    </div>\n</body>',
            '    </div>\n    </div><!-- End main-content -->\n</body>',
            $content
        );
    }
    
    // Save the updated file
    if (file_put_contents($file, $content) !== false) {
        echo "Updated: $file<br>";
    } else {
        echo "Failed to update: $file<br>";
    }
}

echo "<br>Update process completed. Please verify the changes and test the responsive design on different devices.";
?>
