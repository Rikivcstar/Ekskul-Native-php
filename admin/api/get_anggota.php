<?php
// admin/api/get_anggota.php
header('Content-Type: application/json');
require_once '../../config/database.php';

$eskul_id = $_GET['eskul_id'] ?? 0;

if ($eskul_id) {
    $anggota = query("
        SELECT ae.id, u.name, u.kelas, u.nis
        FROM anggota_ekskul ae
        JOIN users u ON ae.user_id = u.id
        WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
        ORDER BY u.name
    ", [$eskul_id], 'i');
    
    $result = [];
    while ($row = $anggota->fetch_assoc()) {
        $result[] = $row;
    }
    
    echo json_encode($result);
} else {
    echo json_encode([]);
}
?>