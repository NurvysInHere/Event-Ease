<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/logo.png">
    <title>Selamat Datang di Event Ease</title>
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

        .welcome-container {
            background: var(--card-bg);
            padding: 60px 50px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            max-width: 600px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 1s ease-out;
        }

        .welcome-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        h1 {
            font-size: 3.5em;
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 1.3em;
            margin-bottom: 40px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Button Container */
        .btn-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            text-transform: uppercase;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            min-width: 180px;
            justify-content: center;
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

        .btn-login {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-register {
            background: var(--gradient-secondary);
            color: white;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
        }

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
            padding: 0 20px;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: var(--text-color);
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
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
                transform: translateY(-10px);
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

            .welcome-container {
                padding: 40px 30px;
            }

            h1 {
                font-size: 2.5em;
            }

            .btn-container {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 250px;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Footer */
        .footer {
            background: var(--gradient-secondary);
            color: white;
            text-align: center;
            padding: 30px 20px;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer p {
            margin: 10px 0;
            opacity: 0.9;
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
            <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span class="theme-text">Mode Gelap</span>
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-container floating">
            <h1>Selamat Datang</h1>
            <p class="subtitle">Sistem Informasi Pengajuan Event Ekstrakurikuler</p>
            <div class="btn-container">
                <a href="auth/login.php" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
                <a href="auth/register.php" class="btn btn-register">
                    <i class="fas fa-user-plus"></i>
                    Register
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h3>Kelola Event</h3>
            <p>Ajukan dan kelola event ekstrakurikuler dengan mudah dan efisien</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <h3>Pengajuan Proposal</h3>
            <p>Upload proposal event dan LPJ dengan sistem yang terorganisir</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Kolaborasi</h3>
            <p>Bekerja sama dengan berbagai eskul dalam satu platform</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Monitoring Budget</h3>
            <p>Pantau dan kelola anggaran event secara real-time</p>
        </div>
    </div>
    <footer class="footer">
        <div class="footer-content">
            <p><strong>Event Ease</strong> - Sistem Informasi Pengajuan Event Ekstrakurikuler</p>
            <p>&copy; 2025 Event Ease. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.getElementById('themeToggle').addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            
            const themeText = this.querySelector('.theme-text');
            const themeIcon = this.querySelector('i');
            
            if (newTheme === 'dark') {
                themeText.textContent = 'Mode Terang';
                themeIcon.className = 'fas fa-sun';
            } else {
                themeText.textContent = 'Mode Gelap';
                themeIcon.className = 'fas fa-moon';
            }
            
            localStorage.setItem('theme', newTheme);
        });

        // Load tema yang disimpan
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

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

        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            observer.observe(card);
        });

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>