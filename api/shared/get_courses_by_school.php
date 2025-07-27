<?php
/*
 * api/shared/get_courses_by_school.php
 * Fetches all courses associated with a specific school ID.
 */
header('Content-Type: application/json');
require_once '../../config/database.php';

$school_id = isset($_GET['school_id']) ? filter_var($_GET['school_id'], FILTER_VALIDATE_INT) : 0;

if (!$school_id) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name FROM courses WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$school_id]);
    $courses = $stmt->fetchAll();
    echo json_encode($courses);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
