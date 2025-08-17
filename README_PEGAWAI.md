# Fitur Pegawai - Sistem Kas Rumah Sakit

## Deskripsi

Fitur pegawai telah ditambahkan ke sistem kas rumah sakit untuk mengelola data pegawai yang akan menerima kas bulanan. Fitur ini memungkinkan admin untuk menambahkan, mengedit, dan menghapus data pegawai tanpa perlu password.

## Fitur yang Ditambahkan

### 1. Tabel Pegawai

- **Tabel**: `pegawai`
- **Kolom**:
  - `id` - Primary key
  - `nama_lengkap` - Nama lengkap pegawai (wajib)
  - `departemen` - Departemen pegawai (opsional)
  - `is_active` - Status aktif pegawai
  - `created_at` - Waktu pembuatan
  - `updated_at` - Waktu update

### 2. Modifikasi Tabel Transaksi

- **Kolom baru**: `pegawai_id` di tabel `transaksi_kas`
- **Relasi**: Foreign key ke tabel `pegawai`

### 3. Halaman Manajemen Pegawai

- **File**: `pegawai.php`
- **Fitur**:
  - Tambah pegawai baru
  - Edit data pegawai
  - Hapus pegawai
  - Daftar semua pegawai dengan pagination
  - Modal edit pegawai

### 4. Integrasi dengan Transaksi

- **Kategori khusus**: "Kas Bulanan Pegawai"
- **Fitur**: Ketika kategori ini dipilih, field pemilihan pegawai akan muncul
- **Validasi**: Field pegawai menjadi required untuk kategori kas bulanan

## Cara Penggunaan

### 1. Menambahkan Pegawai

1. Buka menu "Pegawai" di sidebar
2. Isi form dengan data pegawai:
   - Nama Lengkap (wajib)
   - Departemen (opsional)
3. Klik "Simpan Pegawai"

### 2. Membuat Transaksi Kas Bulanan

1. Buka menu "Transaksi"
2. Pilih tipe transaksi "Pengeluaran"
3. Pilih kategori "Kas Bulanan Pegawai"
4. Field "Pilih Pegawai" akan muncul otomatis
5. Pilih pegawai yang akan menerima kas
6. Isi jumlah dan keterangan
7. Klik "Simpan Transaksi"

### 3. Mengelola Data Pegawai

- **Edit**: Klik ikon edit (biru) pada baris pegawai
- **Hapus**: Klik ikon hapus (merah) pada baris pegawai

## Struktur Database

### SQL untuk Menambahkan Tabel Pegawai

```sql
-- Buat tabel pegawai
CREATE TABLE IF NOT EXISTS pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    departemen VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tambah kolom pegawai_id ke tabel transaksi_kas
ALTER TABLE transaksi_kas ADD COLUMN IF NOT EXISTS pegawai_id INT NULL AFTER kategori_id;
ALTER TABLE transaksi_kas ADD CONSTRAINT fk_transaksi_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai(id);

-- Tambah kategori kas bulanan
INSERT IGNORE INTO kategori_transaksi (nama_kategori, tipe, deskripsi) VALUES
('Kas Bulanan Pegawai', 'pengeluaran', 'Kas bulanan untuk pegawai rumah sakit');
```

## Keamanan

- **Tidak ada password**: Pegawai hanya untuk maintenance data, bukan untuk login sistem
- **Validasi input**: Semua input divalidasi dan di-sanitize
- **Hak akses**: Semua user yang sudah login bisa mengakses fitur pegawai

## Catatan Penting

1. **Kategori "Kas Bulanan Pegawai"** harus sudah ada di database sebelum menggunakan fitur ini
2. **Field pegawai** hanya muncul ketika kategori kas bulanan dipilih
3. **Data pegawai** tidak terhubung dengan sistem login
4. **Backup database** sebelum menjalankan script SQL
5. **Struktur sederhana**: Hanya nama lengkap dan departemen yang diperlukan

## Troubleshooting

### Error: Cannot redeclare formatRupiah()

**Penyebab**: Fungsi helper dideklarasikan di beberapa file
**Solusi**:

- Semua fungsi helper sudah dipindah ke `config/database.php`
- Hapus fungsi duplikat dari file lain
- Pastikan `require_once 'config/database.php'` ada di setiap file

### Error: Cannot redeclare sanitizeInput()

**Penyebab**: Fungsi sanitizeInput dideklarasikan di beberapa file
**Solusi**:

- Fungsi sudah tersedia di `config/database.php`
- Hapus deklarasi duplikat dari file lain

### Field pegawai tidak muncul

**Penyebab**: Kategori "Kas Bulanan Pegawai" belum ada
**Solusi**:

- Jalankan script SQL untuk menambah kategori
- Pastikan nama kategori persis sama

### Error foreign key

**Penyebab**: Tabel pegawai belum dibuat
**Solusi**:

- Jalankan script SQL secara berurutan
- Buat tabel pegawai terlebih dahulu
- Kemudian tambah kolom pegawai_id

### Data tidak tersimpan

**Penyebab**: Field required tidak diisi
**Solusi**:

- Periksa apakah semua field required sudah diisi
- Pastikan kategori dan pegawai sudah dipilih untuk kas bulanan

## Update Selanjutnya

- Export data pegawai ke Excel/PDF
- Import data pegawai dari Excel
- Laporan kas bulanan per pegawai
- Notifikasi kas bulanan
- Integrasi dengan sistem gaji

## File yang Dimodifikasi

- `pegawai.php` - Halaman manajemen pegawai baru (struktur disederhanakan)
- `transaksi.php` - Integrasi dengan pegawai
- `dashboard.php` - Menu dan tampilan pegawai
- `kategori.php` - Menu pegawai
- `laporan.php` - Menu pegawai
- `users.php` - Menu pegawai
- `config/database.php` - Fungsi helper terpusat
- `database/pegawai.sql` - Script database (struktur disederhanakan)

## Perubahan Struktur

**Sebelumnya**: Tabel pegawai memiliki field NIP, jabatan, dan gaji pokok
**Sekarang**: Tabel pegawai hanya memiliki field nama_lengkap dan departemen
**Alasan**: Menyederhanakan sistem sesuai kebutuhan yang sebenarnya
