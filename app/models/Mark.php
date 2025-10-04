<?php

namespace App\Models;

class Mark {
    private $db;
    private $table = 'marks'; // Assuming the table is named 'marks'
    public $student_id;
    public $subject_id;
    public $marks;

    public function __construct($db) {
        $this->db = $db;
    }

    // Input marks for students
    public function inputMarks() {
        $query = "INSERT INTO " . $this->table . " (student_id, subject_id, marks) VALUES (:student_id, :subject_id, :marks)";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':student_id', $this->student_id);
        $stmt->bindParam(':subject_id', $this->subject_id);
        $stmt->bindParam(':marks', $this->marks);

        return $stmt->execute();
    }

    // Get marks for a specific student
    public function getMarks($student_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);  // Ensure using the fully qualified PDO class
    }
}
?>
