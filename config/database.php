<?php
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = new mysqli("localhost", "root", "", "town_issues");

        if ($this->conn->connect_error) {
            die("DB Error: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
}
?>
