<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];

// Debug: Cek konstanta
error_log("BASE_URL: " . BASE_URL);
error_log("UPLOAD_DIR: " . UPLOAD_DIR);

// Ambil data pengguna dari database
try {
    $query_user = "SELECT id, username, email, role, profile_picture, dibuat_pada, last_login FROM users WHERE username = :username";
    $stmt_user = $conn->prepare($query_user);
    $stmt_user->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_user->execute();
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        if ($role === 'admin') {
            header("Location: ../dashboard/admin.php");
        } else {
            header("Location: ../dashboard/user.php");
        }
        exit;
    }

    // Simpan nama file asli untuk keperluan hapus
    $current_profile_filename = $user['profile_picture'];

    // Handle profile picture path - PERBAIKAN DI SINI
    if (!empty($user['profile_picture'])) {
        // PERBAIKAN: Gunakan path dari konstanta yang sudah didefinisikan
        $uploadDir = PROFILE_PICTURE_DIR; // dari config/db.php
        $profilePicturePath = $uploadDir . $user['profile_picture'];
        
        // Debug: Cek path file
        error_log("Profile picture path: " . $profilePicturePath);
        error_log("File exists: " . (file_exists($profilePicturePath) ? 'YES' : 'NO'));
        
        if (file_exists($profilePicturePath)) {
            // PERBAIKAN: Gunakan URL yang benar
            $user['profile_picture'] = PROFILE_PICTURE_URL . $user['profile_picture'] . '?t=' . time();
            $has_profile_picture = true;
        } else {
            // Jika file tidak ada, gunakan default
            $user['profile_picture'] = 'https://placehold.co/150x150/3498db/fff?text=' . strtoupper(substr($user['username'], 0, 1));
            $has_profile_picture = false;
            $current_profile_filename = null;
        }
    } else {
        // Default profile picture
        $user['profile_picture'] = 'https://placehold.co/150x150/3498db/fff?text=' . strtoupper(substr($user['username'], 0, 1));
        $has_profile_picture = false;
        $current_profile_filename = null;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Hitung jumlah event yang diajukan (hanya untuk user)
$events_count = 0;
if ($role === 'user') {
    try {
        $query_events = "SELECT COUNT(*) as total FROM events WHERE user_id = :user_id";
        $stmt_events = $conn->prepare($query_events);
        $stmt_events->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $stmt_events->execute();
        $events_result = $stmt_events->fetch(PDO::FETCH_ASSOC);
        $events_count = $events_result['total'];
    } catch (PDOException $e) {
        // Jika error, tetap tampilkan 0
        $events_count = 0;
    }
}

// Hitung hari aktif
$created_date = new DateTime($user['dibuat_pada']);
$today = new DateTime();
$diff = $today->diff($created_date);
$active_days = $diff->days;

// Hitung total users untuk admin
$total_users = 0;
if ($role === 'admin') {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        $total_users = 0;
    }
}

// Handle upload profile picture
$uploadError = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle upload foto baru
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // PERBAIKAN: Gunakan konstanta dari config
        $uploadDir = PROFILE_PICTURE_DIR;
        
        // Debug: Cek direktori upload
        error_log("Upload directory: " . $uploadDir);
        
        // Buat direktori jika belum ada dengan permission yang benar
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // PERBAIKAN: Gunakan 0755 bukan 0777
            // Tambahkan .htaccess untuk keamanan
            file_put_contents($uploadDir . '.htaccess', "Order Deny,Allow\nDeny from all");
        }
        
        // Cek permission direktori
        if (!is_writable($uploadDir)) {
            $uploadError = "Direktori upload tidak dapat ditulisi. Silakan hubungi administrator.";
            error_log("Upload directory not writable: " . $uploadDir);
        } else {
            $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
            
            // Validasi file
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            if (in_array($fileType, $allowedTypes)) {
                if ($_FILES['profile_picture']['size'] <= $maxFileSize) {
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                        // Debug: File berhasil diupload
                        error_log("File uploaded successfully to: " . $targetFilePath);
                        
                        // Hapus foto lama jika ada
                        if (!empty($current_profile_filename)) {
                            $oldFilePath = $uploadDir . $current_profile_filename;
                            if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                                unlink($oldFilePath);
                                error_log("Old file deleted: " . $oldFilePath);
                            }
                        }
                        
                        // Update database
                        try {
                            $updateQuery = "UPDATE users SET profile_picture = :profile_picture WHERE username = :username";
                            $stmtUpdate = $conn->prepare($updateQuery);
                            $stmtUpdate->bindParam(':profile_picture', $fileName);
                            $stmtUpdate->bindParam(':username', $username);
                            
                            if ($stmtUpdate->execute()) {
                                // Update session
                                $_SESSION['profile_picture'] = $fileName;
                                
                                // Redirect dengan sukses
                                header("Location: profile.php?success=upload&t=" . time());
                                exit;
                            } else {
                                $uploadError = "Gagal menyimpan ke database.";
                                // Hapus file yang sudah diupload jika gagal update database
                                if (file_exists($targetFilePath)) {
                                    unlink($targetFilePath);
                                }
                            }
                        } catch (PDOException $e) {
                            $uploadError = "Gagal menyimpan ke database: " . $e->getMessage();
                            error_log("Database error: " . $e->getMessage());
                            // Hapus file yang sudah diupload jika gagal update database
                            if (file_exists($targetFilePath)) {
                                unlink($targetFilePath);
                            }
                        }
                    } else {
                        $uploadError = "Gagal mengupload file. Error: " . $_FILES['profile_picture']['error'];
                        error_log("Upload error: " . $_FILES['profile_picture']['error']);
                    }
                } else {
                    $uploadError = "Ukuran file terlalu besar. Maksimal 5MB.";
                }
            } else {
                $uploadError = "Hanya file JPG, JPEG, PNG & GIF yang diizinkan.";
            }
        }
    }
    
    // Handle hapus foto profil
    if (isset($_POST['delete_profile_picture'])) {
        if (!empty($current_profile_filename)) {
            $uploadDir = PROFILE_PICTURE_DIR;
            $filePath = $uploadDir . $current_profile_filename;
            
            // Hapus file dari server
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
                error_log("Profile picture deleted: " . $filePath);
            }
            
            // Update database
            try {
                $updateQuery = "UPDATE users SET profile_picture = NULL WHERE username = :username";
                $stmtUpdate = $conn->prepare($updateQuery);
                $stmtUpdate->bindParam(':username', $username);
                
                if ($stmtUpdate->execute()) {
                    // Update session
                    unset($_SESSION['profile_picture']);
                    header("Location: profile.php?success=delete");
                    exit;
                }
            } catch (PDOException $e) {
                $uploadError = "Gagal menghapus foto profil: " . $e->getMessage();
                error_log("Delete error: " . $e->getMessage());
            }
        }
    }
}

// Tampilkan pesan sukses jika ada
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'upload') {
        $successMessage = "Foto profil berhasil diubah!";
    } elseif ($_GET['success'] == 'delete') {
        $successMessage = "Foto profil berhasil dihapus!";
    }
}

$page_title = "Profil Pengguna";
$current_page = 'profile';
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
            
            --gradient-primary: linear-gradient(135deg, #3498db, #2980b9);
            --gradient-secondary: linear-gradient(135deg, #2c3e50, #34495e);
            --gradient-accent: linear-gradient(135deg, #1abc9c, #16a085);
            --gradient-success: linear-gradient(135deg, #2ecc71, #27ae60);
            --gradient-danger: linear-gradient(135deg, #e74c3c, #c0392b);
        }

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
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: var(--card-bg);
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 900px;
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

        .profile-wrapper {
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .content-area {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            animation: fadeIn 0.8s ease-in-out;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            font-size: 2em;
            margin-bottom: 30px;
            color: var(--text-color);
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        .content-area h2:after {
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

        .profile-picture-section {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            cursor: pointer;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .profile-picture-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--gradient-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid var(--card-bg);
            z-index: 10;
        }

        .profile-picture-edit:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
        }

        .profile-picture-delete {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: var(--gradient-danger);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid var(--card-bg);
            z-index: 10;
        }

        .profile-picture-delete:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .profile-info {
            width: 100%;
            max-width: 500px;
            margin-bottom: 25px;
        }
        
        .profile-info-item {
            margin: 15px 0;
            font-size: 1em;
            color: var(--text-color);
            padding: 15px 20px;
            background-color: var(--bg-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .profile-info-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-info-item strong {
            color: var(--text-color);
            font-weight: 600;
            min-width: 140px;
            display: inline-block;
        }
        
        .profile-info-item i {
            color: var(--primary-color);
            font-size: 1.1em;
            width: 20px;
            text-align: center;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            width: 100%;
            max-width: 400px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .stat-card i {
            font-size: 1.8em;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 1.6em;
            font-weight: bold;
            margin: 5px 0;
        }

        .stat-label {
            font-size: 0.85em;
            opacity: 0.9;
        }

        .btn-container {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            max-width: 300px;
        }

        .btn {
            text-decoration: none;
            padding: 15px;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: none;
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

        .btn-primary {
            background: var(--gradient-primary);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: var(--gradient-secondary);
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.4);
        }

        .btn-danger {
            background: var(--gradient-danger);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
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
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

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

        .notification.error {
            background: var(--gradient-danger);
        }

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

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: var(--transition);
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .file-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 15px;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .file-upload-area i {
            font-size: 3em;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .file-upload-area p {
            margin: 0;
            color: var(--text-light);
        }

        .file-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin: 15px auto;
            display: none;
        }

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

        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 12px;
            border: 1px solid #ddd;
        }

        .upload-status {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .upload-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .upload-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
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
            
            .content-area {
                padding: 25px 20px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-container {
                grid-template-columns: 1fr;
                max-width: 300px;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <?php if ($role === 'admin') : ?>
                        <li class="<?= ($current_page == 'dashboard_overview') ? 'active' : '' ?>">
                            <a href="../dashboard/admin.php?page=dashboard_overview"><i class="fas fa-chart-line"></i> Dashboard Admin</a>
                        </li>
                        <li class="<?= ($current_page == 'manage_proposals') ? 'active' : '' ?>">
                            <a href="../dashboard/admin.php?page=manage_proposals"><i class="fas fa-file-invoice"></i> Manajemen Pengajuan</a>
                        </li>
                        <li class="<?= ($current_page == 'manage_users') ? 'active' : '' ?>">
                            <a href="../dashboard/manage_users.php"><i class="fas fa-users"></i> Manajemen Pengguna</a>
                        </li>
                    <?php else : ?>
                        <li class="<?= ($current_page == 'user_dashboard') ? 'active' : '' ?>">
                            <a href="../dashboard/user.php"><i class="fas fa-home"></i> Dashboard User</a>
                        </li>
                        <li class="<?= ($current_page == 'upload_proposal') ? 'active' : '' ?>">
                            <a href="../proposal/upload.php"><i class="fas fa-plus-circle"></i> Ajukan Event</a>
                        </li>
                    <?php endif; ?>
                    <li class="active">
                        <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
                    </li>
                </ul>
            </nav>
            <div class="logout">
                <a href="#" id="logoutBtnSidebar"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="profile-wrapper">
                <div class="header">
                    <h1><?= $page_title ?></h1>
                    <div class="user-info">
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                        <span><i class="fas fa-user-tag"></i> <?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
                
                <div class="content-area">
                    <h2>Detail Profil</h2>

                    <!-- Pesan Sukses -->
                    <?php if (!empty($successMessage)): ?>
                        <div class="upload-status success">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Status Upload -->
                    <?php if (!empty($uploadError)): ?>
                        <div class="upload-status error">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($uploadError) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-picture-section">
                        <img src="<?= htmlspecialchars($user['profile_picture']) ?>" 
                             alt="Profil Pengguna" 
                             class="profile-picture"
                             id="profilePicture"
                             onerror="this.src='https://placehold.co/150x150/3498db/fff?text=<?= strtoupper(substr(htmlspecialchars($user['username']), 0, 1)) ?>'">
                        <div class="profile-picture-edit" id="editProfilePicture">
                            <i class="fas fa-camera"></i>
                        </div>
                        <?php if ($has_profile_picture): ?>
                            <div class="profile-picture-delete" id="deleteProfilePicture" title="Hapus Foto Profil">
                                <i class="fas fa-trash"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- STATS CONTAINER YANG SUDAH DIPERBAIKI -->
                    <div class="stats-container">
                        <?php if ($role === 'user'): ?>
                            <!-- Hanya tampilkan Event Diajukan untuk USER -->
                            <div class="stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <div class="stat-number" id="eventsCount"><?= $events_count ?></div>
                                <div class="stat-label">Event Diajukan</div>
                            </div>
                        <?php elseif ($role === 'admin'): ?>
                            <!-- Untuk ADMIN, tampilkan statistik admin -->
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div class="stat-number" id="totalUsers"><?= $total_users ?></div>
                                <div class="stat-label">Total Pengguna</div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Hari Aktif tampil untuk SEMUA role -->
                        <div class="stat-card">
                            <i class="fas fa-clock"></i>
                            <div class="stat-number" id="activeSince"><?= $active_days ?></div>
                            <div class="stat-label">Hari Aktif</div>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <div class="profile-info-item">
                            <i class="fas fa-user"></i>
                            <strong>Username:</strong> <?= htmlspecialchars($user['username']) ?>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-envelope"></i>
                            <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-user-tag"></i>
                            <strong>Peran:</strong> 
                            <span class="role-badge"><?= htmlspecialchars($user['role']) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <strong>Dibuat pada:</strong> <?= date('d F Y H:i:s', strtotime($user['dibuat_pada'])) ?>
                        </div>
                        <div class="profile-info-item">
                            <i class="fas fa-clock"></i>
                            <strong>Terakhir login:</strong> 
                            <?= $user['last_login'] ? date('d F Y H:i:s', strtotime($user['last_login'])) : 'Belum pernah login' ?>
                        </div>
                    </div>
                    
                    <div class="btn-container">
                        <a href="../dashboard/<?= $role === 'admin' ? 'admin.php' : 'user.php' ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                        <button class="btn btn-secondary" id="editProfileBtn">
                            <i class="fas fa-edit"></i> Edit Profil
                        </button>
                        <?php if ($role === 'admin') : ?>
                            <button class="btn btn-danger" id="deleteAccountBtn">
                                <i class="fas fa-trash"></i> Hapus Akun
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profil</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="editProfileForm">
                <div class="form-group">
                    <label for="editUsername">Username</label>
                    <input type="text" id="editUsername" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="editPassword">Password Baru (opsional)</label>
                    <input type="password" id="editPassword" placeholder="Kosongkan jika tidak ingin mengubah">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Upload Foto Profil -->
    <div class="modal" id="uploadPhotoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ubah Foto Profil</h3>
                <button class="close-modal">&times;</button>
            </div>
            <form id="uploadPhotoForm" enctype="multipart/form-data" method="POST">
                <div class="form-group">
                    <label>Pilih Foto</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Klik untuk memilih foto atau drag & drop</p>
                        <p class="small">Format: JPG, PNG, GIF (Maks. 5MB)</p>
                    </div>
                    <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" style="display: none;">
                    <img id="filePreview" class="file-preview" alt="Preview">
                </div>
                <div class="upload-progress" id="uploadProgress">
                    <div class="upload-progress-bar" id="uploadProgressBar"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;" id="uploadButton">
                    <i class="fas fa-upload"></i> Upload Foto
                </button>
            </form>
        </div>
    </div>

    <!-- Form Hapus Foto Profil (Hidden) -->
    <form id="deleteProfilePictureForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_profile_picture" value="1">
    </form>

    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i> Profil berhasil diperbarui!
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
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            notification.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${message}`;
            if (isError) {
                notification.classList.add('error');
            } else {
                notification.classList.remove('error');
            }
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

        // Tampilkan notifikasi sukses jika ada
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'upload') {
                showNotification('Foto profil berhasil diubah!');
                // Force refresh gambar
                const profilePicture = document.getElementById('profilePicture');
                profilePicture.src = profilePicture.src.split('?')[0] + '?t=' + new Date().getTime();
            } else if (urlParams.get('success') === 'delete') {
                showNotification('Foto profil berhasil dihapus!');
            }
        });

        // Modal Edit Profil
        const editProfileModal = document.getElementById('editProfileModal');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const closeModals = document.querySelectorAll('.close-modal');
        const editProfileForm = document.getElementById('editProfileForm');

        editProfileBtn.addEventListener('click', function() {
            editProfileModal.style.display = 'flex';
        });

        closeModals.forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Handle form edit profil
        editProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simulasi update profil
            setTimeout(() => {
                editProfileModal.style.display = 'none';
                showNotification('Profil berhasil diperbarui!');
            }, 1000);
        });

        // Modal Upload Foto Profil
        const uploadPhotoModal = document.getElementById('uploadPhotoModal');
        const editProfilePicture = document.getElementById('editProfilePicture');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const profilePictureInput = document.getElementById('profilePictureInput');
        const filePreview = document.getElementById('filePreview');
        const uploadPhotoForm = document.getElementById('uploadPhotoForm');
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadProgressBar = document.getElementById('uploadProgressBar');
        const uploadButton = document.getElementById('uploadButton');

        editProfilePicture.addEventListener('click', function() {
            uploadPhotoModal.style.display = 'flex';
            // Reset form saat modal dibuka
            filePreview.style.display = 'none';
            fileUploadArea.style.display = 'block';
            profilePictureInput.value = '';
        });

        // Drag and drop functionality
        fileUploadArea.addEventListener('click', function() {
            profilePictureInput.click();
        });

        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--primary-color)';
            this.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
        });

        fileUploadArea.addEventListener('dragleave', function() {
            this.style.borderColor = 'var(--border-color)';
            this.style.backgroundColor = '';
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--border-color)';
            this.style.backgroundColor = '';
            
            if (e.dataTransfer.files.length > 0) {
                profilePictureInput.files = e.dataTransfer.files;
                previewImage(e.dataTransfer.files[0]);
            }
        });

        profilePictureInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                previewImage(this.files[0]);
            }
        });

        function previewImage(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                filePreview.src = e.target.result;
                filePreview.style.display = 'block';
                fileUploadArea.style.display = 'none';
            }
            
            reader.readAsDataURL(file);
        }

        // Handle upload form
        uploadPhotoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!profilePictureInput.files.length) {
                showNotification('Pilih foto terlebih dahulu!', true);
                return;
            }

            // Tampilkan progress bar
            uploadProgress.style.display = 'block';
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengupload...';

            // Submit form secara normal (bukan AJAX)
            // Progress bar hanya simulasi
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    // Submit form setelah progress selesai
                    this.submit();
                }
                uploadProgressBar.style.width = progress + '%';
            }, 200);
        });

        // Handle hapus foto profil
        const deleteProfilePicture = document.getElementById('deleteProfilePicture');
        const deleteProfilePictureForm = document.getElementById('deleteProfilePictureForm');

        if (deleteProfilePicture) {
            deleteProfilePicture.addEventListener('click', function() {
                Swal.fire({
                    title: 'Hapus Foto Profil?',
                    text: 'Foto profil akan dihapus permanen dan diganti dengan foto default.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'swal2-confirm-override',
                        cancelButton: 'swal2-cancel-override'
                    },
                    background: 'var(--card-bg)',
                    color: 'var(--text-color)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteProfilePictureForm.submit();
                    }
                });
            });
        }

        // Handle hapus akun
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Hapus Akun?',
                    html: `<?= $role === 'admin' 
                        ? '<strong>PERINGATAN ADMIN:</strong> Anda akan menghapus akun Anda sendiri. Tindakan ini tidak dapat dibatalkan!' 
                        : 'Apakah Anda yakin ingin menghapus akun? Semua data akan hilang permanen!' ?>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'swal2-confirm-override',
                        cancelButton: 'swal2-cancel-override'
                    },
                    background: 'var(--card-bg)',
                    color: 'var(--text-color)'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../profile/delete_account.php';
                    }
                });
            });
        }

        // Handle logout
        document.getElementById('logoutBtnSidebar').addEventListener('click', function(e) {
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
                    window.location.href = '../auth/logout.php';
                }
            });
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
    </script>
</body>
</html>