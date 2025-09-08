<?php
// api/admin/get_user_specializations.php
header('Content-Type: application/json');
require_once '../../config/database.php';

// --- Authorization Check (ensure user is an admin) ---
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// --- Input Check ---
if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit();
}

$user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);

// --- Fetch Data ---
try {
    $stmt = $pdo->prepare("SELECT specialization_id FROM student_specializations WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'specializations' => $specializations]);

} catch (PDOException $e) {
    // Log error properly in a real application
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>