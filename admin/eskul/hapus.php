<?php
// admin/eskul/hapus.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$id = $_GET['id'] ?? 0;

// Ambil data eskul untuk hapus gambar
$eskul = query("SELECT gambar FROM ekstrakurikulers WHERE id = ?", [$id], 'i');

if ($eskul && $eskul->num_rows > 0) {
    $data = $eskul->fetch_assoc();
    
    // Hapus gambar jika ada
    if ($data['gambar']) {
        deleteFile($data['gambar']);
    }
    
    // Hapus data
    $result = query("DELETE FROM ekstrakurikulers WHERE id = ?", [$id], 'i');
    
    if ($result['success']) {
        setFlash('success', 'Ekstrakurikuler berhasil dihapus!');
    } else {
        setFlash('danger', 'Gagal menghapus ekstrakurikuler!');
    }
} else {
    setFlash('danger', 'Data tidak ditemukan!');
}

redirect('admin/eskul/index.php');
?>