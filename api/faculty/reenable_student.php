<?php
/*
 * api/faculty/reenable_student.php
 * Allows a faculty member to override a disqualification for a student's attempt.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$attempt_id = $data['attempt_id'] ?? null;

if (!$attempt_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing attempt ID.']));
}

try {
    $pdo->beginTransaction();

    // 1. Update the student's attempt status
    $sql_update = "UPDATE student_attempts SET is_disqualified = 0, can_resume = 1 WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$attempt_id]);

    // 2. Log this action for auditing purposes
    // **FIX:** The column name is `student_id`, not `user_id`.
    $stmt_attempt_info = $pdo->prepare("SELECT student_id FROM student_attempts WHERE id = ?");
    $stmt_attempt_info->execute([$attempt_id]);
    $student_user_id = $stmt_attempt_info->fetchColumn();

    if ($student_user_id) {
        $sql_log = "INSERT INTO event_logs (attempt_id, user_id, event_type, description, ip_address) 
                    VALUES (?, ?, ?, ?, ?)";
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->execute([
            $attempt_id,
            $student_user_id,
            'Faculty Override',
            'Faculty (ID: ' . $_SESSION['user_id'] . ') re-enabled the exam for the student.',
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Re-enable failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
