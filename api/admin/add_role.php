<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) { exit('Access Denied'); }

$role_name = strtolower(trim($_POST['role_name'] ?? ''));

if (empty($role_name)) {
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?error=Role+name+cannot+be+empty.');
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO roles (name) VALUES (?)");
    $stmt->execute([$role_name]);
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?success=Role+added+successfully.');
} catch (PDOException $e) {
    header('Location: /nmims_quiz_app/views/admin/manage_roles.php?error=Database+error+or+role+already+exists.');
}
