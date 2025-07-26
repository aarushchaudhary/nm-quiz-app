<?php
/*
 * api/faculty/get_item_analysis.php
 * Fetches a detailed, question-by-question performance analysis for a quiz.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2 || !isset($_GET['quiz_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
$quiz_id = filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT);

try {
    // 1. Get all MCQ/Multiple Answer questions for the quiz
    $sql_questions = "SELECT id, question_text FROM questions WHERE quiz_id = ? AND question_type_id IN (1, 2)";
    $stmt_questions = $pdo->prepare($sql_questions);
    $stmt_questions->execute([$quiz_id]);
    $questions = $stmt_questions->fetchAll();

    $analysis_data = [];

    // 2. Get all student answers for this quiz
    $sql_answers = "SELECT question_id, selected_option_ids FROM student_answers sa JOIN student_attempts sa_t ON sa.attempt_id = sa_t.id WHERE sa_t.quiz_id = ?";
    $stmt_answers = $pdo->prepare($sql_answers);
    $stmt_answers->execute([$quiz_id]);
    $all_answers = $stmt_answers->fetchAll();

    // 3. Process each question
    foreach ($questions as $question) {
        // Get all options for this question
        $stmt_options = $pdo->prepare("SELECT id, option_text, is_correct FROM options WHERE question_id = ?");
        $stmt_options->execute([$question['id']]);
        $options = $stmt_options->fetchAll();

        $option_counts = array_fill_keys(array_column($options, 'id'), 0);
        $correct_answers_count = 0;
        $total_responses = 0;

        // Tally the responses from all students for this question
        foreach ($all_answers as $answer) {
            if ($answer['question_id'] == $question['id']) {
                $total_responses++;
                $selected_ids = json_decode($answer['selected_option_ids'], true) ?? [];
                foreach ($selected_ids as $selected_id) {
                    if (isset($option_counts[$selected_id])) {
                        $option_counts[$selected_id]++;
                    }
                }
                // Check if the answer was fully correct
                $correct_option_ids = array_column(array_filter($options, fn($opt) => $opt['is_correct']), 'id');
                sort($selected_ids);
                sort($correct_option_ids);
                if ($selected_ids == $correct_option_ids) {
                    $correct_answers_count++;
                }
            }
        }
        
        $analysis_data[] = [
            'question_text' => $question['question_text'],
            'options' => $options,
            'option_counts' => $option_counts,
            'correct_count' => $correct_answers_count,
            'total_responses' => $total_responses
        ];
    }

    echo json_encode($analysis_data);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Item analysis failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
