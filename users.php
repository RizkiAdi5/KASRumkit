<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'nama_lengkap' => $_SESSION['nama_lengkap'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['user_role']
];

$message = '';
$error = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add':
                $username = sanitizeInput($_POST['username']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                $nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                
                if(empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
                    $error = 'Semua field harus diisi!';
                } elseif($password !== $confirm_password) {
                    $error = 'Password dan konfirmasi password tidak cocok!';
                } elseif(strlen($password) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } else {
                    // Check if username or email already exists
                    $query_check = "SELECT COUNT(*) as count FROM users WHERE username = :username OR email = :email";
                    $stmt_check = $db->prepare($query_check);
                    $stmt_check->bindParam(":username", $username);
                    $stmt_check->bindParam(":email", $email);
                    $stmt_check->execute();
                    $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if($count > 0) {
                        $error = 'Username atau email sudah digunakan!';
                    } else {
                        try {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $query = "INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (:username, :password, :nama_lengkap, :email, :role)";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":username", $username);
                            $stmt->bindParam(":password", $hashed_password);
                            $stmt->bindParam(":nama_lengkap", $nama_lengkap);
                            $stmt->bindParam(":email", $email);
                            $stmt->bindParam(":role", $role);
                            
                            if($stmt->execute()) {
                                $message = 'User berhasil ditambahkan!';
                            } else {
                                $error = 'Gagal menambahkan user!';
                            }
                        } catch(PDOException $e) {
                            $error = 'Error: ' . $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
                $email = sanitizeInput($_POST['email']);
                $role = sanitizeInput($_POST['role']);
                $is_active = (int)$_POST['is_active'];
                
                if(empty($nama_lengkap) || empty($email)) {
                    $error = 'Nama lengkap dan email harus diisi!';
                } else {
                    try {
                        $query = "UPDATE users SET nama_lengkap = :nama_lengkap, email = :email, role = :role, is_active = :is_active WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":nama_lengkap", $nama_lengkap);
                        $stmt->bindParam(":email", $email);
                        $stmt->bindParam(":role", $role);
                        $stmt->bindParam(":is_active", $is_active);
                        $stmt->bindParam(":id", $id);
                        
                        if($stmt->execute()) {
                            $message = 'User berhasil diupdate!';
                        } else {
                            $error = 'Gagal mengupdate user!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'change_password':
                $id = (int)$_POST['id'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if(empty($new_password)) {
                    $error = 'Password baru harus diisi!';
                } elseif($new_password !== $confirm_password) {
                    $error = 'Password dan konfirmasi password tidak cocok!';
                } elseif(strlen($new_password) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } else {
                    try {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET password = :password WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":password", $hashed_password);
                        $stmt->bindParam(":id", $id);
                        
                        if($stmt->execute()) {
                            $message = 'Password berhasil diubah!';
                        } else {
                            $error = 'Gagal mengubah password!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                if($id == $user['id']) {
                    $error = 'Tidak dapat menghapus akun sendiri!';
                } else {
                    try {
                        $query = "DELETE FROM users WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":id", $id);
                        
                        if($stmt->execute()) {
                            $message = 'User berhasil dihapus!';
                        } else {
                            $error = 'Gagal menghapus user!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get users
$query_users = "SELECT * FROM users ORDER BY created_at DESC";
$stmt_users = $db->prepare($query_users);
$stmt_users->execute();
$users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Sistem Kas Rumah Sakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-hospital text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-3">
                        <h1 class="text-xl font-bold text-gray-800">KAS RUMKIT</h1>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                    </span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="transaksi.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-exchange-alt mr-3"></i>
                        Transaksi
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-chart-bar mr-3"></i>
                        Laporan
                    </a>
                    <a href="kategori.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-tags mr-3"></i>
                        Kategori
                    </a>
                    <a href="pegawai.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-user-tie mr-3"></i>
                        Pegawai
                    </a>
                    <a href="users.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-users mr-3"></i>
                        Pengguna
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Manajemen Pengguna</h2>
                <p class="text-gray-600">Kelola pengguna sistem dan hak akses</p>
            </div>

            <!-- Messages -->
            <?php if($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-plus mr-2"></i>Tambah User Baru
                </h3>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Username">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Password">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Konfirmasi password">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama lengkap">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Email">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-3">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Simpan User
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>Daftar Pengguna
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada pengguna</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($users as $u): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($u['nama_lengkap']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($u['email']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php 
                                                switch($u['role']) {
                                                    case 'admin': echo 'bg-red-100 text-red-800'; break;
                                                    case 'supervisor': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-blue-100 text-blue-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $u['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button onclick="changePassword(<?php echo $u['id']; ?>)" 
                                                    class="text-yellow-600 hover:text-yellow-900">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            
                                            <?php if($u['id'] != $user['id']): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Edit User</h3>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" id="edit_role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="admin">Admin</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="is_active" id="edit_is_active" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Ubah Password</h3>
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="id" id="password_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Password baru">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Konfirmasi password">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closePasswordModal()" 
                                class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').value = user.is_active;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function changePassword(userId) {
            document.getElementById('password_id').value = userId;
            document.getElementById('passwordModal').classList.remove('hidden');
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });
    </script>
</body>
</html>
