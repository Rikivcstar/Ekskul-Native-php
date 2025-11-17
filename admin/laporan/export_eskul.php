<?php
// admin/laporan/export_eskul.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

// Query data eskul
$data = query("
    SELECT 
        e.id,
        e.nama_ekskul,
        e.deskripsi,
        u.name as nama_pembina,
        e.kuota,
        e.status,
        COUNT(CASE WHEN ae.status = 'diterima' THEN 1 END) as total_anggota,
        COUNT(CASE WHEN ae.status = 'pending' THEN 1 END) as total_pending
    FROM ekstrakurikulers e
    LEFT JOIN users u ON e.pembina_id = u.id
    LEFT JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    GROUP BY e.id
    ORDER BY e.nama_ekskul
");

// Set header untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Data_Ekstrakurikuler_' . date('Y-m-d') . '.csv"');

// Buat output stream
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tulis header kolom
fputcsv($output, [
    'No',
    'Nama Ekstrakurikuler',
    'Deskripsi',
    'Pembina',
    'Kuota',
    'Anggota Aktif',
    'Pending',
    'Persentase',
    'Status'
]);

// Tulis data
$no = 1;
while ($row = $data->fetch_assoc()) {
    $persentase = $row['kuota'] > 0 ? round(($row['total_anggota'] / $row['kuota']) * 100) : 0;
    
    fputcsv($output, [
        $no++,
        $row['nama_ekskul'],
        $row['deskripsi'],
        $row['nama_pembina'] ?? '-',
        $row['kuota'],
        $row['total_anggota'],
        $row['total_pending'],
        $persentase . '%',
        ucfirst($row['status'])
    ]);
}

fclose($output);
exit;
?>