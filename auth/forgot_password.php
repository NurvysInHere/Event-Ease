<?php
session_start();
include '../config/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    if (empty($email)) {
        $error = "Email harus diisi.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek apakah email terdaftar
        $check_sql = "SELECT id, username, email FROM users WHERE email = :email";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate token reset password
            $reset_token = bin2hex(random_bytes(32));
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update token reset
            $update_sql = "UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE email = :email";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':token', $reset_token, PDO::PARAM_STR);
            $update_stmt->bindParam(':expiry', $expiry_time, PDO::PARAM_STR);
            $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($update_stmt->execute()) {
                // Kirim email menggunakan fungsi yang sudah ada
                require_once '../utils/send_email.php';
                
                // Kirim email reset password dengan token saja (bukan link)
                $email_sent = send_password_reset_token_email($email, $user['username'], $reset_token);
                
                if ($email_sent) {
                    $success = "Token reset password telah dikirim ke email <strong>{$email}</strong>. Silakan cek inbox atau spam folder. Token berlaku selama 1 jam.";
                } else {
                    $error = "Gagal mengirim email. Silakan coba lagi nanti.";
                }
                
            } else {
                $error = "Gagal mengupdate token reset. Silakan coba lagi.";
            }
            
        } else {
            $error = "Email tidak terdaftar dalam sistem.";
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
    <title>Lupa Password | Event Ease</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        /* CSS tetap sama seperti sebelumnya */
        :root {
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
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e1e8ed;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            
            --gradient-primary: linear-gradient(135deg, #3498db, #2980b9);
            --gradient-secondary: linear-gradient(135deg, #2c3e50, #34495e);
            --gradient-success: linear-gradient(135deg, #2ecc71, #27ae60);
            --gradient-danger: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        [data-theme="dark"] {
            --bg-color: #1a1a2e;
            --card-bg: #16213e;
            --text-color: #ecf0f1;
            --text-light: #bdc3c7;
            --border-color: #2c3e50;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            position: relative;
        }

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
        }

        .navbar .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 1.5em;
        }

        .navbar .logo img {
            height: 40px;
            filter: brightness(0) invert(1);
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
        }

        .navbar-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
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
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 100px 20px 50px;
            position: relative;
            z-index: 1;
        }

        .forgot-container {
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
        }

        .forgot-container:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .logo-forgot {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 10px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .forgot-container h2 {
            margin-bottom: 5px;
            font-size: 1.8em;
            font-weight: 600;
            color: var(--text-color);
        }

        .forgot-container h3 {
            margin-top: 0;
            font-size: 1em;
            margin-bottom: 30px;
            color: var(--text-light);
            font-weight: 400;
        }

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
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-back {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 10px;
            background: var(--gradient-secondary);
            color: white;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.4);
        }

        .instructions {
            background: var(--bg-color);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: left;
            border: 1px solid var(--border-color);
        }

        .instructions h4 {
            margin-bottom: 10px;
            color: var(--text-color);
            font-weight: 600;
        }

        .instructions ul {
            padding-left: 20px;
            color: var(--text-light);
        }

        .instructions li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .navbar-links {
                gap: 15px;
            }

            .main-content {
                padding: 120px 15px 30px;
            }

            .forgot-container {
                padding: 40px 30px;
            }

            .logo-forgot {
                font-size: 1.8em;
            }

            .forgot-container h2 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>

    <nav class="navbar">
        <a href="../welcome.php" class="logo">
            <img src="../assets/logo.png" alt="Event Ease Logo">
            <span>Event Ease</span>
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

    <div class="main-content">
        <div class="forgot-container">
            <div class="logo-forgot">Event Ease</div>
            <h2>Reset Password</h2>
            <h3>Masukkan email Anda untuk mereset password</h3>

            <?php if (!empty($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= $success ?></div>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h4><i class="fas fa-info-circle"></i> Petunjuk:</h4>
                <ul>
                    <li>Masukkan alamat email yang terdaftar di akun Anda</li>
                    <li>Kami akan mengirimkan <strong>token reset password</strong> ke email tersebut</li>
                    <li>Token reset password berlaku selama 1 jam</li>
                    <li>Salin token dan gunakan di halaman reset password</li>
                    <li>Periksa folder spam jika email tidak ditemukan di inbox</li>
                </ul>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Terdaftar</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email yang terdaftar" required
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <button class="btn-submit" type="submit">
                    <i class="fas fa-paper-plane"></i> Kirim Token Reset
                </button>
            </form>

            <a href="reset_password.php" class="btn-back">
                <i class="fas fa-key"></i> Sudah Punya Token? Reset Password
            </a>

            <a href="login.php" class="btn-back" style="margin-top: 10px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Login
            </a>
        </div>
    </div>

    <script>
        // Toggle tema gelap/terang
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

        // Focus pada input email
        document.getElementById('email').focus();
    </script>
</body>
</html>