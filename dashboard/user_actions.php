<?php
session_start();
// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Sertakan file koneksi database
include '../config/db.php';

// Pastikan parameter 'id' dan 'action' ada di URL
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: manage_users.php");
    exit;
}

$id     = $_GET['id'];
$action = $_GET['action'];

// Gunakan prepared statement untuk menghindari SQL Injection
if ($action == 'accept' || $action == 'reject') {
    // Tentukan status baru berdasarkan aksi
    $status = ($action == 'accept') ? 'accepted' : 'rejected';

    // Query untuk memperbarui status akun pengguna
    $query = "UPDATE users SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
} elseif ($action == 'delete') {
    // Query untuk menghapus akun pengguna
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}

// Arahkan kembali ke halaman manajemen akun
header("Location: manage_users.php");
exit;
