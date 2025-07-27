<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) { exit('Access Denied'); }

$course_name = trim($_POST['course_name'] ?? '');
$course_code = trim($_POST['course_code'] ?? '');
$school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);

if (empty($course_name) || empty($course_code) || !$school_id) {
    header('Location: /nmims_quiz_app/views/admin/manage_courses.php?error=All+fields+are+required.');
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO courses (name, code, school_id) VALUES (?, ?, ?)");
    $stmt->execute([$course_name, $course_code, $school_id]);
    header('Location: /nmims_quiz_app/views/admin/manage_courses.php?success=Course+added+successfully.');
} catch (PDOException $e) {
    header('Location: /nmims_quiz_app/views/admin/manage_courses.php?error=Database+error+or+course+already+exists.');
}
