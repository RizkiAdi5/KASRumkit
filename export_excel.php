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

// Get filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';

// Build query for transactions
$where_conditions = [];
$params = [];

if($from_date) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) >= :from_date";
    $params[':from_date'] = $from_date;
}

if($to_date) {
    $where_conditions[] = "DATE(t.tanggal_transaksi) <= :to_date";
    $params[':to_date'] = $to_date;
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

// Get transactions for export
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

// Get categories for display
$query_categories = "SELECT id, nama_kategori FROM kategori_transaksi WHERE is_active = 1";
$stmt_categories = $db->prepare($query_categories);
$stmt_categories->execute();
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array for easy lookup
$categories_lookup = [];
foreach($categories as $cat) {
    $categories_lookup[$cat['id']] = $cat['nama_kategori'];
}

// Calculate summary (based on all filtered transactions)
$query_summary = "SELECT 
    COALESCE(SUM(CASE WHEN t.tipe_transaksi = 'pemasukan' THEN t.jumlah ELSE 0 END), 0) as total_pemasukan,
    COALESCE(SUM(CASE WHEN t.tipe_transaksi = 'pengeluaran' THEN t.jumlah ELSE 0 END), 0) as total_pengeluaran
FROM transaksi_kas t
JOIN kategori_transaksi k ON t.kategori_id = k.id
JOIN users u ON t.user_id = u.id
$where_clause";

$stmt_summary = $db->prepare($query_summary);
foreach($params as $key => $value) {
    $stmt_summary->bindValue($key, $value);
}
$stmt_summary->execute();
$summary_stats = $stmt_summary->fetch(PDO::FETCH_ASSOC);

$total_pemasukan = $summary_stats['total_pemasukan'];
$total_pengeluaran = $summary_stats['total_pengeluaran'];
$saldo = $total_pemasukan - $total_pengeluaran;

// Calculate total balance (Saldo Akhir) from all transactions
$query_total = "SELECT 
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan,
    COALESCE(SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran
FROM transaksi_kas";

$stmt_total = $db->prepare($query_total);
$stmt_total->execute();
$total_stats = $stmt_total->fetch(PDO::FETCH_ASSOC);
$saldo_akhir = $total_stats['total_pemasukan'] - $total_stats['total_pengeluaran'];

// Set headers for Excel download
$filename = "Laporan_Keuangan_" . date('Y-m-d_H-i-s') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');


// Generate Excel content
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .summary { background-color: #e6f3ff; font-weight: bold; }
        .pemasukan { color: #008000; }
        .pengeluaran { color: #ff0000; }
    </style>
</head>
<body>
    <h1>LAPORAN KEUANGAN</h1>
    <h2>Periode: <?php echo date('d/m/Y', strtotime($from_date)); ?> - <?php echo date('d/m/Y', strtotime($to_date)); ?></h2>
    
    <!-- Filter Info -->
    <table style="margin-bottom: 20px; border: none;">
        <tr>
            <td style="border: none;"><strong>Dari Tanggal:</strong></td>
            <td style="border: none;"><?php echo date('d/m/Y', strtotime($from_date)); ?></td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Sampai Tanggal:</strong></td>
            <td style="border: none;"><?php echo date('d/m/Y', strtotime($to_date)); ?></td>
        </tr>
        <?php if($kategori_id > 0): ?>
        <tr>
            <td style="border: none;"><strong>Kategori:</strong></td>
            <td style="border: none;"><?php echo htmlspecialchars($categories_lookup[$kategori_id] ?? 'N/A'); ?></td>
        </tr>
        <?php endif; ?>
        <?php if($tipe): ?>
        <tr>
            <td style="border: none;"><strong>Tipe:</strong></td>
            <td style="border: none;"><?php echo ucfirst($tipe); ?></td>
        </tr>
        <?php endif; ?>
    </table>
    
    <!-- Summary -->
    <h3>Ringkasan Keuangan</h3>
    <table style="margin-bottom: 20px;">
        <tr class="summary">
            <th>Total Pemasukan</th>
            <th>Total Pengeluaran</th>
            <th>Saldo (Filter)</th>
            <th>Saldo Akhir</th>
        </tr>
        <tr>
            <td class="pemasukan"><?php echo formatRupiah($total_pemasukan); ?></td>
            <td class="pengeluaran"><?php echo formatRupiah($total_pengeluaran); ?></td>
            <td class="<?php echo $saldo >= 0 ? 'pemasukan' : 'pengeluaran'; ?>">
                <?php echo formatRupiah(abs($saldo)); ?>
            </td>
            <td class="<?php echo $saldo_akhir >= 0 ? 'pemasukan' : 'pengeluaran'; ?>">
                <?php echo formatRupiah(abs($saldo_akhir)); ?>
            </td>
        </tr>
    </table>
    
    <!-- Category Summary -->
    <h3>Ringkasan per Kategori</h3>
    <table style="margin-bottom: 20px;">
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Total Pemasukan</th>
                <th>Total Pengeluaran</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get category summary for export
            $query_category_summary = "SELECT 
                k.nama_kategori,
                k.tipe,
                SUM(CASE WHEN t.tipe_transaksi = 'pemasukan' THEN t.jumlah ELSE 0 END) as total_pemasukan,
                SUM(CASE WHEN t.tipe_transaksi = 'pengeluaran' THEN t.jumlah ELSE 0 END) as total_pengeluaran
            FROM transaksi_kas t
            JOIN kategori_transaksi k ON t.kategori_id = k.id
            $where_clause
            GROUP BY k.id, k.nama_kategori, k.tipe
            HAVING (SUM(CASE WHEN t.tipe_transaksi = 'pemasukan' THEN t.jumlah ELSE 0 END) > 0 
                    OR SUM(CASE WHEN t.tipe_transaksi = 'pengeluaran' THEN t.jumlah ELSE 0 END) > 0)
            ORDER BY (SUM(CASE WHEN t.tipe_transaksi = 'pemasukan' THEN t.jumlah ELSE 0 END) + 
                      SUM(CASE WHEN t.tipe_transaksi = 'pengeluaran' THEN t.jumlah ELSE 0 END)) DESC";
            
            $stmt_category_summary = $db->prepare($query_category_summary);
            foreach($params as $key => $value) {
                $stmt_category_summary->bindValue($key, $value);
            }
            $stmt_category_summary->execute();
            $category_summary = $stmt_category_summary->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($category_summary as $summary):
                $net = $summary['total_pemasukan'] - $summary['total_pengeluaran'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($summary['nama_kategori']); ?></td>
                    <td><?php echo ucfirst($summary['tipe']); ?></td>
                    <td class="pemasukan"><?php echo formatRupiah($summary['total_pemasukan']); ?></td>
                    <td class="pengeluaran"><?php echo formatRupiah($summary['total_pengeluaran']); ?></td>
                    <td class="<?php echo $net >= 0 ? 'pemasukan' : 'pengeluaran'; ?>">
                        <?php echo formatRupiah(abs($net)); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Monthly Data -->
    <h3>Data Bulanan</h3>
    <table style="margin-bottom: 20px;">
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Total Pemasukan</th>
                <th>Total Pengeluaran</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get monthly data for export (based on date range)
            $query_monthly_where = [];
            $monthly_params = [];

            if($from_date) {
                $query_monthly_where[] = "DATE(tanggal_transaksi) >= :monthly_from_date";
                $monthly_params[':monthly_from_date'] = $from_date;
            }

            if($to_date) {
                $query_monthly_where[] = "DATE(tanggal_transaksi) <= :monthly_to_date";
                $monthly_params[':monthly_to_date'] = $to_date;
            }

            $monthly_where_clause = !empty($query_monthly_where) ? "WHERE " . implode(" AND ", $query_monthly_where) : "";

            $query_monthly = "SELECT 
                DATE_FORMAT(tanggal_transaksi, '%Y-%m') as bulan,
                SUM(CASE WHEN tipe_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as pemasukan,
                SUM(CASE WHEN tipe_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran
            FROM transaksi_kas 
            $monthly_where_clause
            GROUP BY DATE_FORMAT(tanggal_transaksi, '%Y-%m')
            ORDER BY bulan";
            
            $stmt_monthly = $db->prepare($query_monthly);
            foreach($monthly_params as $key => $value) {
                $stmt_monthly->bindValue($key, $value);
            }
            $stmt_monthly->execute();
            $monthly_data = $stmt_monthly->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($monthly_data as $month):
                $net = $month['pemasukan'] - $month['pengeluaran'];
                $bulan_text = date('F Y', strtotime($month['bulan'] . '-01'));
            ?>
                <tr>
                    <td><?php echo $bulan_text; ?></td>
                    <td class="pemasukan"><?php echo formatRupiah($month['pemasukan']); ?></td>
                    <td class="pengeluaran"><?php echo formatRupiah($month['pengeluaran']); ?></td>
                    <td class="<?php echo $net >= 0 ? 'pemasukan' : 'pengeluaran'; ?>">
                        <?php echo formatRupiah(abs($net)); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Transactions Table -->
    <h3>Detail Transaksi (<?php echo count($transactions); ?> transaksi)</h3>
    <table>
        <thead>
            <tr>
                <th>No. Transaksi</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th>User</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transactions)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada transaksi untuk filter yang dipilih</td>
                </tr>
            <?php else: ?>
                <?php foreach($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['nomor_transaksi']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($transaction['tanggal_transaksi'])); ?></td>
                        <td><?php echo htmlspecialchars($transaction['nama_kategori']); ?></td>
                        <td><?php echo ucfirst($transaction['tipe_transaksi']); ?></td>
                        <td class="<?php echo $transaction['tipe_transaksi'] === 'pemasukan' ? 'pemasukan' : 'pengeluaran'; ?>">
                            <?php echo formatRupiah($transaction['jumlah']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['keterangan']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <br><br>
    <p><strong>Dicetak pada:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Oleh:</strong> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
</body>
</html> 