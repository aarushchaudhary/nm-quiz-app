<?php
/*
 * api/admin/upload_students.php
 * Handles bulk creation of student accounts from an Excel file.
 */
session_start();
require_once '../../config/database.php';
require_once '../../lib/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Authorization & File Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) { exit('Access Denied.'); }
if (!isset($_FILES['student_file']) || $_FILES['student_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?error=File+upload+failed.');
    exit();
}

$file = $_FILES['student_file']['tmp_name'];
$default_password = password_hash('Welcome123', PASSWORD_DEFAULT);
$student_role_id = 4; // Assuming 4 is the role ID for students

try {
    $pdo->beginTransaction();
    
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    // **FIX:** The query for the 'users' table no longer includes 'full_name'.
    $sql_user = "INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)";
    $stmt_user = $pdo->prepare($sql_user);
    
    // This query correctly includes the 'name' column for the 'students' table.
    $sql_student = "INSERT INTO students (user_id, sap_id, name, roll_no, course_id, batch, graduation_year) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_student = $pdo->prepare($sql_student);

    $stmt_course_lookup = $pdo->prepare("SELECT c.id FROM courses c JOIN schools s ON c.school_id = s.id WHERE s.name = ? AND c.name = ?");

    for ($row = 2; $row <= $highestRow; $row++) {
        $full_name = trim($sheet->getCell('A' . $row)->getValue());
        $sap_id = trim($sheet->getCell('B' . $row)->getValue());
        $roll_no = trim($sheet->getCell('C' . $row)->getValue());
        $school_name = trim($sheet->getCell('D' . $row)->getValue());
        $course_name = trim($sheet->getCell('E' . $row)->getValue());
        $graduation_year = trim($sheet->getCell('F' . $row)->getValue());
        $batch = trim($sheet->getCell('G' . $row)->getValue());
        $username = trim($sheet->getCell('H' . $row)->getValue());

        if (empty($username) || empty($full_name)) continue;
        
        $stmt_course_lookup->execute([$school_name, $course_name]);
        $course_id = $stmt_course_lookup->fetchColumn();

        if (!$course_id) {
            throw new Exception("Could not find a course matching School '{$school_name}' and Course Name '{$course_name}' on row {$row}.");
        }
        
        // **FIX:** Create user record without the name.
        $stmt_user->execute([$username, $default_password, $student_role_id]);
        $new_user_id = $pdo->lastInsertId();

        // **FIX:** Create student record, now including the name in the correct table.
        $stmt_student->execute([$new_user_id, $sap_id, $full_name, $roll_no, $course_id, $batch, $graduation_year]);
    }

    $pdo->commit();
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?success=Students+uploaded+successfully.');

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Student upload failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?error=' . urlencode($e->getMessage()));
}
exit();
