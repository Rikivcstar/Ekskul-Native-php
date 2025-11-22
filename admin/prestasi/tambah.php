<?php
// admin/prestasi/tambah.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Tambah Prestasi';
$current_user = getCurrentUser();
$edit_mode = false;
$data = null;

// Check edit mode
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $result = query("SELECT * FROM prestasis WHERE id = ?", [$id], 'i');
    $data = $result->fetch_assoc();
    $page_title = 'Edit Prestasi';
}

// Ambil daftar eskul
$where_eskul = "";
$params_eskul = [];
$types_eskul = "";

if ($current_user['role'] == 'pembina') {
    $where_eskul = "WHERE pembina_id = ?";
    $params_eskul = [$current_user['id']];
    $types_eskul = "i";
}

$eskul_list = query("SELECT id, nama_ekskul FROM ekstrakurikulers $where_eskul ORDER BY nama_ekskul", $params_eskul, $types_eskul);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'];
    $anggota_id = $_POST['anggota_id'] ?: NULL;
    $nama_prestasi = $_POST['nama_prestasi'];
    $tingkat = $_POST['tingkat'];
    $peringkat = $_POST['peringkat'];
    $tanggal = $_POST['tanggal'];
    $penyelenggara = $_POST['penyelenggara'];
    $deskripsi = $_POST['deskripsi'];
    
    $sertifikat = $edit_mode ? $data['sertifikat'] : '';
    
    // Upload sertifikat
    if (isset($_FILES['sertifikat']) && $_FILES['sertifikat']['error'] == 0) {
        if ($edit_mode && $data['sertifikat']) {
            deleteFile($data['sertifikat']);
        }
        $upload = uploadFile($_FILES['sertifikat'], 'prestasi');
        if ($upload['success']) {
            $sertifikat = $upload['filename'];
        }
    }
    
    if ($edit_mode) {
        $sql = "UPDATE prestasis SET 
                ekstrakurikuler_id = ?, anggota_id = ?, nama_prestasi = ?, tingkat = ?, 
                peringkat = ?, tanggal = ?, penyelenggara = ?, deskripsi = ?, sertifikat = ?
                WHERE id = ?";
        $result = query($sql, [$ekstrakurikuler_id, $anggota_id, $nama_prestasi, $tingkat, $peringkat, $tanggal, $penyelenggara, $deskripsi, $sertifikat, $id], 'iisssssssi');
    } else {
        $sql = "INSERT INTO prestasis (ekstrakurikuler_id, anggota_id, nama_prestasi, tingkat, peringkat, tanggal, penyelenggara, deskripsi, sertifikat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $result = query($sql, [$ekstrakurikuler_id, $anggota_id, $nama_prestasi, $tingkat, $peringkat, $tanggal, $penyelenggara, $deskripsi, $sertifikat], 'iisssssss');
    }
    
    if ($result['success']) {
        setFlash('success', 'Prestasi berhasil ' . ($edit_mode ? 'diupdate' : 'ditambahkan') . '!');
        redirect('admin/prestasi/index.php');
    }
}
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
<div class="p-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <a href="<?php echo BASE_URL; ?>admin/prestasi/index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <h2 class="mb-4">
                <i class="bi bi-<?php echo $edit_mode ? 'pencil-square' : 'plus-circle'; ?>"></i> 
                <?php echo $page_title; ?>
            </h2>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" class="form-select" id="eskulSelect" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php while ($eskul = $eskul_list->fetch_assoc()): ?>
                                <option value="<?php echo $eskul['id']; ?>" 
                                    <?php echo ($edit_mode && $data['ekstrakurikuler_id'] == $eskul['id']) ? 'selected' : ''; ?>>
                                    <?php echo $eskul['nama_ekskul']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anggota (Opsional)</label>
                            <select name="anggota_id" class="form-select" id="anggotaSelect">
                                <option value="">-- Pilih Anggota (Opsional) --</option>
                            </select>
                            <small class="text-muted">Kosongkan jika prestasi untuk tim/kelompok</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Prestasi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_prestasi" class="form-control" 
                                value="<?php echo $edit_mode ? htmlspecialchars($data['nama_prestasi']) : ''; ?>" 
                                placeholder="Contoh: Juara 1 Lomba Pramuka" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                    <select name="tingkat" class="form-select" required>
                                        <option value="">-- Pilih Tingkat --</option>
                                        <option value="sekolah" <?php echo ($edit_mode && $data['tingkat'] == 'sekolah') ? 'selected' : ''; ?>>Sekolah</option>
                                        <option value="kecamatan" <?php echo ($edit_mode && $data['tingkat'] == 'kecamatan') ? 'selected' : ''; ?>>Kecamatan</option>
                                        <option value="kabupaten" <?php echo ($edit_mode && $data['tingkat'] == 'kabupaten') ? 'selected' : ''; ?>>Kabupaten</option>
                                        <option value="provinsi" <?php echo ($edit_mode && $data['tingkat'] == 'provinsi') ? 'selected' : ''; ?>>Provinsi</option>
                                        <option value="nasional" <?php echo ($edit_mode && $data['tingkat'] == 'nasional') ? 'selected' : ''; ?>>Nasional</option>
                                        <option value="internasional" <?php echo ($edit_mode && $data['tingkat'] == 'internasional') ? 'selected' : ''; ?>>Internasional</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Peringkat <span class="text-danger">*</span></label>
                                    <input type="text" name="peringkat" class="form-control" 
                                        value="<?php echo $edit_mode ? htmlspecialchars($data['peringkat']) : ''; ?>" 
                                        placeholder="Contoh: Juara 1, Juara Harapan" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal" class="form-control" 
                                        value="<?php echo $edit_mode ? $data['tanggal'] : ''; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Penyelenggara</label>
                                    <input type="text" name="penyelenggara" class="form-control" 
                                        value="<?php echo $edit_mode ? htmlspecialchars($data['penyelenggara']) : ''; ?>" 
                                        placeholder="Contoh: Dinas Pendidikan">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" 
                                placeholder="Deskripsi singkat tentang prestasi ini"><?php echo $edit_mode ? htmlspecialchars($data['deskripsi']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sertifikat/Bukti (PDF/Gambar)</label>
                            <input type="file" name="sertifikat" class="form-control" accept="image/*,application/pdf">
                            <small class="text-muted">Max 5MB (JPG, PNG, PDF)</small>
                            <?php if ($edit_mode && $data['sertifikat']): ?>
                            <div class="mt-2">
                                <a href="<?php echo UPLOAD_URL . $data['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark"></i> Lihat File Saat Ini
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <div class="text-end">
                            <a href="<?php echo BASE_URL; ?>admin/prestasi/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> <?php echo $edit_mode ? 'Update' : 'Simpan'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
<script>
    // Load anggota berdasarkan eskul yang dipilih
    document.getElementById('eskulSelect').addEventListener('change', function() {
        const eskulId = this.value;
        const anggotaSelect = document.getElementById('anggotaSelect');
        
        if (eskulId) {
            fetch('<?php echo BASE_URL; ?>admin/api/get_anggota.php?eskul_id=' + eskulId)
                .then(response => response.json())
                .then(data => {
                    anggotaSelect.innerHTML = '<option value="">-- Pilih Anggota (Opsional) --</option>';
                    data.forEach(anggota => {
                        anggotaSelect.innerHTML += `<option value="${anggota.id}">${anggota.name} - ${anggota.kelas}</option>`;
                    });
                });
        }
    });

    <?php if ($edit_mode && $data['ekstrakurikuler_id']): ?>
    // Trigger load anggota untuk edit mode
    document.getElementById('eskulSelect').dispatchEvent(new Event('change'));
    setTimeout(() => {
        document.getElementById('anggotaSelect').value = '<?php echo $data['anggota_id']; ?>';
    }, 500);
    <?php endif; ?>
</script>