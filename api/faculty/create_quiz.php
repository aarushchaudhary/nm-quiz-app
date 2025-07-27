<?php
/*
 * api/faculty/create_quiz.php
 * Handles the server-side logic for creating a new quiz.
 */
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    exit('Access denied.');
}

// --- Retrieve and Sanitize Form Data ---
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
if (empty($title) || !$course_id || !$graduation_year || !$duration_minutes) {
    header('Location: /nmims_quiz_app/views/faculty/create_quiz.php?error=invalid_input');
    exit();
}

// --- Prepare and Execute SQL INSERT Statement ---
$sql = "INSERT INTO quizzes (
            title, faculty_id, course_id, graduation_year, start_time, end_time, 
            duration_minutes, status_id, config_easy_count, config_medium_count, config_hard_count
        ) VALUES (
            :title, :faculty_id, :course_id, :graduation_year, :start_time, :end_time,
            :duration_minutes, 1, :config_easy_count, :config_medium_count, :config_hard_count
        )";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':faculty_id' => $faculty_id,
        ':course_id' => $course_id,
        ':graduation_year' => $graduation_year,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':duration_minutes' => $duration_minutes,
        ':config_easy_count' => $config_easy_count,
        ':config_medium_count' => $config_medium_count,
        ':config_hard_count' => $config_hard_count
    ]);

    header('Location: /nmims_quiz_app/views/faculty/manage_quizzes.php?success=quiz_created');

} catch (PDOException $e) {
    error_log("Quiz creation failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/create_quiz.php?error=db_error');
}
exit();
