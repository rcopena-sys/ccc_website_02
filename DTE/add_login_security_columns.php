<?php
require_once 'db.php';

// Check if columns exist, if not add them
$check_columns = [
    'failed_attempts' => "INT NOT NULL DEFAULT 0",
    'last_failed_attempt' => "DATETIME DEFAULT NULL",
    'account_locked_until' => "DATETIME DEFAULT NULL"
];

echo "<h2>Adding Login Security Columns</h2>";

foreach ($check_columns as $column => $definition) {
    $check_sql = "SHOW COLUMNS FROM `signin_db` LIKE '$column'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $alter_sql = "ALTER TABLE `signin_db` ADD COLUMN `$column` $definition";
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p>✅ Successfully added column: $column</p>";
        } else {
            echo "<p>❌ Error adding column $column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>ℹ️ Column '$column' already exists</p>";
    }
}

echo "<p>✅ Security columns check complete. <a href='login.php'>Return to login</a></p>";
?>
