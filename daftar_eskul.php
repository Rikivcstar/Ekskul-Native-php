<?php
// daftar_eskul.php
$page_title = 'Pendaftaran Ekstrakurikuler';
require_once 'config/database.php';
require_once 'config/middleware.php';
only('siswa');
// Hanya boleh diakses oleh siswa yang sudah login
requireRole(['siswa']);
$current_user = getCurrentUser();
$user_id = $current_user ? (int)$current_user['id'] : 0;

// Ambil daftar eskul aktif
$eskul_list = query("SELECT * FROM ekstrakurikulers WHERE status = 'aktif' ORDER BY nama_ekskul");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ekstrakurikuler_id = (int)($_POST['ekstrakurikuler_id'] ?? 0);
    $alasan_daftar      = trim($_POST['alasan_daftar'] ?? '');

    if ($user_id <= 0) {
        setFlash('danger', 'Sesi login Anda tidak valid. Silakan login kembali.');
        redirect('admin/login.php');
    }

    if ($ekstrakurikuler_id <= 0 || $alasan_daftar === '') {
        setFlash('danger', 'Silakan pilih ekstrakurikuler dan isi alasan mendaftar.');
        redirect('daftar_eskul.php');
    }

    // Cek apakah sudah pernah daftar eskul yang sama
    $cek_pendaftaran = query(
        "SELECT id FROM anggota_ekskul WHERE user_id = ? AND ekstrakurikuler_id = ?",
        [$user_id, $ekstrakurikuler_id],
        'ii'
    );
    
    if ($cek_pendaftaran && $cek_pendaftaran->num_rows > 0) {
        setFlash('warning', 'Anda sudah pernah mendaftar ekstrakurikuler ini!');
    } else {
        // Insert pendaftaran
        $tanggal_daftar = date('Y-m-d');
        $result = query(
            "INSERT INTO anggota_ekskul (user_id, ekstrakurikuler_id, tanggal_daftar, alasan_daftar, status) 
             VALUES (?, ?, ?, ?, 'pending')",
            [$user_id, $ekstrakurikuler_id, $tanggal_daftar, $alasan_daftar],
            'iiss'
        );
        
        if ($result['success']) {
            setFlash('success', 'Pendaftaran ekstrakurikuler berhasil! Silakan tunggu konfirmasi dari admin/pembina.');
        } else {
            setFlash('danger', 'Pendaftaran gagal! Silakan coba lagi.');
        }
    }
    
    redirect('daftar_eskul.php');
}
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-success text-white text-center py-4">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Formulir Pendaftaran Ekstrakurikuler
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Silakan isi formulir dengan lengkap dan benar. 
                        Pendaftaran akan diproses oleh admin.
                    </div>

                    <form method="POST" action="">
                        <h5 class="mb-3 text-success">
                            <i class="bi bi-person-fill"></i> Data Siswa (diambil dari akun Anda)
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">NISN</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['nisn'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kelas</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['kelas'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <input type="text" class="form-control" value="<?php echo ($current_user['jenis_kelamin'] ?? '') === 'L' ? 'Laki-laki' : 'Perempuan'; ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">No HP/WA</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['no_hp'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" rows="2" readonly><?php echo htmlspecialchars($current_user['alamat'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3 text-success">
                            <i class="bi bi-grid-fill"></i> Pilihan Ekstrakurikuler
                        </h5>

                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" class="form-select" id="eskulSelect" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>" 
                                    data-deskripsi="<?php echo htmlspecialchars($eskul['deskripsi']); ?>">
                                    <?php echo $eskul['nama_ekskul']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="eskulInfo" class="alert alert-light d-none">
                            <strong><i class="bi bi-info-circle"></i> Tentang Eskul Ini:</strong>
                            <p id="infoDeskripsi" class="mb-0 mt-2">-</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alasan Mendaftar <span class="text-danger">*</span></label>
                            <textarea name="alasan_daftar" class="form-control" rows="4" 
                                placeholder="Ceritakan alasan Anda ingin bergabung dengan ekstrakurikuler ini..." required></textarea>
                            <small class="text-muted">Minimal 20 karakter</small>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agree" required>
                            <label class="form-check-label" for="agree">
                                Saya menyatakan bahwa data yang saya isi adalah benar dan siap mengikuti kegiatan ekstrakurikuler dengan penuh tanggung jawab.
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-send"></i> Kirim Pendaftaran
                            </button>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Tambahan -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-question-circle"></i> Informasi Penting</h6>
                    <ul class="mb-0">
                        <li>Pastikan data yang Anda isi sudah benar</li>
                        <li>Pendaftaran akan diverifikasi oleh admin (1-3 hari kerja)</li>
                        <li>Anda akan dihubungi melalui nomor HP/WA yang terdaftar</li>
                        <li>Setiap siswa dapat mendaftar maksimal 2 ekstrakurikuler</li>
                        <li>Kegiatan ekstrakurikuler wajib diikuti sesuai jadwal</li>
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show eskul info when selected
document.getElementById('eskulSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const info = document.getElementById('eskulInfo');
    
    if (this.value) {
        document.getElementById('infoDeskripsi').textContent = option.dataset.deskripsi || 'Tidak ada deskripsi';
        info.classList.remove('d-none');
    } else {
        info.classList.add('d-none');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const alasan = document.querySelector('[name="alasan_daftar"]').value;
    if (alasan.length < 20) {
        e.preventDefault();
        alert('Alasan mendaftar minimal 20 karakter!');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>