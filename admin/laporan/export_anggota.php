<?php
// admin/laporan/export_anggota.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

// Query data anggota
$data = query("
    SELECT 
        u.nis,
        u.name,
        u.kelas,
        u.jenis_kelamin,
        u.no_hp,
        u.alamat,
        e.nama_ekskul,
        ae.tanggal_daftar,
        ae.tanggal_diterima,
        ae.status
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    ORDER BY e.nama_ekskul, u.name
");

// Set header untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Data_Anggota_Ekstrakurikuler_' . date('Y-m-d') . '.csv"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tulis header kolom
fputcsv($output, [
    'No',
    'NIS',
    'Nama Lengkap',
    'Kelas',
    'Jenis Kelamin',
    'No HP',
    'Alamat',
    'Ekstrakurikuler',
    'Tanggal Daftar',
    'Tanggal Diterima',
    'Status'
]);

// Tulis data
$no = 1;
while ($row = $data->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['nis'],
        $row['name'],
        $row['kelas'],
        $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan',
        $row['no_hp'],
        $row['alamat'],
        $row['nama_ekskul'],
        date('d/m/Y', strtotime($row['tanggal_daftar'])),
        $row['tanggal_diterima'] ? date('d/m/Y', strtotime($row['tanggal_diterima'])) : '-',
        ucfirst($row['status'])
    ]);
}

fclose($output);
exit;
?>