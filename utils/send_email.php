<?php
// utils/send_email.php

// Manual require PHPMailer classes
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Mengirim email verifikasi akun yang diterima
 */
function send_verification_email($recipient_email, $recipient_username) {
    $mail = new PHPMailer(true);

    try {
        // *** PENGATURAN SERVER SMTP ***
        $mail->isSMTP();                                   
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;                         
        $mail->Username   = 'nuraune21@gmail.com';       
        $mail->Password   = 'dqex vrjl aywg ffil'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
        $mail->Port       = 465;                          

        // PENGATURAN PENGIRIM (FROM)
        $mail->setFrom('nuraune21@gmail.com', 'Admin SIPENG-EVENT'); 

        // PENGATURAN PENERIMA (TO)
        $mail->addAddress($recipient_email, $recipient_username);

        // Konten Email
        $mail->isHTML(true);                                       
        $mail->Subject = 'Verifikasi Akun SIPENG-EVENT Anda Telah Diterima!';
        $mail->Body    = "
            <html>
            <head>
                <title>Akun Anda Telah Diverifikasi!</title>
                <style>
                    body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #2c3e50; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                    .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 40px 30px; text-align: center; }
                    .content { padding: 40px 30px; background: #f8f9fa; }
                    .footer { text-align: center; font-size: 0.8em; color: #7f8c8d; padding: 20px; background: #2c3e50; color: white; }
                    .button { display: inline-block; background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 15px 0; }
                    .icon { font-size: 3em; margin-bottom: 20px; }
                    .budget-card { background: white; padding: 25px; border-radius: 12px; border-left: 4px solid #3498db; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <div class='icon'>🎉</div>
                        <h1>Verifikasi Akun Berhasil!</h1>
                        <p>Sistem Informasi Pengajuan Event (SIPENG-EVENT)</p>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>" . htmlspecialchars($recipient_username) . "</strong>,</p>
                        <p>Kami sangat senang memberitahukan bahwa akun SIPENG-EVENT Anda telah berhasil diverifikasi dan diterima oleh admin kami!</p>
                        <p>Anda sekarang dapat login ke sistem kami dan mulai menggunakan semua fitur yang tersedia.</p>
                        
                        <div style='text-align: center;'>
                            <a href='http://localhost/pengajuan_event/welcome.php' class='button'>Login Sekarang</a>
                        </div>
                        
                        <p>Terima kasih telah bergabung dengan kami!</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " SIPENG-EVENT. Semua Hak Dilindungi.</p>
                        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = 'Halo ' . $recipient_username . ', Akun SIPENG-EVENT Anda telah berhasil diverifikasi dan diterima oleh admin kami! Anda sekarang dapat login ke sistem kami. Kunjungi: http://localhost/pengajuan_event/welcome.php';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Gagal mengirim email verifikasi ke {$recipient_email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Mengirim email notifikasi budget kepada pengguna
 */
function send_budget_notification_email($recipient_email, $recipient_username, $budget_amount) {
    $mail = new PHPMailer(true);

    try {
        // *** PENGATURAN SERVER SMTP: SAMA DENGAN FUNGSI SEBELUMNYA ***
        $mail->isSMTP();                                   
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;                         
        $mail->Username   = 'nuraune21@gmail.com';       
        $mail->Password   = 'dqex vrjl aywg ffil'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
        $mail->Port       = 465;                          

        // PENGATURAN PENGIRIM (FROM)
        $mail->setFrom('nuraune21@gmail.com', 'Admin SIPENG-EVENT'); 

        // PENGATURAN PENERIMA (TO)
        $mail->addAddress($recipient_email, $recipient_username);

        // Konten Email - TANPA NOMINAL BUDGET
        $mail->isHTML(true);                                       
        $mail->Subject = 'Budget Eskul Anda Telah Diperbarui - SIPENG-EVENT';
        $mail->Body    = "
            <html>
            <head>
                <title>Budget Eskul Diperbarui</title>
                <style>
                    body { 
                        font-family: 'Poppins', Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #2c3e50; 
                        margin: 0; 
                        padding: 0; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: #ffffff; 
                        border-radius: 15px;
                        overflow: hidden;
                        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #3498db, #2980b9); 
                        color: white; 
                        padding: 40px 30px; 
                        text-align: center; 
                    }
                    .content { 
                        padding: 40px 30px; 
                        background: #f8f9fa; 
                    }
                    .footer { 
                        text-align: center; 
                        font-size: 0.8em; 
                        color: #7f8c8d; 
                        padding: 25px; 
                        background: #2c3e50; 
                        color: white; 
                    }
                    .button { 
                        display: inline-block; 
                        background: linear-gradient(135deg, #3498db, #2980b9); 
                        color: white; 
                        padding: 14px 30px; 
                        text-decoration: none; 
                        border-radius: 8px; 
                        font-weight: 600; 
                        margin: 20px 0; 
                        transition: all 0.3s ease;
                        border: none;
                        cursor: pointer;
                    }
                    .button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
                    }
                    .icon { 
                        font-size: 4em; 
                        margin-bottom: 20px; 
                        display: block;
                    }
                    .budget-card { 
                        background: white; 
                        padding: 30px; 
                        border-radius: 12px; 
                        border-left: 4px solid #2ecc71; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
                        margin: 25px 0; 
                        text-align: center;
                    }
                    .budget-title {
                        font-size: 1.3em;
                        color: #2c3e50;
                        font-weight: 600;
                        margin-bottom: 15px;
                    }
                    .budget-message {
                        font-size: 1.1em;
                        color: #27ae60;
                        font-weight: 500;
                        padding: 15px;
                        background: #e8f6ef;
                        border-radius: 8px;
                        border: 1px dashed #27ae60;
                    }
                    .info-box {
                        background: #e8f4fd;
                        padding: 20px;
                        border-radius: 10px;
                        margin: 20px 0;
                        border-left: 4px solid #3498db;
                    }
                    h1 {
                        margin: 0;
                        font-size: 2em;
                        font-weight: 700;
                    }
                    h2 {
                        color: #2c3e50;
                        margin-bottom: 20px;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <span class='icon'>💰</span>
                        <h1>Budget Eskul Diperbarui</h1>
                        <p>Sistem Informasi Pengajuan Event (SIPENG-EVENT)</p>
                    </div>
                    <div class='content'>
                        <h2>Halo, <strong>" . htmlspecialchars($recipient_username) . "! 👋</strong></h2>
                        
                        <p>Kami ingin memberitahukan bahwa <strong>budget eskul Anda telah diperbarui</strong> oleh administrator sistem.</p>
                        
                        <div class='budget-card'>
                            <div class='budget-title'>📊 Status Budget Terkini</div>
                            <div class='budget-message'>
                                ✅ Budget Anda telah disesuaikan
                            </div>
                            <p style='margin-top: 15px; color: #7f8c8d; font-size: 0.9em;'>
                                <i>Silakan login untuk melihat ketersediaan budget terbaru</i>
                            </p>
                        </div>

                        <div class='info-box'>
                            <h3 style='color: #3498db; margin-top: 0;'>📋 Informasi:</h3>
                            <ul style='text-align: left;'>
                                <li>Budget eskul Anda telah diperbarui oleh administrator</li>
                                <li>Username: <strong>" . htmlspecialchars($recipient_username) . "</strong></li>
                                <li>Waktu Update: " . date('d F Y H:i') . "</li>
                                <li>Status: <strong style='color: #27ae60;'>Telah Diperbarui ✅</strong></li>
                            </ul>
                        </div>

                        <p>Dengan budget yang telah disesuaikan, Anda dapat mengajukan event-event untuk eskul Anda sesuai dengan ketentuan yang berlaku.</p>
                        
                        <div style='text-align: center;'>
                            <a href='http://localhost/pengajuan_event/welcome.php' class='button'>📱 Login ke Sistem</a>
                        </div>
                        
                        <p style='text-align: center; color: #7f8c8d; margin-top: 20px;'>
                            <strong>Perhatian:</strong> Untuk mengetahui detail budget terkini, silakan login ke sistem.
                        </p>

                        <p>Jika Anda memiliki pertanyaan atau membutuhkan bantuan, silakan hubungi administrator.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " SIPENG-EVENT. Semua Hak Dilindungi.</p>
                        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Versi plain text untuk klien email yang tidak support HTML
        $mail->AltBody = "
BUDGET ESKUL DIPERBARUI - SIPENG-EVENT

Halo " . $recipient_username . "!

Kami ingin memberitahukan bahwa budget eskul Anda telah diperbarui oleh administrator sistem.

Status: Budget telah disesuaikan
Username: " . $recipient_username . "
Waktu Update: " . date('d F Y H:i') . "

Dengan budget yang telah disesuaikan, Anda dapat mengajukan event-event untuk eskul Anda sesuai dengan ketentuan yang berlaku.

Silakan login ke sistem untuk melihat ketersediaan budget terbaru:
http://localhost/pengajuan_event/welcome.php

Untuk mengetahui detail budget terkini, silakan login ke sistem.

Jika Anda memiliki pertanyaan atau membutuhkan bantuan, silakan hubungi administrator.

---
© " . date('Y') . " SIPENG-EVENT. Semua Hak Dilindungi.
Email ini dikirim secara otomatis, mohon tidak membalas email ini.
        ";

        $mail->send();
        error_log("Email notifikasi budget berhasil dikirim ke: {$recipient_email}");
        return true;
    } catch (Exception $e) {
        error_log("Gagal mengirim email notifikasi budget ke {$recipient_email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Mengirim email reset password dengan TOKEN (bukan link)
 */
function send_password_reset_token_email($recipient_email, $recipient_username, $reset_token) {
    $mail = new PHPMailer(true);

    try {
        // *** PENGATURAN SERVER SMTP ***
        $mail->isSMTP();                                   
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;                         
        $mail->Username   = 'nuraune21@gmail.com';       
        $mail->Password   = 'dqex vrjl aywg ffil'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
        $mail->Port       = 465;                          

        // PENGATURAN PENGIRIM (FROM)
        $mail->setFrom('nuraune21@gmail.com', 'Sistem Pengajuan Event'); 

        // PENGATURAN PENERIMA (TO)
        $mail->addAddress($recipient_email, $recipient_username);

        // Konten Email - DENGAN TOKEN
        $mail->isHTML(true);                                       
        $mail->Subject = 'Reset Password - Sistem Pengajuan Event';
        $mail->Body    = "
            <html>
            <head>
                <title>Reset Password</title>
                <style>
                    body { 
                        font-family: 'Poppins', Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #2c3e50; 
                        margin: 0; 
                        padding: 0; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: #ffffff; 
                        border-radius: 15px;
                        overflow: hidden;
                        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #3498db, #2980b9); 
                        color: white; 
                        padding: 40px 30px; 
                        text-align: center; 
                    }
                    .content { 
                        padding: 40px 30px; 
                        background: #f8f9fa; 
                    }
                    .footer { 
                        text-align: center; 
                        font-size: 0.8em; 
                        color: #7f8c8d; 
                        padding: 25px; 
                        background: #2c3e50; 
                        color: white; 
                    }
                    .button { 
                        display: inline-block; 
                        background: linear-gradient(135deg, #3498db, #2980b9); 
                        color: white; 
                        padding: 14px 30px; 
                        text-decoration: none; 
                        border-radius: 8px; 
                        font-weight: 600; 
                        margin: 20px 0; 
                        transition: all 0.3s ease;
                        border: none;
                        cursor: pointer;
                    }
                    .button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
                    }
                    .icon { 
                        font-size: 4em; 
                        margin-bottom: 20px; 
                        display: block;
                    }
                    .token-box { 
                        background: linear-gradient(135deg, #2c3e50, #34495e); 
                        color: white; 
                        padding: 25px; 
                        border-radius: 12px; 
                        margin: 25px 0; 
                        text-align: center;
                        font-family: 'Courier New', monospace;
                        font-size: 1.2em;
                        font-weight: 600;
                        letter-spacing: 1px;
                        border: 2px solid #3498db;
                        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                    }
                    .info-box {
                        background: #e8f4fd;
                        padding: 20px;
                        border-radius: 10px;
                        margin: 20px 0;
                        border-left: 4px solid #3498db;
                    }
                    .warning-box {
                        background: #fef9e7;
                        padding: 15px;
                        border-radius: 8px;
                        border-left: 4px solid #f39c12;
                        margin: 15px 0;
                    }
                    .steps-box {
                        background: #f8f9fa;
                        padding: 20px;
                        border-radius: 10px;
                        border: 2px solid #e9ecef;
                        margin: 20px 0;
                    }
                    .step {
                        display: flex;
                        align-items: center;
                        margin-bottom: 15px;
                        padding: 12px;
                        background: white;
                        border-radius: 8px;
                        border-left: 4px solid #3498db;
                    }
                    .step-number {
                        background: linear-gradient(135deg, #3498db, #2980b9);
                        color: white;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        margin-right: 15px;
                        flex-shrink: 0;
                    }
                    h1 {
                        margin: 0;
                        font-size: 2em;
                        font-weight: 700;
                    }
                    h2 {
                        color: #2c3e50;
                        margin-bottom: 20px;
                    }
                    .highlight {
                        background: linear-gradient(135deg, #ffeaa7, #fab1a0);
                        padding: 3px 8px;
                        border-radius: 5px;
                        font-weight: 600;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <span class='icon'>🔐</span>
                        <h1>Reset Password</h1>
                        <p>Sistem Pengajuan Event</p>
                    </div>
                    <div class='content'>
                        <h2>Halo, <strong>" . htmlspecialchars($recipient_username) . "!</strong></h2>
                        
                        <p>Kami menerima permintaan reset password untuk akun Anda. Gunakan <span class='highlight'>token berikut</span> untuk mengatur ulang password Anda.</p>
                        
                        <div class='token-box'>
                            🔑 TOKEN RESET PASSWORD<br>
                            <strong style='font-size: 1.4em; letter-spacing: 2px;'>" . htmlspecialchars($reset_token) . "</strong>
                        </div>

                        <div class='steps-box'>
                            <h3 style='color: #2c3e50; margin-top: 0; text-align: center;'>📝 Langkah-langkah Reset Password:</h3>
                            
                            <div class='step'>
                                <div class='step-number'>1</div>
                                <div>Kunjungi halaman: <strong>http://localhost/pengajuan_event/auth/reset_password.php</strong></div>
                            </div>
                            
                            <div class='step'>
                                <div class='step-number'>2</div>
                                <div>Masukkan <strong>token di atas</strong> pada form yang tersedia</div>
                            </div>
                            
                            <div class='step'>
                                <div class='step-number'>3</div>
                                <div>Buat <strong>password baru</strong> dan konfirmasi</div>
                            </div>
                            
                            <div class='step'>
                                <div class='step-number'>4</div>
                                <div>Klik <strong>\"Reset Password\"</strong> untuk menyimpan</div>
                            </div>
                        </div>

                        <div class='warning-box'>
                            <strong>⏰ Penting:</strong> Token reset password ini akan kadaluarsa dalam <strong>1 jam</strong>.
                        </div>

                        <div class='info-box'>
                            <h3 style='color: #3498db; margin-top: 0;'>🛡️ Informasi Keamanan:</h3>
                            <ul style='text-align: left; margin: 0; padding-left: 20px;'>
                                <li>Jangan bagikan token ini kepada siapapun</li>
                                <li>Token hanya dapat digunakan <strong>satu kali</strong></li>
                                <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                                <li>Password Anda tidak akan berubah sampai Anda membuat yang baru</li>
                                <li>Pastikan Anda mengunjungi website resmi kami</li>
                            </ul>
                        </div>

                        <div style='text-align: center; margin-top: 30px;'>
                            <a href='http://localhost/pengajuan_event/auth/reset_password.php' class='button'>🎯 Pergi ke Halaman Reset Password</a>
                        </div>

                        <p style='text-align: center; color: #7f8c8d; margin-top: 20px;'>
                            <strong>Perhatian:</strong> Token bersifat rahasia dan hanya untuk Anda.
                        </p>

                        <p>Jika Anda mengalami kesulitan atau memiliki pertanyaan, silakan hubungi administrator sistem.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Sistem Pengajuan Event. Semua Hak Dilindungi.</p>
                        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Versi plain text untuk klien email yang tidak support HTML
        $mail->AltBody = "
RESET PASSWORD - SISTEM PENGAJUAN EVENT

Halo " . $recipient_username . "!

Kami menerima permintaan reset password untuk akun Anda. 
Gunakan TOKEN berikut untuk mengatur ulang password Anda:

TOKEN RESET PASSWORD: " . $reset_token . "

LANGKAH-LANGKAH RESET PASSWORD:
1. Kunjungi halaman: http://localhost/pengajuan_event/auth/reset_password.php
2. Masukkan TOKEN di atas pada form yang tersedia
3. Buat PASSWORD BARU dan konfirmasi
4. Klik \"Reset Password\" untuk menyimpan

⏰ PENTING: 
- Token reset password ini akan kadaluarsa dalam 1 jam
- Jangan bagikan token ini kepada siapapun
- Token hanya dapat digunakan satu kali
- Jika Anda tidak meminta reset password, abaikan email ini

INFORMASI KEAMANAN:
- Jangan bagikan token ini kepada siapapun
- Token hanya dapat digunakan satu kali
- Jika Anda tidak meminta reset password, abaikan email ini
- Password Anda tidak akan berubah sampai Anda membuat yang baru
- Pastikan Anda mengunjungi website resmi kami

Jika Anda mengalami kesulitan atau memiliki pertanyaan, silakan hubungi administrator sistem.

---
© " . date('Y') . " Sistem Pengajuan Event. Semua Hak Dilindungi.
Email ini dikirim secara otomatis, mohon tidak membalas email ini.
        ";

        $mail->send();
        error_log("Email reset password dengan token berhasil dikirim ke: {$recipient_email}");
        return true;
    } catch (Exception $e) {
        error_log("Gagal mengirim email reset password dengan token ke {$recipient_email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Mengirim email penolakan event kepada pengguna
 */
function send_event_rejection_email($recipient_email, $recipient_username, $event_name, $rejection_reason) {
    $mail = new PHPMailer(true);

    try {
        // *** PENGATURAN SERVER SMTP ***
        $mail->isSMTP();                                   
        $mail->Host       = 'smtp.gmail.com';             
        $mail->SMTPAuth   = true;                         
        $mail->Username   = 'nuraune21@gmail.com';       
        $mail->Password   = 'dqex vrjl aywg ffil'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  
        $mail->Port       = 465;                          

        // PENGATURAN PENGIRIM (FROM)
        $mail->setFrom('nuraune21@gmail.com', 'Admin SIPENG-EVENT'); 

        // PENGATURAN PENERIMA (TO)
        $mail->addAddress($recipient_email, $recipient_username);

        // Konten Email
        $mail->isHTML(true);                                       
        $mail->Subject = 'Pengajuan Event Ditolak - SIPENG-EVENT';
        $mail->Body    = "
            <html>
            <head>
                <title>Pengajuan Event Ditolak</title>
                <style>
                    body { 
                        font-family: 'Poppins', Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #2c3e50; 
                        margin: 0; 
                        padding: 0; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: #ffffff; 
                        border-radius: 15px;
                        overflow: hidden;
                        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #e74c3c, #c0392b); 
                        color: white; 
                        padding: 40px 30px; 
                        text-align: center; 
                    }
                    .content { 
                        padding: 40px 30px; 
                        background: #f8f9fa; 
                    }
                    .footer { 
                        text-align: center; 
                        font-size: 0.8em; 
                        color: #7f8c8d; 
                        padding: 25px; 
                        background: #2c3e50; 
                        color: white; 
                    }
                    .button { 
                        display: inline-block; 
                        background: linear-gradient(135deg, #3498db, #2980b9); 
                        color: white; 
                        padding: 14px 30px; 
                        text-decoration: none; 
                        border-radius: 8px; 
                        font-weight: 600; 
                        margin: 20px 0; 
                        transition: all 0.3s ease;
                    }
                    .button:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
                    }
                    .icon { 
                        font-size: 4em; 
                        margin-bottom: 20px; 
                        display: block;
                    }
                    .rejection-box { 
                        background: white; 
                        padding: 25px; 
                        border-radius: 12px; 
                        border-left: 4px solid #e74c3c; 
                        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
                        margin: 25px 0; 
                    }
                    .reason-box {
                        background: #fdedec;
                        padding: 20px;
                        border-radius: 8px;
                        border: 1px solid #f5b7b1;
                        margin: 15px 0;
                    }
                    .info-box {
                        background: #e8f4fd;
                        padding: 20px;
                        border-radius: 10px;
                        margin: 20px 0;
                        border-left: 4px solid #3498db;
                    }
                    h1 {
                        margin: 0;
                        font-size: 2em;
                        font-weight: 700;
                    }
                    h2 {
                        color: #2c3e50;
                        margin-bottom: 20px;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <span class='icon'>❌</span>
                        <h1>Pengajuan Event Ditolak</h1>
                        <p>Sistem Informasi Pengajuan Event (SIPENG-EVENT)</p>
                    </div>
                    <div class='content'>
                        <h2>Halo, <strong>" . htmlspecialchars($recipient_username) . "!</strong></h2>
                        
                        <p>Kami ingin memberitahukan bahwa pengajuan event Anda <strong>tidak dapat disetujui</strong> pada saat ini.</p>
                        
                        <div class='rejection-box'>
                            <h3 style='color: #e74c3c; margin-top: 0;'>📋 Detail Pengajuan Event:</h3>
                            <p><strong>Nama Event:</strong> " . htmlspecialchars($event_name) . "</p>
                            <p><strong>Status:</strong> <span style='color: #e74c3c; font-weight: 600;'>Ditolak</span></p>
                            <p><strong>Tanggal Penolakan:</strong> " . date('d F Y H:i') . "</p>
                        </div>

                        <div class='reason-box'>
                            <h4 style='color: #c0392b; margin-top: 0;'>📝 Alasan Penolakan:</h4>
                            <p style='font-style: italic;'>\"" . htmlspecialchars($rejection_reason) . "\"</p>
                        </div>

                        <div class='info-box'>
                            <h3 style='color: #3498db; margin-top: 0;'>💡 Saran untuk Pengajuan Selanjutnya:</h3>
                            <ul style='text-align: left;'>
                                <li>Perbaiki alasan penolakan yang disebutkan di atas</li>
                                <li>Pastikan semua data yang diajukan sudah lengkap dan valid</li>
                                <li>Periksa kembali budget yang diajukan sesuai dengan ketentuan</li>
                                <li>Hubungi administrator jika membutuhkan bantuan</li>
                            </ul>
                        </div>

                        <p>Jangan berkecil hati! Anda dapat mengajukan event baru dengan memperbaiki hal-hal yang disebutkan di atas.</p>
                        
                        <div style='text-align: center;'>
                            <a href='http://localhost/pengajuan_event/welcome.php' class='button'>📱 Ajukan Event Baru</a>
                        </div>

                        <p>Terima kasih atas pengertiannya.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " SIPENG-EVENT. Semua Hak Dilindungi.</p>
                        <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Versi plain text
        $mail->AltBody = "
PENGAJUAN EVENT DITOLAK - SIPENG-EVENT

Halo " . $recipient_username . "!

Kami ingin memberitahukan bahwa pengajuan event Anda TIDAK DAPAT DISETUJUI pada saat ini.

DETAIL PENGAJUAN EVENT:
- Nama Event: " . $event_name . "
- Status: Ditolak
- Tanggal Penolakan: " . date('d F Y H:i') . "

ALASAN PENOLAKAN:
" . $rejection_reason . "

SARAN UNTUK PENGAJUAN SELANJUTNYA:
- Perbaiki alasan penolakan yang disebutkan di atas
- Pastikan semua data yang diajukan sudah lengkap dan valid
- Periksa kembali budget yang diajukan sesuai dengan ketentuan
- Hubungi administrator jika membutuhkan bantuan

Jangan berkecil hati! Anda dapat mengajukan event baru dengan memperbaiki hal-hal yang disebutkan di atas.

Login ke sistem: http://localhost/pengajuan_event/welcome.php

Terima kasih atas pengertiannya.

---
© " . date('Y') . " SIPENG-EVENT. Semua Hak Dilindungi.
Email ini dikirim secara otomatis, mohon tidak membalas email ini.
        ";

        $mail->send();
        error_log("Email penolakan event berhasil dikirim ke: {$recipient_email}");
        return true;
    } catch (Exception $e) {
        error_log("Gagal mengirim email penolakan event ke {$recipient_email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Debug info untuk memastikan file terload dengan benar
error_log("send_email.php loaded successfully - All functions available");
?>