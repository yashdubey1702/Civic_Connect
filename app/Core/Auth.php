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

    // ================= LOGIN =================
    public function login($email, $password) {
        $query = "SELECT id, email, password_hash, full_name, user_type, ward, is_active
                  FROM {$this->table_name}
                  WHERE LOWER(email) = LOWER(?) AND is_active = 1
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
                $_SESSION['user_type'] = strtolower(trim($row['user_type']));
                $_SESSION['ward']      = strtolower($row['ward']);

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

    // ================= SESSION =================
    public function isLoggedIn() {
        return !empty($_SESSION['logged_in']);
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();
    }

    // ================= ROLE HELPERS =================
    public function getRole() {
        return $_SESSION['user_type'] ?? '';
    }

    public function isCitizen() {
        return $this->getRole() === 'citizen';
    }

    public function isWardAdmin() {
        return $this->getRole() === 'ward_admin';
    }

    public function isMunicipalAdmin() {
        return $this->getRole() === 'municipal_admin';
    }

    public function isSuperAdmin() {
        return $this->getRole() === 'super_admin';
    }

    public function isAdmin() {
        return in_array($_SESSION['user_type'] ?? '', [
            'ward_admin',
            'municipal_admin',
            'super_admin'
        ]);
    }

    public function getWard() {
        return $this->isWardAdmin() ? ($_SESSION['ward'] ?? null) : null;
    }

    // ================= ACCESS GUARD =================
    public function requireAuth($required_type = null) {

        if (!$this->isLoggedIn()) {
            header("Location: /town_issues/login.php");
            exit;
        }

        if ($required_type === 'citizen' && !$this->isCitizen()) {
            header("Location: /town_issues/unauthorized.php");
            exit;
        }

        if ($required_type === 'ward_admin' && !$this->isWardAdmin()) {
            header("Location: /town_issues/unauthorized.php");
            exit;
        }

        if ($required_type === 'municipal_admin' && !$this->isMunicipalAdmin()) {
            header("Location: /town_issues/unauthorized.php");
            exit;
        }

        if ($required_type === 'super_admin' && !$this->isSuperAdmin()) {
            header("Location: /town_issues/unauthorized.php");
            exit;
        }

        if ($required_type === 'any_admin' && !$this->isAdmin()) {
            header("Location: /town_issues/unauthorized.php");
            exit;
        }
    }
}