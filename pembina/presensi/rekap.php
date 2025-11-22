<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/middleware.php";
only('pembina');

// 1. Ambil ID Jadwal dan Tanggal dari URL
$jadwal_id = $_GET['jadwal'] ?? null;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d'); 

if (!$jadwal_id) {
    setFlash('error', 'ID Jadwal tidak ditemukan.');
    header("Location: index.php");
    exit;
}

// 2. Ambil detail Jadwal dan Ekskul
$detail_jadwal = query("
    SELECT 
        j.hari, j.jam_mulai, j.jam_selesai, j.lokasi, e.nama_ekskul, e.pembina_id
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    WHERE j.id = ?
", [$jadwal_id], "i");

if ($detail_jadwal->num_rows == 0) {
    setFlash('error', 'Jadwal tidak valid.');
    header("Location: index.php");
    exit;
}

$j = $detail_jadwal->fetch_assoc();

// 3. Pastikan Pembina yang login berhak melihat data ini
if ($j['pembina_id'] != $_SESSION['user_id']) {
    setFlash('error', 'Anda tidak memiliki akses untuk jadwal ini.');
    header("Location: index.php");
    exit;
}

// 4. Ambil data absensi (kehadiran)
// 4. Ambil data absensi (kehadiran)
$rekap_absensi = query("
    SELECT 
        p.status, p.waktu_presensi, p.keterangan, u.nis, u.name -- KOREKSI: Gunakan alias 'u' (users)
    FROM presensis p                           
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id 
    JOIN users u ON ae.user_id = u.id           -- KOREKSI: Ganti 'siswa' menjadi 'users'
    WHERE p.jadwal_id = ? AND p.tanggal = ?
    ORDER BY p.waktu_presensi ASC
", [$jadwal_id, $tanggal], "is");

// Format tanggal untuk tampilan
$tanggal_tampil = date('d F Y', strtotime($tanggal));

?>
<?php include "../../includes/berry_head.php"; ?>
<?php include "../../includes/berry_shell_open.php"; ?>

<div class="p-4">

<h2><i class="bi bi-list-columns-reverse"></i> Rekap Presensi Latihan</h2>
<p class="text-muted">Data kehadiran siswa untuk latihan pada tanggal <?= $tanggal_tampil ?>.</p>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h4>"" <?= htmlspecialchars($j['nama_ekskul']) ?> ""</h4>
        <p class="mb-1">
            <i class="bi bi-calendar-date"></i> Tanggal : <?= $tanggal_tampil ?>
        </p>
        <p class="mb-1">
            <i class="bi bi-clock"></i> Waktu: <?= $j['hari'] ?>, <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>
        </p>
        <p class="mb-0">
            <i class="bi bi-geo-alt"></i> Lokasi: <?= htmlspecialchars($j['lokasi']) ?>
        </p>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Presensi</a>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Daftar Kehadiran (<?= $rekap_absensi->num_rows ?> Siswa)</h5>
        
        <?php if ($rekap_absensi->num_rows == 0): ?>
            <div class="alert alert-info mt-3">Belum ada siswa yang melakukan presensi pada jadwal ini (<?= $tanggal_tampil ?>).</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Status</th>
                            <th>Keterangan Tambahan</th>
                            <th>Waktu Presensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($rekap = $rekap_absensi->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($rekap['nis']) ?></td>
                                <td><?= htmlspecialchars($rekap['name']) ?></td>
                                <td>
                                    <?php 
                                        // Menampilkan badge status (sesuai kolom 'status' di tabel presensis)
                                        $status_color = match($rekap['status']) {
                                            'hadir' => 'success',
                                            'izin' => 'warning',
                                            'sakit' => 'info',
                                            default => 'danger',
                                        };
                                    ?>
                                    <span class="badge bg-<?= $status_color ?>"><?= htmlspecialchars(ucfirst($rekap['status'])) ?></span>
                                </td>
                                <td><?= htmlspecialchars($rekap['keterangan']) ?: '-' ?></td>
                                <td><?= date('H:i:s', strtotime($rekap['waktu_presensi'])) ?> WIB</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

</div>

<?php include "../../includes/berry_shell_close.php"; ?>