<?php
// presensis/checkin.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/middleware.php';

// Pastikan user adalah siswa yang sudah login
$current_user = only('siswa');

$jadwal_id = isset($_GET['jadwal']) ? intval($_GET['jadwal']) : 0;
$pembina_id = isset($_GET['pembina']) ? intval($_GET['pembina']) : 0;

// Validasi parameter
if ($jadwal_id <= 0 || $pembina_id <= 0) {
    $_SESSION['error_message'] = "⚠️ Parameter tidak valid! QR Code mungkin rusak atau kadaluarsa.";
    header("Location: " . BASE_URL . "siswa/presensi/");
    exit;
}

// Cek apakah jadwal valid
$jadwal = query("
    SELECT j.*, e.nama_ekskul, e.id as eskul_id
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    WHERE j.id = ? AND j.ekstrakurikuler_id IN (
        SELECT ekstrakurikuler_id FROM jadwal_latihans WHERE id = ?
    )
", [$jadwal_id, $jadwal_id], 'ii')->fetch_assoc();

if (!$jadwal) {
    $_SESSION['error_message'] = "❌ Jadwal tidak ditemukan! Pastikan QR Code yang Anda scan masih valid.";
    header("Location: " . BASE_URL . "siswa/presensi/");
    exit;
}

// Cek apakah siswa terdaftar di eskul ini
$anggota = query("
    SELECT id 
    FROM anggota_ekskul 
    WHERE user_id = ? AND ekstrakurikuler_id = ? AND status = 'diterima'
", [$current_user['id'], $jadwal['eskul_id']], 'ii')->fetch_assoc();

if (!$anggota) {
    $_SESSION['error_message'] = "❌ Anda tidak terdaftar di ekstrakurikuler <strong>" . htmlspecialchars($jadwal['nama_ekskul']) . "</strong>!";
    header("Location: " . BASE_URL . "siswa/presensi/");
    exit;
}

$anggota_id = $anggota['id'];
$tanggal_sekarang = date('Y-m-d');
$waktu_sekarang = date('Y-m-d H:i:s');

// Cek apakah sudah presensi hari ini (untuk ekstrakurikuler ini)
$cek_presensi = query("
    SELECT id, status, waktu_presensi
    FROM presensis 
    WHERE anggota_id = ? AND tanggal = ? AND jadwal_id = ?
", [$anggota_id, $tanggal_sekarang, $jadwal_id], 'isi')->fetch_assoc();

if ($cek_presensi) {
    $waktu_presensi_lama = date('H:i', strtotime($cek_presensi['waktu_presensi']));
    $_SESSION['info_message'] = "ℹ️ Anda sudah melakukan presensi hari ini pada pukul <strong>{$waktu_presensi_lama}</strong> dengan status: <strong>" . strtoupper($cek_presensi['status']) . "</strong>";
    header("Location: " . BASE_URL . "siswa/presensi/");
    exit;
}

// Simpan presensi baru dengan status "hadir"
$insert = query("
    INSERT INTO presensis (anggota_id, jadwal_id, tanggal, status, waktu_presensi, keterangan)
    VALUES (?, ?, ?, 'hadir', ?, 'Check-in via QR Code')
", [$anggota_id, $jadwal_id, $tanggal_sekarang, $waktu_sekarang], 'iiss');

if ($insert) {
    $jam_presensi = date('H:i', strtotime($waktu_sekarang));
    $_SESSION['success_message'] = "✅ <strong>Presensi Berhasil!</strong><br>Ekstrakurikuler: <strong>" . htmlspecialchars($jadwal['nama_ekskul']) . "</strong><br>Waktu: <strong>{$jam_presensi} WIB</strong><br>Selamat mengikuti kegiatan!";
} else {
    $_SESSION['error_message'] = "❌ Gagal menyimpan presensi. Silakan hubungi pembina atau coba lagi.";
}

// Redirect kembali ke halaman presensi siswa
header("Location: " . BASE_URL . "siswa/presensi/");
exit;
?>