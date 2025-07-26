<?php
/*
 * auth.php
 * Handles user authentication.
 * Verifies username and password, and on success, creates a session.
 */

// Start the session to store user data upon successful login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
require_once '../config/database.php';

// --- Check if the form was submitted ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not a POST request, redirect to login page
    header('Location: /nmims_quiz_app/login.php');
    exit();
}

// --- Get user inputs from the form ---
$username = $_POST['username'];
$password = $_POST['password'];

// --- Prepare and execute the database query ---
// We need to fetch the user's ID, hashed password, and role.
// We also join with all possible role tables to get the user's full name.
$sql = "
    SELECT 
        u.id, 
        u.password_hash, 
        u.role_id,
        COALESCE(s.name, f.name, p.name, a.name) as name
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id AND u.role_id = 4
    LEFT JOIN faculties f ON u.id = f.user_id AND u.role_id = 2
    LEFT JOIN placement_officers p ON u.id = p.user_id AND u.role_id = 3
    LEFT JOIN admins a ON u.id = a.user_id AND u.role_id = 1
    WHERE u.username = :username
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

// --- Verify user and password ---
if ($user && password_verify($password, $user['password_hash'])) {
    // Password is correct, so we create the session for the user.
    
    // Regenerate session ID for security to prevent session fixation
    session_regenerate_id(true);

    // Store essential user data in the session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['name'] = $user['name'];
    
    // Redirect to the main index file, which will route them to their dashboard
    header('Location: /nmims_quiz_app/index.php');
    exit();

} else {
    // Invalid credentials (user not found or password incorrect)
    // Redirect back to the login page with an error message
    header('Location: /nmims_quiz_app/login.php?error=invalid_credentials');
    exit();
}
