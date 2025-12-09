<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_id = $_SESSION['username'];

// --- Ambil Data Statistik untuk User ---
$total_proposals = 0;
$budget_terpakai = 0;
$total_accepted = 0;
$total_rejected = 0;
$total_pending = 0;

try {
    // Total pengajuan yang dibuat user
    $query_total_proposals = "SELECT COUNT(*) AS total FROM event_pengajuan WHERE pengaju = :user_id";
    $stmt_total_proposals = $conn->prepare($query_total_proposals);
    $stmt_total_proposals->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_total_proposals->execute();
    $total_proposals = $stmt_total_proposals->fetch(PDO::FETCH_ASSOC)['total'];

    // Budget yang telah dihabiskan (hanya dari proposal yang Accepted)
    $query_budget_terpakai = "SELECT SUM(budget) AS total_budget FROM event_pengajuan WHERE pengaju = :user_id AND status = 'accepted'";
    $stmt_budget_terpakai = $conn->prepare($query_budget_terpakai);
    $stmt_budget_terpakai->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_budget_terpakai->execute();
    $budget_terpakai_result = $stmt_budget_terpakai->fetch(PDO::FETCH_ASSOC)['total_budget'];
    $budget_terpakai = $budget_terpakai_result ? (float)$budget_terpakai_result : 0;

    // Jumlah proposal diterima
    $query_accepted = "SELECT COUNT(*) AS total FROM event_pengajuan WHERE pengaju = :user_id AND status = 'accepted'";
    $stmt_accepted = $conn->prepare($query_accepted);
    $stmt_accepted->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_accepted->execute();
    $total_accepted = $stmt_accepted->fetch(PDO::FETCH_ASSOC)['total'];

    // Jumlah proposal ditolak
    $query_rejected = "SELECT COUNT(*) AS total FROM event_pengajuan WHERE pengaju = :user_id AND status = 'rejected'";
    $stmt_rejected = $conn->prepare($query_rejected);
    $stmt_rejected->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_rejected->execute();
    $total_rejected = $stmt_rejected->fetch(PDO::FETCH_ASSOC)['total'];

    // Jumlah proposal pending
    $query_pending = "SELECT COUNT(*) AS total FROM event_pengajuan WHERE pengaju = :user_id AND status = 'pending'";
    $stmt_pending = $conn->prepare($query_pending);
    $stmt_pending->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_pending->execute();
    $total_pending = $stmt_pending->fetch(PDO::FETCH_ASSOC)['total'];

    // Mengambil data pengajuan event milik pengguna untuk tabel
    $query_riwayat = "SELECT id, nama_event, budget, proposal_file, lpj_file, status, tanggal_pengajuan FROM event_pengajuan WHERE pengaju = :user_id ORDER BY id DESC";
    $stmt_riwayat = $conn->prepare($query_riwayat);
    $stmt_riwayat->bindParam(':user_id', $user_id, PDO::PARAM_STR);
    $stmt_riwayat->execute();
    $result_pengajuan = $stmt_riwayat->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Tambahkan logika untuk menampilkan pesan SweetAlert dari session
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
    <title>Dashboard User | SIPENG-EVENT</title>
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
            --gradient-info: linear-gradient(135deg, #3498db, #2980b9);
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

        .header .user-info a {
            text-decoration: none;
            color: #ffffff;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            transition: var(--transition);
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .header .user-info a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        /* Gaya untuk kartu statistik */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .stat-card:hover:before {
            left: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .stat-card.total-proposals {
            background: var(--gradient-secondary);
        }
        .stat-card.budget-terpakai {
            background: var(--gradient-success);
        }
        .stat-card.accepted-proposals {
            background: var(--gradient-accent);
        }
        .stat-card.rejected-proposals {
            background: var(--gradient-danger);
        }
        .stat-card.pending-proposals {
            background: var(--gradient-warning);
        }

        .stat-card .icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .stat-card .value {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 0.9em;
            opacity: 0.9;
            font-weight: 500;
        }

        .content-area {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
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
        
        .status {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
            font-size: 0.8em;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status.pending { 
            background: var(--gradient-warning);
            color: #fff;
        }
        .status.accepted { 
            background: var(--gradient-success);
        }
        .status.rejected { 
            background: var(--gradient-danger);
        }
        
        .btn-action {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            color: #fff;
            font-size: 0.85em;
            margin-right: 5px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-action.cancel {
            background: var(--gradient-danger);
        }
        
        .btn-action.cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
        }

        .btn-action.upload-lpj {
            background: var(--gradient-primary);
        }
        
        .btn-action.upload-lpj:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-action.view {
            background: var(--gradient-info);
        }
        
        .btn-action.view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .file-link {
            color: var(--primary-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .file-link:hover {
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Modal untuk Upload LPJ */
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

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .modal-form label {
            text-align: left;
            font-weight: 500;
            color: var(--text-color);
        }

        .modal-form input[type="file"] {
            border: 2px dashed var(--border-color);
            padding: 15px;
            border-radius: 8px;
            background-color: var(--bg-color);
            transition: var(--transition);
            cursor: pointer;
        }

        .modal-form input[type="file"]:hover {
            border-color: var(--primary-color);
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-btn {
            cursor: pointer;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            transition: var(--transition);
        }

        .modal-btn-confirm {
            background: var(--gradient-primary);
            color: white;
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .modal-btn-cancel {
            background: var(--gradient-secondary);
            color: white;
        }

        .modal-btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
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

            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Animasi untuk konten */
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

        .dashboard-stats,
        .content-area {
            animation: fadeInUp 0.6s ease-out;
        }

        .dashboard-stats .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-stats .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-stats .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-stats .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .dashboard-stats .stat-card:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo">
                <img src="<?= BASE_URL ?>assets/logo.png" alt="Logo">
            </div>
            
            <div class="theme-toggle" id="themeToggle">
                <div class="theme-text">Mode Gelap</div>
                <i class="fas fa-moon"></i>
            </div>
            
            <nav>
                <ul>
                    <li class="active"><a href="<?= BASE_URL ?>dashboard/user.php"><i class="fas fa-home"></i> Dashboard User</a></li>
                    <li><a href="<?= BASE_URL ?>proposal/upload.php"><i class="fas fa-file-upload"></i> Ajukan Event</a></li>
                    <li><a href="<?= BASE_URL ?>profile/profile.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Dashboard User</h1>
                <div class="user-info">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                    <a href="<?= BASE_URL ?>proposal/upload.php">
                        <i class="fas fa-plus-circle"></i> Ajukan Event Baru
                    </a>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card total-proposals">
                    <i class="fas fa-clipboard-list icon"></i>
                    <div class="value"><?= $total_proposals ?></div>
                    <div class="label">Total Pengajuan</div>
                </div>
                <div class="stat-card budget-terpakai">
                    <i class="fas fa-wallet icon"></i>
                    <div class="value">Rp <?= number_format($budget_terpakai, 0, ',', '.') ?></div>
                    <div class="label">Budget Terpakai</div>
                </div>
                <div class="stat-card accepted-proposals">
                    <i class="fas fa-check-circle icon"></i>
                    <div class="value"><?= $total_accepted ?></div>
                    <div class="label">Pengajuan Diterima</div>
                </div>
                <div class="stat-card rejected-proposals">
                    <i class="fas fa-times-circle icon"></i>
                    <div class="value"><?= $total_rejected ?></div>
                    <div class="label">Pengajuan Ditolak</div>
                </div>
                <div class="stat-card pending-proposals">
                    <i class="fas fa-clock icon"></i>
                    <div class="value"><?= $total_pending ?></div>
                    <div class="label">Pengajuan Pending</div>
                </div>
            </div>

            <div class="content-area">
                <h2>Riwayat Pengajuan Event</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Event</th>
                            <th>Budget</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Proposal</th>
                            <th>LPJ</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($result_pengajuan) > 0): ?>
                            <?php foreach ($result_pengajuan as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_event']) ?></td>
                                    <td>Rp <?= number_format($row['budget'], 0, ',', '.') ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal_pengajuan'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($row['proposal_file']) ?>" target="_blank" class="file-link">
                                            <i class="fas fa-file-pdf"></i> Lihat
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['lpj_file'])): ?>
                                            <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($row['lpj_file']) ?>" target="_blank" class="file-link">
                                                <i class="fas fa-file-pdf"></i> Lihat
                                            </a>
                                        <?php else: ?>
                                            <em style="color: var(--text-light);">Belum diunggah</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?= htmlspecialchars($row['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'pending') : ?>
                                            <button data-id="<?= $row['id'] ?>" class="btn-action cancel cancel-proposal-btn">
                                                <i class="fas fa-times"></i> Batalkan
                                            </button>
                                        <?php elseif ($row['status'] == 'accepted' && empty($row['lpj_file'])): ?>
                                            <button data-id="<?= $row['id'] ?>" class="btn-action upload-lpj upload-lpj-link">
                                                <i class="fas fa-upload"></i> Unggah LPJ
                                            </button>
                                        <?php elseif ($row['status'] == 'accepted' && !empty($row['lpj_file'])): ?>
                                            <span style="color: var(--success-color);">
                                                <i class="fas fa-check"></i> LPJ Terunggah
                                            </span>
                                        <?php else: ?>
                                            <em style="color: var(--text-light);">Tidak ada aksi</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-light);">
                                    <i class="fas fa-inbox" style="font-size: 3em; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                                    Belum ada pengajuan event.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal untuk Upload LPJ -->
    <div class="modal" id="lpjModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Unggah Laporan Pertanggungjawaban (LPJ)</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form action="<?= BASE_URL ?>proposal/upload_lpj.php" method="POST" enctype="multipart/form-data" class="modal-form">
                <input type="hidden" name="id_pengajuan" id="lpjIdInput">
                <label for="lpj_file">Pilih file LPJ (PDF):</label>
                <input type="file" name="lpj_file" id="lpj_file" accept=".pdf" required>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn modal-btn-cancel" id="cancelLpjBtn">Batal</button>
                    <button type="submit" class="modal-btn modal-btn-confirm">Unggah</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/sweetalert2.all.min.js"></script>
    <script>
        // Fungsi untuk toggle tema gelap/terang
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        const themeText = themeToggle.querySelector('.theme-text');
        
        // Cek preferensi tema yang disimpan
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
        updateThemeToggle(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeToggle(newTheme);
        });
        
        function updateThemeToggle(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Mode Terang';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = 'Mode Gelap';
            }
        }

        // Data alert dari PHP (untuk error dari halaman lain)
        const tempAlertConfig = <?= $temp_alert_json ?>;

        // Tampilkan alert dari tempAlertConfig jika ada
        if (tempAlertConfig && tempAlertConfig.type) {
            Swal.fire({
                title: tempAlertConfig.title,
                html: tempAlertConfig.text,
                icon: tempAlertConfig.type,
                confirmButtonText: 'Oke',
                customClass: {'confirmButton': 'swal2-confirm-override'}
            });
        }

        // Handle Logout dengan SweetAlert
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Yakin ingin logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'swal2-confirm-override',
                    cancelButton: 'swal2-cancel-override'
                },
                background: 'var(--card-bg)',
                color: 'var(--text-color)'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= BASE_URL ?>auth/logout.php';
                }
            });
        });

        // Handle Batalkan Pengajuan dengan SweetAlert
        document.querySelectorAll('.cancel-proposal-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Batalkan Pengajuan',
                    text: 'Yakin ingin membatalkan pengajuan ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Tidak',
                    customClass: {
                        confirmButton: 'swal2-confirm-override',
                        cancelButton: 'swal2-cancel-override'
                    },
                    background: 'var(--card-bg)',
                    color: 'var(--text-color)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `<?= BASE_URL ?>proposal/cancel_user_proposal.php?id=${eventId}`;
                    }
                });
            });
        });

        // LPJ Modal Logic
        const lpjModal = document.getElementById('lpjModal');
        const lpjIdInput = document.getElementById('lpjIdInput');
        const cancelLpjBtn = document.getElementById('cancelLpjBtn');
        const closeModal = document.querySelector('.close-modal');

        document.querySelectorAll('.upload-lpj-link').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const eventId = this.getAttribute('data-id');
                lpjIdInput.value = eventId;
                lpjModal.style.display = 'flex';
            });
        });

        cancelLpjBtn.addEventListener('click', function() {
            lpjModal.style.display = 'none';
        });

        closeModal.addEventListener('click', function() {
            lpjModal.style.display = 'none';
        });

        // Menutup LPJ modal jika klik di luar konten modal
        window.addEventListener('click', function(event) {
            if (event.target == lpjModal) {
                lpjModal.style.display = 'none';
            }
        });

        // Animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const contentArea = document.querySelector('.content-area');
            contentArea.style.opacity = '0';
            contentArea.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                contentArea.style.transition = 'all 0.5s ease';
                contentArea.style.opacity = '1';
                contentArea.style.transform = 'translateY(0)';
            }, 300);
        });

        // Efek hover pada stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>