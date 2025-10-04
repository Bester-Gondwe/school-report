<?php
namespace App\Controllers;

use App\config\Database; // Ensure this matches the actual Database class location
use PDO;

class MarksController {
    private $db;
    private $table = 'marks'; // Marks table in the database

    public function __construct() {
        $this->db = (new Database())->getConnection(); // Database connection
    }

    // Method to add marks for a student
    public function addMark($student_id, $subject_id, $marks) {
        $query = "INSERT INTO {$this->table} (student_id, subject_id, marks) VALUES (:student_id, :subject_id, :marks)";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':marks', $marks);

        return $stmt->execute() ? "Marks added successfully!" : "Failed to add marks.";
    }

    // Method to update marks for a student
    public function updateMark($student_id, $subject_id, $marks) {
        $query = "UPDATE {$this->table} SET marks = :marks WHERE student_id = :student_id AND subject_id = :subject_id";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);
        $stmt->bindParam(':marks', $marks);

        return $stmt->execute() ? "Marks updated successfully!" : "Failed to update marks.";
    }

    // Method to retrieve marks for a particular student
    public function getMarks($student_id) {
        $query = "SELECT * FROM {$this->table} WHERE student_id = :student_id";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all marks for the student
    }

    // Method to delete marks for a student
    public function deleteMark($student_id, $subject_id) {
        $query = "DELETE FROM {$this->table} WHERE student_id = :student_id AND subject_id = :subject_id";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':subject_id', $subject_id);

        return $stmt->execute() ? "Marks deleted successfully!" : "Failed to delete marks.";
    }

    // Method to list all marks for all students
    public function listMarks() {
        $query = "SELECT * FROM {$this->table}";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all marks for all students
    }

    // ✅ Added: List all subjects
    public function listSubjects() {
        $query = "SELECT * FROM subjects";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all subjects
    }

    // ✅ Added: Process input marks from a form submission
    public function inputMarks() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $student_id = $_POST['student_id'] ?? null;
            $subject_id = $_POST['subject_id'] ?? null;
            $marks = $_POST['marks'] ?? null;

            if ($student_id && $subject_id && $marks !== null) {
                return $this->addMark($student_id, $subject_id, $marks);
            }
            return "Invalid input data.";
        }
    }
}
?>
