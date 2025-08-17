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
                $nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
                $departemen = sanitizeInput($_POST['departemen']);
                
                if(empty($nama_lengkap)) {
                    $error = 'Nama Lengkap harus diisi!';
                } else {
                    try {
                        $query = "INSERT INTO pegawai (nama_lengkap, departemen) 
                                 VALUES (:nama_lengkap, :departemen)";
                        
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":nama_lengkap", $nama_lengkap);
                        $stmt->bindParam(":departemen", $departemen);
                        
                        if($stmt->execute()) {
                            $message = 'Pegawai berhasil ditambahkan!';
                        } else {
                            $error = 'Gagal menambahkan pegawai!';
                        }
                    } catch(PDOException $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
                $departemen = sanitizeInput($_POST['departemen']);
                
                try {
                    $query = "UPDATE pegawai SET nama_lengkap = :nama_lengkap, 
                             departemen = :departemen WHERE id = :id";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    $stmt->bindParam(":nama_lengkap", $nama_lengkap);
                    $stmt->bindParam(":departemen", $departemen);
                    
                    if($stmt->execute()) {
                        $message = 'Data pegawai berhasil diupdate!';
                    } else {
                        $error = 'Gagal mengupdate data pegawai!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $query = "DELETE FROM pegawai WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    
                    if($stmt->execute()) {
                        $message = 'Pegawai berhasil dihapus!';
                    } else {
                        $error = 'Gagal menghapus pegawai!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get employees with pagination and transaction count
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$query_employees = "SELECT 
    p.*,
    COALESCE(t.transaction_count, 0) as transaction_count,
    COALESCE(t.total_amount, 0) as total_amount
FROM pegawai p
LEFT JOIN (
    SELECT 
        pegawai_id,
        COUNT(*) as transaction_count,
        SUM(jumlah) as total_amount
    FROM transaksi_kas tk
    JOIN kategori_transaksi kt ON tk.kategori_id = kt.id
    WHERE kt.nama_kategori = 'Kas Bulanan Pegawai'
    GROUP BY pegawai_id
) t ON p.id = t.pegawai_id
WHERE p.is_active = 1 
ORDER BY p.nama_lengkap 
LIMIT :limit OFFSET :offset";
$stmt_employees = $db->prepare($query_employees);
$stmt_employees->bindParam(":limit", $limit, PDO::PARAM_INT);
$stmt_employees->bindParam(":offset", $offset, PDO::PARAM_INT);
$stmt_employees->execute();
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$query_count = "SELECT COUNT(*) as total FROM pegawai WHERE is_active = 1";
$stmt_count = $db->prepare($query_count);
$stmt_count->execute();
$total_employees = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_employees / $limit);




?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pegawai - Sistem Kas Rumah Sakit</title>
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
                    <a href="pegawai.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
                        <i class="fas fa-users mr-3"></i>
                        Pegawai
                    </a>
                    <?php if($user['role'] === 'admin'): ?>
                    <a href="users.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-user-cog mr-3"></i>
                        Pengguna
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Data Pegawai</h2>
                <p class="text-gray-600">Kelola data pegawai untuk sistem kas bulanan</p>
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

            <!-- Add Employee Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-plus mr-2"></i>Tambah Pegawai Baru
                </h3>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Nama lengkap pegawai">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Departemen</label>
                        <input type="text" name="departemen"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Departemen">
                    </div>
                    
                    <div class="md:col-span-2">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Simpan Pegawai
                        </button>
                    </div>
                </form>
            </div>

            <!-- Employees List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>Daftar Pegawai
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pembayaran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 200px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($employees)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada data pegawai</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($employees as $employee): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($employee['nama_lengkap']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($employee['departemen']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $employee['transaction_count']; ?> transaksi
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                            <?php echo formatRupiah($employee['total_amount']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="showPaymentHistory(<?php echo $employee['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pegawai ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
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
                                Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_employees); ?> 
                                dari <?php echo $total_employees; ?> pegawai
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

    <!-- Edit Employee Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">Edit Pegawai</h3>
                </div>
                
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="edit_nama_lengkap" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departemen</label>
                            <input type="text" name="departemen" id="edit_departemen"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
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

    <!-- Payment History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">Riwayat Pembayaran Kas Bulanan</h3>
                    <p class="text-sm text-gray-600" id="history_employee_name"></p>
                </div>
                
                <div class="px-6 py-4">
                    <div id="payment_history_content">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button onclick="closeHistoryModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store employees data for JavaScript
        const employees = <?php echo json_encode($employees); ?>;
        
        function editEmployee(employee) {
            document.getElementById('edit_id').value = employee.id;
            document.getElementById('edit_nama_lengkap').value = employee.nama_lengkap;
            document.getElementById('edit_departemen').value = employee.departemen;
            
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function showPaymentHistory(employeeId) {
            // Get employee name
            const employee = employees.find(emp => emp.id == employeeId);
            if (employee) {
                document.getElementById('history_employee_name').textContent = `Pegawai: ${employee.nama_lengkap} - ${employee.departemen}`;
            }
            
            // Show loading
            document.getElementById('payment_history_content').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i><p class="mt-2 text-gray-600">Memuat riwayat pembayaran...</p></div>';
            
            // Show modal
            document.getElementById('historyModal').classList.remove('hidden');
            
            // Load payment history via AJAX
            fetch(`get_payment_history.php?employee_id=${employeeId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('payment_history_content').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('payment_history_content').innerHTML = '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl"></i><p class="mt-2">Error memuat data: ' + error.message + '</p></div>';
                });
        }
        
        function closeHistoryModal() {
            document.getElementById('historyModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        document.getElementById('historyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeHistoryModal();
            }
        });
    </script>
</body>
</html> 