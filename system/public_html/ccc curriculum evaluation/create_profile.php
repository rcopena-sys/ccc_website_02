<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetching and sanitizing inputs
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['userEmail']);
    $password = password_hash($_POST['userPassword'], PASSWORD_DEFAULT);
    $userType = $_POST['userType'];

    // Check if all fields are filled
    if (!empty($firstName) && !empty($lastName) && !empty($email) && !empty($password) && !empty($userType)) {
        try {
            // Database connection
            $conn = new PDO("mysql:host=localhost;dbname=your_database_name", "your_username", "your_password");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert the user data into the database
            $sql = "INSERT INTO users (firstName, lastName, email, password, userType) 
                    VALUES (:firstName, :lastName, :email, :password, :userType)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':userType', $userType);

            if ($stmt->execute()) {
                echo "<script>alert('Account created successfully!'); window.location.href='login.php';</script>";
            } else {
                echo "<script>alert('Error: Unable to create account.');</script>";
            }
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    } else {
        echo "<script>alert('Please fill all the fields.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City College of Calamba - Create Account</title>
    <style>
        body {
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
            background-image: url('https://www.ccc.edu.ph/images/2019/07/08/ccc-facade.png');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .create-account-box {
            background: rgba(0, 0, 255, 0.5); /* Semi-transparent blue */
            backdrop-filter: blur(50px);
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
        }

        .logo {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
        }

        .create-account-box h2 {
            margin-bottom: 10px;
        }

        .create-account-box input[type="text"],
        .create-account-box input[type="password"],
        .create-account-box input[type="email"],
        .create-account-box select {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .create-account-box button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .create-account-box a {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: #007bff;
        }

        .social-icons {
            margin-top: 20px;
        }

        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
            color: #333;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="create-account-box">
        <img src="https://www.ccc.edu.ph/images/home/ccc-home-logo.png" alt="City College of Calamba Logo" class="logo">
        <h2>Create Account</h2>
        <p>Create your CCC CURRICULUM EVALUATION ACCOUNT account</p>
        <form action="create_account.php" method="post">
            <input type="text" name="firstName" id="firstName" placeholder="Enter First Name" required>
            <input type="text" name="lastName" id="lastName" placeholder="Enter Last tName" required>
            <input type="email" name="userEmail" id="userEmail" placeholder="Enter yout email address " required>
            <input type="password" name="userPassword" id="userPassword" placeholder="Enter your password" required>
            <select name="userType" id="userType" required>
                <option value="">Select User Type</option>
                <option value="dean">Dean</option>
                <option value="registrar">Registrar</option>
            </select>
            <button type="submit">Create Account</button>

        </form>

        <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>