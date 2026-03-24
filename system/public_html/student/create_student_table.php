<?php
require_once 'db_connect.php';

echo "<h2>Creating student_accounts table...</h2>";

// SQL to create the table
$sql = "CREATE TABLE IF NOT EXISTS `student_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `academic_year` enum('1st year','2nd year','3rd year','4th year') NOT NULL,
  `course` enum('BSIT','BSCS','DCI','DBA','DTE','DAS','PSYCH') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_number` (`student_number`),
  KEY `course` (`course`),
  KEY `academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Table student_accounts created successfully<br>";
    
    // Check if we should add sample data
    $check = $conn->query("SELECT COUNT(*) as count FROM student_accounts");
    $row = $check->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Sample data
        $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
        $sample_data_sql = "INSERT INTO `student_accounts` 
            (`firstname`, `lastname`, `email`, `password`, `student_number`, `academic_year`, `course`) 
            VALUES 
            ('John', 'Doe', 'john.doe@example.com', '".$hashed_password."', '2023-0001', '1st year', 'BSIT'),
            ('Jane', 'Smith', 'jane.smith@example.com', '".$hashed_password."', '2023-0002', '2nd year', 'BSCS')";
            
        if ($conn->query($sample_data_sql) === TRUE) {
            echo "Sample data inserted successfully<br>";
            echo "<strong>Test Account:</strong><br>";
            echo "Email: john.doe@example.com<br>";
            echo "Password: password123<br>";
        } else {
            echo "Error inserting sample data: " . $conn->error . "<br>";
        }
    }
    
    // Check if courses table exists and add foreign key if it does
    $check_courses = $conn->query("SHOW TABLES LIKE 'courses'");
    if ($check_courses->num_rows > 0) {
        $fk_sql = "ALTER TABLE `student_accounts` 
                  ADD CONSTRAINT `fk_student_course` 
                  FOREIGN KEY (`course`) 
                  REFERENCES `courses`(`course_code`) 
                  ON UPDATE CASCADE";
        
        if ($conn->query($fk_sql) === TRUE) {
            echo "Foreign key constraint added successfully<br>";
        } else {
            echo "Error adding foreign key: " . $conn->error . "<br>";
            echo "Note: This might be because the courses table doesn't exist or the foreign key already exists.<br>";
        }
    }
    
    echo "<h3>Table is ready to use!</h3>";
    echo "<a href='create_acc.php'>Go to Registration Page</a>";
    
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 20px;
        max-width: 800px;
        margin: 0 auto;
    }
    h2, h3 {
        color: #2c3e50;
    }
    .success {
        color: green;
        font-weight: bold;
    }
    .error {
        color: red;
        font-weight: bold;
    }
    a {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 15px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    a:hover {
        background-color: #2980b9;
    }
</style>
