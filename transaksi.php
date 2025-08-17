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
                $nomor_transaksi = generateNomorTransaksi($_POST['tipe_transaksi']);
                $tanggal_transaksi = sanitizeInput($_POST['tanggal_transaksi']);
                $kategori_id = (int)$_POST['kategori_id'];
                $pegawai_id = isset($_POST['pegawai_id']) && !empty($_POST['pegawai_id']) ? (int)$_POST['pegawai_id'] : null;
                $tipe_transaksi = sanitizeInput($_POST['tipe_transaksi']);
                
                // PERBAIKI INPUT JUMLAH - HAPUS FORMAT SEPARATOR
                $jumlah_input = str_replace(['.', ','], '', $_POST['jumlah']); // Hapus separator
                $jumlah = (float)$jumlah_input;
                
                $keterangan = sanitizeInput($_POST['keterangan']);
                
                if($jumlah <= 0) {
                    $error = 'Jumlah harus lebih dari 0!';
                } else {
                    try {
                        $query = "INSERT INTO transaksi_kas (nomor_transaksi, tanggal_transaksi, kategori_id, pegawai_id, tipe_transaksi, jumlah, keterangan, user_id) 
                                 VALUES (:nomor_transaksi, :tanggal_transaksi, :kategori_id, :pegawai_id, :tipe_transaksi, :jumlah, :keterangan, :user_id)";
                        
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":nomor_transaksi", $nomor_transaksi);
                        $stmt->bindParam(":tanggal_transaksi", $tanggal_transaksi);
                        $stmt->bindParam(":kategori_id", $kategori_id);
                        $stmt->bindParam(":pegawai_id", $pegawai_id);
                        $stmt->bindParam(":tipe_transaksi", $tipe_transaksi);
                        $stmt->bindParam(":jumlah", $jumlah);
                        $stmt->bindParam(":keterangan", $keterangan);
                        $stmt->bindParam(":user_id", $user['id']);
                        
                        if($stmt->execute()) {
                            $message = 'Transaksi berhasil ditambahkan!';
                        } else {
                            $error = 'Gagal menambahkan transaksi!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM transaksi_kas WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    
                    if($stmt->execute()) {
                        $message = 'Transaksi berhasil dihapus!';
                    } else {
                        $error = 'Gagal menghapus transaksi!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get categories for dropdown
$query_categories = "SELECT id, nama_kategori, tipe FROM kategori_transaksi WHERE is_active = 1 ORDER BY nama_kategori";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Get employees for dropdown
$query_employees = "SELECT id, nama_lengkap, departemen FROM pegawai WHERE is_active = 1 ORDER BY nama_lengkap";
$stmt_employees = $db->prepare($query_employees);
$stmt_employees->execute();
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

// Get transactions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query_transactions = "SELECT 
    t.id,
    t.nomor_transaksi,
    t.tanggal_transaksi,
    t.tipe_transaksi,
    t.jumlah,
    t.keterangan,
    k.nama_kategori,
    p.nama_lengkap as pegawai_nama,
    u.nama_lengkap as user_name
FROM transaksi_kas t
JOIN kategori_transaksi k ON t.kategori_id = k.id
LEFT JOIN pegawai p ON t.pegawai_id = p.id
JOIN users u ON t.user_id = u.id
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset";

$stmt_transactions = $db->prepare($query_transactions);
$stmt_transactions->bindParam(":limit", $limit, PDO::PARAM_INT);
$stmt_transactions->bindParam(":offset", $offset, PDO::PARAM_INT);
$stmt_transactions->execute();
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$query_count = "SELECT COUNT(*) as total FROM transaksi_kas";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_transactions = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_transactions / $limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Sistem Kas Rumah Sakit</title>
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
                    <a href="transaksi.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
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
                    <?php if($user['role'] === 'admin'): ?>
                    <a href="users.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
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
                <h2 class="text-3xl font-bold text-gray-800">Transaksi Kas</h2>
                <p class="text-gray-600">Kelola transaksi pemasukan dan pengeluaran kas</p>
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

            <!-- Add Transaction Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-plus mr-2"></i>Tambah Transaksi Baru
                </h3>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Transaksi</label>
                        <input type="date" name="tanggal_transaksi" required value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Transaksi</label>
                        <select name="tipe_transaksi" required onchange="filterCategories(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Tipe</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select name="kategori_id" id="kategori_select" required onchange="checkKategoriBulanan(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>
                    
                    <div id="pegawai_field" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Pegawai</label>
                        <select name="pegawai_id" id="pegawai_select"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Pilih Pegawai</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah (Rp)</label>
                        <input type="text" name="jumlah" id="jumlah_input" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="0">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                        <textarea name="keterangan" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Keterangan transaksi..."></textarea>
                    </div>
                    
                    <div class="md:col-span-3">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transactions List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>Daftar Transaksi
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pegawai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($transactions)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">Belum ada transaksi</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($transactions as $transaction): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($transaction['nomor_transaksi']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($transaction['tanggal_transaksi'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($transaction['nama_kategori']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $transaction['pegawai_nama'] ? htmlspecialchars($transaction['pegawai_nama']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $transaction['tipe_transaksi'] === 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($transaction['tipe_transaksi']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium 
                                            <?php echo $transaction['tipe_transaksi'] === 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo formatRupiah($transaction['jumlah']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($transaction['user_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_transactions); ?> 
                                dari <?php echo $total_transactions; ?> transaksi
                            </div>
                            <div class="flex space-x-2">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300'; ?> border rounded-md hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Store categories and employees data
        const categories = <?php echo json_encode($categories); ?>;
        const employees = <?php echo json_encode($employees); ?>;
        
        function filterCategories(tipe) {
            const kategoriSelect = document.getElementById('kategori_select');
            kategoriSelect.innerHTML = '<option value="">Pilih Kategori</option>';
            
            if(tipe) {
                const filteredCategories = categories.filter(cat => cat.tipe === tipe);
                filteredCategories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.nama_kategori;
                    kategoriSelect.appendChild(option);
                });
            }
            
            // Reset pegawai field when changing transaction type
            document.getElementById('pegawai_field').classList.add('hidden');
            document.getElementById('pegawai_select').value = '';
        }
        
        function checkKategoriBulanan(kategoriId) {
            const pegawaiField = document.getElementById('pegawai_field');
            const pegawaiSelect = document.getElementById('pegawai_select');
            
            // Find the selected category
            const selectedCategory = categories.find(cat => cat.id == kategoriId);
            
            if (selectedCategory && selectedCategory.nama_kategori === 'Kas Bulanan Pegawai') {
                // Show pegawai field and populate with employees
                pegawaiField.classList.remove('hidden');
                pegawaiSelect.innerHTML = '<option value="">Pilih Pegawai</option>';
                
                employees.forEach(emp => {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = `${emp.nama_lengkap} - ${emp.departemen}`;
                    pegawaiSelect.appendChild(option);
                });
                
                // Make pegawai field required
                pegawaiSelect.required = true;
            } else {
                // Hide pegawai field
                pegawaiField.classList.add('hidden');
                pegawaiSelect.value = '';
                pegawaiSelect.required = false;
            }
        }
        
        // PERBAIKAN AUTO-FORMAT NUMBER INPUT
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        function parseNumber(str) {
            return str.replace(/\./g, '');
        }
        
        // Event listener untuk format input jumlah
        document.getElementById('jumlah_input').addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Hapus semua karakter non-digit
            value = value.replace(/[^\d]/g, '');
            
            if (value) {
                // Format dengan titik sebagai thousand separator
                e.target.value = formatNumber(value);
            } else {
                e.target.value = '';
            }
        });
        
        // Pastikan form mengirim nilai yang benar (tanpa separator)
        document.querySelector('form').addEventListener('submit', function(e) {
            const jumlahInput = document.getElementById('jumlah_input');
            const rawValue = parseNumber(jumlahInput.value);
            
            if (!rawValue || rawValue === '0') {
                e.preventDefault();
                alert('Jumlah harus diisi dan lebih dari 0!');
                return false;
            }
            
            // Set nilai mentah untuk dikirim ke server
            jumlahInput.value = rawValue;
        });
    </script>
</body>
</html>