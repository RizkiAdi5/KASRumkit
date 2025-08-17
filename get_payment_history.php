<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo '<div class="text-center py-8 text-red-600"><i class="fas fa-lock text-2xl"></i><p class="mt-2">Akses ditolak</p></div>';
    exit();
}

// Check if employee_id is provided
if(!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    echo '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl"></i><p class="mt-2">ID Pegawai tidak valid</p></div>';
    exit();
}

$employee_id = (int)$_GET['employee_id'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

try {
    // Get employee info
    $query_employee = "SELECT nama_lengkap, departemen FROM pegawai WHERE id = :id AND is_active = 1";
    $stmt_employee = $db->prepare($query_employee);
    $stmt_employee->bindParam(":id", $employee_id);
    $stmt_employee->execute();
    $employee = $stmt_employee->fetch(PDO::FETCH_ASSOC);
    
    if(!$employee) {
        echo '<div class="text-center py-8 text-red-600"><i class="fas fa-user-slash text-2xl"></i><p class="mt-2">Pegawai tidak ditemukan</p></div>';
        exit();
    }
    
    // Get payment history
    $query_history = "SELECT 
        t.id,
        t.nomor_transaksi,
        t.tanggal_transaksi,
        t.jumlah,
        t.keterangan,
        k.nama_kategori,
        u.nama_lengkap as user_name,
        t.created_at
    FROM transaksi_kas t
    JOIN kategori_transaksi k ON t.kategori_id = k.id
    JOIN users u ON t.user_id = u.id
    WHERE t.pegawai_id = :employee_id 
    AND k.nama_kategori = 'Kas Bulanan Pegawai'
    ORDER BY t.tanggal_transaksi DESC, t.created_at DESC";
    
    $stmt_history = $db->prepare($query_history);
    $stmt_history->bindParam(":employee_id", $employee_id);
    $stmt_history->execute();
    $payments = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total payments
    $total_payments = array_sum(array_column($payments, 'jumlah'));
    $total_count = count($payments);
    
    // Get payment statistics by year/month
    $query_stats = "SELECT 
        YEAR(t.tanggal_transaksi) as tahun,
        MONTH(t.tanggal_transaksi) as bulan,
        SUM(t.jumlah) as total_bulanan,
        COUNT(*) as jumlah_transaksi
    FROM transaksi_kas t
    JOIN kategori_transaksi k ON t.kategori_id = k.id
    WHERE t.pegawai_id = :employee_id 
    AND k.nama_kategori = 'Kas Bulanan Pegawai'
    GROUP BY YEAR(t.tanggal_transaksi), MONTH(t.tanggal_transaksi)
    ORDER BY tahun DESC, bulan DESC";
    
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->bindParam(":employee_id", $employee_id);
    $stmt_stats->execute();
    $monthly_stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo '<div class="text-center py-8 text-red-600"><i class="fas fa-database text-2xl"></i><p class="mt-2">Error database: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    exit();
}

// Helper function
function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? 'Unknown';
}
?>

<!-- Payment Summary -->
<div class="mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600">Total Pembayaran</p>
                    <p class="text-lg font-bold text-blue-800"><?php echo formatRupiah($total_payments); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-calendar-check text-green-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600">Jumlah Transaksi</p>
                    <p class="text-lg font-bold text-green-800"><?php echo $total_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-chart-line text-purple-600"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600">Rata-rata per Transaksi</p>
                    <p class="text-lg font-bold text-purple-800">
                        <?php echo $total_count > 0 ? formatRupiah($total_payments / $total_count) : 'Rp 0'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Statistics -->
<?php if(!empty($monthly_stats)): ?>
<div class="mb-6">
    <h4 class="text-lg font-medium text-gray-800 mb-4">
        <i class="fas fa-chart-bar mr-2"></i>Statistik Bulanan
    </h4>
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($monthly_stats as $stat): ?>
            <div class="bg-white rounded-lg p-3 border border-gray-200">
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-600">
                        <?php echo getMonthName($stat['bulan']); ?> <?php echo $stat['tahun']; ?>
                    </p>
                    <p class="text-lg font-bold text-gray-800">
                        <?php echo formatRupiah($stat['total_bulanan']); ?>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?php echo $stat['jumlah_transaksi']; ?> transaksi
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Payment History Table -->
<div class="mb-6">
    <h4 class="text-lg font-medium text-gray-800 mb-4">
        <i class="fas fa-list mr-2"></i>Riwayat Pembayaran
    </h4>
    
    <?php if(empty($payments)): ?>
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-inbox text-4xl mb-4"></i>
            <p class="text-lg">Belum ada riwayat pembayaran</p>
            <p class="text-sm">Pegawai ini belum pernah menerima kas bulanan</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($payments as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($payment['nomor_transaksi']); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($payment['tanggal_transaksi'])); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-red-600">
                                    <?php echo formatRupiah($payment['jumlah']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($payment['keterangan'] ?: '-'); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($payment['user_name']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Export Options -->
<?php if(!empty($payments)): ?>
<div class="text-center">
    <div class="inline-flex space-x-3">
        <button onclick="exportToPDF(<?php echo $employee_id; ?>)" 
                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200">
            <i class="fas fa-file-pdf mr-2"></i>Export PDF
        </button>
        <button onclick="exportToExcel(<?php echo $employee_id; ?>)" 
                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
            <i class="fas fa-file-excel mr-2"></i>Export Excel
        </button>
    </div>
</div>
<?php endif; ?>

<script>
function exportToPDF(employeeId) {
    // TODO: Implement PDF export
    alert('Fitur export PDF akan segera tersedia!');
}

function exportToExcel(employeeId) {
    // TODO: Implement Excel export
    alert('Fitur export Excel akan segera tersedia!');
}
</script> 