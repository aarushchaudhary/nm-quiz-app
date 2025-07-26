<?php
/*
 * index.php
 * This is the main router for the application.
 * It checks if a user is logged in and redirects them to the appropriate
 * dashboard based on their role. If not logged in, it sends them to the login page.
 */

// Start or resume the existing session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user ID and role ID are set in the session
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
    
    // Route the user based on their role ID
    switch ($_SESSION['role_id']) {
        case 1: // Admin
            header('Location: views/admin/dashboard.php');
            exit();
        case 2: // Faculty
            header('Location: views/faculty/dashboard.php');
            exit();
        case 3: // Placement Officer
            header('Location: views/placecom/dashboard.php'); // Assuming a placecom dashboard
            exit();
        case 4: // Student
            header('Location: views/student/dashboard.php');
            exit();
        default:
            // If role is invalid, destroy the session and redirect to login
            session_destroy();
            header('Location: login.php?error=invalid_role');
            exit();
    }

} else {
    // If no user is logged in, redirect to the login page
    header('Location: login.php');
    exit();
}
