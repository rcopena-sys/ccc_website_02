<?php
session_start();
if (!isset($_SESSION['studentnumber'])) {
    header("Location: index.php");
    exit();
}

$target_dir = "profile_pictures/";
$studentnumber = $_SESSION['studentnumber'];
$target_file = $target_dir . $studentnumber . "_" . basename($_FILES["profile_picture"]["name"]);

if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
    // Save new profile path to DB if you want
    header("Location: dashboard.php");
    exit();
} else {
    echo "Sorry, there was an error uploading your file.";
}