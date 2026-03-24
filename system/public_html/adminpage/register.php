<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['userEmail']);
    $userType = trim($_POST['userType']);
    $password = $_POST['userPassword'];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($userType) || empty($password)) {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }


    $checkEmail = $conn->prepare("SELECT * FROM signup_db WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        echo "<script>alert('Email already registered. Please use another one.'); window.history.back();</script>";
        $checkEmail->close();
    } else {
        $checkEmail->close();

        $stmt = $conn->prepare("INSERT INTO signup_db (firstName, lastName, email, password, user_Type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $password, $userType);

        if ($stmt->execute()) {
           header("Location: index.php");
        } else {
            echo "<script>alert('Something went wrong while creating the account.');</script>";
        }

        $stmt->close();
    }
}

$conn->close();
?>
