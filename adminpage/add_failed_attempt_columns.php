<?php
require_once 'db.php';

// Add new columns to track failed login attempts
$sql = [
    "ALTER TABLE signin_db ADD COLUMN IF NOT EXISTS failed_attempts INT NOT NULL DEFAULT 0",
    "ALTER TABLE signin_db ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME DEFAULT NULL",
    "ALTER TABLE signin_db ADD COLUMN IF NOT EXISTS account_locked_until DATETIME DEFAULT NULL"
];

$conn->begin_transaction();

try {
    foreach ($sql as $query) {
        if (!$conn->query($query)) {
            throw new Exception("Error executing query: " . $conn->error);
        }
    }
    $conn->commit();
    echo "Successfully added security columns to signin_db table.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
