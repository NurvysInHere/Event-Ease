<?php
session_start();
include '../config/db.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = $_POST['login_input']; 
    $password = $_POST['password'];

    // PERBAIKAN: Gunakan positional parameter (?)
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$login_input, $login_input]); // Kirim 2 parameter dalam array
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            if ($user['status'] == 'accepted') { 
                // Perbarui last_login saat berhasil login
                // PERBAIKAN: Gunakan positional parameter juga di sini
                $update_login_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $update_login_stmt = $conn->prepare($update_login_sql);
                $update_login_stmt->execute([$user['id']]);

                $_SESSION['login'] = true;
                $_SESSION['user_id'] = $user['id']; // Tambahkan ini
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect dengan waktu delay untuk debugging (opsional)
                echo "<script>console.log('Login berhasil, redirecting...');</script>";
                
                if ($user['role'] == 'admin') {
                    header("Location: ../dashboard/admin.php");
                } else if ($user['role'] == 'user') {
                    header("Location: ../dashboard/user.php");
                }
                exit;
            } else {
                $error = "Akun Anda belum disetujui oleh admin. Mohon tunggu verifikasi.";
            }
        } else {
            $error = "Password salah. Silakan coba lagi.";
        }
    } else {
        $error = "Nama pengguna atau email tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/logo.png">
    <title>Login | Event Ease Telesandi</title>
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

        .login-container {
            background: var(--card-bg);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .login-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .logo-login {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 10px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-container h2 {
            margin-bottom: 5px;
            font-size: 1.8em;
            font-weight: 600;
            color: var(--text-color);
        }

        .login-container h3 {
            margin-top: 0;
            font-size: 1em;
            margin-bottom: 30px;
            color: var(--text-light);
            font-weight: 400;
        }

        /* Alert Styling */
        .alert {
            background: var(--gradient-danger);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 0.9em;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 1.2em;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
            transition: var(--transition);
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group input::placeholder {
            color: var(--text-light);
        }

        /* Options Styling */
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 0.9em;
            color: var(--text-light);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Button Styling */
        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            background: var(--gradient-primary);
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submit:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-submit:hover:before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        /* Link Styling */
        .link {
            margin-top: 25px;
            font-size: 0.95em;
            color: var(--text-light);
        }

        .link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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

            .login-container {
                padding: 40px 30px;
            }

            .logo-login {
                font-size: 1.8em;
            }

            .login-container h2 {
                font-size: 1.5em;
            }

            .options {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Background Pattern -->
    <div class="background-pattern"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="../welcome.php" class="logo">
            <img src="../assets/logo.png" alt="SIPENG-EVENT Logo">
            <span>Event Ease Telesandi</span>
        </a>
        <div class="navbar-links">
            <a href="../welcome.php"><i class="fas fa-home"></i> Home</a>
            <a href="../about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="../contact.php"><i class="fas fa-envelope"></i> Contact</a>
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
                <span class="theme-text">Mode Gelap</span>
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="login-container floating">
            <div class="logo-login">Event Ease</div>
            <h2>Login Akun Eskul</h2>
            <h3>Masuk ke akun Anda untuk mengelola event</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="login_input"><i class="fas fa-user"></i> Username atau Email</label>
                    <input type="text" id="login_input" name="login_input" placeholder="Masukkan username atau email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                
                <div class="options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember-me" name="remember-me">
                        <label for="remember-me">Ingatkan saya</label>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot_password.php"><i class="fas fa-key"></i> Lupa Password?</a>
                    </div>
                </div>
                
                <button class="btn-submit" type="submit">
                    <i class="fas fa-sign-in-alt"></i> SIGN IN
                </button>
            </form>
            
            <p class="link">
                Belum punya akun Eskul? 
                <a href="register.php"><i class="fas fa-user-plus"></i> Register di sini</a>
            </p>
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

        // Focus pada input pertama
        document.getElementById('login_input').focus();

        // Animasi untuk form elements
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

        // Observe form elements
        document.querySelectorAll('.form-group, .options, .btn-submit, .link').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            observer.observe(element);
        });

        // Show/hide password functionality
        const passwordInput = document.getElementById('password');
        const showPasswordBtn = document.createElement('button');
        showPasswordBtn.type = 'button';
        showPasswordBtn.innerHTML = '<i class="fas fa-eye"></i>';
        showPasswordBtn.style.cssText = `
            position: absolute;
            right: 15px;
            top: 70%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1em;
        `;
        
        const passwordGroup = passwordInput.parentElement;
        passwordGroup.style.position = 'relative';
        passwordGroup.appendChild(showPasswordBtn);

        showPasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    </script>
</body>
</html>