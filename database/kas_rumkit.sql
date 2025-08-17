-- Database: kas_rumkit
-- Description: Sistem Pengaturan Kas Rumah Sakit

CREATE DATABASE IF NOT EXISTS kas_rumkit;
USE kas_rumkit;

-- Table: users (pengguna sistem)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'kasir', 'supervisor') NOT NULL DEFAULT 'kasir',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: kategori_transaksi
CREATE TABLE kategori_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    tipe ENUM('pemasukan', 'pengeluaran') NOT NULL,
    deskripsi TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: transaksi_kas
CREATE TABLE transaksi_kas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_transaksi VARCHAR(20) UNIQUE NOT NULL,
    tanggal_transaksi DATE NOT NULL,
    kategori_id INT NOT NULL,
    tipe_transaksi ENUM('pemasukan', 'pengeluaran') NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_transaksi(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: saldo_kas
CREATE TABLE saldo_kas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    saldo_awal DECIMAL(15,2) DEFAULT 0,
    total_pemasukan DECIMAL(15,2) DEFAULT 0,
    total_pengeluaran DECIMAL(15,2) DEFAULT 0,
    saldo_akhir DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@rumkit.com', 'admin');

-- Insert default categories
INSERT INTO kategori_transaksi (nama_kategori, tipe, deskripsi) VALUES
('Pendapatan Rawat Jalan', 'pemasukan', 'Pendapatan dari layanan rawat jalan'),
('Pendapatan Rawat Inap', 'pemasukan', 'Pendapatan dari layanan rawat inap'),
('Pendapatan Laboratorium', 'pemasukan', 'Pendapatan dari layanan laboratorium'),
('Pendapatan Farmasi', 'pemasukan', 'Pendapatan dari layanan farmasi'),
('Gaji Karyawan', 'pengeluaran', 'Pengeluaran untuk gaji karyawan'),
('Biaya Operasional', 'pengeluaran', 'Biaya operasional rumah sakit'),
('Biaya Utilitas', 'pengeluaran', 'Biaya listrik, air, dan utilitas lainnya'),
('Biaya Maintenance', 'pengeluaran', 'Biaya pemeliharaan peralatan dan gedung');

-- Insert initial cash balance
INSERT INTO saldo_kas (tanggal, saldo_awal, saldo_akhir) VALUES 
(CURDATE(), 0, 0);
