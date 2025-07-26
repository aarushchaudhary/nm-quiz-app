<?php
/*
 * api/shared/get_quiz_status.php
 * A simple API endpoint to fetch the current status of a quiz.
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

// --- Input Check ---
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid or missing quiz ID.']);
    exit();
}

$quiz_id = $_GET['id'];

try {
    // Fetch the status name by joining with the exam_statuses table
    $sql = "SELECT es.name as status_name 
            FROM quizzes q
            JOIN exam_statuses es ON q.status_id = es.id
            WHERE q.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quiz_id]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode(['status' => $result['status_name']]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Quiz not found.']);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Get status failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
