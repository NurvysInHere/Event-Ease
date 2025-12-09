<?php
session_start();
include '../config/db.php'; // Sertakan file koneksi database & BASE_URL

// Pastikan hanya user yang login dan memiliki role 'user' yang bisa mengakses ini
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    // Jika tidak login atau bukan user, redirect ke halaman login dengan pesan error
    $_SESSION['temp_alert_message'] = [
        'type' => 'error',
        'title' => 'Akses Ditolak!',
        'text' => 'Anda harus login sebagai user untuk melakukan aksi ini.'
    ];
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Pastikan ada ID pengajuan yang dikirim melalui GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['temp_alert_message'] = [
        'type' => 'error',
        'title' => 'Error!',
        'text' => 'ID pengajuan tidak ditemukan.'
    ];
    header("Location: " . BASE_URL . "dashboard/user.php");
    exit;
}

$proposal_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$user_id = $_SESSION['username']; // Mengambil username dari sesi

if ($proposal_id === false) {
    $_SESSION['temp_alert_message'] = [
        'type' => 'error',
        'title' => 'Error!',
        'text' => 'ID pengajuan tidak valid.'
    ];
    header("Location: " . BASE_URL . "dashboard/user.php");
    exit;
}

try {
    $conn->beginTransaction();

    // Ambil data pengajuan untuk memastikan itu milik user yang sedang login
    // dan hanya pengajuan dengan status 'pending' yang bisa dibatalkan
    $query_check = "SELECT proposal_file FROM event_pengajuan WHERE id = :id AND pengaju = :pengaju AND status = 'pending' FOR UPDATE";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':id', $proposal_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':pengaju', $user_id, PDO::PARAM_STR);
    $stmt_check->execute();
    $proposal_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$proposal_data) {
        // Jika pengajuan tidak ditemukan, bukan milik user ini, atau statusnya bukan pending
        $conn->rollBack(); // Pastikan rollback jika transaksi sudah dimulai
        $_SESSION['temp_alert_message'] = [
            'type' => 'error',
            'title' => 'Error!',
            'text' => 'Pengajuan tidak ditemukan, bukan milik Anda, atau tidak bisa dibatalkan saat ini.'
        ];
        header("Location: " . BASE_URL . "dashboard/user.php");
        exit;
    }

    // Hapus file proposal dari server sebelum menghapus record dari database
    // Pastikan path ke folder uploads sudah benar
    $file_path = __DIR__ . '/../uploads/' . $proposal_data['proposal_file'];
    if (file_exists($file_path) && is_file($file_path)) {
        unlink($file_path); // Hapus file
    }

    // Hapus record pengajuan dari database
    $query_delete = "DELETE FROM event_pengajuan WHERE id = :id";
    $stmt_delete = $conn->prepare($query_delete);
    $stmt_delete->bindParam(':id', $proposal_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    $conn->commit();

    $_SESSION['temp_alert_message'] = [
        'type' => 'success',
        'title' => 'Berhasil!',
        'text' => 'Pengajuan berhasil dibatalkan.'
    ];
    header("Location: " . BASE_URL . "dashboard/user.php"); // Redirect kembali ke dashboard user
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['temp_alert_message'] = [
        'type' => 'error',
        'title' => 'Error Database!',
        'text' => 'Gagal membatalkan pengajuan: ' . $e->getMessage()
    ];
    header("Location: " . BASE_URL . "dashboard/user.php");
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['temp_alert_message'] = [
        'type' => 'error',
        'title' => 'Error!',
        'text' => 'Gagal membatalkan pengajuan: ' . $e->getMessage()
    ];
    header("Location: " . BASE_URL . "dashboard/user.php");
    exit;
}
?>