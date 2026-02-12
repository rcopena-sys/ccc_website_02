<?php
// Set session cookie parameters BEFORE starting session
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', true);

session_start();

require_once 'db_connect.php';

// Debug logging
error_log("terms_conditions.php accessed");
error_log("Session data: " . json_encode($_SESSION));

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['studentnumber'])) {
    error_log("No user_id or studentnumber in session - redirecting to index.php");
    header("Location: ../index.php");
    exit();
}

// Check if terms have already been accepted in this session
if (isset($_SESSION['terms_accepted']) && $_SESSION['terms_accepted'] === true) {
    error_log("Terms already accepted - redirecting to dashboard");
    // Redirect to appropriate dashboard
    $course = isset($_SESSION['course']) ? strtoupper(trim($_SESSION['course'])) : '';
    if ($course === 'BSCS') {
        error_log("Redirecting BSCS student to cs_studash.php");
        header("Location: cs_studash.php");
    } else if ($course === 'BSIT') {
        error_log("Redirecting BSIT student to dci_page.php");
        header("Location: dci_page.php");
    } else {
        error_log("Unknown course - redirecting to index.php");
        header("Location: ../index.php");
    }
    exit();
}

// Get student info
$student = null;
if (isset($_SESSION['user_id'])) {
    error_log("Getting student info for user_id: " . $_SESSION['user_id']);
    $stmt = $conn->prepare("SELECT firstname, lastname, course FROM signin_db WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
} elseif (isset($_SESSION['studentnumber'])) {
    error_log("Getting student info for studentnumber: " . $_SESSION['studentnumber']);
    $stmt = $conn->prepare("SELECT firstname, lastname, course FROM signin_db WHERE student_number = ?");
    $stmt->bind_param("s", $_SESSION['studentnumber']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

if (!$student) {
    error_log("Student not found in database - redirecting to index.php");
    header("Location: ../index.php");
    exit();
}

error_log("Student found: " . json_encode($student));

$course = strtoupper(trim($student['course']));
$dashboard_page = ($course === 'BSCS') ? 'cs_studash.php' : 'dci_page.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agree_terms'])) {
        error_log("Terms accepted - setting session variables and redirecting");
        $_SESSION['terms_accepted'] = true;
        $_SESSION['terms_accepted_date'] = date('Y-m-d H:i:s');
        $_SESSION['course'] = $student['course']; // Store course in session
        error_log("Redirecting to dashboard: " . $dashboard_page);
        header("Location: " . $dashboard_page);
        exit();
    } else {
        error_log("Terms declined - redirecting to index");
        header("Location: ../index.php");
        exit();
    }
} elseif (isset($_POST['skip_session'])) {
    // Skip terms for this session only
    $_SESSION['terms_accepted'] = true;
    $_SESSION['terms_temporary'] = true;
    header("Location: " . $dashboard_page);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Prospectus System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Floating particles for ambiance */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(66, 133, 244, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(66, 133, 244, 0.15) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(1deg); }
            66% { transform: translateY(20px) rotate(-1deg); }
        }
        
        .terms-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 32px 64px rgba(10, 25, 41, 0.5),
                0 16px 32px rgba(30, 58, 95, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            max-width: 900px;
            width: 100%;
            padding: 50px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(66, 133, 244, 0.2);
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            transform: translateY(30px);
            opacity: 0;
        }
        
        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .terms-header {
            text-align: center;
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
        
        .terms-header h1 {
            background: linear-gradient(135deg, #0a1929 0%, #1e3a5f 50%, #2e5490 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 2.8rem;
            letter-spacing: -0.02em;
            position: relative;
        }
        
        .terms-header h1::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #1e3a5f, #4285f4, #1e3a5f);
            border-radius: 2px;
            animation: expandWidth 1s ease-out 0.5s both;
        }
        
        @keyframes expandWidth {
            from { width: 0; }
            to { width: 80px; }
        }
        
        .terms-header p {
            color: #4285f4;
            font-size: 1.2rem;
            margin: 0;
            font-weight: 400;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 0.3s forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        .student-info {
            background: linear-gradient(135deg, #1e3a5f 0%, #2e5490 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 35px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: slideInLeft 0.8s ease-out 0.4s both;
            transform: translateX(-30px);
            opacity: 0;
        }
        
        @keyframes slideInLeft {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .student-info::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(66, 133, 244, 0.2) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .student-info h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.3rem;
            position: relative;
            z-index: 1;
        }
        
        .student-info .course-badge {
            background: rgba(66, 133, 244, 0.3);
            backdrop-filter: blur(10px);
            padding: 12px 28px;
            border-radius: 30px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 500;
            font-size: 1rem;
            border: 1px solid rgba(66, 133, 244, 0.4);
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .student-info .course-badge:hover {
            background: rgba(66, 133, 244, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.1) 0%, rgba(66, 133, 244, 0.15) 100%);
            border: 2px solid rgba(66, 133, 244, 0.3);
            color: #1e3a5f;
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 30px;
            animation: slideInRight 0.8s ease-out 0.5s both;
            transform: translateX(30px);
            opacity: 0;
            backdrop-filter: blur(10px);
        }
        
        @keyframes slideInRight {
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .terms-content {
            background: linear-gradient(135deg, #f8fafc 0%, #e8f0fe 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 35px;
            border-left: 5px solid #4285f4;
            position: relative;
            overflow: hidden;
            animation: fadeInScale 0.8s ease-out 0.6s both;
            transform: scale(0.95);
            opacity: 0;
        }
        
        @keyframes fadeInScale {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .terms-content::before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 120px;
            color: rgba(66, 133, 244, 0.15);
            font-family: Georgia, serif;
            line-height: 1;
        }
        
        .terms-text {
            color: #1e3a5f;
            line-height: 1.9;
            font-size: 1.15rem;
            text-align: justify;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
            font-weight: 400;
        }
        
        .agree-section {
            text-align: center;
            animation: fadeInUp 0.8s ease-out 0.7s both;
            transform: translateY(30px);
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .checkbox-wrapper {
            margin-bottom: 30px;
        }
        
        .form-check {
            display: inline-flex;
            align-items: center;
            padding: 15px 25px;
            background: rgba(66, 133, 244, 0.05);
            border-radius: 12px;
            border: 2px solid rgba(66, 133, 244, 0.2);
            transition: all 0.3s ease;
        }
        
        .form-check:hover {
            background: rgba(66, 133, 244, 0.1);
            border-color: rgba(66, 133, 244, 0.4);
            transform: translateY(-2px);
        }
        
        .form-check-input {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            border: 2px solid #4285f4;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .form-check-input:checked {
            background-color: #4285f4;
            border-color: #4285f4;
            animation: checkPulse 0.4s ease;
        }
        
        @keyframes checkPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .form-check-label {
            color: #1e3a5f;
            font-weight: 500;
            cursor: pointer;
            font-size: 1.05rem;
            user-select: none;
        }
        
        .btn-agree {
            background: linear-gradient(135deg, #1e3a5f 0%, #4285f4 50%, #669df6 100%);
            color: white;
            border: none;
            padding: 18px 55px;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 
                0 8px 25px rgba(66, 133, 244, 0.4),
                0 4px 12px rgba(30, 58, 95, 0.3);
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }
        
        .btn-agree::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-agree:hover::before {
            left: 100%;
        }
        
        .btn-agree:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 
                0 12px 35px rgba(66, 133, 244, 0.5),
                0 6px 18px rgba(30, 58, 95, 0.4);
            color: white;
        }
        
        .btn-agree:active {
            transform: translateY(-1px) scale(1.01);
        }
        
        .btn-agree:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 12px rgba(30, 58, 95, 0.2);
        }
        
        .btn-outline-secondary {
            background: transparent;
            color: #4285f4;
            border: 2px solid #4285f4;
            padding: 18px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-outline-secondary:hover {
            background: #4285f4;
            color: white;
            border-color: #4285f4;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(66, 133, 244, 0.4);
        }
        
        @media (max-width: 768px) {
            .terms-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .terms-header h1 {
                font-size: 2.2rem;
            }
            
            .terms-content {
                padding: 25px;
            }
            
            .btn-agree {
                padding: 15px 40px;
                font-size: 1rem;
            }
            
            .btn-outline-secondary {
                padding: 15px 35px;
                font-size: 1rem;
            }
        }
        
        /* Pulse animation for important elements */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pulse-on-hover:hover {
            animation: pulse 1s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="terms-container">
        <div class="terms-header">
            <h1><i class="fas fa-shield-alt me-3 pulse-on-hover"></i>Terms and Conditions</h1>
            <p>Please read and accept the terms before proceeding</p>
        </div>
        
        <div class="student-info">
            <h5>Welcome, <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h5>
            <div class="course-badge pulse-on-hover">
                <i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($course); ?> Student
            </div>
        </div>
        
        <div class="alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Important:</strong> You must read and accept these terms and conditions to access the prospectus system.
        </div>
        
        <div class="terms-content">
            <p class="terms-text">
                "By accessing and using this prospectus system, you acknowledge and agree that all academic information, course details, and student records provided herein are the property of the institution and are intended solely for legitimate academic purposes. You agree not to misuse, alter, distribute, or share any data without proper authorization. Continued use of this system constitutes your acceptance of these terms and your compliance with all institutional policies, data privacy guidelines, and applicable regulations."
            </p>
        </div>
        
        <div class="agree-section">
            <div class="checkbox-wrapper mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agreeCheckbox" required>
                    <label class="form-check-label" for="agreeCheckbox">
                        I have read, understood, and agree to the Terms and Conditions
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <form id="termsForm" method="POST" style="display: inline;">
                    <input type="hidden" name="agree_terms" value="1">
                    <button type="submit" class="btn-agree" id="agreeBtn" disabled>
                        <i class="fas fa-check-circle me-2"></i>Agree and Continue
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="skip_session" value="1">
                    <button type="submit" class="btn btn-outline-secondary" style="padding: 18px 40px; border-radius: 50px; font-weight: 600;">
                        <i class="fas fa-forward me-2"></i>Skip for this session
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const agreeCheckbox = document.getElementById('agreeCheckbox');
            const agreeBtn = document.getElementById('agreeBtn');
            const termsForm = document.getElementById('termsForm');
            
            // Enable/disable button based on checkbox
            agreeCheckbox.addEventListener('change', function() {
                agreeBtn.disabled = !this.checked;
                if (this.checked) {
                    agreeBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                } else {
                    agreeBtn.style.background = 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)';
                }
            });
            
            // Handle form submission
            termsForm.addEventListener('submit', function(e) {
                if (!agreeCheckbox.checked) {
                    e.preventDefault();
                    alert('You must agree to the terms and conditions to continue.');
                    return false;
                }
                
                // Show loading state
                agreeBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                agreeBtn.disabled = true;
                
                // Form will submit normally and PHP will handle the redirect
            });
        });
    </script>
</body>
</html>
