<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'kas_rumkit';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                 "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";port=3307",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}

// Helper function untuk format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Helper function untuk generate nomor transaksi
function generateNomorTransaksi($tipe) {
    $prefix = ($tipe == 'pemasukan') ? 'IN' : 'OUT';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 4));
    return $prefix . $date . $random;
}

// Helper function untuk validasi input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function untuk check login status
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function untuk check user role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}
?>
