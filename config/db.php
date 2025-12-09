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
    define('DB_PASS', 'admin123');
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