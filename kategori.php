<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
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
                $nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $tipe = sanitizeInput($_POST['tipe']);
                $deskripsi = sanitizeInput($_POST['deskripsi']);
                
                if(empty($nama_kategori)) {
                    $error = 'Nama kategori harus diisi!';
                } else {
                    try {
                        $query = "INSERT INTO kategori_transaksi (nama_kategori, tipe, deskripsi) VALUES (:nama_kategori, :tipe, :deskripsi)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":nama_kategori", $nama_kategori);
                        $stmt->bindParam(":tipe", $tipe);
                        $stmt->bindParam(":deskripsi", $deskripsi);
                        
                        if($stmt->execute()) {
                            $message = 'Kategori berhasil ditambahkan!';
                        } else {
                            $error = 'Gagal menambahkan kategori!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $tipe = sanitizeInput($_POST['tipe']);
                $deskripsi = sanitizeInput($_POST['deskripsi']);
                
                if(empty($nama_kategori)) {
                    $error = 'Nama kategori harus diisi!';
                } else {
                    try {
                        $query = "UPDATE kategori_transaksi SET nama_kategori = :nama_kategori, tipe = :tipe, deskripsi = :deskripsi WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":nama_kategori", $nama_kategori);
                        $stmt->bindParam(":tipe", $tipe);
                        $stmt->bindParam(":deskripsi", $deskripsi);
                        $stmt->bindParam(":id", $id);
                        
                        if($stmt->execute()) {
                            $message = 'Kategori berhasil diupdate!';
                        } else {
                            $error = 'Gagal mengupdate kategori!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if category is used in transactions
                $query_check = "SELECT COUNT(*) as count FROM transaksi_kas WHERE kategori_id = :id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->bindParam(":id", $id);
                $stmt_check->execute();
                $count = $stmt_check->fetch(PDO::FETCH_ASSOC)['count'];
                
                if($count > 0) {
                    $error = 'Kategori tidak dapat dihapus karena masih digunakan dalam transaksi!';
                } else {
                    try {
                        $query = "DELETE FROM kategori_transaksi WHERE id = :id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":id", $id);
                        
                        if($stmt->execute()) {
                            $message = 'Kategori berhasil dihapus!';
                        } else {
                            $error = 'Gagal menghapus kategori!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                $status = (int)$_POST['status'];
                
                try {
                    $query = "UPDATE kategori_transaksi SET is_active = :status WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":status", $status);
                    $stmt->bindParam(":id", $id);
                    
                    if($stmt->execute()) {
                        $message = 'Status kategori berhasil diubah!';
                    } else {
                        $error = 'Gagal mengubah status kategori!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get categories
$query_categories = "SELECT * FROM kategori_transaksi ORDER BY nama_kategori";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Sistem Kas Rumah Sakit</title>
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
                    <a href="kategori.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-tags mr-3"></i>
                        Kategori
                    </a>
                    <a href="pegawai.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-user-tie mr-3"></i>
                        Pegawai
                    </a>
                    <?php if($user['role'] === 'admin'): ?>
                    <a href="users.php" class="flex items-center px-4 py-2 rounded-lg">
                        <i class="fas fa-users mr-3"></i>
                        Pengguna
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Kategori Transaksi</h2>
                <p class="text-gray-600">Kelola kategori untuk transaksi pemasukan dan pengeluaran</p>
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

            <!-- Add Category Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-plus mr-2"></i>Tambah Kategori Baru
                </h3>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                        <input type="text" name="nama_kategori" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama kategori">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                        <select name="tipe" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Tipe</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <input type="text" name="deskripsi"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Deskripsi kategori">
                    </div>
                    
                    <div class="md:col-span-3">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Simpan Kategori
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>Daftar Kategori
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $category['tipe'] === 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($category['tipe']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($category['deskripsi'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $category['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $category['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                    <i class="fas fa-<?php echo $category['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
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

    <!-- Edit Category Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Edit Kategori</h3>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                        <input type="text" name="nama_kategori" id="edit_nama_kategori" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                        <select name="tipe" id="edit_tipe" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <input type="text" name="deskripsi" id="edit_deskripsi"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

    <script>
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_nama_kategori').value = category.nama_kategori;
            document.getElementById('edit_tipe').value = category.tipe;
            document.getElementById('edit_deskripsi').value = category.deskripsi || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
