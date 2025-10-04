<?php
namespace App\Controllers; // ✅ Added correct namespace

use App\Config\Database;
use App\Models\Ranking;
use App\Models\Mark;
use PDO; // ✅ Added missing PDO import

class RankingsController {
    private $db;
    private $ranking;
    private $mark;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->ranking = new Ranking($this->db);
        $this->mark = new Mark($this->db);
    }

    // Calculate and update rankings
    public function calculateRankings() {
        // Get all students' total marks
        $students = $this->getStudentMarks();

        // Sort students by total marks (higher marks = better rank)
        usort($students, function($a, $b) {
            return $b['total_marks'] - $a['total_marks'];
        });

        // Update rankings for each student
        $rank = 1;
        foreach ($students as $student) {
            $this->ranking->student_id = $student['student_id'];
            $this->ranking->total_marks = $student['total_marks'];
            $this->ranking->rank = $rank;
            
            // ✅ Ensure `updateRanking()` is correctly defined in Ranking model
            if (!$this->ranking->updateRanking()) {
                echo "Failed to update ranking for student ID: " . $student['student_id'];
            }
            $rank++;
        }
    }

    // Get student marks and calculate total marks
    private function getStudentMarks() {
        $query = "SELECT student_id, SUM(marks) AS total_marks FROM marks GROUP BY student_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
