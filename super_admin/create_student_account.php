<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student Account</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', sans-serif;
        }
        
        body { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #1d4ed8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #1e3a8a;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(30, 58, 138, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.4);
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success {
            background: rgba(72, 187, 120, 0.1);
            color: #22543d;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .error {
            background: rgba(245, 101, 101, 0.1);
            color: #742a2a;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .student-info {
            background: rgba(30, 58, 138, 0.1);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid rgba(30, 58, 138, 0.2);
        }
        
        .student-info h3 {
            color: #1e3a8a;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .student-info p {
            color: #4a5568;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Create Student Account</h2>
        
        <?php
        include 'db.php';
        
        $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
        $email = isset($_GET['email']) ? $_GET['email'] : '';
        
        // Get student information from students_db
        $student_info = null;
        if (!empty($student_id)) {
            $query = "SELECT * FROM students_db WHERE student_id = '$student_id'";
            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                $student_info = $result->fetch_assoc();
            }
        }
        
        $message = '';
        $message_type = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $role = $_POST['role'];
            
            // Validation
            if (empty($username) || empty($password) || empty($confirm_password)) {
                $message = 'All fields are required';
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = 'Passwords do not match';
                $message_type = 'error';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters long';
                $message_type = 'error';
            } else {
                // Check if username already exists
                $check_query = "SELECT COUNT(*) as count FROM signin_db WHERE username = '$username'";
                $check_result = $conn->query($check_query);
                if ($check_result && $row = $check_result->fetch_assoc()) {
                    if ($row['count'] > 0) {
                        $message = 'Username already exists';
                        $message_type = 'error';
                    } else {
                        // Insert new account
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $insert_query = "INSERT INTO signin_db (student_id, firstname, lastname, email, username, password, role_name, created_at) 
                                       VALUES ('$student_id', '{$student_info['student_name']}', '', '$email', '$username', '$hashed_password', '$role', NOW())";
                        
                        if ($conn->query($insert_query)) {
                            $message = 'Account created successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Error creating account: ' . $conn->error;
                            $message_type = 'error';
                        }
                    }
                }
            }
        }
        ?>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($student_info): ?>
            <div class="student-info">
                <h3>Student Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student_info['student_name']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_info['student_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student_info['email']); ?></p>
                <p><strong>Program:</strong> <?php echo htmlspecialchars($student_info['programs']); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Student">Student</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
            <a href="list_students.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">Back to Student List</a>
        </form>
    </div>
</body>
</html>
