<?php
/*
 * api/faculty/delete_question.php
 * Handles the server-side logic for deleting a question and its options.
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
$question_id = $data['question_id'] ?? null;

if (!$question_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid question ID.']));
}

try {
    // --- Security Check: Ensure the faculty owns the quiz this question belongs to ---
    $stmt_check = $pdo->prepare("SELECT q.id FROM questions q JOIN quizzes quiz ON q.quiz_id = quiz.id WHERE q.id = ? AND quiz.faculty_id = ?");
    $stmt_check->execute([$question_id, $_SESSION['user_id']]);
    if (!$stmt_check->fetch()) {
        http_response_code(403);
        exit(json_encode(['error' => 'You are not authorized to delete this question.']));
    }

    $pdo->beginTransaction();

    // 1. Delete all associated options from the 'options' table.
    // The ON DELETE CASCADE constraint in your database also handles this, but it's safer to be explicit.
    $stmt_delete_options = $pdo->prepare("DELETE FROM options WHERE question_id = ?");
    $stmt_delete_options->execute([$question_id]);

    // 2. Delete the question from the 'questions' table.
    $stmt_delete_question = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt_delete_question->execute([$question_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Delete question failed: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
