<?php
session_start();
require_once '../config/db.php';

// Pastikan hanya admin atau user yang bisa menghapus akunnya sendiri
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_to_delete = $_POST['user_id_to_delete'];
    
    // Validasi hak akses
    if ($_SESSION['role'] === 'admin' || ($_SESSION['role'] === 'user' && $_SESSION['id'] == $user_id_to_delete)) {
        
        $query_get_username = "SELECT username FROM users WHERE id = :id";
        $stmt_get_username = $conn->prepare($query_get_username);
        $stmt_get_username->bindParam(':id', $user_id_to_delete);
        $stmt_get_username->execute();
        $user_data = $stmt_get_username->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            $username_to_delete = $user_data['username'];
            
            try {
                // Hapus pengajuan event yang terkait
                $query_events = "DELETE FROM event_pengajuan WHERE pengaju = :username";
                $stmt_events = $conn->prepare($query_events);
                $stmt_events->bindParam(':username', $username_to_delete);
                $stmt_events->execute();
                
                // Hapus akun pengguna
                $query_user = "DELETE FROM users WHERE id = :id";
                $stmt_user = $conn->prepare($query_user);
                $stmt_user->bindParam(':id', $user_id_to_delete);
                $stmt_user->execute();
                
                if ($_SESSION['role'] === 'admin') {
                    header("Location: ../dashboard/manage_users.php");
                } else {
                    session_destroy();
                    header("Location: ../welcome.php");
                }
                exit;

            } catch(PDOException $e) {
                die("Error: " . $e->getMessage());
            }
        } else {
            header("Location: ../dashboard/manage_users.php");
            exit;
        }
    } else {
        echo "Akses ditolak.";
        exit;
    }
} else {
    header("Location: ../dashboard/manage_users.php");
    exit;
}
