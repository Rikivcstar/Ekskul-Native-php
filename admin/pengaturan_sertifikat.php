<?php
// admin/pengaturan_sertifikat.php
require_once '../config/database.php';
requireRole(['admin']);

$page_title = 'Pengaturan Sertifikat';
$current_user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengaturan'])) {
    $predikat = trim($_POST['predikat_sekolah']);
    $nama_sekolah = trim($_POST['nama_sekolah']);
    $alamat = trim($_POST['alamat_sekolah']);
    $nama_pembina = trim($_POST['nama_pembina']);
    $nip = trim($_POST['nip_pembina']);
    $tempat = trim($_POST['tempat_sekolah']);
    
    // Update pengaturan
    $updates = [
        ['predikat_sekolah', $predikat],
        ['nama_sekolah', $nama_sekolah],
        ['alamat_sekolah', $alamat],
        ['nama_pembina', $nama_pembina],
        ['nip_pembina', $nip],
        ['tempat_sekolah', $tempat]
    ];
    
    $success = true;
    // UPSERT agar kunci baru otomatis dibuat jika belum ada
    foreach ($updates as $update) {
        $result = execute(
            "INSERT INTO pengaturan (key_name, key_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)",
            [$update[0], $update[1]],
            'ss'
        );
        if (!$result || !$result['success']) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        setFlash('success', 'Pengaturan sertifikat berhasil disimpan!');
    } else {
        setFlash('danger', 'Gagal menyimpan pengaturan!');
    }
    
    header("Location: pengaturan_sertifikat.php");
    exit();
}

// Get current settings
$settings = [];
$result = query("SELECT * FROM pengaturan");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['key_name']] = $row['key_value'];
    }
}

// Statistik Penilaian untuk badge
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];
?>
<?php include __DIR__ . '/../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../includes/berry_shell_open.php'; ?>

    <div class="p-4">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-gear-fill text-primary"></i> Pengaturan Sertifikat</h2>
                <p class="text-muted">Kelola informasi yang muncul di sertifikat siswa</p>
            </div>
        </div>

        <!-- Alert Info -->
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle"></i>
            <strong>Informasi:</strong> Pengaturan ini akan diterapkan pada semua sertifikat yang dicetak oleh siswa.
        </div>

        <!-- Form Pengaturan -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-award"></i> Data Sertifikat</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-building"></i> Nama Sekolah
                                </label>
                                <input type="text" name="nama_sekolah" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['nama_sekolah'] ?? 'MTsN 1 LEBAK'); ?>" required>
                                <small class="text-muted">Nama sekolah yang muncul di header sertifikat</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-award-fill"></i> Predikat Sekolah
                                </label>
                                <input type="text" name="predikat_sekolah" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['predikat_sekolah'] ?? 'TERAKREDITASI A'); ?>" required>
                                <small class="text-muted">Contoh: TERAKREDITASI A, TERAKREDITASI B, dll.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-geo-alt"></i> Alamat Sekolah
                                </label>
                                <input type="text" name="alamat_sekolah" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['alamat_sekolah'] ?? 'Jl. Raya Rangkasbitung, Lebak, Banten'); ?>" required>
                                <small class="text-muted">Alamat lengkap sekolah</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-pin-map"></i> Tempat/Kota
                                </label>
                                <input type="text" name="tempat_sekolah" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['tempat_sekolah'] ?? 'Lebak'); ?>" required>
                                <small class="text-muted">Nama kota untuk tanggal di sertifikat (contoh: Lebak)</small>
                            </div>

                            <hr class="my-4">

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-person-badge"></i> Nama Ketua Pembina
                                </label>
                                <input type="text" name="nama_pembina" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['nama_pembina'] ?? 'Fajar Satria Utama'); ?>" required>
                                <small class="text-muted">Nama lengkap ketua pembina ekstrakurikuler</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-credit-card"></i> NIP Ketua Pembina
                                </label>
                                <input type="text" name="nip_pembina" class="form-control" 
                                       value="<?php echo htmlspecialchars($settings['nip_pembina'] ?? '198505152010011023'); ?>" required>
                                <small class="text-muted">NIP ketua pembina ekstrakurikuler</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="simpan_pengaturan" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Simpan Pengaturan
                                </button>
                                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-eye"></i> Preview Sertifikat</h6>
                    </div>
                    <div class="card-body text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px;">
                        <i class="bi bi-award-fill" style="font-size: 3rem;"></i>
                        <h5 class="mt-3" id="preview-nama"><?php echo $settings['nama_sekolah'] ?? 'MTsN 1 LEBAK'; ?></h5>
                        <div class="badge bg-light text-dark my-2" id="preview-predikat">
                            <?php echo $settings['predikat_sekolah'] ?? 'TERAKREDITASI A'; ?>
                        </div>
                        <p class="mb-0" style="font-size: 0.85rem;" id="preview-alamat">
                            <?php echo $settings['alamat_sekolah'] ?? 'Jl. Raya Rangkasbitung, Lebak, Banten'; ?>
                        </p>
                        <hr class="my-3 bg-white">
                        <h6>CERTIFICATE</h6>
                        <p style="font-size: 0.8rem;">of Achievement</p>
                        <hr class="my-3 bg-white">
                        <div style="font-size: 0.75rem;" id="preview-tempat">
                            <?php echo $settings['tempat_sekolah'] ?? 'Lebak'; ?>, [Tanggal]
                        </div>
                        <div style="font-size: 0.75rem; margin-top: 40px;">
                            <strong id="preview-pembina"><?php echo $settings['nama_pembina'] ?? 'Fajar Satria Utama'; ?></strong><br>
                            NIP. <span id="preview-nip"><?php echo $settings['nip_pembina'] ?? '198505152010011023'; ?></span>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Preview akan update otomatis saat Anda mengetik
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips -->
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-lightbulb"></i> Tips:</h6>
            <ul class="mb-0">
                <li>Pastikan semua data terisi dengan benar sebelum menyimpan</li>
                <li>Predikat sekolah biasanya berupa status akreditasi (A, B, C)</li>
                <li>Nama dan NIP pembina harus sesuai dengan data resmi</li>
                <li>Perubahan akan langsung berlaku pada semua sertifikat yang dicetak</li>
            </ul>
        </div>
        
    <script>
        // Live preview update
        document.querySelector('input[name="nama_sekolah"]').addEventListener('input', function(e) {
            document.getElementById('preview-nama').textContent = e.target.value || 'MTsN 1 LEBAK';
        });
        
        document.querySelector('input[name="predikat_sekolah"]').addEventListener('input', function(e) {
            document.getElementById('preview-predikat').textContent = e.target.value || 'TERAKREDITASI A';
        });
        
        document.querySelector('input[name="alamat_sekolah"]').addEventListener('input', function(e) {
            document.getElementById('preview-alamat').textContent = e.target.value || 'Jl. Raya Rangkasbitung, Lebak, Banten';
        });
        
        document.querySelector('input[name="tempat_sekolah"]').addEventListener('input', function(e) {
            document.getElementById('preview-tempat').textContent = (e.target.value || 'Lebak') + ', [Tanggal]';
        });
        
        document.querySelector('input[name="nama_pembina"]').addEventListener('input', function(e) {
            document.getElementById('preview-pembina').textContent = e.target.value || 'Fajar Satria Utama';
        });
        
        document.querySelector('input[name="nip_pembina"]').addEventListener('input', function(e) {
            document.getElementById('preview-nip').textContent = e.target.value || '198505152010011023';
        });
    </script>
<?php include __DIR__ . '/../includes/berry_shell_close.php'; ?>