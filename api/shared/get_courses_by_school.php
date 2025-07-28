<?php
/*
 * api/shared/get_courses_by_school.php
 * Fetches all courses associated with a specific school ID.
 */
header('Content-Type: application/json'); // Ensure the browser knows this is a JSON response
require_once '../../config/database.php';

// Sanitize the input to ensure it's an integer
$school_id = isset($_GET['school_id']) ? filter_var($_GET['school_id'], FILTER_VALIDATE_INT) : 0;

if (!$school_id) {
    // If no valid school_id is provided, return an empty array
    echo json_encode([]);
    exit();
}

try {
    // Use a prepared statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT id, name, code FROM courses WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$school_id]);
    $courses = $stmt->fetchAll();

    // Return the fetched courses as a JSON array
    echo json_encode($courses);

} catch (PDOException $e) {
    // If there is a database error, log it on the server and send a clear error response
    http_response_code(500); // Internal Server Error
    error_log("Failed to fetch courses by school: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
