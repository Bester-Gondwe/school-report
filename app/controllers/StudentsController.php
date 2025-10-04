<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Mark;
use App\Models\Ranking;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDO;

class StudentsController {
    private $db;
    private $mark;
    private $ranking;
    private $user;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->mark = new Mark($this->db);
        $this->ranking = new Ranking($this->db);
        $this->user = new User($this->db);
    }

    // View student dashboard
    public function dashboard() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
            header('Location: ../Auth/login.php');
            exit();
        }
        $student_id = $_SESSION['user_id'];
        $marks = $this->getStudentMarks($student_id);
        $ranking = $this->getStudentRanking($student_id);
        $student = $this->user->findById($student_id);

        // Calculate average marks
        $average_marks = 'N/A';
        if (!empty($marks)) {
            $total = 0;
            $count = 0;
            foreach ($marks as $mark) {
                $total += $mark['marks'];
                $count++;
            }
            if ($count > 0) {
                $average_marks = round($total / $count, 2);
            }
        }

        // Get student name
        $student_name = $student['name'] ?? 'Student';

        // Get student rank
        $student_rank = $ranking['rank'] ?? 'N/A';

        // Get total students
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
        $stmt->execute();
        $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 'N/A';

        // Prepare marks array for table (add grade and rank if needed)
        // For now, just pass $marks as is, but you may want to add grade/rank logic here

        include 'app/views/student_dashboard.php';
    }

    // Get student marks
    private function getStudentMarks($student_id) {
        $query = "SELECT subjects.name AS subject_name, marks.marks 
                  FROM marks 
                  JOIN subjects ON marks.subject_id = subjects.id 
                  WHERE marks.student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get student ranking
    private function getStudentRanking($student_id) {
        $query = "SELECT rank, total_marks FROM rankings WHERE student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generate report card (PDF)
    public function downloadReport($student_id) {
        $marks = $this->getStudentMarks($student_id);
        $ranking = $this->getStudentRanking($student_id);
        $student = $this->user->findById($student_id);  // ✅ FIXED

        ob_start();
        include 'app/views/report_card_template.php';
        $content = ob_get_clean();

        // Convert to PDF using Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("report_card_{$student['name']}.pdf");
    }
}
?>
