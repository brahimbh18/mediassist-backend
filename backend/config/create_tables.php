<?php
/**
 * Table creation script for all required tables.
 * 
 * This file is responsible for creating all the necessary tables in the database.
 */

// Include database connection
require_once __DIR__ . '/database.php';

function createUserTable() {
    try {
        $pdo = getDatabaseConnection();

        // Create users table if it doesn't exist
        $query = "CREATE TABLE IF NOT EXISTS User (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            age INTEGER,
            gender TEXT,
            blood_type TEXT,
            weight REAL,
            height REAL,
            allergiesAndChronic_diseases TEXT,
            phone TEXT,
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $pdo->exec($query);
        echo "User table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating user table: " . $e->getMessage());
    }
}

function createMedicamentTable() {
    try {
        $pdo = getDatabaseConnection();

        $query = "CREATE TABLE IF NOT EXISTS Medicament (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $pdo->exec($query);
        echo "Medicament table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating Medicament table: " . $e->getMessage());
    }
}

function createPrescribedMedicamentsTable() {
    try {
        $pdo = getDatabaseConnection();

        $query = "CREATE TABLE IF NOT EXISTS PrescribedMedicaments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            medicament_id INTEGER NOT NULL,
            type TEXT,
            frequency TEXT,
            dosage TEXT,
            time TEXT,
            photo TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES User(id),
            FOREIGN KEY (medicament_id) REFERENCES Medicament(id)
        )";

        $pdo->exec($query);
        echo "PrescribedMedicaments table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating PrescribedMedicaments table: " . $e->getMessage());
    }
}

function createLoginActivityTable() {
    try {
        $pdo = getDatabaseConnection();

        $query = "CREATE TABLE IF NOT EXISTS Login_activity (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address TEXT NOT NULL,
            user_agent TEXT,
            FOREIGN KEY (user_id) REFERENCES User(id)
        )";

        $pdo->exec($query);
        echo "Login_activity table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating Login_activity table: " . $e->getMessage());
    }
}

function createAppointmentTable() {
    try {
        $pdo = getDatabaseConnection();

        $query = "CREATE TABLE IF NOT EXISTS Appointment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            note TEXT,
            date TIMESTAMP NOT NULL,
            category TEXT,
            Healthcare_Provider TEXT,
            location TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES User(id)
        )";

        $pdo->exec($query);
        echo "Appointment table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating Appointment table: " . $e->getMessage());
    }
}

function createPrescriptionTable() {
    try {
        $pdo = getDatabaseConnection();

        $query = "CREATE TABLE IF NOT EXISTS Prescription (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            photo TEXT,
            prescriber TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES User(id)
        )";

        $pdo->exec($query);
        echo "Prescription table created successfully\n";
    } catch (PDOException $e) {
        die("Error creating Prescription table: " . $e->getMessage());
    }
}

// Call all the create table functions
createUserTable();
createMedicamentTable();
createPrescribedMedicamentsTable();
createLoginActivityTable();
createAppointmentTable();
createPrescriptionTable();

?>