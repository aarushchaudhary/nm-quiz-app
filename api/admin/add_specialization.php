<?php
// api/admin/add_specialization.php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['name']) || empty($data['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'School and Specialization Name are required.']);
    exit();
}

$query = "INSERT INTO specializations (name, description, school_id) VALUES (:name, :description, :school_id)";
$stmt = $pdo->prepare($query);

$stmt->execute([
    ':name' => $data['name'],
    ':description' => $data['description'] ?? null,
    ':school_id' => $data['school_id']
]);

if ($stmt->rowCount()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add specialization.']);
}
?>