<?php
/*
 * api/faculty/update_quiz_status.php
 * Handles updating the status of a quiz (e.g., opening lobby, starting exam).
 * -- MODIFIED FOR AJAX with robust error handling --
 */
header('Content-Type: application/json'); // Set this first to guarantee JSON output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';

// --- Authorization & Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Access denied.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$quiz_id = $data['quiz_id'] ?? null;
$new_status_id = $data['new_status_id'] ?? null;
$faculty_id = $_SESSION['user_id'];

// --- Validation ---
if (!is_numeric($quiz_id) || !is_numeric($new_status_id)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid request data.']));
}

try {
    $pdo->beginTransaction();
    
    $sql = "UPDATE quizzes SET status_id = :new_status_id WHERE id = :quiz_id AND faculty_id = :faculty_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':new_status_id' => $new_status_id,
        ':quiz_id' => $quiz_id,
        ':faculty_id' => $faculty_id
    ]);

    if ($stmt->rowCount() > 0) {
        // Fetch the new status name to send back to the client
        $stmt_status = $pdo->prepare("SELECT name FROM exam_statuses WHERE id = ?");
        $stmt_status->execute([$new_status_id]);
        $new_status_name = $stmt_status->fetchColumn();

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Quiz status updated successfully.',
            'new_status_name' => $new_status_name
        ]);
    } else {
        throw new Exception('No changes made or you are not authorized to perform this action.');
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Quiz status update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
