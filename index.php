<?php
/*
 * index.php
 * The main router for the application.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
    switch ($_SESSION['role_id']) {
        case 1: // Admin
            header('Location: views/admin/dashboard.php');
            exit();
        case 2: // Faculty
            header('Location: views/faculty/dashboard.php');
            exit();
        case 3: // Placement Officer
            header('Location: views/placecom/dashboard.php');
            exit();
        case 4: // Student
            header('Location: views/student/dashboard.php');
            exit();
        // **NEW:** A default case to handle all other roles
        default:
            header('Location: views/shared/dashboard.php');
            exit();
    }
} else {
    header('Location: login.php');
    exit();
}
