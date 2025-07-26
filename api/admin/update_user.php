<?php
/*
 * api/admin/update_user.php
 * Handles the server-side logic for updating a user's account details.
 */
session_start();
require_once '../../config/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    exit('Access denied.');
}

// --- Retrieve User Data ---
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username']);
$full_name = trim($_POST['full_name']);
$password = $_POST['password'];

// --- Validation ---
if (!$user_id || !$role_id || empty($username) || empty($full_name)) {
    header('Location: /nmims_quiz_app/views/admin/user_management.php?error=missing_fields');
    exit();
}

try {
    // --- NEW: Check for duplicate username before updating ---
    // Check if another user (not the current one) already has this username.
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt_check->execute([$username, $user_id]);
    if ($stmt_check->fetch()) {
        // If a user is found, the username is already taken. Redirect with an error.
        header('Location: /nmims_quiz_app/views/admin/edit_user.php?id=' . $user_id . '&error=username_exists');
        exit();
    }

    $pdo->beginTransaction();

    // 1. Update the main 'users' table
    $sql_user = "UPDATE users SET username = ? ";
    $params_user = [$username];
    // Only update the password if a new one was provided
    if (!empty($password)) {
        $sql_user .= ", password_hash = ? ";
        $params_user[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $sql_user .= "WHERE id = ?";
    $params_user[] = $user_id;
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute($params_user);

    // 2. Update the role-specific table
    if ($role_id == 4) { // Student
        $sql_role = "UPDATE students SET name = ?, sap_id = ?, roll_no = ?, course_id = ?, batch = ?, graduation_year = ? WHERE user_id = ?";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$full_name, $_POST['sap_id'], $_POST['roll_no'], $_POST['course_id'], $_POST['batch'], $_POST['graduation_year'], $user_id]);
    } elseif ($role_id == 2) { // Faculty
        $sql_role = "UPDATE faculties SET name = ?, sap_id = ?, department = ? WHERE user_id = ?";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$full_name, $_POST['faculty_sap_id'], $_POST['department'], $user_id]);
    } elseif ($role_id == 3) { // Placement Officer
        $sql_role = "UPDATE placement_officers SET name = ?, sap_id = ?, department = ? WHERE user_id = ?";
        $stmt_role = $pdo->prepare($sql_role);
        $stmt_role->execute([$full_name, $_POST['faculty_sap_id'], $_POST['department'], $user_id]);
    }

    $pdo->commit();
    header('Location: /nmims_quiz_app/views/admin/user_management.php?success=User+updated+successfully.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Update user failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/admin/edit_user.php?id=' . $user_id . '&error=db_error');
}
exit();
