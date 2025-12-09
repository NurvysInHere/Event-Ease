<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/logo.png">
    <title>Tentang Kami | SIPENG-EVENT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
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
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Background Pattern */
        .background-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(26, 188, 156, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 50px;
            background: var(--gradient-secondary);
            box-shadow: var(--shadow);
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 1.5em;
            transition: var(--transition);
        }

        .navbar .logo:hover {
            transform: translateY(-2px);
        }

        .navbar .logo img {
            height: 40px;
        }

        .navbar-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .navbar-links a {
            text-decoration: none;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .navbar-links a:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .navbar-links a:hover:before {
            left: 100%;
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateY(-2px);
        }

        .navbar-links a.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 100px 20px 50px;
            position: relative;
            z-index: 1;
        }

        .about-container {
            background: var(--card-bg);
            padding: 60px 50px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            max-width: 900px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .about-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .about-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .about-header h1 {
            font-size: 2.8em;
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-header .subtitle {
            font-size: 1.2em;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Content Sections */
        .content-section {
            margin-bottom: 40px;
        }

        .content-section h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            color: var(--text-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-section h2 i {
            color: var(--primary-color);
        }

        .content-section p {
            font-size: 1.1em;
            line-height: 1.8;
            color: var(--text-light);
            margin-bottom: 15px;
            text-align: justify;
        }

        /* Features List */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .feature-card {
            background: var(--bg-color);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .feature-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-accent);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 2.2em;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .feature-card h3 {
            font-size: 1.3em;
            margin-bottom: 12px;
            color: var(--text-color);
            font-weight: 600;
        }

        .feature-card p {
            font-size: 1em;
            line-height: 1.6;
            color: var(--text-light);
            margin: 0;
        }

        /* Mission List */
        .mission-list {
            list-style: none;
            padding: 0;
        }

        .mission-list li {
            position: relative;
            margin-bottom: 20px;
            padding-left: 40px;
            line-height: 1.6;
            color: var(--text-light);
        }

        .mission-list li:before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 0;
            width: 28px;
            height: 28px;
            background: var(--gradient-success);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
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

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .navbar-links {
                gap: 15px;
            }

            .navbar-links a {
                padding: 8px 15px;
                font-size: 0.9em;
            }

            .main-content {
                padding: 120px 15px 30px;
            }

            .about-container {
                padding: 40px 30px;
            }

            .about-header h1 {
                font-size: 2.2em;
            }

            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .content-section h2 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <!-- Background Pattern -->
    <div class="background-pattern"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="welcome.php" class="logo">
            <img src="assets/logo.png" alt="SIPENG-EVENT Logo">
            <span>Event Ease</span>
        </a>
        <div class="navbar-links">
            <a href="welcome.php"><i class="fas fa-home"></i> Home</a>
            <a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span class="theme-text">Mode Gelap</span>
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="about-container floating">
            <div class="about-header">
                <h1>Tentang Event Ease</h1>
                <p class="subtitle">
                    Platform Manajemen Event Ekstrakurikuler yang Inovatif dan Efisien
                </p>
            </div>

            <div class="content-section">
                <h2><i class="fas fa-rocket"></i> Apa Itu Event Ease?</h2>
                <p>
                    <strong>Event Ease</strong> adalah sebuah platform manajemen acara yang dirancang khusus 
                    untuk memudahkan Ekstrakurikuler dalam mengajukan, mengelola, dan memantau setiap event 
                    yang mereka selenggarakan. Sistem ini dibangun dengan teknologi modern untuk memberikan 
                    pengalaman yang optimal bagi semua pengguna.
                </p>
            </div>

            <div class="content-section">
                <h2><i class="fas fa-bullseye"></i> Misi & Tujuan Kami</h2>
                <p>Tujuan utama dari aplikasi ini adalah:</p>
                <ul class="mission-list">
                    <li>
                        <strong>Efisiensi Pengajuan:</strong> Mempercepat proses pengajuan event dengan format 
                        digital yang terstruktur, mengurangi penggunaan kertas, dan meminimalisir kesalahan data.
                    </li>
                    <li>
                        <strong>Transparansi Informasi:</strong> Menyediakan visibilitas penuh bagi admin dan 
                        pengurus ekstrakurikuler mengenai status pengajuan, jadwal, dan detail event yang akan datang.
                    </li>
                    <li>
                        <strong>Sentralisasi Data:</strong> Mengintegrasikan semua informasi event dalam satu tempat, 
                        memudahkan pencarian dan pelaporan.
                    </li>
                    <li>
                        <strong>Kolaborasi Mudah:</strong> Memungkinkan komunikasi dan kolaborasi yang lebih baik 
                        antara pihak ekstrakurikuler dan admin sekolah.
                    </li>
                    <li>
                        <strong>Monitoring Real-time:</strong> Memantau perkembangan event secara real-time 
                        dan memberikan notifikasi otomatis.
                    </li>
                </ul>
            </div>

            <div class="content-section">
                <h2><i class="fas fa-star"></i> Fitur Unggulan</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-upload"></i>
                        </div>
                        <h3>Pengajuan Digital</h3>
                        <p>Ajukan event secara online dengan form yang terstruktur dan mudah diisi</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Tracking Status</h3>
                        <p>Pantau status pengajuan event secara real-time dari pending hingga approved</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3>Manajemen Budget</h3>
                        <p>Kelola anggaran event dengan sistem yang transparan dan terukur</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Notifikasi Otomatis</h3>
                        <p>Dapatkan pemberitahuan instan untuk setiap update status pengajuan</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Multi-user</h3>
                        <p>Dukung kolaborasi antara admin, ketua eskul, dan anggota tim</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Keamanan Data</h3>
                        <p>Data tersimpan aman dengan sistem autentikasi dan enkripsi modern</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2><i class="fas fa-handshake"></i> Manfaat</h2>
                <p>
                    Dengan menggunakan <strong>Event Ease</strong>, sekolah dan ekstrakurikuler dapat 
                    meningkatkan efisiensi operasional, mengurangi beban administratif, dan fokus pada 
                    penyelenggaraan event yang berkualitas. Sistem ini tidak hanya mengotomatiskan proses, 
                    tetapi juga memberikan insights berharga untuk pengambilan keputusan yang lebih baik.
                </p>
            </div>
        </div>
    </div>

    <script>
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

        // Animasi untuk content sections
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe content sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            observer.observe(section);
        });

        // Observe feature cards dengan delay bertahap
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.animationDelay = `${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>