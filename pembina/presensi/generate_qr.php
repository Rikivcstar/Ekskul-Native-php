<?php
// pembina/presensi/generate_qr.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

header('Content-Type: application/json');

$current_user = getCurrentUser();
$pembina_id = intval($current_user['id'] ?? 0);

$jadwal_id = isset($_GET['jadwal']) ? intval($_GET['jadwal']) : 0;

if ($jadwal_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter jadwal tidak valid.'
    ]);
    exit;
}

try {
    $BASE = rtrim(BASE_URL, '/');
    
    // URL target untuk presensi
    $target_url = $BASE . "/presensis/checkin.php?jadwal={$jadwal_id}&pembina={$pembina_id}";
    
    // Include library phpqrcode offline
    require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';
    
    // Folder penyimpanan QR
    $qr_folder = __DIR__ . "/qrcodes/";
    if (!file_exists($qr_folder)) {
        mkdir($qr_folder, 0777, true);
    }
    
    // File QR yang digenerate
    $qr_filename = "qr_{$jadwal_id}_{$pembina_id}.png";
    $qr_file = $qr_folder . $qr_filename;
    
    // Generate QR offline
    QRcode::png($target_url, $qr_file, QR_ECLEVEL_L, 6, 2);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'qr_url' => BASE_URL . 'pembina/presensi/qrcodes/' . $qr_filename,
        'target_url' => $target_url,
        'jadwal_id' => $jadwal_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>