<?php
// api/admin/get_specializations.php
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$query = "SELECT spec.id, spec.name, spec.description, sch.name AS school_name 
          FROM specializations spec
          JOIN schools sch ON spec.school_id = sch.id
          ORDER BY sch.name, spec.name";
$stmt = $pdo->prepare($query);
$stmt->execute();

$specializations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($specializations);
?>