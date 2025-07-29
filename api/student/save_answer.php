<?php
/*
 * api/student/save_answer.php
 * Saves a student's answer for a single question to the database.
 * Includes security checks for exam status and manual locks.
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
$answer_text = $data['answer_text'] ?? null;

if (!$attempt_id || !$question_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required data.']));
}

try {
    // --- Security Check ---
    // 1. Get the quiz_id and lock status from the student's attempt.
    $stmt_attempt_check = $pdo->prepare("SELECT quiz_id, is_manually_locked FROM student_attempts WHERE id = ?");
    $stmt_attempt_check->execute([$attempt_id]);
    $attempt_info = $stmt_attempt_check->fetch();

    if (!$attempt_info) {
        throw new Exception("Invalid attempt ID provided.");
    }
    
    // **NEW:** Check if the attempt has been manually locked by the faculty.
    if ($attempt_info['is_manually_locked'] == 1) {
        http_response_code(403); // Forbidden
        exit(json_encode(['error' => 'Your exam session has been locked by the faculty.']));
    }
    
    // 2. Check the current status of that quiz.
    $stmt_status = $pdo->prepare("SELECT status_id FROM quizzes WHERE id = ?");
    $stmt_status->execute([$attempt_info['quiz_id']]);
    $status_id = $stmt_status->fetchColumn();

    // 3. The status_id for 'In Progress' is 3. If the quiz is not in progress, block the save.
    if ($status_id != 3) {
        http_response_code(403); // Forbidden
        exit(json_encode(['error' => 'The exam has been ended by the faculty. Your answer was not saved.']));
    }
    // --- End of Security Check ---


    $selected_option_ids_json = json_encode($selected_option_ids);

    // Use "upsert" logic to insert or update the answer
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

} catch (Exception $e) {
    http_response_code(500);
    error_log("Save answer failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while saving answer.']);
}
