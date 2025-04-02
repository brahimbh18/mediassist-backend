<?php
/**
 * Database connection using PDO for SQLite.
 */

// Database file path
$dbPath = __DIR__ . '/mediassist.db';

function logError(string $message): void {
    error_log($message, 3, __DIR__ . '/../logs/errors.log');
}

// Update the PDO error handling:

function getDatabaseConnection() {
    global $dbPath;
    try {
        // Create a PDO connection to SQLite
        $pdo = new PDO("sqlite:$dbPath");

        // Set error mode to exceptions
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    } catch (PDOException $e) {
        // Log the error
        logError("Database connection failed: " . $e->getMessage());
        
        // Throw a new exception with a user-friendly message
        throw new Exception("Database connection failed. Please try again later.");
    }
}


?>