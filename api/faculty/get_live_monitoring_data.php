<?php
/*
 * api/faculty/get_live_monitoring_data.php
 * Fetches a complete, real-time status of all students for a specific quiz.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid or missing quiz ID.']));
}

$quiz_id = $_GET['id'];

try {
    // 1. Get total number of questions for this quiz
    $stmt_quiz = $pdo->prepare("SELECT config_easy_count, config_medium_count, config_hard_count FROM quizzes WHERE id = ?");
    $stmt_quiz->execute([$quiz_id]);
    $quiz_config = $stmt_quiz->fetch();
    $total_questions = ($quiz_config['config_easy_count'] ?? 0) + ($quiz_config['config_medium_count'] ?? 0) + ($quiz_config['config_hard_count'] ?? 0);

    // 2. Fetch all students associated with the quiz's course
    $sql = "SELECT s.user_id, s.name, s.sap_id, sa.id as attempt_id, sa.submitted_at, sa.is_disqualified
            FROM students s
            JOIN quizzes q ON s.course_id = q.course_id
            LEFT JOIN student_attempts sa ON s.user_id = sa.student_id AND sa.quiz_id = q.id
            WHERE q.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quiz_id]);
    $students = $stmt->fetchAll();

    // 3. Get counts of answered questions for all attempts in this quiz
    $progress_data = [];
    if (!empty($students)) {
        $attempt_ids = array_filter(array_column($students, 'attempt_id'));
        if (!empty($attempt_ids)) {
            $placeholders = implode(',', array_fill(0, count($attempt_ids), '?'));
            $sql_progress = "SELECT attempt_id, COUNT(id) as answered_count FROM student_answers WHERE attempt_id IN ($placeholders) GROUP BY attempt_id";
            $stmt_progress = $pdo->prepare($sql_progress);
            $stmt_progress->execute($attempt_ids);
            $progress_data = $stmt_progress->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }
    
    // 4. Get students currently in the lobby
    $stmt_lobby = $pdo->prepare("SELECT student_id FROM quiz_lobby WHERE quiz_id = ?");
    $stmt_lobby->execute([$quiz_id]);
    $lobby_students = $stmt_lobby->fetchAll(PDO::FETCH_COLUMN, 0);

    // 5. Process and combine the data
    $monitoring_data = [];
    foreach ($students as $student) {
        $status = 'Not Started';
        $progress = 'N/A';

        if ($student['is_disqualified']) {
            $status = 'Disqualified';
        } elseif ($student['submitted_at']) {
            $status = 'Finished';
        } elseif ($student['attempt_id']) {
            $status = 'In Progress';
            $answered_count = $progress_data[$student['attempt_id']] ?? 0;
            $progress = "{$answered_count} / {$total_questions}";
        } elseif (in_array($student['user_id'], $lobby_students)) {
            $status = 'In Lobby';
        }

        $monitoring_data[] = [
            'name' => $student['name'],
            'sap_id' => $student['sap_id'],
            'status' => $status,
            'progress' => $progress,
            'attempt_id' => $student['attempt_id']
        ];
    }

    echo json_encode($monitoring_data);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Live monitoring failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
