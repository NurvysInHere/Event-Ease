<?php
// Memulai sesi dan memeriksa status login
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pengajuan = $_POST['id_pengajuan'] ?? null;
    
    if (empty($id_pengajuan) || !isset($_FILES['lpj_file'])) {
        echo "<script>alert('Terjadi kesalahan: ID pengajuan atau file LPJ tidak ditemukan.'); window.location.href='../dashboard/user.php';</script>";
        exit;
    }

    $target_dir = "../uploads/";

    if (!is_dir($target_dir) || !is_writable($target_dir)) {
        echo "<script>alert('Error: Direktori unggahan tidak dapat ditulis. Periksa izin folder.'); window.location.href='../dashboard/user.php';</script>";
        exit;
    }

    $file_info = $_FILES["lpj_file"];

    if ($file_info["error"] !== UPLOAD_ERR_OK) {
        $php_upload_errors = array(
            UPLOAD_ERR_INI_SIZE   => "File LPJ terlalu besar (melebihi upload_max_filesize).",
            UPLOAD_ERR_FORM_SIZE  => "File LPJ terlalu besar (melebihi batas formulir).",
            UPLOAD_ERR_PARTIAL    => "File LPJ hanya terunggah sebagian.",
            UPLOAD_ERR_NO_FILE    => "Tidak ada file LPJ yang diunggah.",
            UPLOAD_ERR_NO_TMP_DIR => "Direktori sementara tidak ditemukan.",
            UPLOAD_ERR_CANT_WRITE => "Gagal menulis file LPJ ke disk.",
            UPLOAD_ERR_EXTENSION  => "Ekstensi file LPJ tidak diizinkan."
        );
        $error_message = $php_upload_errors[$file_info["error"]] ?? 'Terjadi kesalahan tidak diketahui.';
        echo "<script>alert('Maaf, terjadi kesalahan saat mengunggah LPJ: " . htmlspecialchars($error_message) . "'); window.location.href='../dashboard/user.php';</script>";
        exit;
    }

    // --- BARIS YANG DIUBAH ---
    // Mengganti mime_content_type dengan pemeriksaan ekstensi file
    $file_ext = strtolower(pathinfo($file_info["name"], PATHINFO_EXTENSION));
    $allowed_ext = 'pdf';
    
    if ($file_ext !== $allowed_ext) {
        echo "<script>alert('Hanya file PDF yang diizinkan untuk LPJ.'); window.location.href='../dashboard/user.php';</script>";
        exit;
    }
    // --- AKHIR BARIS YANG DIUBAH ---

    $file_name = basename($file_info["name"]);
    $target_file = $target_dir . uniqid('lpj_') . "_" . $file_name;
    
    if (move_uploaded_file($file_info["tmp_name"], $target_file)) {
        try {
            $query = "UPDATE event_pengajuan SET lpj_file = ? WHERE id = ? AND pengaju = ?";
            $stmt = $conn->prepare($query);
            
            $uploaded_lpj_name = basename($target_file);
            $pengaju = $_SESSION['username'];
            
            $stmt->bindParam(1, $uploaded_lpj_name);
            $stmt->bindParam(2, $id_pengajuan);
            $stmt->bindParam(3, $pengaju);
            
            $stmt->execute();
            
            header("Location: ../dashboard/user.php");
            exit;
            
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    } else {
        echo "<script>alert('Gagal memindahkan file LPJ. Periksa izin folder 'uploads'.'); window.location.href='../dashboard/user.php';</script>";
        exit;
    }
} else {
    header("Location: ../dashboard/user.php");
    exit;
}
?>