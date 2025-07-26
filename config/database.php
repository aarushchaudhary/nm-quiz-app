<?php
/*
 * database.php
 * This file handles the connection to the MySQL/MariaDB database using PDO.
 * PDO (PHP Data Objects) is used as it provides a secure way to access databases
 * and prevents SQL injection attacks through prepared statements.
 */

// --- Database Credentials ---
// Replace with your actual database details.
// For a standard XAMPP installation, the user is 'root' and there is no password.
define('DB_HOST', '127.0.0.1'); // Or 'localhost'
define('DB_NAME', 'nmims_quiz_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Data Source Name (DSN) ---
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

// --- PDO Connection Options ---
$options = [
    // Throw an exception if a database error occurs
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Use the default fetch mode (associative array)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulation of prepared statements for security
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// --- Create PDO Instance ---
try {
    // Attempt to create a new PDO instance to connect to the database
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // If the connection fails, stop the script and show an error message.
    // In a production environment, you would log this error instead of showing it to the user.
    error_log("Database Connection Error: " . $e->getMessage());
    // For the user, show a generic error and redirect
    header('Location: /nmims_quiz_app/login.php?error=db_error');
    exit('Database connection failed. Please check server logs.');
}
