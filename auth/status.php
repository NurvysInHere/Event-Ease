<?php
session_start();
if (!isset($_GET['username'])) {
    header("Location: register.php");
    exit;
}

$username = htmlspecialchars($_GET['username']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Status Pendaftaran | SIPENG-EVENT</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('https://images.unsplash.com/photo-1549880338-65ddcdfd017b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            position: relative;
            color: #fff;
            text-align: center;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }

        .status-container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.1);
            padding: 40px 60px;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            max-width: 500px;
            width: 100%;
            box-sizing: border-box;
            animation: fadeIn 1s ease-in-out;
        }

        .status-container h2 {
            margin-bottom: 10px;
            font-size: 2em;
            font-weight: 600;
        }

        .status-container p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }

        .btn-link {
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 10px;
            background-color: #ffb791;
            color: white;
            font-size: 1.2em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.3s ease;
            text-transform: uppercase;
        }
        
        .btn-link:hover {
            background-color: #ff9a6e;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="status-container">
        <h2>Pendaftaran Berhasil!</h2>
        <p>Halo, Eskul <?= $username ?>.</p>
        <p>Akun Anda telah berhasil didaftarkan. Mohon tunggu hingga admin menyetujui akun Anda.</p>
        <p>Kami akan segera memberitahu Anda. Silakan coba login kembali nanti.</p>
        <a href="login.php" class="btn-link">Kembali ke Halaman Login</a>
    </div>
</body>
</html>
