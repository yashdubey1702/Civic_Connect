<?php

require_once __DIR__ . '/../Support/env.php';

// Provides database connection access for the application.
class Database
{
    public $conn;

    // Opens and returns the mysqli database connection.
    public function getConnection()
    {
        $host = civicconnect_env('DB_HOST', 'localhost');
        $username = civicconnect_env('DB_USERNAME', civicconnect_env('DB_USER', 'root'));
        $password = civicconnect_env('DB_PASSWORD', '');
        $database = civicconnect_env('DB_DATABASE', civicconnect_env('DB_NAME', 'town_issues'));
        $port = (int) civicconnect_env('DB_PORT', '3306');
        $socket = civicconnect_env('DB_SOCKET', '');

        mysqli_report(MYSQLI_REPORT_OFF);

        $this->conn = new mysqli(
            $host,
            $username,
            $password,
            $database,
            $port,
            $socket === '' ? null : $socket
        );

        if ($this->conn->connect_error) {
            die("DB Error: " . $this->conn->connect_error);
        }

        $this->conn->set_charset(civicconnect_env('DB_CHARSET', 'utf8mb4'));

        return $this->conn;
    }
}
