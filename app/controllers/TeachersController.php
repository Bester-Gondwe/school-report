<?php


namespace App\Controllers; // ✅ Correct namespace

use App\Config\Database;
use App\Controllers\MarksController;
use App\Controllers\RankingsController; // ✅ Ensure this is correct


class TeachersController {
    private $db;
    private $marksController;
    private $rankingsController;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->marksController = new MarksController();
        $this->rankingsController = new RankingsController();
    }

    // Show teacher's dashboard
    public function dashboard() {
        // Load the subjects
        $subjects = $this->marksController->listSubjects();
        include 'app/views/teacher_dashboard.php';
    }

    // Handle the form for inputting marks
    public function inputMarks() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->marksController->inputMarks();
        }
    }

    // Update rankings
    public function updateRankings() {
        $this->rankingsController->calculateRankings();
        echo "Rankings have been updated!";
    }
}
?>
