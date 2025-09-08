<?php
// api/admin/bulk_assign_specialization.php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$specialization_id = $data['specialization_id'] ?? null;
$sap_ids = $data['sap_ids'] ?? [];

if (empty($specialization_id) || empty($sap_ids) || !is_array($sap_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit();
}

$successCount = 0;
$notFoundCount = 0;
$notFoundSaps = [];

try {
    $pdo->beginTransaction();

    // Prepare statements
    $stmt_find_student = $pdo->prepare("SELECT user_id FROM students WHERE sap_id = ?");
    $stmt_assign = $pdo->prepare("INSERT IGNORE INTO student_specializations (student_id, specialization_id) VALUES (?, ?)");

    foreach ($sap_ids as $sap_id) {
        // Find the student's user_id from their sap_id
        $stmt_find_student->execute([$sap_id]);
        $student_id = $stmt_find_student->fetchColumn();

        if ($student_id) {
            // Assign the specialization (INSERT IGNORE prevents errors if already assigned)
            $stmt_assign->execute([$student_id, $specialization_id]);
            if ($stmt_assign->rowCount() > 0) {
                $successCount++;
            }
        } else {
            $notFoundCount++;
            $notFoundSaps[] = $sap_id;
        }
    }

    $pdo->commit();

    $message = "Assignment complete. {$successCount} new assignments were made.";
    if ($notFoundCount > 0) {
        $message .= "\nCould not find {$notFoundCount} students with SAP IDs: " . implode(', ', $notFoundSaps);
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Bulk assign error: " . $e->getMessage());
    // FIXED: Return the specific database error message for debugging
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>