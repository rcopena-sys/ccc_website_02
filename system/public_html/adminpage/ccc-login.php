<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
   

    $sql = "SELECT * FROM signup_db WHERE email = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_type'] = $row['user_type'];
	$_SESSION['password'] = $row['password'];

        

	if ($_SESSION['password'] && $password)
	{
		if($_SESSION['user_type'] == 'dean'){
		header("Location: dashboard2.php");
		exit();
		}
		if($_SESSION['user_type'] == 'registrar'){
		header("Location: registrar.php");
		exit();
		}
	}
    } else {
        echo "<script>alert('Invalid Username or Password'); window.location.href='index.php';</script>";
    }

    $stmt->close();
}
$conn->close();
?>