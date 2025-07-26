<?php
/*
 * api/admin/get_dashboard_stats.php
 * Fetches key statistics for the admin dashboard.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

try {
    // Count total students (role_id = 4)
    $stmt_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 4");
    $student_count = $stmt_students->fetchColumn();

    // Count total faculty (role_id = 2)
    $stmt_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2");
    $faculty_count = $stmt_faculty->fetchColumn();

    // Count total quizzes
    $stmt_quizzes = $pdo->query("SELECT COUNT(*) FROM quizzes");
    $quiz_count = $stmt_quizzes->fetchColumn();

    // Count active quizzes (status_id = 3 for 'In Progress')
    $stmt_active = $pdo->query("SELECT COUNT(*) FROM quizzes WHERE status_id = 3");
    $active_count = $stmt_active->fetchColumn();

    // Return all stats as a single JSON object
    echo json_encode([
        'students' => $student_count,
        'faculty' => $faculty_count,
        'quizzes' => $quiz_count,
        'active_quizzes' => $active_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Admin stats fetch failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
