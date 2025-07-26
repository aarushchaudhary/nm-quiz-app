<?php
/*
 * api/student/finish_exam.php
 * Finalizes an exam, calculates score, and handles disqualification correctly.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$attempt_id = $data['attempt_id'] ?? null;
$is_disqualified = $data['is_disqualified'] ?? false;

if (!$attempt_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing attempt ID.']));
}

try {
    $pdo->beginTransaction();

    // 1. Fetch all of the student's answers for this attempt
    $stmt_answers = $pdo->prepare("SELECT id, question_id, selected_option_ids FROM student_answers WHERE attempt_id = ?");
    $stmt_answers->execute([$attempt_id]);
    $student_answers = $stmt_answers->fetchAll();

    $total_score = 0;

    // Prepare statement for updating the correctness of each answer
    $stmt_update_answer = $pdo->prepare("UPDATE student_answers SET is_correct = ? WHERE id = ?");

    // 2. Loop through each answer to grade it
    foreach ($student_answers as $answer) {
        $question_id = $answer['question_id'];
        $selected_ids = json_decode($answer['selected_option_ids'], true) ?? [];

        $stmt_question = $pdo->prepare("SELECT question_type_id, points FROM questions WHERE id = ?");
        $stmt_question->execute([$question_id]);
        $question_info = $stmt_question->fetch();
        
        $is_correct_value = null; // Default to NULL for descriptive questions

        if ($question_info && ($question_info['question_type_id'] == 1 || $question_info['question_type_id'] == 2)) {
            $stmt_correct = $pdo->prepare("SELECT id FROM options WHERE question_id = ? AND is_correct = 1");
            $stmt_correct->execute([$question_id]);
            $correct_ids = $stmt_correct->fetchAll(PDO::FETCH_COLUMN, 0);

            sort($selected_ids);
            sort($correct_ids);

            if ($selected_ids == $correct_ids) {
                $is_correct_value = 1; // Correct
                $total_score += $question_info['points'];
            } else {
                $is_correct_value = 0; // Incorrect
            }
        }
        
        // Update the student_answers table with the result
        $stmt_update_answer->execute([$is_correct_value, $answer['id']]);
    }

    // 3. Update the student_attempts table with corrected logic
    $sql_update_attempt = "UPDATE student_attempts SET 
                                total_score = ?, 
                                submitted_at = ?, -- Use a variable for the timestamp
                                is_disqualified = ?,
                                can_resume = ? -- Lock the attempt
                           WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update_attempt);

    // If disqualified, submitted_at is NULL. If finished normally, submitted_at is the current time.
    $submitted_at_value = $is_disqualified ? null : date("Y-m-d H:i:s");
    $can_resume_value = 0; // Lock the attempt in both cases so it can't be re-entered without faculty action.

    $stmt_update->execute([$total_score, $submitted_at_value, $is_disqualified ? 1 : 0, $can_resume_value, $attempt_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Finish exam failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while finalizing exam.']);
}
