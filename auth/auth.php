<?php
session_start();
require_once '../config/database.php';

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT id, username, password, nama_lengkap, email, role FROM users WHERE username = :username AND is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['user_role'] = $row['role'];
                    $_SESSION['login_time'] = time();
                    
                    return true;
                }
            }
            return false;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nama_lengkap' => $_SESSION['nama_lengkap'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
    
    public function requireLogin() {
        if(!$this->isLoggedIn()) {
            redirect('../login.php');
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
            redirect('../dashboard.php?error=unauthorized');
        }
    }
}

// Initialize Auth
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
?>
