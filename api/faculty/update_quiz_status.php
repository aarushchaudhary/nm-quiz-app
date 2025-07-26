<?php
/*
 * api/faculty/update_quiz_status.php
 * Handles updating the status of a quiz (e.g., opening lobby, starting exam).
 */

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
$new_status_id = filter_input(INPUT_POST, 'new_status_id', FILTER_VALIDATE_INT);
$faculty_id = $_SESSION['user_id'];

// --- Validation ---
if (!$quiz_id || !$new_status_id) {
    header('Location: /nmims_quiz_app/views/faculty/manage_quizzes.php?error=invalid_request');
    exit();
}

// --- Prepare and Execute SQL UPDATE Statement ---
// We include `faculty_id` in the WHERE clause as a security measure
// to ensure a faculty member can only update their own quizzes.
$sql = "UPDATE quizzes SET status_id = :new_status_id WHERE id = :quiz_id AND faculty_id = :faculty_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':new_status_id' => $new_status_id,
        ':quiz_id' => $quiz_id,
        ':faculty_id' => $faculty_id
    ]);

    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        $message = "Quiz status updated successfully.";
    } else {
        $message = "No changes made or you are not authorized.";
    }

    // Redirect back to the quiz management page
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&success=' . urlencode($message));
    exit();

} catch (PDOException $e) {
    error_log("Quiz status update failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&error=db_error');
    exit();
}
