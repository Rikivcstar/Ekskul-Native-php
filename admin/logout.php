<?php
// admin/logout.php
session_start();
require_once '../config/database.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login dengan pesan
header("Location: " . BASE_URL . "admin/login_admin.php");
exit;
?>