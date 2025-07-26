<?php
/*
 * api/shared/get_event_logs.php
 * Fetches all event logs for a specific quiz.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
if (!isset($_GET['quiz_id']) || !filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid or missing quiz ID.']));
}

$quiz_id = $_GET['quiz_id'];

try {
    // This query joins event logs with student attempts and student details
    $sql = "SELECT 
                el.timestamp,
                el.event_type,
                el.description,
                el.ip_address,
                s.name as student_name
            FROM event_logs el
            JOIN student_attempts sa ON el.attempt_id = sa.id
            JOIN students s ON el.user_id = s.user_id
            WHERE sa.quiz_id = ?
            ORDER BY el.timestamp DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$quiz_id]);
    $logs = $stmt->fetchAll();

    echo json_encode($logs);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get event logs failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
