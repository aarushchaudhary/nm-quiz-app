<?php
/*
 * api/admin/delete_user.php
 * Handles the server-side logic for deleting a user account.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid user ID.']));
}

try {
    // Get the role_id of the user to be deleted
    $stmt_role = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt_role->execute([$user_id]);
    $role_id = $stmt_role->fetchColumn();

    if (!$role_id) {
        throw new Exception("User not found.");
    }

    $pdo->beginTransaction();

    // 1. Delete from the role-specific table first due to foreign key constraints
    $role_table_map = [
        2 => 'faculties',
        3 => 'placement_officers',
        4 => 'students'
        // We don't allow deleting admins for this example
    ];

    if (isset($role_table_map[$role_id])) {
        $table = $role_table_map[$role_id];
        $stmt_delete_role = $pdo->prepare("DELETE FROM {$table} WHERE user_id = ?");
        $stmt_delete_role->execute([$user_id]);
    }

    // 2. Delete from the main 'users' table
    // The database should cascade deletes to related tables like 'student_attempts', etc.
    // but it's safer to handle them explicitly if needed.
    $stmt_delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->execute([$user_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Delete user failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred. The user might be linked to other records.']);
}
