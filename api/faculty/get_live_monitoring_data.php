<?php
/*
 * api/faculty/get_live_monitoring_data.php
 * Fetches a paginated, real-time status of all students for a specific quiz.
 */
header('Content-Type: application/json');
session_start();
require_once '../../config/database.php';

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2 || !isset($_GET['id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized access.']));
}
$quiz_id = $_GET['id'];

// --- **MODIFIED:** Pagination Parameters now include a dynamic limit ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default to 10 if not provided
$offset = ($page - 1) * $limit;

try {
    // 1. Get quiz info (unchanged)
    $stmt_quiz = $pdo->prepare("SELECT q.config_easy_count, q.config_medium_count, q.config_hard_count, es.name as status_name FROM quizzes q JOIN exam_statuses es ON q.status_id = es.id WHERE q.id = ?");
    $stmt_quiz->execute([$quiz_id]);
    $quiz_info = $stmt_quiz->fetch();
    $total_questions = ($quiz_info['config_easy_count'] ?? 0) + ($quiz_info['config_medium_count'] ?? 0) + ($quiz_info['config_hard_count'] ?? 0);
    $quiz_status = $quiz_info['status_name'] ?? 'Unknown';

    // 2. Get the TOTAL number of students for this quiz for pagination
    $sql_total = "SELECT COUNT(s.user_id) FROM students s JOIN quizzes q ON s.course_id = q.course_id AND s.graduation_year = q.graduation_year WHERE q.id = ?";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute([$quiz_id]);
    $total_students = $stmt_total->fetchColumn();
    $total_pages = $total_students > 0 ? ceil($total_students / $limit) : 0;

    // 3. **FIX:** Fetch one page of students, with disqualified students first, and using the correct name column.
    $sql_students = "SELECT 
                        u.id as user_id, 
                        s.name as name, /* CORRECTED: Changed u.full_name to s.name */
                        s.sap_id, 
                        sa.id as attempt_id, 
                        sa.submitted_at, 
                        sa.is_disqualified, 
                        sa.is_manually_locked
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    JOIN quizzes q ON s.course_id = q.course_id AND s.graduation_year = q.graduation_year
                    LEFT JOIN student_attempts sa ON s.user_id = sa.student_id AND sa.quiz_id = q.id
                    WHERE q.id = ?
                    ORDER BY sa.is_disqualified DESC, sa.is_manually_locked DESC, s.name ASC /* CORRECTED: Changed u.full_name to s.name */
                    LIMIT ? OFFSET ?";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->bindValue(1, $quiz_id, PDO::PARAM_INT);
    $stmt_students->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt_students->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll();

    // 4. Get progress data for the current page of students
    $progress_data = [];
    if (!empty($students)) {
        $attempt_ids = array_filter(array_column($students, 'attempt_id'));
        if (!empty($attempt_ids)) {
            $attempt_ids = array_values($attempt_ids);
            $placeholders = implode(',', array_fill(0, count($attempt_ids), '?'));
            $sql_progress = "SELECT attempt_id, COUNT(id) as answered_count FROM student_answers WHERE attempt_id IN ($placeholders) GROUP BY attempt_id";
            $stmt_progress = $pdo->prepare($sql_progress);
            $stmt_progress->execute($attempt_ids);
            $progress_data = $stmt_progress->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }
    
    // 5. Get lobby data (unchanged)
    $stmt_lobby = $pdo->prepare("SELECT student_id FROM quiz_lobby WHERE quiz_id = ?");
    $stmt_lobby->execute([$quiz_id]);
    $lobby_students = $stmt_lobby->fetchAll(PDO::FETCH_COLUMN, 0);

    // 6. Process and combine data (unchanged)
    $monitoring_data = [];
    foreach ($students as $student) {
        $status = 'Not Started';
        $progress = 'N/A';
        if ($student['is_manually_locked']) { $status = 'Locked'; }
        elseif ($student['is_disqualified']) { $status = 'Disqualified'; }
        elseif ($student['submitted_at']) { $status = 'Finished'; }
        elseif ($student['attempt_id']) {
            $status = 'In Progress';
            $answered_count = $progress_data[$student['attempt_id']] ?? 0;
            $progress = "{$answered_count} / {$total_questions}";
        } elseif (in_array($student['user_id'], $lobby_students)) {
            $status = 'In Lobby';
        }
        $monitoring_data[] = [
            'name' => $student['name'],
            'sap_id' => $student['sap_id'],
            'status' => $status,
            'progress' => $progress,
            'attempt_id' => $student['attempt_id'],
            'is_disqualified' => (bool)$student['is_disqualified'],
            'is_manually_locked' => (bool)$student['is_manually_locked']
        ];
    }

    // 7. **MODIFIED:** Return the combined data package with full pagination info
    echo json_encode([
        'quiz_status' => $quiz_status,
        'students' => $monitoring_data,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_students' => $total_students,
            'limit' => $limit
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Live monitoring failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database error.']);
}