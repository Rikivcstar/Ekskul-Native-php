<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/middleware.php';

only('siswa');
requireRole(['siswa']);
$page_title = 'Presensi Saya';

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
$eskul_filter = $_GET['eskul'] ?? '';

// --- Logika Query Presensi ---
// PERBAIKAN: Cek hasil query terlebih dahulu
$result_anggota = query("SELECT id FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'", [$current_user['id']], 'i');
$anggota_eskul = $result_anggota && $result_anggota->num_rows > 0 ? $result_anggota->fetch_assoc() : null;
$anggota_id = $anggota_eskul['id'] ?? 0;

$where_clause = "ae.user_id = ?";
$params = [$current_user['id']];
$types = 'i';

if ($eskul_filter) {
    $where_clause .= " AND e.id = ?";
    $params[] = $eskul_filter;
    $types .= 'i';
}

if ($bulan && $tahun) {
    $where_clause .= " AND MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
    $types .= 'ii';
}

$presensi = query("
    SELECT p.id, p.tanggal, p.status, p.keterangan, e.nama_ekskul, e.id as eskul_id, p.waktu_presensi
    FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE $where_clause
    ORDER BY p.tanggal DESC
", $params, $types);

// Query Statistik - PERBAIKAN: Cek hasil query
$result_stats = query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN p.status = 'alpa' THEN 1 ELSE 0 END) as alpa
    FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
    " . ($bulan && $tahun ? "AND MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?" : ""),
    $bulan && $tahun ? [$current_user['id'], $bulan, $tahun] : [$current_user['id']],
    $bulan && $tahun ? 'iii' : 'i'
);

// PERBAIKAN: Set default jika query gagal
$stats = $result_stats && $result_stats->num_rows > 0 ? $result_stats->fetch_assoc() : [
    'total' => 0,
    'hadir' => 0,
    'izin' => 0,
    'sakit' => 0,
    'alpa' => 0
];

// Query List Eskul
$eskul_list = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
", [$current_user['id']], 'i');

$persentase_hadir = $stats['total'] > 0 ? round(($stats['hadir'] / $stats['total']) * 100) : 0;

require_once '../../includes/berry_siswa_head.php';
require_once '../../includes/berry_siswa_shell_open.php';
?>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<style>
.stat-card {
    border-left: 4px solid var(--bs-primary);
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(15,23,42,.1);
}
#reader {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    border: 2px solid #007bff;
    border-radius: 10px;
}
</style>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['info_message'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?= $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="badge bg-light text-primary mb-2"><i class="bi bi-clipboard-check"></i> Presensi</span>
        <h3 class="fw-bold mb-1">Riwayat Kehadiran</h3>
        <p class="text-muted mb-0">Pantau catatan kehadiran Anda di setiap kegiatan ekstrakurikuler.</p>
    </div>
    <div>
        <button type="button" class="btn btn-lg btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#qrScanModal">
            <i class="bi bi-qr-code-scan me-2"></i> Presensi Sekarang
        </button>
    </div>
</div>

<!-- Modal QR Scanner -->
<div class="modal fade" id="qrScanModal" tabindex="-1" aria-labelledby="qrScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white" id="qrScanModalLabel"><i class="bi bi-camera me-2"></i> Scan QR Code Presensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopScanner()"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted">Arahkan kamera ke QR Code yang ditampilkan oleh Pembina.</p>
                <div id="reader"></div>
                <div id="scan-status" class="mt-3"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopScanner()">Tutup</button>
            </div>
        </div>
    </div>
</div>

<hr>

<!-- Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php
                    $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    for ($i = 1; $i <= 12; $i++):
                        $val = sprintf('%02d', $i);
                    ?>
                        <option value="<?php echo $val; ?>" <?php echo $bulan == $val ? 'selected' : ''; ?>>
                            <?php echo $nama_bulan[$i]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ekstrakurikuler</label>
                <select name="eskul" class="form-select">
                    <option value="">Semua Eskul</option>
                    <?php 
                    if ($eskul_list && $eskul_list->num_rows > 0):
                        while ($e = $eskul_list->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $eskul_filter == $e['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['nama_ekskul']); ?>
                        </option>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<hr>

<!-- Statistik Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card border-0">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Pertemuan</h6>
                <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0">
            <div class="card-body">
                <h6 class="text-muted mb-1">Hadir</h6>
                <h2 class="mb-0 text-success"><?php echo $stats['hadir']; ?></h2>
                <small class="text-success"><?php echo $persentase_hadir; ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0">
            <div class="card-body">
                <h6 class="text-muted mb-1">Izin / Sakit</h6>
                <h2 class="mb-0 text-warning"><?php echo $stats['izin'] + $stats['sakit']; ?></h2>
                <small class="text-warning">Izin: <?php echo $stats['izin']; ?> | Sakit: <?php echo $stats['sakit']; ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card border-0">
            <div class="card-body">
                <h6 class="text-muted mb-1">Alpa</h6>
                <h2 class="mb-0 text-danger"><?php echo $stats['alpa']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar Kehadiran -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="mb-3">Persentase Kehadiran</h6>
        <div class="progress" style="height:30px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $persentase_hadir; ?>%;"
                aria-valuenow="<?php echo $persentase_hadir; ?>" aria-valuemin="0" aria-valuemax="100">
                <?php echo $persentase_hadir; ?>% Hadir
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            <?php if ($persentase_hadir >= 80): ?>
                <i class="bi bi-emoji-smile text-success"></i> Kehadiran Anda sangat baik! Pertahankan!
            <?php elseif ($persentase_hadir >= 60): ?>
                <i class="bi bi-emoji-neutral text-warning"></i> Kehadiran cukup, tingkatkan lagi ya!
            <?php else: ?>
                <i class="bi bi-emoji-frown text-danger"></i> Kehadiran kurang, ayo lebih rajin hadir!
            <?php endif; ?>
        </small>
    </div>
</div>

<hr>

<!-- Tabel Riwayat Presensi -->
<?php if ($presensi && $presensi->num_rows > 0): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-table"></i> Riwayat Presensi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Ekstrakurikuler</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $badge_status = ['hadir' => 'success', 'izin' => 'warning', 'sakit' => 'info', 'alpa' => 'danger'];
                        $icon_status = ['hadir' => 'check-circle', 'izin' => 'file-earmark-text', 'sakit' => 'file-earmark-medical', 'alpa' => 'x-circle'];
                        while ($p = $presensi->fetch_assoc()):
                            $waktu_absen = $p['waktu_presensi'] ? date('H:i:s', strtotime($p['waktu_presensi'])) : '-';
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><i class="bi bi-calendar3"></i> <?php echo formatTanggal($p['tanggal']); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo $waktu_absen; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($p['nama_ekskul']); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $badge_status[$p['status']]; ?>">
                                        <i class="bi bi-<?php echo $icon_status[$p['status']]; ?>"></i> <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $p['keterangan'] ? '<small class="text-muted">'.htmlspecialchars($p['keterangan']).'</small>' : '<small class="text-muted">-</small>'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-x text-muted" style="font-size:4rem;opacity:.2;"></i>
            <h5 class="mt-3 text-muted">Belum ada data presensi</h5>
            <p class="text-muted mb-0">Tidak ditemukan catatan kehadiran untuk periode yang dipilih.</p>
        </div>
    </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
    <i class="bi bi-info-circle"></i> Presensi dicatat oleh pembina pada setiap pertemuan menggunakan <strong>QR Code</strong>. Pastikan Anda selalu hadir tepat waktu dan memindai kode saat kegiatan berlangsung.
</div>

<?php require_once '../../includes/berry_siswa_shell_close.php'; ?>

<script>
let html5QrcodeScanner = null;
let scannerRunning = false;

function stopScanner() {
    if (html5QrcodeScanner && scannerRunning) {
        html5QrcodeScanner.clear()
            .then(() => {
                console.log("QR Code Scanner dihentikan.");
                scannerRunning = false;
                html5QrcodeScanner = null;
            })
            .catch(error => {
                console.error("Gagal menghentikan scanner:", error);
                scannerRunning = false;
            });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    console.log("QR Code detected:", decodedText);
    stopScanner();
    
    const scanStatus = document.getElementById('scan-status');
    scanStatus.innerHTML = '<div class="alert alert-warning"><i class="bi bi-hourglass-split"></i> Memproses presensi...</div>';
    
    setTimeout(() => {
        window.location.href = decodedText;
    }, 500);
}

function onScanFailure(error) {
    // Silent error handling
}

function startScanner() {
    const scanStatus = document.getElementById('scan-status');
    
    if (scannerRunning) {
        console.log("Scanner sudah berjalan");
        return;
    }
    
    try {
        scanStatus.innerHTML = '<div class="alert alert-info"><i class="bi bi-camera"></i> Mengaktifkan kamera...</div>';
        
        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { 
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                disableFlip: false,
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
            },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        scannerRunning = true;
        scanStatus.innerHTML = '<div class="alert alert-success"><i class="bi bi-camera-video"></i> Arahkan kamera ke QR Code</div>';
        
    } catch (error) {
        console.error("Error starting scanner:", error);
        scanStatus.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Gagal mengaktifkan kamera. Pastikan izin kamera telah diberikan.</div>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('qrScanModal');
    
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', function () {
            console.log("Modal QR Scanner dibuka");
            startScanner();
        });
        
        modalElement.addEventListener('hidden.bs.modal', function () {
            console.log("Modal QR Scanner ditutup");
            stopScanner();
        });
        
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                stopScanner();
            });
        });
    }
});

window.addEventListener('beforeunload', function() {
    stopScanner();
});
</script>