<?php
session_start();

// Pastikan pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Sertakan file koneksi database menggunakan PDO
require_once '../config/db.php'; 
// Sertakan fungsi pengiriman email verifikasi dan budget
require_once '../utils/send_email.php';

// Pastikan variabel koneksi terdefinisi setelah memanggil db.php
if (!isset($conn)) {
    die("Error: Koneksi database tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = htmlspecialchars($_POST['action']);
        
        // Menggunakan operator null coalescing (??) untuk menangani input yang mungkin tidak ada
        $id_or_username = htmlspecialchars($_POST['id'] ?? $_POST['username'] ?? '');
        
        // Validasi input utama jika diperlukan oleh aksi tertentu
        if ($action !== 'set_budget' && empty($id_or_username)) {
            $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'ID atau Username tidak valid.'];
            header("Location: manage_users.php");
            exit;
        }

        switch ($action) {
            case 'delete_user':
                $conn->beginTransaction();

                $target_username = $id_or_username;
                
                // Hapus pengajuan terkait pengguna
                $query_delete_pengajuan = "DELETE FROM event_pengajuan WHERE pengaju = :username";
                $stmt_delete_pengajuan = $conn->prepare($query_delete_pengajuan);
                $stmt_delete_pengajuan->bindParam(':username', $target_username, PDO::PARAM_STR);
                $stmt_delete_pengajuan->execute();

                // Hapus pengguna dari tabel users
                $query_delete_user = "DELETE FROM users WHERE username = :username";
                $stmt_delete_user = $conn->prepare($query_delete_user);
                $stmt_delete_user->bindParam(':username', $target_username, PDO::PARAM_STR);
                $stmt_delete_user->execute();
                
                $conn->commit();
                $_SESSION['temp_alert_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Akun pengguna berhasil dihapus.'];
                break;
            
            case 'accept_user':
            case 'reject_user':
                $new_status = ($action === 'accept_user') ? 'accepted' : 'rejected';
                
                // Sebelum update status, ambil email dan username pengguna
                $user_data_query = "SELECT email, username FROM users WHERE id = :id";
                $stmt_user_data = $conn->prepare($user_data_query);
                $stmt_user_data->bindParam(':id', $id_or_username, PDO::PARAM_INT);
                $stmt_user_data->execute();
                $user_to_update = $stmt_user_data->fetch(PDO::FETCH_ASSOC);

                if ($user_to_update) {
                    $conn->beginTransaction();
                    
                    $query_update_status = "UPDATE users SET status = :status WHERE id = :id";
                    $stmt_update_status = $conn->prepare($query_update_status);
                    $stmt_update_status->bindParam(':status', $new_status, PDO::PARAM_STR);
                    $stmt_update_status->bindParam(':id', $id_or_username, PDO::PARAM_INT);
                    $stmt_update_status->execute();
                    
                    $conn->commit();
                    
                    $message_text = ($action === 'accept_user') ? 'diterima.' : 'ditolak.';
                    $alert_message = 'Akun pengguna berhasil ' . $message_text;

                    // KIRIM EMAIL JIKA AKUN DITERIMA
                    if ($action === 'accept_user') {
                        $email_sent = send_verification_email($user_to_update['email'], $user_to_update['username']);
                        if ($email_sent) {
                            $alert_message .= ' Email notifikasi berhasil dikirim.';
                        } else {
                            $alert_message .= ' Gagal mengirim email notifikasi.';
                        }
                    } else if ($action === 'reject_user') {
                        $query_delete_pengajuan_rejected = "DELETE FROM event_pengajuan WHERE pengaju = :username";
                        $stmt_delete_pengajuan_rejected = $conn->prepare($query_delete_pengajuan_rejected);
                        $stmt_delete_pengajuan_rejected->bindParam(':username', $user_to_update['username'], PDO::PARAM_STR);
                        $stmt_delete_pengajuan_rejected->execute();
                        $alert_message .= ' Pengajuan terkait juga dihapus.';
                    }

                    $_SESSION['temp_alert_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => $alert_message];

                } else {
                    $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Pengguna tidak ditemukan untuk update status.'];
                }
                break;
            
            case 'set_budget':
                $target_username = htmlspecialchars($_POST['username'] ?? '');
                $new_budget_value = filter_var($_POST['new_budget'] ?? 0, FILTER_VALIDATE_FLOAT);

                if (empty($target_username) || $new_budget_value === false || $new_budget_value < 0) {
                    $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Input budget tidak valid. Pastikan memilih eskul dan mengisi angka positif.'];
                    header("Location: manage_users.php");
                    exit;
                }

                // Ambil data pengguna sebelum update budget
                $user_data_query = "SELECT email, username FROM users WHERE username = :username";
                $stmt_user_data = $conn->prepare($user_data_query);
                $stmt_user_data->bindParam(':username', $target_username, PDO::PARAM_STR);
                $stmt_user_data->execute();
                $user_to_update = $stmt_user_data->fetch(PDO::FETCH_ASSOC);

                if ($user_to_update) {
                    $conn->beginTransaction();
                    $query_update_budget = "UPDATE users SET budget = :budget WHERE username = :username";
                    $stmt_update_budget = $conn->prepare($query_update_budget);
                    $stmt_update_budget->bindParam(':budget', $new_budget_value, PDO::PARAM_STR);
                    $stmt_update_budget->bindParam(':username', $target_username, PDO::PARAM_STR);
                    $stmt_update_budget->execute();
                    $conn->commit();

                    // KIRIM EMAIL NOTIFIKASI BUDGET
                    $email_sent = send_budget_notification_email(
                        $user_to_update['email'], 
                        $user_to_update['username'], 
                        $new_budget_value
                    );

                    if ($email_sent) {
                        $_SESSION['temp_alert_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Budget untuk ' . $target_username . ' berhasil diatur menjadi Rp ' . number_format($new_budget_value, 2, ',', '.') . '. Email notifikasi berhasil dikirim.'];
                    } else {
                        $_SESSION['temp_alert_message'] = ['type' => 'success', 'title' => 'Berhasil!', 'text' => 'Budget untuk ' . $target_username . ' berhasil diatur menjadi Rp ' . number_format($new_budget_value, 2, ',', '.') . '. Gagal mengirim email notifikasi.'];
                    }
                } else {
                    $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Pengguna tidak ditemukan untuk mengatur budget.'];
                }
                break;
            
            default:
                $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Aksi tidak valid.'];
                break;
        }

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Gagal memproses aksi: ' . $e->getMessage()];
    }

    header("Location: manage_users.php");
    exit;
}

// --- BAGIAN LOGIKA UNTUK MENGAMBIL DATA DARI DATABASE ---
try {
    // Ambil akun yang masih pending untuk persetujuan
    $query_pending = "SELECT id, username, dibuat_pada, email FROM users WHERE role = 'user' AND status = 'pending' ORDER BY dibuat_pada ASC";
    $stmt_pending = $conn->prepare($query_pending);
    $stmt_pending->execute();
    $pending_users = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

    // Ambil akun yang sudah aktif, sekarang termasuk kolom 'budget'
    $query_active = "SELECT id, username, role, dibuat_pada, last_login, email, budget FROM users WHERE role = 'user' AND status = 'accepted' ORDER BY dibuat_pada DESC";
    $stmt_active = $conn->prepare($query_active);
    $stmt_active->execute();
    $active_users = $stmt_active->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error mengambil data dari database: " . $e->getMessage());
}

// Tentukan judul halaman
$page_title = "Manajemen Akun Eskul";
$current_page = 'manage_users';

// Ambil pesan alert dari session
$temp_alert_json = 'null';
if (isset($_SESSION['temp_alert_message'])) {
    $temp_alert_json = json_encode($_SESSION['temp_alert_message']);
    unset($_SESSION['temp_alert_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | SIPENG-EVENT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sweetalert2.min.css">
    <style>
        :root {
            /* Tema Terang (Default) */
            --primary-color: #3498db;
            --primary-light: #5dade2;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --secondary-light: #34495e;
            --secondary-dark: #1c2833;
            --accent-color: #1abc9c;
            --accent-hover: #16a085;
            --danger-color: #e74c3c;
            --danger-hover: #c0392b;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #3498db;
            
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-bg: #ffffff;
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e1e8ed;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #3498db, #2980b9);
            --gradient-secondary: linear-gradient(135deg, #2c3e50, #34495e);
            --gradient-accent: linear-gradient(135deg, #1abc9c, #16a085);
            --gradient-success: linear-gradient(135deg, #2ecc71, #27ae60);
            --gradient-danger: linear-gradient(135deg, #e74c3c, #c0392b);
            --gradient-warning: linear-gradient(135deg, #f39c12, #e67e22);
        }

        /* Tema Gelap */
        [data-theme="dark"] {
            --primary-color: #3498db;
            --primary-light: #5dade2;
            --primary-dark: #2980b9;
            --secondary-color: #ecf0f1;
            --secondary-light: #bdc3c7;
            --secondary-dark: #95a5a6;
            --accent-color: #1abc9c;
            --accent-hover: #16a085;
            --danger-color: #e74c3c;
            --danger-hover: #c0392b;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            
            --bg-color: #1a1a2e;
            --card-bg: #16213e;
            --sidebar-bg: #0f3460;
            --text-color: #ecf0f1;
            --text-light: #bdc3c7;
            --border-color: #2c3e50;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            color: var(--text-color);
        }
        
        .dashboard-container {
            display: flex;
            width: 100%;
        }

        .sidebar {
            width: 250px;
            background: var(--gradient-secondary);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo img {
            max-width: 150px;
            transition: transform 0.3s ease;
        }

        .sidebar .logo img:hover {
            transform: scale(1.05);
        }

        .sidebar nav ul {
            list-style: none;
        }

        .sidebar nav ul li {
            margin-bottom: 8px;
        }
        
        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar nav ul li a:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .sidebar nav ul li a:hover:before {
            left: 100%;
        }
        
        .sidebar nav ul li a i, 
        .sidebar .logout a i {
            margin-right: 10px;
            font-size: 1.2em;
            width: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .sidebar nav ul li a:hover i, 
        .sidebar nav ul li.active a i {
            transform: scale(1.2);
        }

        .sidebar nav ul li a:hover, 
        .sidebar nav ul li.active a {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .logout {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .logout a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            border-radius: 8px;
            transition: var(--transition);
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .sidebar .logout a:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .sidebar .logout a:hover:before {
            left: 100%;
        }

        .sidebar .logout a:hover {
            background-color: var(--danger-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .theme-toggle:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .theme-toggle:hover:before {
            left: 100%;
        }

        .theme-toggle:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .theme-toggle i {
            font-size: 1.2em;
            transition: transform 0.5s ease;
        }

        .theme-toggle:hover i {
            transform: rotate(180deg);
        }

        .theme-text {
            font-size: 0.9em;
        }

        .main-content {
            margin-left: 250px;
            flex-grow: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--card-bg);
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .header:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .header h1 {
            margin: 0;
            font-size: 1.8em;
            color: var(--text-color);
            font-weight: 600;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header .user-info span {
            font-weight: 600;
            color: var(--text-color);
            padding: 10px 20px;
            background-color: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .content-area {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        .content-area:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .content-area h2 {
            font-size: 1.8em;
            margin-bottom: 25px;
            color: var(--text-color);
            position: relative;
            padding-bottom: 15px;
        }

        .content-area h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
        }

        table th, table td {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        table th {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table tbody tr {
            transition: var(--transition);
        }

        table tbody tr:hover {
            background-color: var(--bg-color);
            transform: translateX(5px);
        }

        .btn {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            color: #fff;
            font-size: 0.85em;
            margin-right: 8px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover:before {
            left: 100%;
        }

        .btn-danger { 
            background: var(--gradient-danger);
        }
        .btn-danger:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }
        
        .btn-success { 
            background: var(--gradient-success);
        }
        .btn-success:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }
        
        .btn-warning { 
            background: var(--gradient-warning);
            color: #fff;
        }
        .btn-warning:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }

        .btn-primary { 
            background: var(--gradient-primary);
        }
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
            position: relative;
            border: 1px solid var(--border-color);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger-color);
            transform: rotate(90deg);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }

        .modal-btn {
            cursor: pointer;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
            min-width: 80px;
        }

        .modal-btn-confirm-delete {
            background: var(--gradient-danger);
            color: white;
        }

        .modal-btn-confirm-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .modal-btn-confirm-accept {
            background: var(--gradient-success);
            color: white;
        }

        .modal-btn-confirm-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }

        .modal-btn-confirm-reject {
            background: var(--gradient-warning);
            color: white;
        }

        .modal-btn-confirm-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }

        .modal-btn-cancel {
            background: var(--gradient-secondary);
            color: white;
        }

        .modal-btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        }

        /* Budget Form Styling */
        .budget-form {
            margin-top: 20px;
            max-width: 600px;
            padding: 25px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background-color: var(--bg-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        /* SweetAlert2 custom styling */
        .swal2-confirm-override {
            background: var(--gradient-primary) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3) !important;
            transition: var(--transition) !important;
        }
        .swal2-confirm-override:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4) !important;
        }
        .swal2-cancel-override {
            background: var(--gradient-secondary) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3) !important;
            transition: var(--transition) !important;
        }
        .swal2-cancel-override:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.4) !important;
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .btn {
                margin-bottom: 5px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-area:nth-child(1) { animation-delay: 0.1s; }
        .content-area:nth-child(2) { animation-delay: 0.2s; }
        .content-area:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <img src="../assets/logo.png" alt="Logo SIPENG-EVENT">
            </div>
            
            <div class="theme-toggle" id="themeToggle">
                <div class="theme-text">Mode Gelap</div>
                <i class="fas fa-moon"></i>
            </div>
            
            <nav>
                <ul>
                    <li>
                        <a href="admin.php?page=dashboard_overview"><i class="fas fa-chart-line"></i> Dashboard Admin</a>
                    </li>
                    <li>
                        <a href="admin.php?page=manage_proposals"><i class="fas fa-file-invoice"></i> Manajemen Pengajuan</a>
                    </li>
                    <li class="active">
                        <a href="manage_users.php"><i class="fas fa-users"></i> Manajemen Pengguna</a>
                    </li>
                    <li>
                        <a href="../profile/profile.php"><i class="fas fa-user-circle"></i> Profil</a>
                    </li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" id="logoutBtnSidebar"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1><?= $page_title ?></h1>
                <div class="user-info">
                    <span><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>

            <div class="content-area">
                <h2><i class="fas fa-user-clock"></i> Akun Baru Menunggu Persetujuan</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Akun Eskul</th>
                            <th>Email</th> 
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_users) > 0) : ?>
                            <?php foreach ($pending_users as $row_user) : ?>
                                <tr>
                                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($row_user['username']) ?></td>
                                    <td><i class="fas fa-envelope"></i> <?= htmlspecialchars($row_user['email']) ?></td>
                                    <td><i class="fas fa-calendar"></i> <?= date('d M Y H:i', strtotime($row_user['dibuat_pada'])) ?></td>
                                    <td>
                                        <button onclick="showActionModal('accept_user', '<?= htmlspecialchars($row_user['id']) ?>', '<?= htmlspecialchars($row_user['username']) ?>')" class="btn btn-success">
                                            <i class="fas fa-check"></i> Terima
                                        </button>
                                        <button onclick="showActionModal('reject_user', '<?= htmlspecialchars($row_user['id']) ?>', '<?= htmlspecialchars($row_user['username']) ?>')" class="btn btn-warning">
                                            <i class="fas fa-times"></i> Tolak
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-user-check"></i>
                                    <p>Tidak ada akun baru yang menunggu persetujuan.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-area">
                <h2><i class="fas fa-user-check"></i> Daftar Pengguna Aktif</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th> 
                            <th>Budget</th>
                            <th>Tanggal Dibuat</th>
                            <th>Terakhir Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($active_users) > 0) : ?>
                            <?php foreach ($active_users as $row_user) : ?>
                                <tr>
                                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($row_user['username']) ?></td>
                                    <td><i class="fas fa-envelope"></i> <?= htmlspecialchars($row_user['email']) ?></td>
                                    <td><i class="fas fa-wallet"></i> Rp <?= number_format($row_user['budget'] ?? 0, 2, ',', '.') ?></td>
                                    <td><i class="fas fa-calendar-plus"></i> <?= date('d M Y H:i', strtotime($row_user['dibuat_pada'])) ?></td>
                                    <td><i class="fas fa-sign-in-alt"></i> <?= $row_user['last_login'] ? date('d M Y H:i', strtotime($row_user['last_login'])) : 'Belum pernah' ?></td>
                                    <td>
                                        <button onclick="showBudgetModal('<?= htmlspecialchars($row_user['username']) ?>', <?= $row_user['budget'] ?? 0 ?>)" class="btn btn-primary">
                                            <i class="fas fa-coins"></i> Atur Budget
                                        </button>
                                        <button onclick="showActionModal('delete_user', '<?= htmlspecialchars($row_user['username']) ?>', '<?= htmlspecialchars($row_user['username']) ?>')" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>Tidak ada pengguna aktif.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal untuk konfirmasi aksi -->
    <div class="modal" id="actionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Konfirmasi Aksi</h3>
                <button class="close-modal" onclick="closeModal('actionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Apakah Anda yakin ingin melakukan aksi ini?</p>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('actionModal')">Batal</button>
                <form id="actionForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" id="actionInput">
                    <input type="hidden" name="id" id="idInput">
                    <button type="submit" class="modal-btn" id="confirmButton">Konfirmasi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal untuk mengatur budget -->
    <div class="modal" id="budgetModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atur Budget Eskul</h3>
                <button class="close-modal" onclick="closeModal('budgetModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="budgetForm" method="POST">
                    <input type="hidden" name="action" value="set_budget">
                    <input type="hidden" name="username" id="budgetUsername">
                    
                    <div class="form-group">
                        <label for="new_budget">Jumlah Budget (Rp):</label>
                        <input type="number" 
                               class="form-control" 
                               id="new_budget" 
                               name="new_budget" 
                               min="0" 
                               step="1000" 
                               required 
                               placeholder="Masukkan jumlah budget">
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('budgetModal')">Batal</button>
                        <button type="submit" class="modal-btn modal-btn-confirm-accept">Simpan Budget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/sweetalert2.all.min.js"></script>
    <script>
        // Fungsi untuk menampilkan modal aksi
        function showActionModal(action, id, username) {
            const modal = document.getElementById('actionModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const actionInput = document.getElementById('actionInput');
            const idInput = document.getElementById('idInput');
            const confirmButton = document.getElementById('confirmButton');
            const actionForm = document.getElementById('actionForm');

            let title = '';
            let message = '';
            let buttonClass = '';
            let buttonText = '';

            switch(action) {
                case 'delete_user':
                    title = 'Hapus Akun Pengguna';
                    message = `Apakah Anda yakin ingin menghapus akun <strong>${username}</strong>? Tindakan ini akan menghapus semua data terkait termasuk pengajuan event dan tidak dapat dikembalikan!`;
                    buttonClass = 'modal-btn-confirm-delete';
                    buttonText = 'Hapus Akun';
                    break;
                
                case 'accept_user':
                    title = 'Terima Akun Pengguna';
                    message = `Apakah Anda yakin ingin menerima akun <strong>${username}</strong>? Pengguna akan dapat login dan mengajukan event. Email notifikasi akan dikirim.`;
                    buttonClass = 'modal-btn-confirm-accept';
                    buttonText = 'Terima Akun';
                    break;
                
                case 'reject_user':
                    title = 'Tolak Akun Pengguna';
                    message = `Apakah Anda yakin ingin menolak akun <strong>${username}</strong>? Semua pengajuan terkait juga akan dihapus.`;
                    buttonClass = 'modal-btn-confirm-reject';
                    buttonText = 'Tolak Akun';
                    break;
            }

            modalTitle.textContent = title;
            modalMessage.innerHTML = message;
            actionInput.value = action;
            idInput.value = id;
            
            // Reset dan set ulang tombol konfirmasi
            confirmButton.className = 'modal-btn ' + buttonClass;
            confirmButton.textContent = buttonText;

            modal.style.display = 'flex';
        }

        // Fungsi untuk menampilkan modal budget
        function showBudgetModal(username, currentBudget) {
            const modal = document.getElementById('budgetModal');
            const budgetUsername = document.getElementById('budgetUsername');
            const newBudget = document.getElementById('new_budget');

            budgetUsername.value = username;
            newBudget.value = currentBudget;
            newBudget.focus();

            modal.style.display = 'flex';
        }

        // Fungsi untuk menutup modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tutup modal ketika klik di luar konten modal
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Toggle tema gelap/terang
        document.getElementById('themeToggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // Update teks dan ikon
            const themeText = this.querySelector('.theme-text');
            const themeIcon = this.querySelector('i');
            
            if (newTheme === 'dark') {
                themeText.textContent = 'Mode Terang';
                themeIcon.className = 'fas fa-sun';
            } else {
                themeText.textContent = 'Mode Gelap';
                themeIcon.className = 'fas fa-moon';
            }
            
            // Simpan preferensi tema di localStorage
            localStorage.setItem('theme', newTheme);
        });

        // Load tema yang disimpan
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Update teks dan ikon tema berdasarkan tema yang disimpan
        const themeToggle = document.getElementById('themeToggle');
        const themeText = themeToggle.querySelector('.theme-text');
        const themeIcon = themeToggle.querySelector('i');

        if (savedTheme === 'dark') {
            themeText.textContent = 'Mode Terang';
            themeIcon.className = 'fas fa-sun';
        } else {
            themeText.textContent = 'Mode Gelap';
            themeIcon.className = 'fas fa-moon';
        }

        // SweetAlert2 untuk notifikasi
        const tempAlertMessage = <?= $temp_alert_json ?>;
        if (tempAlertMessage !== null) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: tempAlertMessage.type,
                title: tempAlertMessage.title,
                text: tempAlertMessage.text,
                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
            });
        }

        // Konfirmasi logout
        document.getElementById('logoutBtnSidebar').addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                confirmButtonClass: 'swal2-confirm-override',
                cancelButtonClass: 'swal2-cancel-override',
                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../auth/logout.php';
                }
            });
        });

        // Validasi form budget
        document.getElementById('budgetForm').addEventListener('submit', function(e) {
            const budgetInput = document.getElementById('new_budget');
            const budgetValue = parseFloat(budgetInput.value);
            
            if (budgetValue < 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error!',
                    text: 'Budget tidak boleh negatif.',
                    icon: 'error',
                    confirmButtonClass: 'swal2-confirm-override',
                    background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                    color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                });
                budgetInput.focus();
            }
        });

        // Animasi hover untuk semua tombol
        document.querySelectorAll('.btn, .modal-btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Responsive sidebar untuk mobile
        function handleResponsive() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 768) {
                sidebar.style.width = '100%';
                sidebar.style.height = 'auto';
                sidebar.style.position = 'relative';
                mainContent.style.marginLeft = '0';
            } else {
                sidebar.style.width = '250px';
                sidebar.style.height = '100%';
                sidebar.style.position = 'fixed';
                mainContent.style.marginLeft = '250px';
            }
        }

        // Jalankan saat load dan resize
        window.addEventListener('load', handleResponsive);
        window.addEventListener('resize', handleResponsive);
    </script>
</body>
</html>