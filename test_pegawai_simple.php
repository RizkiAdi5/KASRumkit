<?php
session_start();

// Simulate login for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test';
$_SESSION['nama_lengkap'] = 'Test User';
$_SESSION['email'] = 'test@test.com';
$_SESSION['user_role'] = 'admin';

require_once 'config/database.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get employees
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
LIMIT 5";

$stmt_employees = $db->prepare($query_employees);
$stmt_employees->execute();
$employees = $stmt_employees->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$query_categories = "SELECT id, nama_kategori, tipe FROM kategori_transaksi WHERE is_active = 1 ORDER BY nama_kategori";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Pegawai - Sistem Kas Rumah Sakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Test Fitur Pegawai</h1>
        
        <!-- Test Database Connection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Status Database</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm font-medium text-green-600">Koneksi Database</p>
                    <p class="text-lg font-bold text-green-800">✅ Berhasil</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm font-medium text-blue-600">Jumlah Pegawai</p>
                    <p class="text-lg font-bold text-blue-800"><?php echo count($employees); ?> pegawai</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm font-medium text-purple-600">Jumlah Kategori</p>
                    <p class="text-lg font-bold text-purple-800"><?php echo count($categories); ?> kategori</p>
                </div>
            </div>
        </div>
        
        <!-- Test Employees Data -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Data Pegawai</h2>
            <?php if(empty($employees)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p class="text-lg">Belum ada data pegawai</p>
                    <p class="text-sm">Jalankan script SQL untuk membuat tabel pegawai</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pembayaran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
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
                                        <button onclick="showPaymentHistory(<?php echo $employee['id']; ?>)" 
                                                class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fas fa-history"></i> Riwayat
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Test Categories -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Kategori Transaksi</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach($categories as $category): ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($category['nama_kategori']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo ucfirst($category['tipe']); ?></p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo $category['tipe'] === 'pemasukan' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $category['tipe'] === 'pemasukan' ? 'IN' : 'OUT'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Test Payment History Modal -->
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
    </div>

    <script>
        // Store employees data for JavaScript
        const employees = <?php echo json_encode($employees); ?>;
        
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
        
        // Close modal when clicking outside
        document.getElementById('historyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeHistoryModal();
            }
        });
    </script>
</body>
</html> 