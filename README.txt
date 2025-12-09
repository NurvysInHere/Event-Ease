SIPENG-EVENT - Sistem Pengajuan Event
📋 Tentang Sistem
SIPENG-EVENT adalah sistem manajemen pengajuan kegiatan ekstrakurikuler berbasis web dengan sistem dual role: Admin dan User.
Admin dapat mengelola pengajuan, User dapat mengajukan kegiatan dengan melampirkan proposal.
⚙️ Instalasi & Setup
- Persyaratan Sistem
PHP 7.4 atau lebih tinggi (dengan ekstensi PDO dan GD)
MySQL 5.7 atau MariaDB 10.2+
Web server (Apache atau Nginx)
Akses untuk membuat database dan table

Langkah 1: Setup Database
Buka phpMyAdmin atau MySQL client, lalu jalankan perintah berikut:

sql
-- Buat database
CREATE DATABASE pengajuan_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Pilih database
USE pengajuan_event;

-- Buat tabel pengguna
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    profile_picture VARCHAR(255) NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
);

-- Buat tabel kegiatan
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nama_event VARCHAR(100) NOT NULL,
    deskripsi TEXT NOT NULL,
    proposal_file VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    catatan_admin TEXT NULL,
    tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_diproses DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tambahkan akun admin default
INSERT INTO users (username, email, password, role, status) 
VALUES (
    'admin', 
    'admin@sipengevent.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    'accepted'
);

-- Tidak ada data kegiatan awal, tabel events tetap kosong
Catatan: Password default untuk akun admin adalah admin123

Langkah 2: Konfigurasi Aplikasi
Edit file config/db.php pada folder aplikasi:

php
<?php
// config/db.php

// Cek apakah konstanta sudah didefinisikan sebelum mendefinisikannya
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'pengajuan_event');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/pengajuan_event/');
}

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/pengajuan_event/uploads/');
}

if (!defined('PROFILE_PICTURE_DIR')) {
    define('PROFILE_PICTURE_DIR', UPLOAD_DIR . 'profiles/');
}

if (!defined('PROFILE_PICTURE_URL')) {
    define('PROFILE_PICTURE_URL', BASE_URL . 'uploads/profiles/');
}

// Cek apakah koneksi sudah dibuat
if (!isset($conn)) {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set timezone untuk Indonesia (WIB)
        $conn->exec("SET time_zone = '+07:00'");
    } catch(PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}
?>

Langkah 3: Persiapan Folder
Pastikan folder berikut ada dan dapat ditulisi:

text
/pengajuan_event/
├── uploads/
│   ├── profiles/     ← Folder untuk foto profil
│   └── proposals/    ← Folder untuk file proposal
└── config/
    └── db.php        ← File konfigurasi
Untuk Linux/MacOS:

bash
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/proposals/
Untuk Windows: Folder sudah bisa ditulisi secara default pada XAMPP/Laragon.

🔐 Informasi Login
Akun Administrator (Hanya 1 akun default)
Username: admin
Password: admin123
Alamat Email: admin@sipengevent.com
Peran: Administrator
Status: Aktif

Catatan Penting:
1. Hanya ada 1 akun admin saat instalasi awal
2. Akun user harus mendaftar melalui halaman register
3. Password disimpan dengan enkripsi bcrypt

🎨 Fitur Utama
Panel Administrator
Dashboard Interaktif: Statistik lengkap kegiatan dan pengguna

Manajemen Pengajuan: Tinjau, setujui, atau tolak pengajuan kegiatan

Kelola Pengguna: Aktivasi dan deaktivasi akun user

Profil Admin: Unggah dan kelola foto profil

Monitoring: Pantau semua aktivitas sistem

Panel Pengguna (User)
Dashboard Personal: Ringkasan pengajuan pribadi

Form Pengajuan: Ajukan kegiatan baru dengan lampiran proposal

Tracking Status: Lacak status pengajuan secara real-time

Kelola Profil: Unggah foto profil dan lihat riwayat

Sistem Keamanan
Autentikasi Ganda: Login dengan username atau email

Enkripsi Password: Menggunakan algoritma bcrypt

Manajemen Session: Pengelolaan session yang aman

Kontrol Akses Berbasis Peran: Pembatasan akses berdasarkan role

Validasi File: Filter jenis dan ukuran file upload

🚀 Panduan Penggunaan Cepat
Untuk Pengguna Baru (User)
Akses halaman register: /auth/register.php

Daftar dengan mengisi formulir

Tunggu aktivasi dari admin (jika diperlukan)

Login dan mulai ajukan kegiatan

Untuk Administrator
Login dengan akun admin default

Tinjau dashboard untuk statistik awal

Kelola pengajuan dari menu "Manajemen Pengajuan"

Pantau user dari menu "Manajemen Pengguna"

Mengajukan Kegiatan (User)
Login ke akun user

Pilih "Ajukan Event" dari menu

Isi detail kegiatan

Unggah proposal (PDF/DOC/DOCX, maks. 10MB)

Submit dan tunggu persetujuan admin

⚠️ Pemecahan Masalah Umum
Masalah Koneksi Database
Gejala: Error "Koneksi database gagal"
Solusi:

Pastikan MySQL/MariaDB sedang berjalan

Verifikasi username dan password di config/db.php

Cek apakah database pengajuan_event sudah dibuat

Upload File Gagal
Gejala: File tidak terupload atau error upload
Solusi:

Pastikan folder uploads/ dan subfoldernya bisa ditulisi

Cek ukuran file tidak melebihi batas (5MB untuk foto, 10MB untuk proposal)

Pastikan format file sesuai (JPG, PNG, GIF untuk foto; PDF, DOC, DOCX untuk proposal)

Foto Profil Tidak Tampil
Gejala: Foto profil muncul sebagai gambar default
Solusi:

Refresh cache browser (Ctrl+F5)

Cek apakah file benar-benar terupload ke folder uploads/profiles/

Verifikasi permission folder

Error PHP atau PDO
Gejala: Pesan error PHP muncul
Solusi:

Pastikan ekstensi PDO MySQL diaktifkan di php.ini

Cek versi PHP minimal 7.4

Aktifkan error reporting untuk debugging:

php
error_reporting(E_ALL);
ini_set('display_errors', 1);
🗂️ Struktur Data & File
Lokasi Penyimpanan
Database: Semua data disimpan di database MySQL pengajuan_event

Foto Profil: pengajuan_event/uploads/profiles/[nama_file]

File Proposal: pengajuan_event/uploads/proposals/[nama_file]

Keamanan File Upload
File .htaccess otomatis dibuat di folder upload untuk membatasi akses langsung:

text
Order Deny,Allow
Deny from all
<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx)$">
    Allow from all
</FilesMatch>
🔧 Pengaturan Lanjutan
Mengganti Password Admin
Untuk keamanan, ganti password admin setelah instalasi pertama:

Login sebagai admin

Akses halaman profil

Gunakan fitur "Edit Profil" untuk mengganti password

Atau melalui database langsung:

sql
UPDATE users SET password = '$2y$10$...hash.baru...' WHERE username = 'admin';
Meningkatkan Batas Upload
Edit file php.ini:

ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
Backup Database
Untuk backup rutin:

bash
mysqldump -u root -p pengajuan_event > backup_$(date +%Y%m%d).sql
📞 Dukungan Teknis
Checklist Setelah Instalasi
Database berhasil diimport

File config/db.php sudah dikonfigurasi

Folder uploads/ bisa ditulisi

Bisa login dengan akun admin

Test upload file berhasil

Form pengajuan berfungsi

Jika Masih Mengalami Masalah
Cek file error log PHP

Verifikasi semua requirement terpenuhi

Bandingkan struktur database dengan SQL di atas

Pastikan semua file ada di lokasi yang benar

Logging & Monitoring
Error log PHP: Cek file error_log di direktori aplikasi

Log database: Aktifkan general log di MySQL jika diperlukan

Browser console: Tekan F12 untuk debugging JavaScript

📄 Informasi Proyek
Spesifikasi Teknis
Backend: PHP Native dengan PDO

Frontend: HTML5, CSS3, Vanilla JavaScript

Database: MySQL/MariaDB

Keamanan: Bcrypt hashing, Prepared statements

Responsif: Design mobile-friendly dengan tema gelap/terang

Lisensi & Hak Cipta
Sistem ini dikembangkan untuk penggunaan internal organisasi atau pendidikan. Tidak untuk distribusi komersial tanpa izin.

Kredit
Developer: Tim Pengembangan SIPENG-EVENT
Styling: CSS custom dengan sistem tema

🎯 Mulai Menggunakan
Setup Database: Jalankan script SQL di atas

Konfigurasi: Edit file config/db.php

Persiapan: Pastikan folder uploads siap

Akses: Buka http://localhost/pengajuan_event/

Login: Gunakan username admin dan password admin123

URL Penting:

Login: /auth/login.php

Register: /auth/register.php

Dashboard Admin: /dashboard/admin.php

Dashboard User: /dashboard/user.php

Selamat menggunakan SIPENG-EVENT! 🚀