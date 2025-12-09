<?php
define('BASE_URL', 'http://localhost:8000/');
session_start();
// ✅ 1. DEFINISIKAN BASE_URL PERTAMA
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost:8000/');
}

// ✅ 2. MANUAL INCLUDE PHPMailer 
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// ✅ 3. SESSION START
session_start();

// ✅ 4. ✅ TAMBAHKAN KONEKSI DATABASE
try {
    $host = 'localhost';
    $dbname = 'pengajuan_event'; // Ganti dengan nama database Anda
    $username = 'root';
    $password = 'admin123';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// ✅ 5. ✅ DEFINISIKAN CONSTANT EMAIL (TAMBAHKAN INI)
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'nuraune21@gmail.com');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'SIPENG-EVENT');
}

// ✅ 6. ERROR HANDLING UNTUK PHPMailer
$phpmailer_available = false;

try {
    // Coba load PHPMailer dari Composer
    if (file_exists('../vendor/autoload.php')) {
        require '../vendor/autoload.php';
        $phpmailer_available = true;
    } 
    // Coba load PHPMailer manual
    else if (file_exists('../libs/PHPMailer/src/PHPMailer.php')) {
        require '../libs/PHPMailer/src/Exception.php';
        require '../libs/PHPMailer/src/PHPMailer.php';
        require '../libs/PHPMailer/src/SMTP.php';
        $phpmailer_available = true;
    }
} catch (Exception $e) {
    $phpmailer_available = false;
    error_log("PHPMailer load error: " . $e->getMessage());
}

// ✅ 7. BARU CEK ADMIN AUTH SETELAH SESSION START
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// ... sisa kode Anda TETAP SAMA ...
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id']) && isset($_GET['aksi'])) {
    $proposal_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $aksi = htmlspecialchars($_GET['aksi']);
    $admin_comment = isset($_GET['comment']) ? htmlspecialchars($_GET['comment']) : '';

    if ($proposal_id === false) {
        $_SESSION['admin_message'] = 'ID proposal tidak valid.';
        header("Location: " . BASE_URL . "dashboard/admin.php?page=manage_proposals");
        exit;
    }

    try {
        $conn->beginTransaction();

        $query_get_proposal = "
            SELECT 
                ep.budget, ep.pengaju, ep.status, ep.nama_event,
                u.email, u.username AS pengaju_nama_lengkap 
            FROM event_pengajuan ep
            JOIN users u ON ep.pengaju = u.username
            WHERE ep.id = :id FOR UPDATE";
        $stmt_get_proposal = $conn->prepare($query_get_proposal);
        $stmt_get_proposal->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_get_proposal->execute();
        $proposal_data = $stmt_get_proposal->fetch(PDO::FETCH_ASSOC);

        if (!$proposal_data) {
            throw new Exception("Proposal tidak ditemukan.");
        }

        $current_status = $proposal_data['status'];
        $proposal_budget = (float)$proposal_data['budget'];
        $pengaju_username = $proposal_data['pengaju'];
        $pengaju_email = $proposal_data['email'];
        $nama_event = $proposal_data['nama_event'];
        $pengaju_nama_lengkap = $proposal_data['pengaju_nama_lengkap'] ?: $pengaju_username;

        $new_status = '';
        $message = '';
        $email_subject = '';
        $email_status_text = '';
        $email_color = '';
        $email_comment_section = '';
        $email_budget_info = '';

        if ($aksi == 'accept') {
            $email_status_text = 'DITERIMA';
            $email_color = '#28a745';
        } elseif ($aksi == 'reject') {
            $email_status_text = 'DITOLAK';
            $email_color = '#dc3545';
        }

        if (!empty($admin_comment)) {
            $email_comment_section = '<p><strong>Pesan dari Admin:</strong><br>' . nl2br(htmlspecialchars($admin_comment)) . '</p>';
        }

        // LOGIC UNTUK ACCEPT/REJECT
        if ($aksi == 'accept') {
            if ($current_status == 'pending') {
                $new_status = 'accepted';
                $message = 'Proposal berhasil diterima.';
                $email_subject = "Pengajuan Event '{$nama_event}' Anda Telah Diterima!";
                $email_body_content = '<p>Pengajuan event Anda dengan nama <strong>' . htmlspecialchars($nama_event) . '</strong> (Budget Rp ' . number_format($proposal_budget, 0, ',', '.') . ') telah <strong>DITERIMA</strong> oleh Admin.</p>';
                $email_body_content .= '<p>Anda dapat melihat status terbaru di dashboard Anda.</p>';

            } else if ($current_status == 'rejected') {
                $query_get_user_budget = "SELECT budget FROM users WHERE username = :username FOR UPDATE";
                $stmt_get_user_budget = $conn->prepare($query_get_user_budget);
                $stmt_get_user_budget->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                $stmt_get_user_budget->execute();
                $user_current_budget = (float) $stmt_get_user_budget->fetchColumn();

                if ($user_current_budget < $proposal_budget) {
                    throw new Exception("Sisa budget user " . htmlspecialchars($pengaju_username) . " tidak mencukupi untuk menerima proposal ini (Rp " . number_format($proposal_budget, 0, ',', '.') . "). Sisa: Rp " . number_format($user_current_budget, 0, ',', '.') . ".");
                }

                $query_update_user_budget_deduct = "UPDATE users SET budget = budget - :deduct_budget WHERE username = :username";
                $stmt_update_user_budget_deduct = $conn->prepare($query_update_user_budget_deduct);
                $stmt_update_user_budget_deduct->bindParam(':deduct_budget', $proposal_budget, PDO::PARAM_STR);
                $stmt_update_user_budget_deduct->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                $stmt_update_user_budget_deduct->execute();

                $new_status = 'accepted';
                $message = 'Proposal berhasil diterima (setelah sebelumnya ditolak). Budget user telah dikurangi kembali.';
                $email_subject = "Pengajuan Event '{$nama_event}' Anda Telah Diterima Kembali!";
                $email_body_content = '<p>Pengajuan event Anda dengan nama <strong>' . htmlspecialchars($nama_event) . '</strong> (Budget Rp ' . number_format($proposal_budget, 0, ',', '.') . ') yang sebelumnya ditolak, kini telah <strong>DITERIMA KEMBALI</strong> oleh Admin.</p>';
                $email_body_content .= '<p>Budget Anda telah dikurangi kembali sejumlah <strong>Rp ' . number_format($proposal_budget, 0, ',', '.') . '</strong>.</p>';
                $email_body_content .= '<p>Anda dapat melihat status terbaru di dashboard Anda.</p>';
                
            } else {
                throw new Exception("Proposal sudah " . $current_status . ", tidak bisa diterima lagi.");
            }
        } elseif ($aksi == 'reject') {
            if ($current_status == 'pending' || $current_status == 'accepted') {
                $new_status = 'rejected';
                
                if ($current_status != 'rejected') {
                    $query_update_user_budget_return = "UPDATE users SET budget = budget + :return_budget WHERE username = :username";
                    $stmt_update_user_budget_return = $conn->prepare($query_update_user_budget_return);
                    $stmt_update_user_budget_return->bindParam(':return_budget', $proposal_budget, PDO::PARAM_STR);
                    $stmt_update_user_budget_return->bindParam(':username', $pengaju_username, PDO::PARAM_STR);
                    $stmt_update_user_budget_return->execute();
                    $message = 'Proposal berhasil ditolak. Budget Rp ' . number_format($proposal_budget, 0, ',', '.') . ' dikembalikan ke user ' . htmlspecialchars($pengaju_username) . '.';
                    $email_budget_info = '<p>Budget Anda sejumlah <strong>Rp ' . number_format($proposal_budget, 0, ',', '.') . '</strong> telah dikembalikan ke akun Anda.</p>';
                } else {
                    $message = 'Proposal berhasil ditolak. Budget tidak perlu dikembalikan karena sudah ditolak sebelumnya.';
                    $email_budget_info = '<p>Budget tidak perlu dikembalikan karena pengajuan ini sudah berstatus ditolak sebelumnya.</p>';
                }

                $email_subject = "Pengajuan Event '{$nama_event}' Anda Telah Ditolak";
                $email_body_content = '<p>Dengan menyesal kami memberitahukan bahwa pengajuan event Anda dengan nama <strong>' . htmlspecialchars($nama_event) . '</strong> (Budget Rp ' . number_format($proposal_budget, 0, ',', '.') . ') telah <strong>DITOLAK</strong> oleh Admin.</p>';
                $email_body_content .= $email_budget_info;
                $email_body_content .= '<p>Mohon periksa dashboard Anda untuk detail lebih lanjut atau hubungi administrator jika Anda memiliki pertanyaan.</p>';

            } else {
                throw new Exception("Proposal sudah " . $current_status . ", tidak bisa ditolak lagi.");
            }
        } else {
            throw new Exception("Aksi status tidak valid.");
        }

        // Update status proposal
        $query_update_proposal_status = "UPDATE event_pengajuan SET status = :status WHERE id = :id";
        $stmt_update_proposal_status = $conn->prepare($query_update_proposal_status);
        $stmt_update_proposal_status->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt_update_proposal_status->bindParam(':id', $proposal_id, PDO::PARAM_INT);
        $stmt_update_proposal_status->execute();

        $conn->commit();
        $_SESSION['admin_message'] = $message;

        // --- KIRIM EMAIL DENGAN ERROR HANDLING ---
        if (!empty($pengaju_email) && ($aksi == 'accept' || $aksi == 'reject')) {
            if ($phpmailer_available) {
                $mail = new PHPMailer(true);
                try {
                    // Konfigurasi Server
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'nuraune21@gmail.com'; 
                    $mail->Password   = 'dqex vrjl aywg ffil'; 
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465; 
                    
                    // Penerima
                    $mail->setFrom('nuraune21@gmail.com', 'SIPENG-EVENT');
                    $mail->addAddress($pengaju_email, $pengaju_nama_lengkap);
                    
                    // Konten
                    $mail->isHTML(true);
                    $mail->Subject = $email_subject;
                    
                    // Bangun Body HTML
                    $email_html_body = '
                        <!DOCTYPE html>
                        <html lang="id">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <style>
                                body { font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                                .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); overflow: hidden; }
                                .header { background-color: #ffb791; color: #ffffff; padding: 20px; text-align: center; }
                                .header h1 { margin: 0; font-size: 24px; }
                                .content { padding: 30px; line-height: 1.6; color: #333333; }
                                .content p { margin-bottom: 15px; }
                                .content strong { color: #555555; }
                                .highlight { background-color: #e6f7ff; padding: 10px 15px; border-left: 5px solid ' . $email_color . '; margin: 20px 0; font-size: 1.1em; }
                                .footer { background-color: #f0f0f0; padding: 20px; text-align: center; font-size: 12px; color: #777777; border-top: 1px solid #e0e0e0; }
                                .button-link { display: inline-block; background-color: #007bff; color: #ffffff !important; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-top: 20px; }
                            </style>
                        </head>
                        <body>
                            <div class="email-container">
                                <div class="header">
                                    <h1>SIPENG-EVENT Notification</h1>
                                </div>
                                <div class="content">
                                    <p>Halo <strong>' . htmlspecialchars($pengaju_nama_lengkap) . '</strong>,</p>
                                    <p>Kami ingin memberitahukan status terbaru dari pengajuan event Anda.</p>
                                    <div class="highlight">
                                        Status Pengajuan: <strong>' . htmlspecialchars($nama_event) . '</strong> <span style="color:' . $email_color . '; font-weight: bold;">[' . $email_status_text . ']</span>
                                    </div>
                                    ' . $email_body_content . '
                                    ' . $email_comment_section . '
                                    <p>Terima kasih,</p>
                                    <p>Tim SIPENG-EVENT</p>
                                    <a href="' . BASE_URL . 'auth/login.php" class="button-link">Login ke Dashboard Anda</a>
                                </div>
                                <div class="footer">
                                    <p>&copy; ' . date('Y') . ' SIPENG-EVENT. Semua hak dilindungi.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ';

                    $mail->Body = $email_html_body;
                    $mail->AltBody = 'Halo ' . htmlspecialchars($pengaju_nama_lengkap) . ', Pengajuan event Anda "' . htmlspecialchars($nama_event) . '" telah ' . $email_status_text . '. ' . strip_tags($email_body_content) . ' ' . strip_tags($email_comment_section) . ' Terima kasih, Tim SIPENG-EVENT.';

                    $mail->send();
                    $_SESSION['admin_message'] .= " Email notifikasi berhasil dikirim ke " . htmlspecialchars($pengaju_email) . ".";
                } catch (Exception $e) {
                    $_SESSION['admin_message'] .= " Namun, email notifikasi gagal dikirim. Mailer Error: {$mail->ErrorInfo}";
                    error_log("Gagal mengirim email notifikasi proposal ke " . $pengaju_email . ": " . $mail->ErrorInfo);
                }
            } else {
                $_SESSION['admin_message'] .= " Email notifikasi tidak dikirim karena PHPMailer tidak tersedia.";
            }
        } else {
            $_SESSION['admin_message'] .= " Email notifikasi tidak dikirim karena email pengaju tidak ditemukan atau aksi tidak valid.";
        }

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['admin_message'] = 'Gagal memproses proposal: ' . htmlspecialchars($e->getMessage());
        error_log("Error di action.php: " . $e->getMessage());
    }

    header("Location: " . BASE_URL . "dashboard/admin.php?page=manage_proposals"); 
    exit;
} else {
    $_SESSION['admin_message'] = 'Aksi tidak valid.';
    header("Location: " . BASE_URL . "dashboard/admin.php?page=manage_proposals");
    exit;
}
?>