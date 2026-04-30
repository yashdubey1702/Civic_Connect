<?php

// Provides database connection access for the application.
class Database
{
    public $conn;

    // Opens and returns the mysqli database connection.
    public function getConnection()
    {
        $this->conn = new mysqli("localhost", "root", "", "town_issues");

        if ($this->conn->connect_error) {
            die("DB Error: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
}
