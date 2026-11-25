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

// Get current user info
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'nama_lengkap' => $_SESSION['nama_lengkap'],
    'email' => $_SESSION['email'],
    'role' => $_SESSION['user_role']
];

// Get dashboard statistics
$today = date('Y-m-d');
$month = date('Y-m');

// Today's transactions
$query_today = "SELECT 
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan_hari,
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran_hari
FROM transaksi_kas 
WHERE DATE(tanggal_transaksi) = :today";

$stmt_today = $db->prepare($query_today);
$stmt_today->bindParam(":today", $today);
$stmt_today->execute();
$today_stats = $stmt_today->fetch(PDO::FETCH_ASSOC);

// Month's transactions
$query_month = "SELECT 
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan_bulan,
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran_bulan
FROM transaksi_kas 
WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = :month";

$stmt_month = $db->prepare($query_month);
$stmt_month->bindParam(":month", $month);
$stmt_month->execute();
$month_stats = $stmt_month->fetch(PDO::FETCH_ASSOC);

// Total transactions (Saldo Akhir)
$query_total = "SELECT 
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan,
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran
FROM transaksi_kas";

$stmt_total = $db->prepare($query_total);
$stmt_total->execute();
$total_stats = $stmt_total->fetch(PDO::FETCH_ASSOC);

// Recent transactions
$query_recent = "SELECT 
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
LIMIT 10";

$stmt_recent = $db->prepare($query_recent);
$stmt_recent->execute();
$recent_transactions = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

// Calculate balances
$saldo_hari_ini = $today_stats['total_pemasukan_hari'] - $today_stats['total_pengeluaran_hari'];
$saldo_bulan_ini = $month_stats['total_pemasukan_bulan'] - $month_stats['total_pengeluaran_bulan'];
$saldo_akhir = $total_stats['total_pemasukan'] - $total_stats['total_pengeluaran'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem KAS Ruang Gelatik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h1 class="text-xl font-bold text-gray-800">KAS Ruang Gelatik</h1>
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

    <!-- Sidebar and Main Content -->
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-4">
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
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
                <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
                <p class="text-gray-600">Selamat datang kembali, <?php echo htmlspecialchars($user['nama_lengkap']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Today's Income -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-arrow-up text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pemasukan Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo formatRupiah($today_stats['total_pemasukan_hari']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Today's Expense -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Pengeluaran Hari Ini</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo formatRupiah($today_stats['total_pengeluaran_hari']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Today's Balance -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Saldo Hari Ini</p>
                            <p class="text-2xl font-bold <?php echo $saldo_hari_ini >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatRupiah(abs($saldo_hari_ini)); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Month's Balance -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Saldo Bulan Ini</p>
                            <p class="text-2xl font-bold <?php echo $saldo_bulan_ini >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatRupiah(abs($saldo_bulan_ini)); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Saldo Akhir -->
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <i class="fas fa-coins text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Saldo Akhir</p>
                            <p class="text-2xl font-bold <?php echo $saldo_akhir >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatRupiah(abs($saldo_akhir)); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">Transaksi Terbaru</h3>
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
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($recent_transactions)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada transaksi</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recent_transactions as $transaction): ?>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any interactive features here
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh dashboard every 5 minutes
            setInterval(function() {
                location.reload();
            }, 300000);
        });
    </script>
</body>
</html>
