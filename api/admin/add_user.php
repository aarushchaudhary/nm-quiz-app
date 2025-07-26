<?php
/*
 * api/admin/add_user.php
 * Handles the server-side logic for creating a new user account.
 */
session_start();
require_once '../../config/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// --- Retrieve Common User Data ---
$username = trim($_POST['username']);
$password = $_POST['password'];
$role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$full_name = trim($_POST['full_name']);

// --- Validation ---
if (empty($username) || empty($password) || empty($full_name) || !$role_id) {
    header('Location: /nmims_quiz_app/views/admin/add_user.php?error=missing_fields');
    exit();
}

// Hash the password for secure storage
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // 1. Insert into the main 'users' table
    $sql_user = "INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$username, $password_hash, $role_id]);
    $new_user_id = $pdo->lastInsertId();

    // 2. Insert into the role-specific table
    if ($role_id == 4) { // Student
        $sql_role = "INSERT INTO students (user_id, name, sap_id, roll_no, course_id, batch, graduation_year) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$new_user_id, $full_name, $_POST['sap_id'], $_POST['roll_no'], $_POST['course_id'], $_POST['batch'], $_POST['graduation_year']]);
    } elseif ($role_id == 2) { // Faculty
        $sql_role = "INSERT INTO faculties (user_id, name, sap_id, department) VALUES (?, ?, ?, ?)";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$new_user_id, $full_name, $_POST['faculty_sap_id'], $_POST['department']]);
    } elseif ($role_id == 3) { // Placement Officer
        $sql_role = "INSERT INTO placement_officers (user_id, name, sap_id, department) VALUES (?, ?, ?, ?)";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$new_user_id, $full_name, $_POST['faculty_sap_id'], $_POST['department']]);
    }

    $pdo->commit();
    header('Location: /nmims_quiz_app/views/admin/user_management.php?success=User+created+successfully.');

} catch (PDOException $e) {
    $pdo->rollBack();
    // Check for duplicate username error
    if ($e->getCode() == 23000) {
        header('Location: /nmims_quiz_app/views/admin/add_user.php?error=username_exists');
    } else {
        error_log("Add user failed: " . $e->getMessage());
        header('Location: /nmims_quiz_app/views/admin/add_user.php?error=db_error');
    }
}
exit();
