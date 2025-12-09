<?php
session_start();

include '../config/db.php'; 

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = 'pending';

    if (empty($username) || empty($password) || empty($role) || empty($email)) {
        $error = "Semua kolom harus diisi.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        $check_sql = "SELECT username, email FROM users WHERE username = :username OR email = :email";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $user_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_exists['username'] == $username) {
                $error = "Nama pengguna sudah terdaftar. Silakan gunakan nama lain.";
            } else {
                $error = "Email sudah terdaftar. Silakan gunakan email lain.";
            }
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_sql = "INSERT INTO users (username, email, password, role, status) VALUES (:username, :email, :password, :role, :status)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            try {
                $insert_stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $insert_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $insert_stmt->bindParam(':role', $role, PDO::PARAM_STR);
                $insert_stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $insert_stmt->execute();
                
                $success = "Akun berhasil terdaftar, harap tunggu verifikasi dari admin.";
                
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi. (" . $e->getMessage() . ")";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/logo.png">
    <title>Register | SIPENG-EVENT</title>
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

        .register-container {
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

        .register-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .logo-register {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 10px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .register-container h2 {
            margin-bottom: 5px;
            font-size: 1.8em;
            font-weight: 600;
            color: var(--text-color);
        }

        .register-container h3 {
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

        .success {
            background: var(--gradient-success);
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

        .success i {
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

        .form-group input,
        .form-group select {
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

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group input::placeholder {
            color: var(--text-light);
        }

        .form-group select option {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        /* Password Wrapper */
        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1em;
            padding: 5px;
            border-radius: 4px;
            transition: var(--transition);
            z-index: 2;
        }

        .toggle-password:hover {
            background-color: var(--border-color);
            color: var(--text-color);
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

            .register-container {
                padding: 40px 30px;
            }

            .logo-register {
                font-size: 1.8em;
            }

            .register-container h2 {
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
        <div class="register-container floating">
            <div class="logo-register">Event Ease</div>
            <h2>Register Akun Eskul</h2>
            <h3>Buat akun baru untuk mengelola event eskul</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Nama Eskul</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan nama eskul" required 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role"><i class="fas fa-user-tag"></i> Peran</label>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih Peran --</option>
                        <option value="user" <?= (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : '' ?>>Ketua Eskul</option>
                    </select>
                </div>
                
                <button class="btn-submit" type="submit">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
            </form>
            
            <p class="link">
                Sudah punya akun Eskul? 
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login di sini</a>
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
        document.getElementById('username').focus();

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
        document.querySelectorAll('.form-group, .btn-submit, .link').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            observer.observe(element);
        });

        // Function untuk toggle show/hide password
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleBtn.className = 'fas fa-eye';
            }
        }

        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('Password harus minimal 6 karakter!');
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>