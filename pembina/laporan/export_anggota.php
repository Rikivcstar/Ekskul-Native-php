<?php
// pembina/laporan/export_anggota.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$eskul_id = $_GET['eskul_id'] ?? 0;

if (!$eskul_id) {
    die("ID Ekstrakurikuler tidak valid");
}

// Cek apakah pembina berhak akses eskul ini
$eskul = query("
    SELECT nama_ekskul
    FROM ekstrakurikulers
    WHERE id = ? AND pembina_id = ?
", [$eskul_id, $current_user['id']], 'ii')->fetch_assoc();

if (!$eskul) {
    die("Anda tidak memiliki akses ke ekstrakurikuler ini");
}

// Ambil data anggota
$anggota = query("
    SELECT 
        u.nis,
        COALESCE(u.name) as nama_siswa,
        u.email,
        u.no_hp,
        u.alamat,
        ae.tanggal_daftar,
        ae.status,
        ae.nilai
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    ORDER BY u.nis ASC
", [$eskul_id], 'i');

// Set header untuk download CSV
$filename = "Anggota_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $eskul['nama_ekskul']) . "_" . date('Ymd') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8 agar Excel bisa baca dengan benar
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, [
    'No',
    'NIS',
    'Nama Lengkap',
    'Email',
    'No. HP',
    'Alamat',
    'Tanggal Daftar',
    'Status',
    'Nilai'
]);

// Data
$no = 1;
while ($row = $anggota->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['nis'],
        $row['nama_siswa'],
        $row['email'],
        $row['no_hp'] ?: '-',
        $row['alamat'] ?: '-',
        date('d/m/Y', strtotime($row['tanggal_daftar'])),
        ucfirst($row['status']),
        $row['nilai'] ?: '-'
    ]);
}

fclose($output);
exit;
?>