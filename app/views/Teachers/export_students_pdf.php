<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../Auth/login.php');
    exit();
}
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Config\Database;

$db = (new Database())->getConnection();

// Get all subjects
$subjects = $db->query('SELECT * FROM subjects')->fetchAll(PDO::FETCH_ASSOC);

// Get all students
$students = $db->query("SELECT * FROM users WHERE role='student'")->fetchAll(PDO::FETCH_ASSOC);

$pdf = new TCPDF();
$pdf->AddPage('L', 'A4');
$pdf->SetFont('helvetica', '', 10);

// Table header
$html = '<h2>All Students Marks Report</h2><table border="1" cellpadding="4"><thead><tr>';
$html .= '<th>Name</th><th>Examination Number</th><th>Grade</th><th>Term</th>';
foreach ($subjects as $sub) {
    $html .= '<th>' . htmlspecialchars($sub['name']) . '</th>';
}
$html .= '</tr></thead><tbody>';

// Table rows
foreach ($students as $stu) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($stu['name']) . '</td>';
    $html .= '<td>' . htmlspecialchars($stu['examination_number']) . '</td>';
    $html .= '<td>' . htmlspecialchars($stu['grade']) . '</td>';
    $html .= '<td>' . htmlspecialchars($stu['term']) . '</td>';
    foreach ($subjects as $sub) {
        $stmt = $db->prepare('SELECT marks_obtained FROM marks WHERE student_id = :sid AND subject_id = :subid');
        $stmt->execute([':sid' => $stu['id'], ':subid' => $sub['id']]);
        $mark = $stmt->fetch(PDO::FETCH_ASSOC);
        $html .= '<td>' . htmlspecialchars($mark['marks_obtained'] ?? '') . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('students_marks.pdf', 'D');
exit; 