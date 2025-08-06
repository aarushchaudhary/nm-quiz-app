<?php
/*
 * api/shared/get_event_logs.php
 * Fetches all event logs for a specific quiz.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization Check ---
// Allows any user who is NOT a student (role_id 4) to access this data.
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] == 4) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
if (!isset($_GET['quiz_id']) || !filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid or missing quiz ID.']));
}

$quiz_id = $_GET['quiz_id'];

try {
    // The query is updated to include the student's SAP ID.
    $sql = "SELECT 
                el.timestamp,
                el.event_type,
                el.description,
                el.ip_address,
                s.name as student_name,
                s.sap_id -- <<< FIX: Added s.sap_id to the SELECT statement
            FROM event_logs el
            LEFT JOIN student_attempts sa ON el.attempt_id = sa.id
            LEFT JOIN students s ON el.user_id = s.user_id
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