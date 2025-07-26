<?php
/*
 * api/student/save_answer.php
 * Saves a student's answer for a single question to the database.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$data = json_decode(file_get_contents('php://input'), true);

$attempt_id = $data['attempt_id'] ?? null;
$question_id = $data['question_id'] ?? null;
$selected_option_ids = $data['selected_option_ids'] ?? [];
$time_spent = $data['time_spent'] ?? 0;
$answer_text = $data['answer_text'] ?? null; // Get descriptive answer text

if (!$attempt_id || !$question_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required data.']));
}

$selected_option_ids_json = json_encode($selected_option_ids);

try {
    $stmt_check = $pdo->prepare("SELECT id FROM student_answers WHERE attempt_id = ? AND question_id = ?");
    $stmt_check->execute([$attempt_id, $question_id]);
    $existing_answer = $stmt_check->fetch();

    if ($existing_answer) {
        $sql = "UPDATE student_answers SET selected_option_ids = ?, answer_text = ?, time_spent_seconds = time_spent_seconds + ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$selected_option_ids_json, $answer_text, $time_spent, $existing_answer['id']]);
    } else {
        $sql = "INSERT INTO student_answers (attempt_id, question_id, selected_option_ids, answer_text, time_spent_seconds) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$attempt_id, $question_id, $selected_option_ids_json, $answer_text, $time_spent]);
    }

    echo json_encode(['success' => true, 'message' => 'Answer saved.']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Save answer failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while saving answer.']);
}
