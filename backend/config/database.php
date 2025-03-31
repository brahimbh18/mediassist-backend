<?php
/**
 * Database connection using PDO for SQLite.
 */

// Database file path
$dbPath = __DIR__ . '/mediassist.db';

function logError(string $message): void {
    // Log the error to the default error log or to a file
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
        // Handle connection errors
        die("Database connection failed: " . $e->getMessage());
    } catch (PDOException $e) {
        error_log($e->getMessage(), 3, __DIR__ . '/../logs/errors.log');
        die("Database error: " . $e->getMessage());
       
    }
}


?>