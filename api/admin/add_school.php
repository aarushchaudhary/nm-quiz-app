<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) { exit('Access Denied'); }

$school_name = trim($_POST['school_name'] ?? '');

if (empty($school_name)) {
    header('Location: /nmims_quiz_app/views/admin/manage_schools.php?error=Name+cannot+be+empty.');
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO schools (name) VALUES (?)");
    $stmt->execute([$school_name]);
    header('Location: /nmims_quiz_app/views/admin/manage_schools.php?success=School+added+successfully.');
} catch (PDOException $e) {
    header('Location: /nmims_quiz_app/views/admin/manage_schools.php?error=Database+error+or+school+already+exists.');
}
