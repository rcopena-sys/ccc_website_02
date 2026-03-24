<?php
// Delegate to the main, environment-aware connection in the root
// db_connect.php so student pages use the same credentials.
require_once __DIR__ . '/../db_connect.php';
?>