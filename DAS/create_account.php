<?php
session_start();
include 'db.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City College of Calamba - Create Account</title> <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style> 
    
    .root {
        --blue: #007bff;
    }
        body {
            margin: 0;
            font-family: 'Garamond', 'EB Garamond', Times, serif;
            background-image: url('ccc.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .create-account-box {
            background: rgba(173, 173, 173, 0.5); /* Semi-transparent blue */
            backdrop-filter: blur(10px); /* Adjust the blur radius for the desired effect */
            padding: 20px;
            border-radius: 8px;
            width: 500px;
            text-align: center;
            color: #fff;
        }

        .logo {
            width: 100px;
            height: auto;
            /* margin-bottom: 12px; */
        }

        .create-account-box h1 {
            margin-bottom: 0px;
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
            font-size: 15px;
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
            color: var(--blue);
        }

        .social-icons {
            margin-top: 20px;
        }

        .social-icons a {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
            color: #fff;
            font-size: 20px;
        }
    </style>

</head>
<body>
    <div class="create-account-box">
        <img src="https://www.ccc.edu.ph/images/home/ccc-home-logo.png" alt="City College of Calamba Logo" class="logo">
        <h1>Create Account</h1>
        <p>Create your CCC CURRICULUM EVALUATION ACCOUNT</p>
        <form action="register.php" method="post">
            <input type="text" name="firstName" id="firstName" placeholder="Enter First Name" required>
            <input type="text" name="lastName" id="lastName" placeholder="Enter Last Name" required>
            <input type="email" name="userEmail" id="userEmail" placeholder="Enter your email address" required>
            <input type="password" name="userPassword" id="userPassword" placeholder="Enter your password" required>
            <select name="userType" id="userType" required>
                <option value="">Select User Type</option>
                <option value="dean">Dean</option>
                <option value="secretary">Secretary</option>
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
    <script src="https://kit.fontawesome.com/YOUR-KIT-ID.js" crossorigin="anonymous"></script>
</body>
</html>
