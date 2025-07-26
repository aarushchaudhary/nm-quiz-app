<?php
/*
 * api/faculty/create_quiz.php
 * Handles the server-side logic for creating a new quiz.
 * -- CORRECTED VERSION --
 */

// Start session and include required files
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    // Redirect if not a POST request or user is not an authorized faculty member
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

// --- Retrieve and Sanitize Form Data ---
$title = trim($_POST['title']);
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
$config_easy_count = filter_input(INPUT_POST, 'config_easy_count', FILTER_VALIDATE_INT);
$config_medium_count = filter_input(INPUT_POST, 'config_medium_count', FILTER_VALIDATE_INT);
$config_hard_count = filter_input(INPUT_POST, 'config_hard_count', FILTER_VALIDATE_INT);

// Get faculty ID from the session
$faculty_id = $_SESSION['user_id'];

// --- Basic Validation ---
if (empty($title) || !$course_id || !$duration_minutes || $config_easy_count === false || $config_medium_count === false || $config_hard_count === false) {
    // Handle validation failure
    header('Location: /nmims_quiz_app/views/faculty/create_quiz.php?error=invalid_input');
    exit();
}

// --- Prepare and Execute SQL INSERT Statement ---
// **FIX:** The query now correctly inserts into `status_id` instead of the non-existent `status` column.
// We are assuming '1' is the ID for 'not_started' in your `exam_statuses` table.
$sql = "INSERT INTO quizzes (
            title, 
            faculty_id, 
            course_id, 
            start_time, 
            end_time, 
            duration_minutes, 
            status_id,
            config_easy_count, 
            config_medium_count, 
            config_hard_count
        ) VALUES (
            :title, 
            :faculty_id, 
            :course_id, 
            :start_time, 
            :end_time, 
            :duration_minutes, 
            1, -- Default status_id for 'not_started'
            :config_easy_count, 
            :config_medium_count, 
            :config_hard_count
        )";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':faculty_id' => $faculty_id,
        ':course_id' => $course_id,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':duration_minutes' => $duration_minutes,
        ':config_easy_count' => $config_easy_count,
        ':config_medium_count' => $config_medium_count,
        ':config_hard_count' => $config_hard_count
    ]);

    // Redirect to a management page on success
    header('Location: /nmims_quiz_app/views/faculty/manage_quizzes.php?success=quiz_created');
    exit();

} catch (PDOException $e) {
    // Log the error and redirect with a generic error message
    error_log("Quiz creation failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/create_quiz.php?error=db_error');
    exit();
}
