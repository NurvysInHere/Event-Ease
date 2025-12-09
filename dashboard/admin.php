<?php
session_start();

// Pastikan pengguna sudah login dan memiliki peran 'admin'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Sertakan file koneksi database menggunakan PDO
require_once '../config/db.php';

// Tentukan halaman yang akan dimuat
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard_overview';

// Variabel untuk data pengajuan jika halaman manajemen pengajuan yang dipilih
$result_pengajuan = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0
];

if ($page === 'manage_proposals') {
    try {
        // PERBAIKAN: Gunakan SELECT * atau sebutkan kolom yang sesuai dengan database
        $query_pengajuan = "SELECT * FROM event_pengajuan ORDER BY tanggal_pengajuan DESC";
        $stmt_pengajuan = $conn->prepare($query_pengajuan);
        $stmt_pengajuan->execute();
        $result_pengajuan = $stmt_pengajuan->fetchAll(PDO::FETCH_ASSOC);

        // Debug: Lihat struktur data yang diterima
        // echo "<pre>"; print_r($result_pengajuan); echo "</pre>";

        // Hitung statistik
        $stats['total'] = count($result_pengajuan);
        foreach ($result_pengajuan as $pengajuan) {
            if (isset($pengajuan['status'])) {
                $stats[$pengajuan['status']]++;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Error!', 'text' => 'Gagal memuat data pengajuan: ' . htmlspecialchars($e->getMessage())];
    }
}

// Tentukan judul halaman berdasarkan page yang aktif
$page_title = "";
switch ($page) {
    case 'dashboard_overview':
        $page_title = "Dashboard Admin";
        break;
    case 'manage_proposals':
        $page_title = "Manajemen Pengajuan Event";
        break;
    case 'manage_users':
        $page_title = "Manajemen Pengguna";
        break;
    default:
        $page_title = "Dashboard Admin";
        break;
}

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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.total:before { background: var(--gradient-primary); }
        .stat-card.pending:before { background: var(--gradient-warning); }
        .stat-card.accepted:before { background: var(--gradient-success); }
        .stat-card.rejected:before { background: var(--gradient-danger); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 0.9em;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card p {
            font-size: 2.2em;
            font-weight: 700;
            margin: 0;
        }

        .stat-card.total p { color: var(--primary-color); }
        .stat-card.pending p { color: var(--warning-color); }
        .stat-card.accepted p { color: var(--success-color); }
        .stat-card.rejected p { color: var(--danger-color); }

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

        .btn-info { 
            background: var(--gradient-secondary);
        }
        .btn-info:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
        }

        /* Status Badges */
        .status {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status.pending { 
            background: var(--gradient-warning);
            color: white;
        }
        .status.accepted { 
            background: var(--gradient-success);
            color: white;
        }
        .status.rejected { 
            background: var(--gradient-danger);
            color: white;
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

        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
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

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Date styling */
        .date-info {
            font-size: 0.9em;
            color: var(--text-light);
        }

        .date-info i {
            margin-right: 5px;
            color: var(--primary-color);
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

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                    <li class="<?= ($page == 'dashboard_overview') ? 'active' : '' ?>">
                        <a href="admin.php?page=dashboard_overview"><i class="fas fa-chart-line"></i> Dashboard Admin</a>
                    </li>
                    <li class="<?= ($page == 'manage_proposals') ? 'active' : '' ?>">
                        <a href="admin.php?page=manage_proposals"><i class="fas fa-file-invoice"></i> Manajemen Pengajuan</a>
                    </li>
                    <li class="<?= ($page == 'manage_users') ? 'active' : '' ?>">
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

            <?php if ($page === 'manage_proposals'): ?>
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3>Total Pengajuan</h3>
                    <p><?= $stats['total'] ?></p>
                </div>
                <div class="stat-card pending">
                    <h3>Menunggu</h3>
                    <p><?= $stats['pending'] ?></p>
                </div>
                <div class="stat-card accepted">
                    <h3>Disetujui</h3>
                    <p><?= $stats['accepted'] ?></p>
                </div>
                <div class="stat-card rejected">
                    <h3>Ditolak</h3>
                    <p><?= $stats['rejected'] ?></p>
                </div>
            </div>

            <div class="content-area">
                <h2><i class="fas fa-file-invoice"></i> Daftar Pengajuan Event</h2>
                
                <!-- Search and Filter -->
                <div class="search-filter">
                    <div class="search-box">
                        <input type="text" id="searchProposals" class="form-control" placeholder="Cari nama event atau pengaju...">
                    </div>
                </div>

               <table id="proposalsTable">
    <thead>
        <tr>
            <th>Nama Event</th>
            <th>Pengaju</th>
            <th>Budget</th>
            <th>Tanggal Pengajuan</th>
            <th>Tanggal Pelaksanaan</th>
            <th>Proposal</th>
            <th>LPJ</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($result_pengajuan) > 0) : ?>
            <?php foreach ($result_pengajuan as $row) : ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['nama_event']) ?></strong></td>
                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($row['pengaju']) ?></td>
                    <td><i class="fas fa-wallet"></i> Rp <?= number_format($row['budget'], 0, ',', '.') ?></td>
                    <td class="date-info">
                        <i class="fas fa-calendar-plus"></i> 
                        <?= $row['tanggal_pengajuan'] ? date('d M Y', strtotime($row['tanggal_pengajuan'])) : '-' ?>
                    </td>
                    <!-- PERBAIKAN: Tampilkan nilai default untuk tanggal pelaksanaan -->
                    <td class="date-info">
    <i class="fas fa-calendar-check"></i> 
    <?= !empty($row['tanggal_mulai']) ? date('d M Y', strtotime($row['tanggal_mulai'])) : 'Belum Ditentukan' ?>
</td>
                    <td>
                        <a href="../uploads/<?= htmlspecialchars($row['proposal_file']) ?>" target="_blank" class="btn btn-info">
                            <i class="fas fa-file-alt"></i> Lihat
                        </a>
                    </td>
                    <td>
                        <?php if (!empty($row['lpj_file'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($row['lpj_file']) ?>" target="_blank" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Lihat
                            </a>
                        <?php else: ?>
                            <em style="color: var(--text-light); font-size: 0.9em;">Belum diunggah</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status <?= htmlspecialchars($row['status']) ?>">
                            <?= ucfirst(htmlspecialchars($row['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'pending') : ?>
                            <button type="button" class="btn btn-success" 
                                    onclick="showActionModal('Yakin ingin menerima pengajuan ini?', '../proposal/action.php?id=<?= $row['id'] ?>&aksi=accept', false, true)">
                                    <i class="fas fa-check"></i> Setujui
                            </button>
                            <button type="button" class="btn btn-warning" 
                                    onclick="showActionModal('Yakin ingin menolak pengajuan ini? Budget akan dikembalikan!', '../proposal/action.php?id=<?= $row['id'] ?>&aksi=reject', true, true)">
                                    <i class="fas fa-times"></i> Tolak
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger" 
                                onclick="showActionModal('Yakin ingin menghapus pengajuan ini secara permanen? Ini tidak bisa dibatalkan!', '../proposal/delete.php?id=<?= $row['id'] ?>', true, false)">
                                <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="9" class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <p>Tidak ada pengajuan event.</p>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
            </div>
            <?php elseif ($page === 'dashboard_overview'): ?>
                <?php include 'dashboard_overview.php'; ?>
            <?php endif; ?>
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
                <p id="modalMessage"></p>
                <div class="form-group" id="commentBox" style="display:none; margin-top: 20px;">
                    <label for="adminComment">Alasan/Komentar (Opsional):</label>
                    <textarea id="adminComment" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal('actionModal')">Batal</button>
                <button class="modal-btn modal-btn-confirm" id="confirmBtn">Ya</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/sweetalert2.all.min.js"></script>
    <script>
        let currentActionUrl = '';
        let currentIsDanger = false;
        
        // Fungsi untuk menampilkan modal aksi
        function showActionModal(message, actionUrl, isDanger = true, showCommentInput = false) {
            document.getElementById('modalMessage').innerText = message;
            currentActionUrl = actionUrl;
            currentIsDanger = isDanger;
            
            const commentBox = document.getElementById('commentBox');
            const adminComment = document.getElementById('adminComment');
            
            if (showCommentInput) {
                commentBox.style.display = 'block';
                adminComment.value = '';
            } else {
                commentBox.style.display = 'none';
                adminComment.value = '';
            }

            document.getElementById('actionModal').style.display = 'flex';
        }

        document.getElementById('confirmBtn').addEventListener('click', function() {
            let finalActionUrl = currentActionUrl;
            const commentBox = document.getElementById('commentBox');
            if (commentBox.style.display === 'block') {
                const adminComment = document.getElementById('adminComment').value.trim();
                if (adminComment !== '') {
                    finalActionUrl += '&comment=' + encodeURIComponent(adminComment);
                }
            }
            window.location.href = finalActionUrl;
        });

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

        // Search functionality
        document.getElementById('searchProposals').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#proposalsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
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