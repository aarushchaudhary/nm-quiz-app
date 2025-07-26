<?php
/*
 * api/faculty/export_results.php
 * Generates and downloads an Excel file with quiz results.
 */
session_start();
require_once '../../config/database.php';
// Make sure you have installed PhpSpreadsheet in the /lib/ folder
require_once '../../lib/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// --- Authorization & Input Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    exit('Access Denied.');
}
if (!isset($_GET['quiz_id']) || !filter_var($_GET['quiz_id'], FILTER_VALIDATE_INT)) {
    exit('Invalid Quiz ID.');
}

$quiz_id = $_GET['quiz_id'];
$faculty_id = $_SESSION['user_id'];

try {
    // --- Fetch Data ---
    $sql = "SELECT 
                st.name as student_name,
                st.sap_id,
                sa.total_score,
                sa.started_at,
                sa.submitted_at,
                sa.is_disqualified,
                q.title as quiz_title
            FROM student_attempts sa
            JOIN students st ON sa.student_id = st.user_id
            JOIN quizzes q ON sa.quiz_id = q.id
            WHERE sa.quiz_id = :quiz_id AND q.faculty_id = :faculty_id
            ORDER BY sa.total_score DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':quiz_id' => $quiz_id, ':faculty_id' => $faculty_id]);
    $results = $stmt->fetchAll();

    if (empty($results)) {
        exit('No results found for this quiz.');
    }

    // --- Create Spreadsheet ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $quiz_title = $results[0]['quiz_title'];
    $sheet->setTitle(substr($quiz_title, 0, 30)); // Sheet title has a 31-char limit

    // Set Header Row
    $sheet->setCellValue('A1', 'Student Name');
    $sheet->setCellValue('B1', 'SAP ID');
    $sheet->setCellValue('C1', 'Score');
    $sheet->setCellValue('D1', 'Time Taken (seconds)');
    $sheet->setCellValue('E1', 'Status');
    $sheet->setCellValue('F1', 'Submitted At');

    // Style Header
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
    ];
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // Populate Data Rows
    $rowNum = 2;
    foreach ($results as $row) {
        $timeTakenSeconds = 'N/A';
        if ($row['started_at'] && $row['submitted_at']) {
            $timeTakenSeconds = strtotime($row['submitted_at']) - strtotime($row['started_at']);
        }

        $sheet->setCellValue('A' . $rowNum, $row['student_name']);
        $sheet->setCellValue('B' . $rowNum, $row['sap_id']);
        $sheet->setCellValue('C' . $rowNum, $row['total_score']);
        $sheet->setCellValue('D' . $rowNum, $timeTakenSeconds);
        $sheet->setCellValue('E' . $rowNum, $row['is_disqualified'] ? 'Disqualified' : 'Completed');
        $sheet->setCellValue('F' . $rowNum, $row['submitted_at']);
        $rowNum++;
    }

    // Auto-size columns
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // --- Output to Browser ---
    $filename = 'quiz_results_' . str_replace(' ', '_', $quiz_title) . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    error_log("Excel export failed: " . $e->getMessage());
    exit('An error occurred while generating the Excel file.');
}
