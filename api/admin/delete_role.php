<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) { exit('Access Denied'); }

$role_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$role_id) {
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?error=Invalid+ID.');
    exit();
}

try {
    // First, check if any users are assigned this role
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $stmt_check->execute([$role_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception('Cannot delete role as it is currently assigned to one or more users.');
    }

    // If no users have this role, proceed with deletion
    $stmt_delete = $pdo->prepare("DELETE FROM roles WHERE id = ?");
    $stmt_delete->execute([$role_id]);
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?success=Role+deleted+successfully.');

} catch (Exception $e) {
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?error=' . urlencode($e->getMessage()));
}
