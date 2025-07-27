<?php
/*
 * api/faculty/delete_quiz.php
 * Handles the server-side logic for deleting a quiz and all its related data.
 * -- MODIFIED for manual cascading delete to ensure it works on all systems --
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
$quiz_id = $data['quiz_id'] ?? null;

if (!$quiz_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid quiz ID.']));
}

try {
    // --- Security Check: Ensure the faculty owns the quiz ---
    $stmt_check = $pdo->prepare("SELECT id FROM quizzes WHERE id = ? AND faculty_id = ?");
    $stmt_check->execute([$quiz_id, $_SESSION['user_id']]);
    if ($stmt_check->fetch() === false) {
        http_response_code(403);
        exit(json_encode(['error' => 'You are not authorized to delete this quiz.']));
    }

    $pdo->beginTransaction();

    // 1. Get all related attempt IDs and question IDs first
    $stmt_attempts = $pdo->prepare("SELECT id FROM student_attempts WHERE quiz_id = ?");
    $stmt_attempts->execute([$quiz_id]);
    $attempt_ids = $stmt_attempts->fetchAll(PDO::FETCH_COLUMN, 0);

    $stmt_questions = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = ?");
    $stmt_questions->execute([$quiz_id]);
    $question_ids = $stmt_questions->fetchAll(PDO::FETCH_COLUMN, 0);

    // 2. Delete from the deepest child tables first
    if (!empty($attempt_ids)) {
        $in_clause_attempts = implode(',', array_fill(0, count($attempt_ids), '?'));
        $pdo->prepare("DELETE FROM event_logs WHERE attempt_id IN ($in_clause_attempts)")->execute($attempt_ids);
        $pdo->prepare("DELETE FROM student_answers WHERE attempt_id IN ($in_clause_attempts)")->execute($attempt_ids);
    }
    if (!empty($question_ids)) {
        $in_clause_questions = implode(',', array_fill(0, count($question_ids), '?'));
        $pdo->prepare("DELETE FROM options WHERE question_id IN ($in_clause_questions)")->execute($question_ids);
    }

    // 3. Now delete from the direct child tables
    $pdo->prepare("DELETE FROM quiz_lobby WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM student_attempts WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);

    // 4. Finally, delete the quiz itself
    $stmt_delete_quiz = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt_delete_quiz->execute([$quiz_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Manual delete quiz failed for quiz_id {$quiz_id}: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred. The quiz could not be deleted.']);
}
