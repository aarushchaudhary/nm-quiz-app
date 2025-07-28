<?php
/*
 * api/shared/get_batches_by_course.php
 * Fetches the distinct batches of students enrolled in a specific course.
 */
header('Content-Type: application/json');
require_once '../../config/database.php';

$course_id = isset($_GET['course_id']) ? filter_var($_GET['course_id'], FILTER_VALIDATE_INT) : 0;

if (!$course_id) {
    echo json_encode([]);
    exit();
}

try {
    // This query finds all unique batches for students in the selected course
    $stmt = $pdo->prepare("SELECT DISTINCT batch FROM students WHERE course_id = ? ORDER BY batch DESC");
    $stmt->execute([$course_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo json_encode($batches);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
