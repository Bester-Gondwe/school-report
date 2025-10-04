<?php
namespace App\Config;

use PDO;
use PDOException;  // Correctly import the global PDOException class

class Database {
    private $host = "localhost";  // Your database host
    private $db_name = "school_report_system";  // Your database name
    private $username = "root";  // Your database username
    private $password = "";  // Your database password
    private $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
