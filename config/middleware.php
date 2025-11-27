<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function redirectToDashboard($role) {
    switch ($role) {
        case 'admin':
            header("Location: " . BASE_URL . "admin/dashboard.php");
            break;
        case 'pembina':
            header("Location: " . BASE_URL . "pembina/dashboard.php");
            break;
        case 'siswa':
            header("Location: " . BASE_URL . "siswa/dashboard.php");
            break;
        default:
            header("Location: " . BASE_URL . "index.php");
    }
    exit;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Silakan login terlebih dahulu!";
        header("Location: " . BASE_URL . "index.php");
        exit;
    }

    $user = getCurrentUser();

    // Jika user tidak ditemukan di DB (akun sudah dihapus)
    if (!$user) {
        session_destroy();
        header("Location: " . BASE_URL . "index.php");
        exit;
    }

    return $user;
}

function only($role) {
    $current_user = requireLogin();

    if ($current_user['role'] !== $role) {
        $_SESSION['error_message'] = "Akses ditolak! Anda tidak memiliki izin untuk halaman ini.";
        redirectToDashboard($current_user['role']);
    }

    return $current_user;
}

function requireRole(array $roles) {
    $current_user = requireLogin();

    if (!in_array($current_user['role'], $roles)) {
        $_SESSION['error_message'] = "Akses ditolak!";
        redirectToDashboard($current_user['role']);
    }

    return $current_user;
}

// Auto-inject global untuk file lama
if (isset($_SESSION['user_id'])) {
    $current_user = getCurrentUser();
}
?>
