<?php

class Auth {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

//LOGIN
     
    public function login($email, $password) {
        $query = "SELECT id, email, password_hash, full_name, user_type, ward, is_active
                  FROM {$this->table_name}
                  WHERE email = ? AND is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password_hash'])) {

                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['email']     = $row['email'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['user_type'] = $row['user_type'];
                $_SESSION['ward']      = $row['ward']; // NULL for others

                $this->updateLastLogin($row['id']);
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin($user_id) {
        $query = "UPDATE {$this->table_name}
                  SET last_login = NOW()
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }


//SESSION
   
    public function isLoggedIn() {
        return !empty($_SESSION['logged_in']);
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
    }

//ROLE CHECKS

    public function isCitizen() {
        return ($_SESSION['user_type'] ?? '') === 'citizen';
    }

    public function isWardAdmin() {
        return ($_SESSION['user_type'] ?? '') === 'ward_admin';
    }

    public function isMunicipalAdmin() {
        return ($_SESSION['user_type'] ?? '') === 'municipal_admin';
    }

    public function isAdmin() {
        return ($_SESSION['user_type'] ?? '') === 'admin';
    }

    public function getWard() {
        return $this->isWardAdmin() ? ($_SESSION['ward'] ?? null) : null;
    }

 
//      ACCESS GUARDS

    public function requireAuth($required_type = null) {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }

        if ($required_type === 'citizen' && !$this->isCitizen()) {
            header("Location: unauthorized.php"); exit;
        }

        if ($required_type === 'ward_admin' && !$this->isWardAdmin()) {
            header("Location: unauthorized.php"); exit;
        }

        if ($required_type === 'municipal_admin' && !$this->isMunicipalAdmin()) {
            header("Location: unauthorized.php"); exit;
        }

        if ($required_type === 'admin' && !$this->isAdmin()) {
            header("Location: unauthorized.php"); exit;
        }

        if ($required_type === 'any_admin' &&
            !$this->isAdmin() &&
            !$this->isMunicipalAdmin() &&
            !$this->isWardAdmin()) {
            header("Location: unauthorized.php"); exit;
        }
    }
}
