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

// Get filter parameters
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';

// Get categories for filter
$query_categories = "SELECT id, nama_kategori, tipe FROM kategori_transaksi WHERE is_active = 1 ORDER BY nama_kategori";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Build query for transactions
$where_conditions = [];
$params = [];

if($bulan) {
    $where_conditions[] = "DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = :bulan";
    $params[':bulan'] = $bulan;
}

if($kategori_id > 0) {
    $where_conditions[] = "t.kategori_id = :kategori_id";
    $params[':kategori_id'] = $kategori_id;
}

if($tipe) {
    $where_conditions[] = "t.tipe_transaksi = :tipe";
    $params[':tipe'] = $tipe;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get transactions for report
$query_transactions = "SELECT 
    t.nomor_transaksi,
    t.tanggal_transaksi,
    t.tipe_transaksi,
    t.jumlah,
    t.keterangan,
    k.nama_kategori,
    u.nama_lengkap as user_name
FROM transaksi_kas t
JOIN kategori_transaksi k ON t.kategori_id = k.id
JOIN users u ON t.user_id = u.id
$where_clause
ORDER BY t.tanggal_transaksi DESC, t.created_at DESC";

$stmt_transactions = $db->prepare($query_transactions);
foreach($params as $key => $value) {
    $stmt_transactions->bindValue($key, $value);
}
$stmt_transactions->execute();
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary
$total_pemasukan = 0;
$total_pengeluaran = 0;
$saldo = 0;

foreach($transactions as $transaction) {
    if($transaction['tipe_transaksi'] === 'pemasukan') {
        $total_pemasukan += $transaction['jumlah'];
    } else {
        $total_pengeluaran += $transaction['jumlah'];
    }
}
$saldo = $total_pemasukan - $total_pengeluaran;

// Get monthly data for chart
$query_monthly = "SELECT 
    DATE_FORMAT(tanggal_transaksi, '%Y-%m') as bulan,
    SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as pemasukan,
    SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran
FROM transaksi_kas 
WHERE DATE_FORMAT(tanggal_transaksi, '%Y') = :tahun
GROUP BY DATE_FORMAT(tanggal_transaksi, '%Y-%m')
ORDER BY bulan";

$stmt_monthly = $db->prepare($query_monthly);
$stmt_monthly->bindParam(":tahun", $tahun);
$stmt_monthly->execute();
$monthly_data = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);

// Get category summary
$query_category_summary = "SELECT 
    k.nama_kategori,
    k.tipe,
    SUM(t.jumlah) as total
FROM transaksi_kas t
JOIN kategori_transaksi k ON t.kategori_id = k.id
$where_clause
GROUP BY k.id, k.nama_kategori, k.tipe
ORDER BY total DESC";

$stmt_category_summary = $db->prepare($query_category_summary);
foreach($params as $key => $value) {
    $stmt_category_summary->bindValue($key, $value);
}
$stmt_category_summary->execute();
$category_summary = $stmt_category_summary->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Kas Rumah Sakit</title>
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
                    <a href="laporan.php" class="flex items-center px-4 py-3 text-blue-600 bg-blue-50 rounded-lg">
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
                <h2 class="text-3xl font-bold text-gray-800">Laporan Keuangan</h2>
                <p class="text-gray-600">Laporan detail transaksi dan analisis keuangan</p>
            </div>

            <!-- Filter Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-800 mb-4">
                    <i class="fas fa-filter mr-2"></i>Filter Laporan
                </h3>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <input type="month" name="bulan" value="<?php echo $bulan; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select name="tahun" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select name="kategori_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">Semua Kategori</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $kategori_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe</label>
                        <select name="tipe" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Semua Tipe</option>
                            <option value="pemasukan" <?php echo $tipe === 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="pengeluaran" <?php echo $tipe === 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-4">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition duration-200">
                            <i class="fas fa-search mr-2"></i>Filter Laporan
                        </button>
                        <a href="laporan.php" 
                           class="ml-3 px-6 py-2 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 transition duration-200">
                            <i class="fas fa-refresh mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-arrow-up text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Pemasukan</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo formatRupiah($total_pemasukan); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Pengeluaran</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo formatRupiah($total_pengeluaran); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Saldo</p>
                            <p class="text-2xl font-bold <?php echo $saldo >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo formatRupiah(abs($saldo)); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Monthly Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Grafik Bulanan <?php echo $tahun; ?></h3>
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>

                <!-- Category Chart -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Ringkasan Kategori</h3>
                    <div class="space-y-3">
                        <?php foreach($category_summary as $summary): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3 <?php echo $summary['tipe'] === 'pemasukan' ? 'bg-green-500' : 'bg-red-500'; ?>"></div>
                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($summary['nama_kategori']); ?></span>
                                </div>
                                <span class="text-sm font-medium <?php echo $summary['tipe'] === 'pemasukan' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo formatRupiah($summary['total']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-800">
                        <i class="fas fa-list mr-2"></i>Detail Transaksi
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Total: <?php echo count($transactions); ?> transaksi
                    </p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if(empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada transaksi untuk filter yang dipilih</td>
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
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        const monthlyLabels = monthlyData.map(item => {
            const date = new Date(item.bulan + '-01');
            return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        });
        
        const pemasukanData = monthlyData.map(item => parseFloat(item.pemasukan));
        const pengeluaranData = monthlyData.map(item => parseFloat(item.pengeluaran));
        
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: pemasukanData,
                        backgroundColor: 'rgba(34, 197, 94, 0.8)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pengeluaran',
                        data: pengeluaranData,
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
