<?php
// test_db.php in backend/
require_once './../config/database.php';
$pdo = getDatabaseConnection();
$pdo->exec("SELECT 1");  // Simple test query
echo "Working!";
?>