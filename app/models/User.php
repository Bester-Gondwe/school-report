<?php

namespace App\Models;

use PDO;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    // Constructor to initialize database connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Method to create a new user (register)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (name, email, password, role) 
                  VALUES(:name, :email, :password, :role)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Bind parameters to prevent SQL injection
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password); // Already hashed in AuthController
        $stmt->bindParam(':role', $this->role);

        // Execute and return success status
        return $stmt->execute();
    }

    // Method to authenticate a user (login)
    public function authenticate($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);

        // Sanitize email input
        $email = htmlspecialchars(strip_tags($email));

        // Bind email parameter to find the user
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: false; // Return user if found, otherwise false
    }

    // Method to authenticate a student (login)
    public function studentAuthenticate($examination_number, $name, $grade, $term) {
        $query = "SELECT * FROM users WHERE examination_number = :examination_number AND name = :name AND grade = :grade AND term = :term AND role = 'student' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':examination_number', $examination_number);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':grade', $grade);
        $stmt->bindParam(':term', $term);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: false;
    }

    // Method to find a user by ID
    public function findById($id) {
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query); 

        // Bind user ID and sanitize
        $id = htmlspecialchars(strip_tags($id));
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC); // Return user details as an associative array
    }
}
?>
