<?php
/*
 * api/admin/upload_students.php
 * Handles bulk creation of student accounts from an Excel file, including specializations.
 */
session_start();
require_once '../../config/database.php';
require_once '../../lib/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Authorization & File Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?error=true&message=Unauthorized+access.');
    exit();
}
if (!isset($_FILES['student_file']) || $_FILES['student_file']['error'] !== UPLOAD_ERR_OK) {
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?error=true&message=File+upload+failed.');
    exit();
}

$file = $_FILES['student_file']['tmp_name'];
$student_role_id = 4; // Assuming 4 is the role ID for students

try {
    $pdo->beginTransaction();
    
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    // --- Prepare reusable statements for performance ---
    // FIXED: Changed 'password' to 'password_hash' to match your database schema.
    $stmt_user = $pdo->prepare("INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)");
    
    $stmt_student = $pdo->prepare("INSERT INTO students (user_id, name, sap_id, roll_no, course_id, graduation_year, batch) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_course = $pdo->prepare("SELECT id FROM courses WHERE name = ?");
    $stmt_spec = $pdo->prepare("SELECT id FROM specializations WHERE name = ?");
    $stmt_assign_spec = $pdo->prepare("INSERT INTO student_specializations (student_id, specialization_id) VALUES (?, ?)");

    $successCount = 0;
    $errorCount = 0;
    $errorMessages = [];

    // --- Loop from row 2 to skip the header ---
    for ($row = 2; $row <= $highestRow; $row++) {
        $full_name = trim($sheet->getCell('A' . $row)->getValue());
        $sap_id = trim($sheet->getCell('B' . $row)->getValue());
        $roll_no = trim($sheet->getCell('C' . $row)->getValue());
        $school_name = trim($sheet->getCell('D' . $row)->getValue());
        $course_name = trim($sheet->getCell('E' . $row)->getValue());
        $graduation_year = trim($sheet->getCell('F' . $row)->getValue());
        $batch = trim($sheet->getCell('G' . $row)->getValue());
        $username = trim($sheet->getCell('H' . $row)->getValue());
        $password = trim($sheet->getCell('I' . $row)->getValue());
        $specializations_str = trim($sheet->getCell('J' . $row)->getValue());

        if (empty($username) || empty($full_name)) {
            $errorCount++;
            $errorMessages[] = "Skipping row $row: Missing username or full name.";
            continue;
        }
        
        // Find Course ID
        $stmt_course->execute([$course_name]);
        $course_id = $stmt_course->fetchColumn();
        if (!$course_id) {
            $errorCount++;
            $errorMessages[] = "Skipping row $row for user '$full_name': Course '{$course_name}' not found.";
            continue;
        }
        
        $password_to_hash = !empty($password) ? $password : 'Welcome123';
        $password_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
        
        // Create user
        $stmt_user->execute([$username, $password_hash, $student_role_id]);
        $new_user_id = $pdo->lastInsertId();

        // Create student
        $stmt_student->execute([$new_user_id, $full_name, $sap_id, $roll_no, $course_id, $graduation_year, $batch]);

        // Assign specializations if provided
        if (!empty($specializations_str)) {
            $specialization_names = array_map('trim', explode(',', $specializations_str));
            foreach ($specialization_names as $spec_name) {
                if(empty($spec_name)) continue;

                $stmt_spec->execute([$spec_name]);
                $spec_id = $stmt_spec->fetchColumn();
                if ($spec_id) {
                    $stmt_assign_spec->execute([$new_user_id, $spec_id]);
                } else {
                    $errorMessages[] = "Warning on row $row for user '$full_name': Specialization '$spec_name' not found and was not assigned.";
                }
            }
        }
        $successCount++;
    }

    $pdo->commit();

    // Prepare final feedback message
    $final_message = "$successCount students uploaded successfully.";
    if ($errorCount > 0) {
        $final_message .= " $errorCount rows were skipped due to errors.";
    }
    $_SESSION['upload_errors'] = $errorMessages;

    header('Location: /nmims_quiz_app/views/admin/upload_students.php?success=true&message=' . urlencode($final_message));

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Student upload failed: " . $e->getMessage());
    header('Location: /nmims_quiz_app/views/admin/upload_students.php?error=true&message=' . urlencode('A critical error occurred: ' . $e->getMessage()));
}
exit();
?>