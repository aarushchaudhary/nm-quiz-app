<?php
/*
 * api/shared/get_years_by_course.php
 * Fetches the distinct graduation years of students enrolled in a specific course.
 */
header('Content-Type: application/json');
require_once '../../config/database.php';

$course_id = isset($_GET['course_id']) ? filter_var($_GET['course_id'], FILTER_VALIDATE_INT) : 0;

if (!$course_id) {
    echo json_encode([]);
    exit();
}

try {
    // This query finds all unique graduation years for students in the selected course
    $stmt = $pdo->prepare("SELECT DISTINCT graduation_year FROM students WHERE course_id = ? ORDER BY graduation_year DESC");
    $stmt->execute([$course_id]);
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo json_encode($years);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
