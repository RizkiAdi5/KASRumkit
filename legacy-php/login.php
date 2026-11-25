<?php
session_start();
require_once 'config/database.php';

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $query = "SELECT id, username, password, nama_lengkap, email, role FROM users WHERE username = :username AND is_active = 1";
        $stmt = $db->prepare($query);
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
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem KAS Ruang Gelatik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-hospital text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">KAS Ruang Gelatik</h1>
            <p class="text-gray-600 mt-2">Sistem Pengaturan KAS Ruang Gelatik</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user mr-2"></i>Username
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                       placeholder="Masukkan username">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                       placeholder="Masukkan password">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200 font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Default: admin / admin123
            </p>
        </div>
    </div>

    <script>
        // Auto focus on username field
        document.getElementById('username').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
            }
        });
    </script>
</body>
</html>
