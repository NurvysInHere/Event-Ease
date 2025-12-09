<?php
// Pastikan PHPMailer sudah diinstal via Composer atau diletakkan di direktori yang benar
require '../vendor/autoload.php'; // Sesuaikan path ini jika Anda menggunakan Composer
// Jika Anda tidak menggunakan Composer dan meletakkan PHPMailer secara manual:
// require_once '../path/to/PHPMailer/src/PHPMailer.php';
// require_once '../path/to/PHPMailer/src/SMTP.php';
// require_once '../path/to/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function send_budget_notification_email($recipient_email, $recipient_username, $new_budget) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Ganti dengan SMTP host Anda
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nuraune21@gmail.com'; // Ganti dengan email Anda
        $mail->Password   = 'dqex vrjl aywg ffil';    // Ganti dengan App Password/Password email Anda
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Gunakan ENCRYPTION_SMTPS untuk port 465
        $mail->Port       = 465;                   // Port TCP untuk SSL/TLS

        // Recipients
        $mail->setFrom('nuraune21@gmail.com', 'Admin SIPENG-EVENT'); // Email pengirim
        $mail->addAddress($recipient_email, $recipient_username); // Tambahkan penerima

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Pembaruan Budget Eskul Anda di SIPENG-EVENT';
        $mail->Body    = '
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
                    .highlight { background-color: #e6f7ff; padding: 10px 15px; border-left: 5px solid #007bff; margin: 20px 0; font-size: 1.1em; }
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
                        <p>Halo <strong>' . htmlspecialchars($recipient_username) . '</strong>,</p>
                        <p>Kami ingin memberitahukan bahwa budget pengajuan event untuk eskul Anda telah diperbarui oleh administrator kami.</p>
                        <div class="highlight">
                            Budget baru Anda adalah: <strong>Rp ' . number_format($new_budget, 2, ',', '.') . '</strong>
                        </div>
                        <p>Budget ini adalah jumlah maksimum yang dapat Anda ajukan untuk event eskul Anda.</p>
                        <p>Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi administrator.</p>
                        <p>Terima kasih,</p>
                        <p>Tim SIPENG-EVENT</p>
                        <a href="http://localhost/pengajuan_event/login.php" class="button-link">Login ke Dashboard Anda</a>
                    </div>
                    <div class="footer">
                        <p>&copy; ' . date('Y') . ' SIPENG-EVENT. Semua hak dilindungi.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
        $mail->AltBody = 'Halo ' . $recipient_username . ', Budget pengajuan event Anda di SIPENG-EVENT telah diperbarui. Budget baru Anda adalah Rp ' . number_format($new_budget, 2, ',', '.') . '. Jika Anda memiliki pertanyaan, silakan hubungi administrator. Terima kasih, Tim SIPENG-EVENT.';

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Untuk debugging, Anda bisa log error ini
        error_log("Gagal mengirim email budget ke " . $recipient_email . ": " . $mail->ErrorInfo);
        return false;
    }
}
?>