<?php
/*
 * api/placecom/get_all_quiz_results.php
 * Fetches all student attempt data for a specific quiz, accessible by Placecom.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization Check (Allow Placecom and Admin) ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
if (!isset($_GET['quiz_id']) || !filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid or missing quiz ID.']));
}

$quiz_id = $_GET['quiz_id'];

try {
    // This query is similar to the faculty version but does NOT check for faculty_id
    $sql = "SELECT 
                st.name as student_name,
                st.sap_id,
                sa.total_score,
                sa.started_at,
                sa.submitted_at,
                sa.is_disqualified
            FROM student_attempts sa
            JOIN students st ON sa.student_id = st.user_id
            WHERE sa.quiz_id = :quiz_id
            ORDER BY sa.total_score DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':quiz_id' => $quiz_id]);
    $results = $stmt->fetchAll();

    // Calculate summary statistics
    $total_attempts = count($results);
    $total_score_sum = 0;
    $disqualified_count = 0;

    foreach ($results as $result) {
        $total_score_sum += $result['total_score'];
        if ($result['is_disqualified']) {
            $disqualified_count++;
        }
    }
    $average_score = ($total_attempts > 0) ? $total_score_sum / $total_attempts : 0;

    echo json_encode([
        'summary' => [
            'total_attempts' => $total_attempts,
            'average_score' => number_format($average_score, 2),
            'disqualified_count' => $disqualified_count
        ],
        'details' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Placecom get results failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}
