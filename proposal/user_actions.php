<?php
session_start();
require_once '../config/db.php';

// Pastikan pengguna sudah login dan memiliki role 'user'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

// Pastikan parameter yang diperlukan ada
if (!isset($_GET['id']) || !isset($_GET['aksi'])) {
    header("Location: ../dashboard/user.php");
    exit;
}

$id   = $_GET['id'];
$aksi = $_GET['aksi'];
$username = $_SESSION['username'];

// Contoh: Membatalkan pengajuan
if ($aksi === 'cancel') {
    // Gunakan PDO untuk prepared statement
    $query = "UPDATE event_pengajuan SET status = 'cancelled' WHERE id = :id AND pengaju = :username AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);

    try {
        $stmt->execute();
        // Alihkan kembali ke dashboard user setelah berhasil
        header("Location: ../dashboard/user.php");
        exit;
    } catch (PDOException $e) {
        // Tangani error
        echo "Error: " . $e->getMessage();
        // Pilihan: log error, lalu redirect
    }
} else {
    // Jika aksi tidak valid, alihkan kembali
    header("Location: ../dashboard/user.php");
    exit;
}
?>