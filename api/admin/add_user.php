<?php
/*
 * api/admin/add_user.php
 * Handles creating a new user account with role-specific fields.
 */
session_start();
require_once '../../config/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// --- Retrieve Core User Data ---
$username = trim($_POST['username']);
$password = $_POST['password'];
$role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$full_name = trim($_POST['full_name']);

// --- Validation ---
if (empty($username) || empty($password) || empty($full_name) || !$role_id) {
    header('Location: /nmims_quiz_app/views/admin/add_user.php?error=missing_fields');
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // 1. Insert into the main 'users' table
    $sql_user = "INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$username, $password_hash, $role_id]);
    $new_user_id = $pdo->lastInsertId();

    // 2. Insert into the appropriate details table based on the role
    if ($role_id == 4) { // Student
        $sql = "INSERT INTO students (user_id, name, sap_id, roll_no, course_id, batch, graduation_year) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_user_id, $full_name, $_POST['sap_id'], $_POST['roll_no'], $_POST['course_id'], $_POST['batch'], $_POST['graduation_year']]);
    
    } elseif ($role_id == 2) { // Faculty
        $sql = "INSERT INTO faculties (user_id, name, sap_id, school_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_user_id, $full_name, $_POST['staff_sap_id'], $_POST['staff_school_id']]);
    
    } elseif ($role_id == 3) { // Placement Officer
        // **FIX:** Only inserts name and sap_id, no school/department.
        $sql = "INSERT INTO placement_officers (user_id, name, sap_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_user_id, $full_name, $_POST['staff_sap_id']]);
    
    } else { // Any other role (e.g., Heads)
        // **FIX:** Only inserts name. No sap_id or school/department.
        $sql = "INSERT INTO heads (user_id, name) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_user_id, $full_name]);
    }

    $pdo->commit();
    header('Location: /nmims_quiz_app/views/admin/user_management.php?success=User+created+successfully.');

} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() == 23000) {
        header('Location: /nmims_quiz_app/views/admin/add_user.php?error=username_exists');
    } else {
        error_log("Add user failed: " . $e->getMessage());
        header('Location: /nmims_quiz_app/views/admin/add_user.php?error=db_error');
    }
}
exit();
