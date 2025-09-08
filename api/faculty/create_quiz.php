<?php
// api/faculty/create_quiz.php

header('Content-Type: application/json');
require_once '../../config/database.php';

// --- Authorization Check ---
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// --- Input Validation ---
$required_fields = [
    'title', 'school_id', 'course_id', 'graduation_year', 
    'start_time', 'end_time', 'duration_minutes',
    'config_easy_count', 'config_medium_count', 'config_hard_count'
];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// --- Data Preparation ---
$title = $_POST['title'];
$school_id = filter_var($_POST['school_id'], FILTER_VALIDATE_INT);
$course_id = filter_var($_POST['course_id'], FILTER_VALIDATE_INT);
$graduation_year = filter_var($_POST['graduation_year'], FILTER_VALIDATE_INT);
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$duration_minutes = filter_var($_POST['duration_minutes'], FILTER_VALIDATE_INT);

// Optional Fields
$sap_id_start = !empty($_POST['sap_id_range_start']) ? filter_var($_POST['sap_id_range_start'], FILTER_SANITIZE_NUMBER_INT) : null;
$sap_id_end = !empty($_POST['sap_id_range_end']) ? filter_var($_POST['sap_id_range_end'], FILTER_SANITIZE_NUMBER_INT) : null;
$specialization_id = !empty($_POST['specialization_id']) ? filter_var($_POST['specialization_id'], FILTER_VALIDATE_INT) : null; // NEW

// Configuration
$config_easy_count = filter_var($_POST['config_easy_count'], FILTER_VALIDATE_INT);
$config_medium_count = filter_var($_POST['config_medium_count'], FILTER_VALIDATE_INT);
$config_hard_count = filter_var($_POST['config_hard_count'], FILTER_VALIDATE_INT);
$show_results_immediately = isset($_POST['show_results_immediately']) ? 1 : 0;

$faculty_id = $_SESSION['user_id'];
$status_id = 1; // Default to 'Not Started'

// --- Database Insertion ---
$sql = "INSERT INTO quizzes (
            title, course_id, graduation_year, start_time, end_time, 
            duration_minutes, faculty_id, status_id, sap_id_range_start, sap_id_range_end,
            config_easy_count, config_medium_count, config_hard_count, 
            show_results_immediately, specialization_id
        ) VALUES (
            :title, :course_id, :graduation_year, :start_time, :end_time, 
            :duration_minutes, :faculty_id, :status_id, :sap_id_start, :sap_id_end,
            :easy_count, :medium_count, :hard_count, 
            :show_results, :specialization_id
        )";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':course_id' => $course_id,
        ':graduation_year' => $graduation_year,
        ':start_time' => $start_time,
        ':end_time' => $end_time,
        ':duration_minutes' => $duration_minutes,
        ':faculty_id' => $faculty_id,
        ':status_id' => $status_id,
        ':sap_id_start' => $sap_id_start,
        ':sap_id_end' => $sap_id_end,
        ':easy_count' => $config_easy_count,
        ':medium_count' => $config_medium_count,
        ':hard_count' => $config_hard_count,
        ':show_results' => $show_results_immediately,
        ':specialization_id' => $specialization_id // NEW
    ]);

    $quiz_id = $pdo->lastInsertId();

    // Redirect to the view/edit page for the new quiz
    header('Location: /nmims_quiz_app/views/faculty/view_quiz.php?id=' . $quiz_id . '&created=true');
    exit();

} catch (PDOException $e) {
    // In a real app, you would log this error.
    // For now, redirect with a generic error message.
    error_log('Quiz creation failed: ' . $e->getMessage());
    header('Location: /nmims_quiz_app/views/faculty/create_quiz.php?error=db_error');
    exit();
}
?>