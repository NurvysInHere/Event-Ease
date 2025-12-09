<?php
session_start();
include '../config/db.php'; // Sesuaikan path jika berbeda

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $proposal_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($proposal_id === false) {
        $_SESSION['admin_message'] = 'ID proposal tidak valid.';
        header("Location: ../admin/admin.php?page=manage_proposals");
        exit;
    }

    try {
        $conn->beginTransaction();

        // Ambil data proposal sebelum dihapus untuk mengembalikan budget jika perlu
        $query_get_proposal = "SELECT budget, pengaju, status, proposal_file, lpj_file FROM event_pengajuan WHERE id = :id FOR UPDATE";
        $stmt_get_proposal = $conn->prepare($query_get_proposal);
        $stmt_get_proposal->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_get_proposal->execute();
        $proposal_data = $stmt_get_proposal->fetch(PDO::FETCH_ASSOC);

        if (!$proposal_data) {
            throw new Exception("Proposal tidak ditemukan.");
        }

        $proposal_budget = (float)$proposal_data['budget'];
        $pengaju_username = $proposal_data['pengaju'];
        $current_status = $proposal_data['status'];
        $proposal_file = $proposal_data['proposal_file'];
        $lpj_file = $proposal_data['lpj_file'];

        // Jika statusnya bukan 'rejected', kembalikan budget ke user
        // (artinya budget sudah dikurangi saat pengajuan/accepted)
        if ($current_status != 'rejected') {
            $query_update_user_budget_return = "UPDATE users SET budget = budget + :return_budget WHERE username = :username";
            $stmt_update_user_budget_return = $conn->prepare($query_update_user_budget_return);
            $stmt_update_user_budget_return->bindParam(':return_budget', $proposal_budget, PDO::PARAM_STR);
            $stmt_update_user_budget_return->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
            $stmt_update_user_budget_return->execute();
            $message_budget = " Budget Rp " . number_format($proposal_budget, 2, ',', '.') . " dikembalikan ke user " . htmlspecialchars($pengaju_username) . ".";
        } else {
            $message_budget = " Budget tidak perlu dikembalikan karena proposal sudah ditolak sebelumnya.";
        }

        // Hapus file dari folder uploads
        $target_dir = "../uploads/";
        if (!empty($proposal_file) && file_exists($target_dir . $proposal_file)) {
            unlink($target_dir . $proposal_file);
        }
        if (!empty($lpj_file) && file_exists($target_dir . $lpj_file)) {
            unlink($target_dir . $lpj_file);
        }

        // Hapus entri proposal dari database
        $query_delete_proposal = "DELETE FROM event_pengajuan WHERE id = :id";
        $stmt_delete_proposal = $conn->prepare($query_delete_proposal);
        $stmt_delete_proposal->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_delete_proposal->execute();

        $conn->commit();
        $_SESSION['admin_message'] = 'Pengajuan event berhasil dihapus.' . $message_budget;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['admin_message'] = 'Gagal menghapus proposal: ' . htmlspecialchars($e->getMessage());
    }

    header("Location: ../dashboard/admin.php?page=manage_proposals");
    exit;
} else {
    // Redirect jika akses tidak langsung GET atau parameter kurang
    $_SESSION['admin_message'] = 'Aksi hapus tidak valid.';
    header("Location: ../dashboard/admin.php?page=manage_proposals");
    exit;
}
?>