<?php
// pembina/api/get_anggota.php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$eskul_id = $_GET['eskul_id'] ?? 0;

// Validasi eskul_id
if (!$eskul_id) {
    echo json_encode([]);
    exit;
}

// Cek apakah eskul diampu oleh pembina
$check_eskul = query("
    SELECT id 
    FROM ekstrakurikulers 
    WHERE id = ? AND pembina_id = ?
", [$eskul_id, $current_user['id']], 'ii')->fetch_assoc();

if (!$check_eskul) {
    echo json_encode([]);
    exit;
}

// Ambil daftar anggota yang diterima
$anggota = query("
    SELECT 
        ae.id,
        COALESCE(u.name) as name,
        u.nis
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    WHERE ae.ekstrakurikuler_id = ? AND ae.status = 'diterima'
    ORDER BY u.nis ASC
", [$eskul_id], 'i');

$result = [];
while ($row = $anggota->fetch_assoc()) {
    $result[] = [
        'id' => $row['id'],
        'name' => $row['name'] . ' (' . $row['nis'] . ')'
    ];
}

echo json_encode($result);
?>