<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: welcome.php");
    exit;
}
if ($_SESSION['role'] == 'admin') {
    header("Location: dashboard/admin.php");
} else {
    header("Location: dashboard/user.php");
}
?>
