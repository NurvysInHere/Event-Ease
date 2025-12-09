<?php
session_start();
include '../config/db.php'; // Sesuaikan path jika berbeda

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proposal_id']) && isset($_POST['status_action'])) {
    $proposal_id = filter_var($_POST['proposal_id'], FILTER_VALIDATE_INT);
    $status_action = htmlspecialchars($_POST['status_action']); // 'accept' atau 'reject'

    if ($proposal_id === false) {
        $_SESSION['admin_message'] = 'ID proposal tidak valid.';
        header("Location: admin_proposals.php"); // Ganti dengan halaman manajemen proposal admin Anda
        exit;
    }

    try {
        $conn->beginTransaction();

        // Ambil data proposal sebelum update
        // Menggunakan "FOR UPDATE" untuk mengunci baris agar tidak ada konflik data konkruen
        $query_get_proposal = "SELECT budget, pengaju, status FROM event_pengajuan WHERE id = :id FOR UPDATE";
        $stmt_get_proposal = $conn->prepare($query_get_proposal);
        $stmt_get_proposal->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_get_proposal->execute();
        $proposal_data = $stmt_get_proposal->fetch(PDO::FETCH_ASSOC);

        if (!$proposal_data) {
            throw new Exception("Proposal tidak ditemukan.");
        }

        $current_status = $proposal_data['status'];
        $proposal_budget = (float)$proposal_data['budget'];
        $pengaju_username = $proposal_data['pengaju'];

        $new_status = '';
        $message = '';

        if ($status_action == 'accept') {
            if ($current_status == 'pending') {
                $new_status = 'accepted';
                $message = 'Proposal berhasil diterima.';
                // Jika diterima, budget sudah terkurangi saat pengajuan di upload.php, tidak perlu aksi tambahan di sini
            } else if ($current_status == 'rejected') {
                // Jika status sebelumnya rejected, berarti budget sudah dikembalikan.
                // Sekarang diterima, maka budget harus dikurangi lagi dari user.
                // Perlu cek sisa budget user sebelum mengurangi lagi.
                $query_get_user_budget = "SELECT budget FROM users WHERE username = :username FOR UPDATE";
                $stmt_get_user_budget = $conn->prepare($query_get_user_budget);
                $stmt_get_user_budget->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                $stmt_get_user_budget->execute();
                $user_current_budget = (float) $stmt_get_user_budget->fetchColumn();

                if ($user_current_budget < $proposal_budget) {
                    throw new Exception("Sisa budget user tidak mencukupi untuk menerima proposal ini lagi setelah sebelumnya ditolak.");
                }

                $query_update_user_budget_deduct = "UPDATE users SET budget = budget - :deduct_budget WHERE username = :username";
                $stmt_update_user_budget_deduct = $conn->prepare($query_update_user_budget_deduct);
                $stmt_update_user_budget_deduct->bindParam(':deduct_budget', $proposal_budget, PDO::PARAM_STR);
                $stmt_update_user_budget_deduct->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                $stmt_update_user_budget_deduct->execute();

                $new_status = 'accepted';
                $message = 'Proposal berhasil diterima (setelah sebelumnya ditolak). Budget user telah dikurangi kembali.';
            }
            else {
                throw new Exception("Proposal sudah " . $current_status . ", tidak bisa diterima lagi.");
            }
        } elseif ($status_action == 'reject') {
            if ($current_status == 'pending' || $current_status == 'accepted') { // Bisa ditolak dari pending atau accepted
                $new_status = 'rejected';
                $message = 'Proposal berhasil ditolak. Budget akan dikembalikan ke user.';

                // Hanya kembalikan budget jika proposal belum berstatus 'rejected' sebelumnya
                // Jika statusnya pending, artinya budget sudah terkurangi saat pengajuan, jadi perlu dikembalikan
                // Jika statusnya accepted, artinya budget sudah terkurangi saat pengajuan, jadi perlu dikembalikan
                if ($current_status != 'rejected') {
                    // Kembalikan budget ke user
                    $query_update_user_budget_return = "UPDATE users SET budget = budget + :return_budget WHERE username = :username";
                    $stmt_update_user_budget_return = $conn->prepare($query_update_user_budget_return);
                    $stmt_update_user_budget_return->bindParam(':return_budget', $proposal_budget, PDO::PARAM_STR);
                    $stmt_update_user_budget_return->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                    $stmt_update_user_budget_return->execute();
                }
            } else {
                throw new Exception("Proposal sudah " . $current_status . ", tidak bisa ditolak lagi.");
            }
        } else {
            throw new Exception("Aksi status tidak valid.");
        }

        // Update status proposal
        $query_update_proposal_status = "UPDATE event_pengajuan SET status = :status WHERE id = :id";
        $stmt_update_proposal_status = $conn->prepare($query_update_proposal_status);
        $stmt_update_proposal_status->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt_update_proposal_status->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_update_proposal_status->execute();

        $conn->commit();
        $_SESSION['admin_message'] = $message;

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['admin_message'] = 'Gagal memproses proposal: ' . htmlspecialchars($e->getMessage());
    }

    header("Location: admin_proposals.php"); // Ganti dengan halaman manajemen proposal admin Anda
    exit;
} else {
    header("Location: admin_proposals.php"); // Redirect jika akses tidak langsung POST
    exit;
}
?>