# KAS RUMKIT - Sistem Pengaturan Kas Rumah Sakit

Sistem pengaturan kas rumah sakit yang dibangun dengan PHP Native dan Tailwind CSS untuk manajemen transaksi keuangan yang efisien dan aman.

## 🚀 Fitur Utama

### 🔐 Autentikasi & Keamanan

- Login sistem dengan role-based access control
- Password hashing menggunakan bcrypt
- Session management yang aman
- Role: Admin, Supervisor, dan Kasir

### 💰 Manajemen Transaksi

- Input transaksi pemasukan dan pengeluaran
- Kategori transaksi yang dapat dikustomisasi
- Nomor transaksi otomatis
- Validasi input yang ketat

### 📊 Dashboard & Laporan

- Dashboard real-time dengan statistik keuangan
- Laporan transaksi dengan filter (bulan, tahun, kategori)
- Grafik bulanan pemasukan vs pengeluaran
- Ringkasan kategori transaksi

### 🏷️ Manajemen Kategori

- Tambah, edit, dan hapus kategori transaksi
- Tipe kategori: Pemasukan dan Pengeluaran
- Status aktif/nonaktif kategori

### 👥 Manajemen Pengguna (Admin Only)

- Tambah, edit, dan hapus user
- Ubah password user
- Manajemen role dan status user

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7.4+ (Native)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Icons**: Font Awesome 6.0
- **Charts**: Chart.js
- **Server**: Apache/Nginx dengan XAMPP

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Browser modern dengan JavaScript enabled

## 🚀 Cara Install

### 1. Clone atau Download Proyek

```bash
git clone [repository-url]
cd KASRumkit
```

### 2. Setup Database

1. Buka phpMyAdmin atau MySQL client
2. Buat database baru bernama `kas_rumkit`
3. Import file `database/kas_rumkit.sql`

### 3. Konfigurasi Database

1. Edit file `config/database.php`
2. Sesuaikan konfigurasi database:
   ```php
   private $host = 'localhost';
   private $db_name = 'kas_rumkit';
   private $username = 'root';        // Sesuaikan dengan username MySQL Anda
   private $password = '';            // Sesuaikan dengan password MySQL Anda
   ```

### 4. Setup Web Server

1. Pastikan XAMPP sudah terinstall dan running
2. Copy folder proyek ke `htdocs/`
3. Akses melalui browser: `http://localhost/KASRumkit`

### 5. Login Default

- **Username**: `admin`
- **Password**: `admin123`

## 📁 Struktur Folder

```
KASRumkit/
├── config/
│   └── database.php          # Konfigurasi database dan helper functions
├── auth/
│   └── auth.php              # Class autentikasi dan session management
├── database/
│   └── kas_rumkit.sql        # Script SQL untuk setup database
├── login.php                 # Halaman login
├── dashboard.php             # Dashboard utama
├── transaksi.php             # Manajemen transaksi
├── laporan.php               # Laporan keuangan
├── kategori.php              # Manajemen kategori
├── users.php                 # Manajemen pengguna (admin only)
├── logout.php                # Script logout
└── README.md                 # Dokumentasi ini
```

## 🔧 Konfigurasi

### Database Configuration

File `config/database.php` berisi:

- Konfigurasi koneksi database
- Helper functions untuk format rupiah
- Helper functions untuk generate nomor transaksi
- Helper functions untuk validasi dan security

### Security Features

- Password hashing dengan bcrypt
- Prepared statements untuk mencegah SQL injection
- Input sanitization
- Session timeout protection

## 📱 Penggunaan

### 1. Login

- Akses halaman login
- Masukkan username dan password
- Sistem akan redirect ke dashboard

### 2. Dashboard

- Lihat statistik keuangan hari ini dan bulan ini
- Monitor transaksi terbaru
- Navigasi ke menu lainnya

### 3. Transaksi

- Tambah transaksi baru (pemasukan/pengeluaran)
- Pilih kategori dan tanggal
- Input jumlah dan keterangan
- Lihat daftar semua transaksi

### 4. Laporan

- Filter laporan berdasarkan bulan, tahun, kategori
- Lihat grafik bulanan
- Export data transaksi

### 5. Kategori

- Kelola kategori transaksi
- Tambah kategori baru
- Edit atau hapus kategori yang ada

### 6. Users (Admin Only)

- Tambah user baru
- Edit informasi user
- Ubah password user
- Hapus user

## 🔒 Role dan Permission

### Admin

- Akses penuh ke semua fitur
- Manajemen user
- Manajemen kategori
- Lihat semua transaksi

### Supervisor

- Akses ke dashboard, transaksi, laporan, dan kategori
- Tidak bisa mengelola user
- Lihat semua transaksi

### Kasir

- Akses ke dashboard dan transaksi
- Input transaksi baru
- Lihat transaksi yang dibuat sendiri

## 📊 Database Schema

### Tables

1. **users** - Data pengguna sistem
2. **kategori_transaksi** - Kategori transaksi
3. **transaksi_kas** - Data transaksi keuangan
4. **saldo_kas** - Saldo kas harian

### Relationships

- `transaksi_kas.kategori_id` → `kategori_transaksi.id`
- `transaksi_kas.user_id` → `users.id`

## 🚨 Troubleshooting

### Error Koneksi Database

- Pastikan MySQL service running
- Cek konfigurasi database di `config/database.php`
- Pastikan database `kas_rumkit` sudah dibuat

### Error Login

- Pastikan tabel `users` sudah ter-import
- Cek username dan password default
- Pastikan session PHP berfungsi

### Error Permission

- Pastikan folder memiliki permission write
- Cek error log Apache/PHP

## 🔄 Update dan Maintenance

### Backup Database

```bash
mysqldump -u root -p kas_rumkit > backup_kas_rumkit.sql
```

### Update System

1. Backup database dan file
2. Replace file yang diupdate
3. Import perubahan database jika ada
4. Test sistem

## 📞 Support

Untuk bantuan dan support:

- Buat issue di repository
- Hubungi developer team
- Dokumentasi lengkap tersedia di code

## 📄 License

Proyek ini dibuat untuk keperluan internal rumah sakit. Dilarang menggunakan untuk komersial tanpa izin.

## 🎯 Roadmap

### Versi 1.1 (Coming Soon)

- [ ] Export laporan ke PDF/Excel
- [ ] Notifikasi email untuk transaksi besar
- [ ] Mobile responsive design
- [ ] API endpoints untuk integrasi

### Versi 1.2 (Future)

- [ ] Multi-currency support
- [ ] Advanced reporting
- [ ] Audit trail
- [ ] Backup otomatis

---

**Dibuat dengan ❤️ untuk efisiensi manajemen keuangan rumah sakit**
