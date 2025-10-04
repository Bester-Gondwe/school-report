<?php
namespace App\Models;

use PDO;

class Ranking {
    private $db;
    private $table = 'rankings'; // Ensure this matches your actual table name

    public $student_id;
    public $total_marks;
    public $rank;

    public function __construct($db) {
        $this->db = $db;
    }

    // Update ranking method
    public function updateRanking() {
        $query = "UPDATE " . $this->table . " SET rank = :rank WHERE student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':rank', $this->rank);
        $stmt->bindParam(':student_id', $this->student_id);
        return $stmt->execute();
    }
}
?>
