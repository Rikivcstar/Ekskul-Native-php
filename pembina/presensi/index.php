<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/middleware.php";
only('pembina');

$pembina_id = $_SESSION['user_id'];

// Ambil jadwal hari ini
$hari_ini = date('l');
$hari_translate = [
    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari_indonesia = $hari_translate[$hari_ini];

$jadwal = query("
    SELECT j.*, e.nama_ekskul 
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    WHERE j.hari = ? AND e.pembina_id = ? AND j.is_active = 1
    ORDER BY j.jam_mulai
", [$hari_indonesia, $pembina_id], "si");

?>
<?php include "../../includes/berry_head.php"; ?>
<?php include "../../includes/berry_shell_open.php"; ?>

<style>
.qr-modal-img {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    display: block;
    border: 2px solid #ddd;
    padding: 15px;
    background: white;
    border-radius: 8px;
}
.modal-qr-body {
    text-align: center;
    padding: 30px;
}
</style>

<div class="p-4">

<h2><i class="bi bi-qr-code"></i> Kelola Presensi Latihan</h2>
<p class="text-muted">Tampilkan QR untuk presensi atau lihat data kehadiran hari ini.</p>

<div class="card shadow-sm">
    <div class="card-body">

        <h5>Jadwal Hari Ini: <strong><?= $hari_indonesia ?></strong></h5>

        <?php if ($jadwal->num_rows == 0): ?>
            <div class="alert alert-warning mt-3">Tidak ada jadwal latihan hari ini.</div>
        <?php else: ?>
            <?php while ($j = $jadwal->fetch_assoc()): ?>
                <div class="border p-3 rounded mt-3">
                    <h5><?= $j['nama_ekskul'] ?></h5>
                    <small><i class="bi bi-clock"></i> 
                        <?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>
                    </small><br>
                    <small><i class="bi bi-geo-alt"></i> <?= $j['lokasi'] ?></small>

                    <div class="mt-3 d-flex gap-2">
                        <button type="button" 
                                class="btn btn-success flex-fill" 
                                onclick="showQRModal(<?= $j['id'] ?>, '<?= htmlspecialchars($j['nama_ekskul'], ENT_QUOTES) ?>', '<?= substr($j['jam_mulai'],0,5) ?> - <?= substr($j['jam_selesai'],0,5) ?>')">
                            <i class="bi bi-qr-code"></i> Tampilkan QR Presensi
                        </button>
                        
                        <a href="rekap.php?jadwal=<?= $j['id'] ?>&tanggal=<?= date('Y-m-d') ?>" 
                           class="btn btn-info flex-fill">
                            <i class="bi bi-list-columns-reverse"></i> Lihat Data Absensi
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

    </div>
</div>

</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white" id="qrModalLabel">
                    <i class="bi bi-qr-code me-2"></i> QR Code Presensi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-qr-body">
                <div id="qrLoadingSpinner" class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Generating QR Code...</p>
                </div>
                
                <div id="qrContent" style="display: none;">
                    <h4 id="qrEskulName" class="mb-2"></h4>
                    <p id="qrSchedule" class="text-muted mb-3"></p>
                    <img id="qrImage" src="" alt="QR Code Presensi" class="qr-modal-img mb-3">
                    <a id="qrDownloadBtn" href="#" download class="btn btn-success">
                        <i class="bi bi-download"></i> Download QR Code
                    </a>
                    <p class="mt-3 text-muted small">
                        <i class="bi bi-info-circle"></i> Siswa dapat memindai QR Code ini untuk melakukan presensi kehadiran.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php include "../../includes/berry_shell_close.php"; ?>

<script>
function showQRModal(jadwalId, namaEskul, schedule) {
    // Buka modal
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    modal.show();
    
    // Reset dan tampilkan loading
    document.getElementById('qrLoadingSpinner').style.display = 'block';
    document.getElementById('qrContent').style.display = 'none';
    
    // Set info eskul
    document.getElementById('qrEskulName').textContent = namaEskul;
    document.getElementById('qrSchedule').innerHTML = '<i class="bi bi-clock"></i> ' + schedule;
    
    // Generate QR menggunakan AJAX
    fetch('generate_qr.php?jadwal=' + jadwalId + '&pembina=<?= $pembina_id ?>')
        .then(response => response.json())
        .then(data => {
            // Hide loading
            document.getElementById('qrLoadingSpinner').style.display = 'none';
            
            if (data.success) {
                // Tampilkan QR
                document.getElementById('qrContent').style.display = 'block';
                document.getElementById('qrImage').src = data.qr_url + '?t=' + new Date().getTime();
                document.getElementById('qrDownloadBtn').href = data.qr_url;
                document.getElementById('qrDownloadBtn').download = 'qr_presensi_jadwal_' + jadwalId + '.png';
            } else {
                alert('Gagal generate QR Code: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('qrLoadingSpinner').style.display = 'none';
            alert('Terjadi kesalahan saat generate QR Code');
        });
}
</script>