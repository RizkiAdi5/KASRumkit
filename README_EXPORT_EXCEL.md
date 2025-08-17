# Fitur Export Excel - Sistem Kas Rumah Sakit

## Deskripsi

Fitur export Excel memungkinkan pengguna untuk mengekspor laporan keuangan ke dalam format Excel (.xls) yang dapat dibuka di Microsoft Excel atau aplikasi spreadsheet lainnya.

## Fitur yang Tersedia

### 1. **Export Laporan Lengkap**

- **Ringkasan Keuangan**: Total pemasukan, pengeluaran, dan saldo
- **Ringkasan per Kategori**: Breakdown transaksi berdasarkan kategori
- **Data Bulanan**: Tren keuangan per bulan
- **Detail Transaksi**: Semua transaksi sesuai filter yang dipilih

### 2. **Filter yang Didukung**

- **Bulan**: Export data berdasarkan bulan tertentu
- **Tahun**: Export data berdasarkan tahun tertentu
- **Kategori**: Export data berdasarkan kategori transaksi
- **Tipe**: Export data berdasarkan tipe (pemasukan/pengeluaran)

### 3. **Format File**

- **Ekstensi**: .xls (Excel 97-2003)
- **Nama File**: `Laporan_Keuangan_YYYY-MM-DD_HH-MM-SS.xls`
- **Encoding**: UTF-8

## Cara Penggunaan

### 1. **Akses Laporan**

- Login ke sistem
- Buka menu "Laporan"
- Pilih filter yang diinginkan (bulan, tahun, kategori, tipe)

### 2. **Export ke Excel**

- Klik tombol "Export Excel" (tombol hijau)
- File Excel akan otomatis terdownload
- Buka file dengan Microsoft Excel atau aplikasi spreadsheet

### 3. **Struktur File Excel**

```
Sheet 1: Laporan Keuangan
├── Header dan Informasi Filter
├── Ringkasan Keuangan
├── Ringkasan per Kategori
├── Data Bulanan
└── Detail Transaksi
```

## Format Data

### **Ringkasan Keuangan**

| Total Pemasukan | Total Pengeluaran | Saldo        |
| --------------- | ----------------- | ------------ |
| Rp 1.500.000    | Rp 500.000        | Rp 1.000.000 |

### **Ringkasan per Kategori**

| Kategori   | Tipe      | Total Pemasukan | Total Pengeluaran | Net          |
| ---------- | --------- | --------------- | ----------------- | ------------ |
| Rawat Inap | Pemasukan | Rp 1.000.000    | Rp 0              | Rp 1.000.000 |

### **Data Bulanan**

| Bulan        | Total Pemasukan | Total Pengeluaran | Net          |
| ------------ | --------------- | ----------------- | ------------ |
| Januari 2024 | Rp 1.500.000    | Rp 500.000        | Rp 1.000.000 |

### **Detail Transaksi**

| No. Transaksi | Tanggal    | Kategori   | Tipe      | Jumlah     | Keterangan        | User  |
| ------------- | ---------- | ---------- | --------- | ---------- | ----------------- | ----- |
| IN20240101ABC | 01/01/2024 | Rawat Inap | Pemasukan | Rp 500.000 | Pembayaran pasien | Admin |

## Keamanan

### **Validasi Akses**

- Hanya user yang sudah login yang dapat mengakses export
- Session validation untuk mencegah akses tidak sah
- Redirect ke login jika session expired

### **Sanitasi Data**

- HTML escaping untuk mencegah XSS
- Prepared statements untuk mencegah SQL injection
- Validasi parameter input

## Teknis

### **Dependencies**

- PHP PDO untuk database connection
- Session management
- HTTP headers untuk file download

### **File yang Terlibat**

- `laporan.php`: Halaman laporan dengan tombol export
- `export_excel.php`: Script untuk generate file Excel
- `config/database.php`: Konfigurasi database

### **Query yang Digunakan**

- Transaksi dengan filter dinamis
- Summary per kategori
- Data bulanan
- Semua query menggunakan prepared statements

## Troubleshooting

### **File Tidak Terdownload**

- Pastikan browser tidak memblokir popup
- Cek permission folder untuk write access
- Pastikan session masih aktif

### **Data Tidak Sesuai Filter**

- Refresh halaman laporan
- Pastikan filter sudah diterapkan
- Cek parameter URL export

### **Error Database**

- Pastikan koneksi database aktif
- Cek log error PHP
- Verifikasi struktur tabel database

## Pengembangan Selanjutnya

### **Fitur yang Direncanakan**

- Export ke format .xlsx (Excel modern)
- Multiple sheet dalam satu file
- Template Excel yang dapat dikustomisasi
- Export dengan grafik dan chart
- Export ke format PDF

### **Optimasi**

- Caching data untuk export besar
- Background processing untuk file besar
- Compression file untuk download lebih cepat
- Progress bar untuk export lama

## Kontak Support

Jika mengalami masalah dengan fitur export, silakan hubungi administrator sistem.
