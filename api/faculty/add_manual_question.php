<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// --- Retrieve and Sanitize Form Data ---
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$question_text = trim($_POST['question_text']);
$question_type_id = filter_input(INPUT_POST, 'question_type_id', FILTER_VALIDATE_INT);
$difficulty_id = filter_input(INPUT_POST, 'difficulty_id', FILTER_VALIDATE_INT);
$points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_FLOAT); // **NEW:** Get points value
$options = $_POST['options'] ?? [];

$correct_answers = [];
if (isset($_POST['correct_answer_single'])) { // For MCQ (radio button)
    $correct_answers[] = $_POST['correct_answer_single'];
} elseif (isset($_POST['correct_answers'])) { // For Multiple Answer (checkboxes)
    $correct_answers = $_POST['correct_answers'];
}

// --- Validation ---
if (!$quiz_id || empty($question_text) || !$question_type_id || !$difficulty_id || $points === false) {
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&error=Missing+required+fields.');
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. **FIX:** Insert the question with its points value
    $sql_question = "INSERT INTO questions (quiz_id, question_text, question_type_id, difficulty_id, points) VALUES (?, ?, ?, ?, ?)";
    $stmt_question = $pdo->prepare($sql_question);
    $stmt_question->execute([$quiz_id, $question_text, $question_type_id, $difficulty_id, $points]);
    $question_id = $pdo->lastInsertId();

    // 2. Insert options if it's not a descriptive question
    if ($question_type_id != 3 && !empty($options)) {
        $sql_option = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
        $stmt_option = $pdo->prepare($sql_option);

        foreach ($options as $index => $option_text) {
            if (!empty(trim($option_text))) {
                $is_correct = in_array($index, $correct_answers) ? 1 : 0;
                $stmt_option->execute([$question_id, $option_text, $is_correct]);
            }
        }
    }

    $pdo->commit();
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&success=Question+added+successfully.');

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Manual question add failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&error=' . urlencode('Database error occurred.'));
}
exit();
