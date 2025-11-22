<?php
// config/database.php
// Konfigurasi Database untuk Laragon

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default Laragon kosong
define('DB_NAME', 'sistem_eskul');

// Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Fungsi Koneksi Database
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Koneksi gagal: " . $conn->connect_error);
        }
        
        // Set charset UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Error Database: " . $e->getMessage());
    }
}

// Buat koneksi global
$db = getConnection();

// Fungsi untuk query dengan prepared statement
function query($sql, $params = [], $types = null)
{
    global $db;

    // Deteksi jenis query
    $sql_trimmed = trim($sql);
    $is_select = stripos($sql_trimmed, 'SELECT') === 0;

    // Jika tidak ada parameter → jalankan query biasa
    if (empty($params)) {
        $result = $db->query($sql);

        if ($db->error) {
            // Untuk non-SELECT, kembalikan array dengan error
            if (!$is_select) {
                return ['success' => false, 'error' => $db->error];
            }
            throw new Exception("SQL Error: " . $db->error . " | Query: $sql");
        }

        // SELECT → kembalikan mysqli_result (backward compatible)
        if ($is_select) {
            return $result;
        }
        
        // INSERT/UPDATE/DELETE → kembalikan array
        return ['success' => true, 'affected_rows' => $db->affected_rows];
    }

    // Jika ada parameter → gunakan prepared statement
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        if (!$is_select) {
            return ['success' => false, 'error' => $db->error];
        }
        throw new Exception("Prepare failed: " . $db->error . " | Query: $sql");
    }

    // Jika belum dikasih $types → buat otomatis
    if ($types === null) {
        $types = "";
        foreach ($params as $p) {
            if (is_int($p)) $types .= "i";
            elseif (is_float($p)) $types .= "d";
            else $types .= "s"; // default string
        }
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        if (!$is_select) {
            return ['success' => false, 'error' => $stmt->error];
        }
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // SELECT → kembalikan mysqli_result (backward compatible)
    if ($is_select) {
        return $stmt->get_result();
    }
    
    // INSERT/UPDATE/DELETE → kembalikan array
    return ['success' => true, 'affected_rows' => $stmt->affected_rows];
}

// Fungsi untuk escape string
function escape($string) {
    global $db;
    $escaped = $db->real_escape_string($string);
    return $escaped;
}

// Base URL
define('BASE_URL', 'http://localhost/sistem_eskul/');

// Upload directory
define('UPLOAD_DIR', __DIR__ . '/../assets/img/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/img/uploads/');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi helper untuk redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

// Fungsi untuk set flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Fungsi untuk get dan hapus flash message
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Fungsi untuk check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk check role
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (!is_array($roles)) $roles = [$roles];
    return in_array($_SESSION['user_role'], $roles);
}

// Fungsi untuk require role tertentu
function requireRole_old($roles) {
    if (!isLoggedIn()) {
        redirect('admin/login.php');
    }
    if (!hasRole($roles)) {
        setFlash('danger', 'Anda tidak memiliki akses ke halaman ini!');
        redirect('');
    }
}

// Fungsi untuk get current user
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return false;

    $id = $_SESSION['user_id'];
    $result = query("SELECT * FROM users WHERE id = ?", [$id], 'i');

    if (!$result || $result->num_rows === 0) {
        return false;
    }

    return $result->fetch_assoc();
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $pecah = explode('-', $tanggal);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Fungsi untuk upload file
function uploadFile($file, $folder = '') {
    $target_dir = UPLOAD_DIR . $folder;
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . '/' . $new_filename;
    
    // Validasi tipe file
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    // Validasi ukuran file (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $folder . '/' . $new_filename,
            'path' => $target_file
        ];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

// Fungsi untuk delete file
function deleteFile($filename) {
    $file_path = UPLOAD_DIR . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}
?>