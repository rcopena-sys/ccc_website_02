<?php
// Database connection (replace with your credentials)
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Example: Query to get student data
$sql = "SELECT student_name FROM students";
$result = $conn->query($sql);

// Example: Query to get curriculum data
$sql_curriculum = "SELECT curriculum_name FROM curriculums";
$result_curriculum = $conn->query($sql_curriculum);

?>

<!DOCTYPE html>
<html>
<head>
    <title>School Dashboard</title>
</head>
<body>

    <h1>DashBoard</h1>

    <h2>Dean: Arlou H Fernando</h2>

    <h2>Student List</h2>
    <ul>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<li>" . $row["student_name"]. "</li>";
            }
        } else {
            echo "0 results";
        }
        ?>
    </ul>

    <h2>Curriculum</h2>
    <ul>
        <?php
        if ($result_curriculum->num_rows > 0) {
            while($row = $result_curriculum->fetch_assoc()) {
                echo "<li>" . $row["curriculum_name"]. "</li>";
            }
        } else {
            echo "0 results";
        }
        ?>
    </ul>

    <?php
    $conn->close();
    ?>

</body>
</html>