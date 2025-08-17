-- Tambahan untuk sistem kas bulanan pegawai
-- File: pegawai.sql

USE kas_rumkit;

-- Table: pegawai (untuk maintenance, tidak perlu password)
CREATE TABLE IF NOT EXISTS pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    departemen VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tambah kolom pegawai_id ke tabel transaksi_kas (jika belum ada)
ALTER TABLE transaksi_kas ADD COLUMN IF NOT EXISTS pegawai_id INT NULL AFTER kategori_id;

-- Tambah foreign key jika belum ada
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'kas_rumkit'
    AND TABLE_NAME = 'transaksi_kas'
    AND CONSTRAINT_NAME = 'fk_transaksi_pegawai'
);

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE transaksi_kas ADD CONSTRAINT fk_transaksi_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai(id)',
    'SELECT "Foreign key already exists" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tambah kategori kas bulanan jika belum ada
INSERT IGNORE INTO kategori_transaksi (nama_kategori, tipe, deskripsi) VALUES
('Kas Bulanan Pegawai', 'pengeluaran', 'Kas bulanan untuk pegawai rumah sakit');

-- Insert beberapa data pegawai contoh
INSERT IGNORE INTO pegawai (nama_lengkap, departemen) VALUES
('Dr. Ahmad Rizki', 'Poli Umum'),
('Suster Siti Nurhaliza', 'IGD'),
('Ahmad Fauzi', 'Administrasi'),
('Dr. Sarah Putri', 'Poli Gigi'),
('Budi Santoso', 'Laboratorium');

-- Tampilkan hasil
SELECT 'Tabel pegawai berhasil dibuat' as status;
SELECT COUNT(*) as total_pegawai FROM pegawai;
SELECT COUNT(*) as total_kategori FROM kategori_transaksi WHERE nama_kategori = 'Kas Bulanan Pegawai'; 