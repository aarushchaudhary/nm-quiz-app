<?php
/*
 * api/faculty/update_quiz.php
 * Handles the server-side logic for updating an existing quiz.
 */
session_start();
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// --- Retrieve and Sanitize Form Data ---
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title']);
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$graduation_year = filter_input(INPUT_POST, 'graduation_year', FILTER_VALIDATE_INT); // NEW
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
$config_easy_count = filter_input(INPUT_POST, 'config_easy_count', FILTER_VALIDATE_INT);
$config_medium_count = filter_input(INPUT_POST, 'config_medium_count', FILTER_VALIDATE_INT);
$config_hard_count = filter_input(INPUT_POST, 'config_hard_count', FILTER_VALIDATE_INT);
$faculty_id = $_SESSION['user_id'];

// --- Basic Validation ---
if (!$quiz_id || empty($title) || !$course_id || !$graduation_year || !$duration_minutes) {
    header('Location: /nmims_quiz_app/views/faculty/edit_quiz.php?id=' . $quiz_id . '&error=invalid_input');
    exit();
}

// --- Prepare and Execute SQL UPDATE Statement ---
$sql = "UPDATE quizzes SET
            title = :title,
            course_id = :course_id,
            graduation_year = :graduation_year,
            start_time = :start_time,
            end_time = :end_time,
            duration_minutes = :duration_minutes,
            config_easy_count = :config_easy_count,
            config_medium_count = :config_medium_count,
            config_hard_count = :config_hard_count
        WHERE id = :quiz_id AND faculty_id = :faculty_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':course_id' => $course_id,
        ':graduation_year' => $graduation_year,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':duration_minutes' => $duration_minutes,
        ':config_easy_count' => $config_easy_count,
        ':config_medium_count' => $config_medium_count,
        ':config_hard_count' => $config_hard_count,
        ':quiz_id' => $quiz_id,
        ':faculty_id' => $faculty_id
    ]);

    header('Location: /nmims_quiz_app/views/faculty/manage_quizzes.php?success=quiz_updated');

} catch (PDOException $e) {
    error_log("Quiz update failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/edit_quiz.php?id=' . $quiz_id . '&error=db_error');
}
exit();
