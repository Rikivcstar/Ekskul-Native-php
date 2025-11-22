<?php
// pembina/presensi/qr.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$current_user = getCurrentUser();
$pembina_id = intval($current_user['id'] ?? 0);

$jadwal_id = isset($_GET['jadwal']) ? intval($_GET['jadwal']) : 0;
if ($jadwal_id <= 0) {
    die("Parameter jadwal tidak valid.");
}

$BASE = rtrim(BASE_URL, '/');

// PERBAIKAN: URL yang BENAR tanpa ../
// Arahkan ke file checkin.php yang akan kita buat
$target_url = $BASE . "/presensis/checkin.php?jadwal={$jadwal_id}&pembina={$pembina_id}";

// Include library phpqrcode offline
require_once __DIR__ . '/../../libs/phpqrcode/qrlib.php';

// Folder penyimpanan QR
$qr_folder = __DIR__ . "/qrcodes/";
if (!file_exists($qr_folder)) {
    mkdir($qr_folder, 0777, true);
}

// File QR yang digenerate
$qr_file = $qr_folder . "qr_{$jadwal_id}_{$pembina_id}.png";

// Generate QR offline
QRcode::png($target_url, $qr_file, QR_ECLEVEL_L, 6, 2);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>QR Presensi Offline</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#eef2f5; padding:20px; font-family:Arial }
    .qr-card {
        max-width:420px;
        margin:auto;
        background:white;
        padding:20px;
        border-radius:12px;
        box-shadow:0 0 15px rgba(0,0,0,0.1);
        text-align:center;
    }
    .qr-img {
        width:100%;
        border:1px solid #ddd;
        padding:10px;
        background:white;
        border-radius:8px;
    }
  </style>
</head>
<body>

<div class="qr-card">
    <h3 class="mb-3">QR Presensi (Offline)</h3>
    <p><strong>Jadwal:</strong> #<?= $jadwal_id ?></p>
    <p><strong>Pembina:</strong> <?= htmlspecialchars($current_user['name']) ?></p>
    <img src="qrcodes/<?= "qr_{$jadwal_id}_{$pembina_id}.png" ?>" class="qr-img" alt="QR Presensi">
    <a href="qrcodes/<?= "qr_{$jadwal_id}_{$pembina_id}.png" ?>" 
       download="qr_presensi_jadwal_<?= $jadwal_id ?>.png"
       class="btn btn-success w-100 mt-2">
        Download QR
    </a>
    <p class="mt-3 text-muted small">
        QR ini dibuat secara offline menggunakan library phpqrcode.
    </p>
</div>

</body>
</html>