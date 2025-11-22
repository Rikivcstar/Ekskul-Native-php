<?php
// pembina/laporan/export_presensi.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$eskul_id = $_GET['eskul_id'] ?? 0;
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

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

// Ambil data presensi per siswa
$rekap = query("
    SELECT 
        u.nis,
        COALESCE(u.name) as nama_siswa,
        COUNT(p.id) as total_pertemuan,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN p.status = 'alpha' THEN 1 ELSE 0 END) as alpha,
        ROUND((SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(p.id) * 100), 2) as persentase_kehadiran
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    LEFT JOIN presensis p ON ae.id = p.anggota_id AND p.tanggal BETWEEN ? AND ?
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    GROUP BY ae.id, u.nis, nama_siswa
    ORDER BY u.nis ASC
", [$dari, $sampai, $eskul_id], 'ssi');

// Set header untuk download CSV
$periode = date('d-m-Y', strtotime($dari)) . "_sd_" . date('d-m-Y', strtotime($sampai));
$filename = "Presensi_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $eskul['nama_ekskul']) . "_" . $periode . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, [
    'No',
    'NIS',
    'Nama Lengkap',
    'Hadir',
    'Izin',
    'Sakit',
    'Alpha',
    'Total Pertemuan',
    'Persentase Kehadiran (%)'
]);

// Data
$no = 1;
while ($row = $rekap->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['nis'],
        $row['nama_siswa'],
        $row['hadir'],
        $row['izin'],
        $row['sakit'],
        $row['alpha'],
        $row['total_pertemuan'],
        $row['persentase_kehadiran'] ?? 0
    ]);
}

fclose($output);
exit;
?>