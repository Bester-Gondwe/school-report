<?php
namespace App\Models;

class Subject {
    private $db;
    private $table = 'subjects';

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all subjects
    public function getAllSubjects() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
