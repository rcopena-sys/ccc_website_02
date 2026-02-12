<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    // Here, you would typically save the data to a database
    // Example: mysqli_query($conn, "INSERT INTO users (surname, firstname, email, password, user_type) VALUES ('$surname', '$firstname', '$email', '$password', '$user_type')");

    echo "<script>alert('Account created successfully!');</script>";
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
            background: rgba(0, 0, 255, 0.5);
            backdrop-filter: blur(10px);
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
        <p>Create your CCC CURRICULUM EVALUATION ACCOUNT</p>
        <form method="post">
            <input type="text" name="surname" placeholder="Surname" required>
            <input type="text" name="firstname" placeholder="Firstname" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="user_type" required>
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