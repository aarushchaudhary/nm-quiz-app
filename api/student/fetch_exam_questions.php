<?php
/*
 * api/student/fetch_exam_questions.php
 * Fetches a unique, randomized set of questions for a student starting an exam.
 * -- CORRECTED to handle re-enabled students --
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4 || !isset($_GET['id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}

$quiz_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$student_user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // **FIX:** Check for an existing attempt for this student and quiz first.
    $stmt_check = $pdo->prepare("SELECT id, submitted_at FROM student_attempts WHERE quiz_id = ? AND student_id = ?");
    $stmt_check->execute([$quiz_id, $student_user_id]);
    $existing_attempt = $stmt_check->fetch();

    $attempt_id = null;

    if ($existing_attempt) {
        // An attempt already exists.
        if ($existing_attempt['submitted_at'] !== null) {
            // If the exam was already submitted, they cannot restart.
            throw new Exception("You have already completed this exam.");
        }
        // Reuse the existing attempt ID.
        $attempt_id = $existing_attempt['id'];
    } else {
        // No attempt exists, so create a new one.
        $sql_attempt = "INSERT INTO student_attempts (quiz_id, student_id) VALUES (?, ?)";
        $stmt_attempt = $pdo->prepare($sql_attempt);
        $stmt_attempt->execute([$quiz_id, $student_user_id]);
        $attempt_id = $pdo->lastInsertId();
    }

    // --- (The rest of the script remains largely the same) ---

    // 1. Get Quiz Configuration
    $stmt = $pdo->prepare("SELECT config_easy_count, config_medium_count, config_hard_count, duration_minutes FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz_config = $stmt->fetch();

    if (!$quiz_config) {
        throw new Exception("Quiz configuration not found.");
    }

    // 2. Fetch questions for each difficulty level
    $final_questions = [];
    $difficulty_map = [
        1 => $quiz_config['config_easy_count'],
        2 => $quiz_config['config_medium_count'],
        3 => $quiz_config['config_hard_count']
    ];

    foreach ($difficulty_map as $diff_id => $count_needed) {
        if ($count_needed > 0) {
            $sql = "SELECT id, question_text, question_type_id FROM questions WHERE quiz_id = ? AND difficulty_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quiz_id, $diff_id]);
            $available_questions = $stmt->fetchAll();

            if (count($available_questions) < $count_needed) {
                throw new Exception("Not enough questions in the question bank for this quiz. Please contact the faculty.");
            }
            
            shuffle($available_questions);
            $selected_questions = array_slice($available_questions, 0, $count_needed);
            $final_questions = array_merge($final_questions, $selected_questions);
        }
    }
    shuffle($final_questions);

    // 3. Fetch and shuffle options for each question
    $stmt_options = $pdo->prepare("SELECT id, option_text FROM options WHERE question_id = ? ORDER BY RAND()");
    foreach ($final_questions as $key => $question) {
        $stmt_options->execute([$question['id']]);
        $final_questions[$key]['options'] = $stmt_options->fetchAll();
    }
    
    $pdo->commit();

    // 4. Return the complete quiz data
    echo json_encode([
        'attempt_id' => $attempt_id,
        'duration_minutes' => $quiz_config['duration_minutes'],
        'questions' => $final_questions
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Fetch questions failed: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
