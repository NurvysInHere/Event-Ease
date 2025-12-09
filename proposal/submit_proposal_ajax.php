<?php
session_start();
include '../config/db.php'; // Sertakan file koneksi database & BASE_URL

// Pastikan hanya menerima request POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'text' => 'Metode request tidak diizinkan.']);
    exit;
}

// Pastikan pengguna sudah login
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'user') {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'title' => 'Unauthorized', 'text' => 'Anda harus login untuk mengajukan proposal.']);
    exit;
}

header('Content-Type: application/json'); // Penting untuk respons JSON

$response = [];
$current_username = $_SESSION['username'];
$user_budget_limit = 0.00;

try {
    $query_get_budget = "SELECT budget FROM users WHERE username = :username";
    $stmt_get_budget = $conn->prepare($query_get_budget);
    $stmt_get_budget->bindParam(':username', $current_username, PDO::PARAM_STR);
    $stmt_get_budget->execute();
    $user_data = $stmt_get_budget->fetch(PDO::FETCH_ASSOC);

    if ($user_data && isset($user_data['budget'])) {
        $user_budget_limit = (float) $user_data['budget'];
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal mengambil informasi budget: ' . htmlspecialchars($e->getMessage())]);
    exit;
}

$nama_event = htmlspecialchars($_POST['nama_event'] ?? '');
$budget_ajuan = filter_var($_POST['budget'] ?? 0, FILTER_VALIDATE_FLOAT);
$pengaju      = htmlspecialchars($_SESSION['username']);
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$continue_anyway = isset($_POST['continue_anyway']) && $_POST['continue_anyway'] == 'true';

// --- Validasi Budget Awal ---
if ($budget_ajuan === false || $budget_ajuan <= 0) {
    echo json_encode(['status' => 'error', 'title' => 'Input Tidak Valid!', 'text' => 'Budget yang diajukan tidak valid. Masukkan angka positif.']);
    exit;
}

// --- Validasi Tanggal Mulai ---
if (empty($tanggal_mulai)) {
    echo json_encode(['status' => 'error', 'title' => 'Input Tidak Valid!', 'text' => 'Tanggal pelaksanaan harus diisi.']);
    exit;
}

// Validasi bahwa tanggal mulai harus di masa depan
$today = date('Y-m-d');
if ($tanggal_mulai <= $today) {
    echo json_encode(['status' => 'error', 'title' => 'Input Tidak Valid!', 'text' => 'Tanggal pelaksanaan harus di masa depan.']);
    exit;
}

// Cek jika admin belum menentukan budget atau budgetnya 0, dan belum ada konfirmasi untuk melanjutkan
if ($user_budget_limit <= 0 && !$continue_anyway) {
    echo json_encode([
        'status' => 'warning_confirm',
        'type' => 'warning',
        'title' => 'Budget Belum Ditetapkan!',
        'text' => 'Admin belum menentukan batas budget atau batas budget Anda belum di tentukan. Anda yakin ingin tetap mengajukan proposal ini?',
        'confirmButtonText' => 'Ya, Ajukan!',
        'cancelButtonText' => 'Batal',
    ]);
    exit;
}

// Cek jika budget ajuan melebihi batas yang ditentukan admin, dan belum ada konfirmasi untuk melanjutkan
if ($user_budget_limit > 0 && $budget_ajuan > $user_budget_limit && !$continue_anyway) {
    echo json_encode([
        'status' => 'warning_confirm',
        'type' => 'warning',
        'title' => 'Budget Melebihi Batas!',
        'text' => 'Budget yang diajukan (Rp ' . number_format($budget_ajuan, 0, ',', '.') . ') melebihi batas budget yang ditentukan admin. Proposal Anda akan tetap diajukan, namun mungkin memerlukan persetujuan khusus atau revisi. Anda yakin ingin melanjutkan?',
        'confirmButtonText' => 'Ya, Lanjutkan Ajuan!',
        'cancelButtonText' => 'Batal',
    ]);
    exit;
}

// Menyiapkan direktori dan nama file untuk diunggah
if (!isset($_FILES["proposal"]) || $_FILES["proposal"]["error"] == UPLOAD_ERR_NO_FILE) {
    echo json_encode(['status' => 'error', 'title' => 'Gagal!', 'text' => 'Pilih file proposal yang akan diunggah.']);
    exit;
}

$file_name = basename($_FILES["proposal"]["name"]);
$target_dir = "../uploads/";
$target_file = $target_dir . $file_name;

// Pindahkan file yang diunggah dari direktori sementara ke direktori target
if (move_uploaded_file($_FILES["proposal"]["tmp_name"], $target_file)) {
    try {
        $conn->beginTransaction();

        // PERBAIKAN: Tambahkan tanggal_pengajuan dan tanggal_mulai ke query
        $tanggal_pengajuan = date('Y-m-d H:i:s');
        
        // PERBAIKAN: Gunakan named parameters dengan kolom tanggal_mulai
        $query_insert_proposal = "INSERT INTO event_pengajuan (nama_event, budget, proposal_file, status, pengaju, tanggal_pengajuan, tanggal_mulai)
                                 VALUES (:nama_event, :budget, :proposal_file, 'pending', :pengaju, :tanggal_pengajuan, :tanggal_mulai)";
        $stmt_insert_proposal = $conn->prepare($query_insert_proposal);
        
        $stmt_insert_proposal->execute([
            ':nama_event' => $nama_event,
            ':budget' => $budget_ajuan,
            ':proposal_file' => $file_name,
            ':pengaju' => $pengaju,
            ':tanggal_pengajuan' => $tanggal_pengajuan,
            ':tanggal_mulai' => $tanggal_mulai
        ]);

        $conn->commit();

        echo json_encode(['status' => 'success', 'title' => 'Berhasil!', 'text' => 'Proposal event berhasil diajukan! Admin akan segera meninjau.', 'redirect' => BASE_URL . 'dashboard/user.php']);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        echo json_encode(['status' => 'error', 'title' => 'Gagal!', 'text' => 'Gagal menyimpan pengajuan: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
} else {
    $error_code = $_FILES['proposal']['error'];
    $error_message = '';
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE: $error_message = "File melebihi batas ukuran yang ditentukan."; break;
        case UPLOAD_ERR_FORM_SIZE: $error_message = "File melebihi batas ukuran formulir (MAX_FILE_SIZE)."; break;
        case UPLOAD_ERR_PARTIAL: $error_message = "File hanya terunggah sebagian."; break;
        case UPLOAD_ERR_NO_FILE: $error_message = "Tidak ada file yang dipilih."; break;
        case UPLOAD_ERR_NO_TMP_DIR: $error_message = "Folder sementara untuk unggahan tidak ditemukan."; break;
        case UPLOAD_ERR_CANT_WRITE: $error_message = "Gagal menulis file ke disk. Periksa izin folder 'uploads'."; break;
        case UPLOAD_ERR_EXTENSION: $error_message = "Ekstensi PHP menghentikan proses unggahan."; break;
        default: $error_message = "Terjadi kesalahan unggahan yang tidak diketahui."; break;
    }
    echo json_encode(['status' => 'error', 'title' => 'Gagal Unggah!', 'text' => 'Maaf, terjadi kesalahan saat mengunggah file Anda. Detail: ' . htmlspecialchars($error_message)]);
    exit;
}
?>