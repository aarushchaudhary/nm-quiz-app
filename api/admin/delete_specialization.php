<?php
// api/admin/delete_specialization.php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Specialization ID is required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM specializations WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'Specialization deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Specialization not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. It might be in use by a student or quiz.']);
}
?>