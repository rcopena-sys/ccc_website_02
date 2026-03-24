<?php
session_start();
require_once 'db_connect.php';

$error_message = '';

// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     var_dump($_POST);
//     exit();
// }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and validate inputs
    $email = trim($_POST['email'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($student_id) || empty($password)) {
        $error_message = "All fields are required.";
    } else {
        try {
            // Prepare SQL to get user data including course
            $sql = "SELECT id, firstname, lastname, email, student_id, password, course 
                    FROM signin_db 
                    WHERE email = ? AND student_id = ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("ss", $email, $student_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows != 1) {
                throw new Exception("Invalid email, student id, or password.");
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                throw new Exception("Invalid email, student id, or password.");
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['course'] = $user['course'];
            
            // Redirect based on course
            $course = strtoupper(trim($user['course']));
            if ($course === 'BSCS') {
                header("Location: cs_studash.php");
                exit();
            } else if ($course === 'BSIT') {
                header("Location: dci_page.php");
                exit();
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        } finally {
            // Close statements if they were opened
            if (isset($stmt)) $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City College of Calamba Login</title>
    <style>
        @keyframes fadeSlideIn {
            0% {
                opacity: 0;
                transform: translateY(-50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    
        body {
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
            background-image: url('ccc.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            animation: fadeSlideIn 1s ease-in-out;
        }
    
        .login-box {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: fadeSlideIn 1s ease-in-out;
            animation-delay: 0.3s;
            animation-fill-mode: both;
        }
    
        .logo {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
            animation: fadeSlideIn 1.2s ease-in-out;
        }
    
        .login-box h2,
        .login-box p {
            animation: fadeSlideIn 1.4s ease-in-out;
        }
    
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
    
        .login-box input:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.4);
        }
    
        .login-box button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: 0.3s ease;
        }
    
        .login-box button:hover {
            background-color: #0056b3;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.6);
            transform: scale(1.03);
        }
    
        .login-box a {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s;
        }
    
        .login-box a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    
        .social-icons {
            margin-top: 20px;
            animation: fadeSlideIn 1.6s ease-in-out;
        }
    
        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
            color: #333;
            font-size: 20px;
            transition: transform 0.3s ease;
        }
    
        .social-icons a:hover {
            transform: scale(1.2);
            color: #007bff;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
    
</head>
<body>
    <?php
    if (isset($_SESSION['error'])) {
        $error_message = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    ?>
    <div style="display: flex; justify-content: space-between; align-items: center; height: 100vh; width: 100vw;">
        <!-- Vision and Mission Section (Left) -->
        <div style="flex: 1; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; padding-left: 5vw;">
            <h2 style="font-size: 2rem; color: #1a237e; margin-bottom: 8px;"></h2>
            <h3 style="font-size: 1.5rem; color: #007bff; margin-bottom: 16px;"></h3>
            <div style="background: rgba(255,255,255,0.85); border-radius: 10px; padding: 24px 32px; max-width: 500px; box-shadow: 0 6px 24px rgba(0,0,0,0.08);">
                <h4 style="color: #007bff; font-size: 1.25rem; margin-bottom: 6px;">Vision</h4>
                <p style="margin-bottom: 18px; color: #333;">A leader provider of excellent information technology professional who manifest global standards, prepare to meet and respond to the needs and demands of the computer and IT industry.</p>
                <h4 style="color: #007bff; font-size: 1.25rem; margin-bottom: 6px;">Mission</h4>
                <p style="color: #333;">To strengthen the foundation for computer and IT education and literacy by exposing the would-be IT professionals to the theories and practice guided by ethics, values and professionalism.</p>
            </div>
        </div>
        <!-- Login Box (Right) -->
        <div class="login-box" style="margin-right: 8vw; min-width: 340px; align-self: center;">
            <img src="chomelogo.png" alt="City College of Calamba Logo" class="logo">
            <h2>City College of Calamba</h2>
            <p>CCC CURRICULUM EVALUATION</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form action="index.php" method="post">
                <input type="text" name="email" placeholder="Email" required>
                <input type="text" name="student_id" placeholder="Student ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="course" id="course" required>
                    <option value="">Select Course</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                </select>
                <button type="submit">LOGIN</button>
            </form>
            <a href="forgot_password.php">forget password</a>
            <a href="create_acc.php" style="display: block; margin-top: 10px; padding: 8px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; transition: background-color 0.3s;">Create Account</a>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    </body>
</html>
