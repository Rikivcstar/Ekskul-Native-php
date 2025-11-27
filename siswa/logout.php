<?php
// siswa/logout.php
session_start();
require_once '../config/database.php';

// Simpan pesan sebelum destroy session
$_SESSION['flash_message'] = 'Anda telah berhasil logout!';
$_SESSION['flash_type'] = 'success';

// Hapus semua session kecuali flash message
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;

session_unset();
session_destroy();

// Start session baru untuk flash message
session_start();
if ($flash_message) {
    $_SESSION['flash_message'] = $flash_message;
    $_SESSION['flash_type'] = $flash_type;
}

// Redirect ke halaman login siswa
header("Location: " . BASE_URL . "login.php");
exit;
?>