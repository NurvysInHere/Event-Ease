<?php
// Memulai sesi untuk mengelola status login
session_start();

// Mengimpor file koneksi database menggunakan PDO
include '../config/db.php';

// Memeriksa apakah pengguna sudah login dan memiliki peran 'user'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Mendapatkan username dari sesi untuk mencari budget yang telah ditentukan oleh admin
$current_username = $_SESSION['username'];
$user_budget_limit = 0.00; // Nilai default jika tidak ditemukan atau belum diatur

try {
    // Ambil budget yang telah diatur admin untuk user ini dari tabel 'users'
    $query_get_budget = "SELECT budget FROM users WHERE username = :username";
    $stmt_get_budget = $conn->prepare($query_get_budget);
    $stmt_get_budget->bindParam(':username', $current_username, PDO::PARAM_STR);
    $stmt_get_budget->execute();
    $user_data = $stmt_get_budget->fetch(PDO::FETCH_ASSOC);

    if ($user_data && isset($user_data['budget'])) {
        $user_budget_limit = (float) $user_data['budget'];
    }
} catch (PDOException $e) {
    // Tangani error jika gagal mengambil budget
    $_SESSION['temp_alert_message'] = ['type' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal mengambil informasi budget: ' . htmlspecialchars($e->getMessage())];
    header("Location: " . BASE_URL . "proposal/upload.php");
    exit;
}

// Ambil pesan alert dari session
$temp_alert_json = 'null';
if (isset($_SESSION['temp_alert_message'])) {
    $temp_alert_json = json_encode($_SESSION['temp_alert_message']);
    unset($_SESSION['temp_alert_message']);
}

// Data formulir untuk pre-fill (jika ada)
$form_data = [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Event | SIPENG-EVENT</title>
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
            /* Logo tetap warna asli */
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
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .form-container {
            width: 100%;
            max-width: 500px;
            background-color: var(--card-bg);
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            margin: 60px auto 0 auto;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .form-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .form-container h2 {
            font-size: 2em;
            margin-bottom: 30px;
            color: var(--text-color);
            position: relative;
            padding-bottom: 15px;
        }

        .form-container h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: var(--transition);
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }

        .form-group input[type="file"] {
            border: 2px dashed var(--border-color);
            padding: 15px;
            cursor: pointer;
            background-color: var(--bg-color);
            transition: all 0.3s ease;
        }

        .form-group input[type="file"]:hover {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .form-group input:disabled {
            background-color: var(--bg-color);
            color: var(--text-light);
            cursor: not-allowed;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            display: inline-block;
            text-align: center;
            text-decoration: none;
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

        .btn-submit {
            background: var(--gradient-primary);
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-back {
            background: var(--gradient-secondary);
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
        }

        .btn-back:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn:disabled:before {
            display: none;
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
        
        /* Animasi loading */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Floating notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: var(--gradient-success);
            color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateX(150%);
            transition: transform 0.4s ease;
            z-index: 1000;
        }

        .notification.show {
            transform: translateX(0);
        }

        /* Progress bar untuk file upload */
        .upload-progress {
            width: 100%;
            height: 5px;
            background-color: var(--border-color);
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
            display: none;
        }

        .upload-progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Responsive design */
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
            
            .form-container {
                margin-top: 20px;
                padding: 25px 20px;
            }
        }

        /* Efek ripple pada tombol */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.7);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Tooltip untuk informasi */
        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 5px;
            color: var(--primary-color);
            cursor: help;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8em;
            font-weight: normal;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
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
                    <li><a href="<?= BASE_URL ?>dashboard/user.php"><i class="fas fa-home"></i> Dashboard User</a></li>
                    <li class="active"><a href="<?= BASE_URL ?>proposal/upload.php"><i class="fas fa-file-upload"></i> Ajukan Event</a></li>
                    <li><a href="<?= BASE_URL ?>profile/profile.php"><i class="fas fa-user-circle"></i> Profil</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="form-container">
                <h2>Form Pengajuan Event</h2>
                <form method="POST" enctype="multipart/form-data" id="proposalForm">
                    <input type="hidden" name="continue_anyway" id="continueAnywayFlag" value="false">

                    <div class="form-group">
                        <label for="nama_event">
                            Nama Event
                            <span class="tooltip">
                                <i class="fas fa-info-circle"></i>
                                <span class="tooltiptext">Masukkan nama event yang jelas dan deskriptif</span>
                            </span>
                        </label>
                        <input type="text" id="nama_event" name="nama_event" placeholder="Tuliskan nama event Anda!" required 
                               value="<?= htmlspecialchars($form_data['nama_event'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="budget">
                            Anggaran Yang Diajukan (Rp)
                            <span class="tooltip">
                                <i class="fas fa-info-circle"></i>
                                <span class="tooltiptext">Anggaran harus sesuai dengan batas yang ditentukan</span>
                            </span>
                        </label>
                        <input type="number" id="budget" name="budget" placeholder="Masukan Anggaran!" min="1" step="0.01" required 
                               value="<?= htmlspecialchars($form_data['budget'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_pengajuan_display">Tanggal Pengajuan</label>
                        <input type="date" id="tanggal_pengajuan_display" 
                               value="<?= date('Y-m-d') ?>" 
                               disabled>
                        <input type="hidden" 
                               name="tanggal_pengajuan" 
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_mulai">
                            Tanggal Pelaksanaan
                            <span class="tooltip">
                                <i class="fas fa-info-circle"></i>
                                <span class="tooltiptext">Pilih tanggal pelaksanaan event di masa depan</span>
                            </span>
                        </label> 
                        <input type="date" id="tanggal_mulai" name="tanggal_mulai" required>
                    </div>

                    <div class="form-group">
                        <label for="proposal">Unggah Proposal (.pdf)</label>
                        <input type="file" id="proposal" name="proposal" accept=".pdf" required>
                        <div class="upload-progress" id="uploadProgress">
                            <div class="upload-progress-bar" id="uploadProgressBar"></div>
                        </div>
                    </div>

                    <button class="btn btn-submit" type="submit" id="submitProposalBtn">Ajukan</button>
                    <a href="<?= BASE_URL ?>dashboard/user.php" class="btn btn-back">Kembali</a>
                </form>
            </div>
        </div>
    </div>

    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i> Form berhasil disimpan!
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
            
            // Tampilkan notifikasi
            showNotification(`Mode ${newTheme === 'dark' ? 'Gelap' : 'Terang'} diaktifkan`);
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

        // Fungsi untuk menampilkan notifikasi
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Efek ripple pada tombol
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Simulasi progress bar untuk upload file
        document.getElementById('proposal').addEventListener('change', function() {
            const progressBar = document.getElementById('uploadProgressBar');
            const progressContainer = document.getElementById('uploadProgress');
            
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            
            // Simulasi progress upload
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    setTimeout(() => {
                        progressContainer.style.display = 'none';
                    }, 1000);
                }
                progressBar.style.width = progress + '%';
            }, 200);
        });

        // Validasi form real-time
        document.getElementById('budget').addEventListener('input', function() {
            const budget = parseFloat(this.value);
            if (budget > 100000000) { // Contoh validasi
                this.style.borderColor = 'var(--warning-color)';
                this.style.boxShadow = '0 0 0 3px rgba(243, 156, 18, 0.2)';
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });

        // Data alert dari PHP (untuk error awal atau dari halaman sebelumnya)
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
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= BASE_URL ?>auth/logout.php';
                }
            });
        });

        // ===================================================================
        // SweetAlert2 Integration for Proposal Submission (AJAX)
        // ===================================================================
        document.getElementById('proposalForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah submit form default

            const form = e.target;
            const formData = new FormData(form);
            const submitButton = document.getElementById('submitProposalBtn');

            // Nonaktifkan tombol submit dan tampilkan loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengajukan...';

            // Fungsi untuk mengirim proposal
            const sendProposal = (overrideWarning = false) => {
                if (overrideWarning) {
                    formData.set('continue_anyway', 'true');
                } else {
                    formData.set('continue_anyway', document.getElementById('continueAnywayFlag').value);
                }

                fetch('<?= BASE_URL ?>proposal/submit_proposal_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification('Proposal berhasil diajukan!');
                        Swal.fire({
                            title: data.title,
                            html: data.text,
                            icon: 'success',
                            confirmButtonText: 'Oke',
                            customClass: {'confirmButton': 'swal2-confirm-override'}
                        }).then(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            }
                        });
                    } else if (data.status === 'error') {
                        Swal.fire({
                            title: data.title,
                            html: data.text,
                            icon: 'error',
                            confirmButtonText: 'Oke',
                            customClass: {'confirmButton': 'swal2-confirm-override'}
                        }).then(() => {
                            // Biarkan pengguna di halaman ini untuk memperbaiki
                            if (data.text.includes('Pilih file proposal')) {
                                document.getElementById('proposal').value = '';
                            }
                        });
                    } else if (data.status === 'warning_confirm') {
                        Swal.fire({
                            title: data.title,
                            html: data.text,
                            icon: data.type,
                            showCancelButton: true,
                            confirmButtonText: data.confirmButtonText,
                            cancelButtonText: data.cancelButtonText,
                            customClass: {
                                confirmButton: 'swal2-confirm-override',
                                cancelButton: 'swal2-cancel-override'
                            },
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                sendProposal(true);
                            } else {
                                submitButton.disabled = false;
                                submitButton.innerHTML = 'Ajukan';
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error during fetch:', error);
                    Swal.fire({
                        title: 'Terjadi Kesalahan Jaringan',
                        text: 'Tidak dapat menghubungi server. Mohon coba lagi.',
                        icon: 'error',
                        confirmButtonText: 'Oke',
                        customClass: {'confirmButton': 'swal2-confirm-override'}
                    });
                })
                .finally(() => {
                    if (!overrideWarning || (data && data.status !== 'warning_confirm')) {
                         submitButton.disabled = false;
                         submitButton.innerHTML = 'Ajukan';
                    }
                });
            };

            // Panggil fungsi pengiriman proposal pertama kali
            sendProposal();
        });

        // Animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const formContainer = document.querySelector('.form-container');
            formContainer.style.opacity = '0';
            formContainer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                formContainer.style.transition = 'all 0.5s ease';
                formContainer.style.opacity = '1';
                formContainer.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>