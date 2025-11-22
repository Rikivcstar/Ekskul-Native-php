<?php
// pembina/prestasi/tambah.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$page_title = 'Tambah Prestasi';
$current_user = getCurrentUser();
$edit_mode = false;
$data = null;

// Ambil daftar eskul yang diampu pembina
$eskul_list_result = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ? AND status = 'aktif'
    ORDER BY nama_ekskul
", [$current_user['id']], 'i');

if ($eskul_list_result->num_rows == 0) {
    setFlash('warning', 'Anda belum mengampu ekstrakurikuler apapun!');
    redirect('pembina/prestasi/index.php');
}

// Check edit mode
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    
    // Ambil data prestasi, sekaligus cek otorisasi untuk Pembina
    $result = query("
        SELECT p.*, e.pembina_id
        FROM prestasis p 
        JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
        WHERE p.id = ?
    ", [$id], 'i');
    
    $data = $result->fetch_assoc();
    
    // Validasi akses pembina
    if (!$data || $data['pembina_id'] != $current_user['id']) {
        setFlash('danger', 'Anda tidak memiliki izin untuk mengedit prestasi ini!');
        redirect('pembina/prestasi/index.php');
    }
    
    $page_title = 'Edit Prestasi';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'];
    $anggota_id = $_POST['anggota_id'] ?: NULL;
    $nama_prestasi = trim($_POST['nama_prestasi']);
    $tingkat = $_POST['tingkat'];
    $peringkat = trim($_POST['peringkat']);
    $tanggal = $_POST['tanggal'];
    $penyelenggara = trim($_POST['penyelenggara']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Verifikasi otorisasi POST untuk Pembina
    $eskul_verify = query("
        SELECT id 
        FROM ekstrakurikulers 
        WHERE id = ? AND pembina_id = ?
    ", [$ekstrakurikuler_id, $current_user['id']], 'ii')->fetch_assoc();
    
    if (!$eskul_verify) {
        setFlash('danger', 'Ekstrakurikuler tidak valid atau bukan di bawah bimbingan Anda!');
        redirect('pembina/prestasi/tambah.php' . ($edit_mode ? '?edit=' . $id : ''));
    }

    $sertifikat = $edit_mode && $data['sertifikat'] ? $data['sertifikat'] : '';
    
    // Upload sertifikat
    if (isset($_FILES['sertifikat']) && $_FILES['sertifikat']['error'] == 0) {
        // Cek ukuran file (5MB)
        if ($_FILES['sertifikat']['size'] > 5 * 1024 * 1024) {
            setFlash('danger', 'Ukuran file sertifikat terlalu besar (Maks. 5MB)!');
            redirect('pembina/prestasi/tambah.php' . ($edit_mode ? '?edit=' . $id : ''));
        }
        
        // Hapus file lama jika edit mode
        if ($edit_mode && $data['sertifikat']) {
            deleteFile($data['sertifikat']);
        }
        
        $upload = uploadFile($_FILES['sertifikat'], 'prestasi');
        
        if ($upload['success']) {
            $sertifikat = $upload['filename'];
        } else {
            setFlash('danger', 'Gagal mengupload file sertifikat: ' . ($upload['message'] ?? 'Unknown error'));
            redirect('pembina/prestasi/tambah.php' . ($edit_mode ? '?edit=' . $id : ''));
        }
    }
    
    try {
        if ($edit_mode) {
            // UPDATE
            $result = query("
                UPDATE prestasis SET 
                    ekstrakurikuler_id = ?, 
                    anggota_id = ?, 
                    nama_prestasi = ?, 
                    tingkat = ?, 
                    peringkat = ?, 
                    tanggal = ?, 
                    penyelenggara = ?, 
                    deskripsi = ?, 
                    sertifikat = ?
                WHERE id = ?
            ", [
                $ekstrakurikuler_id, 
                $anggota_id, 
                $nama_prestasi, 
                $tingkat, 
                $peringkat, 
                $tanggal, 
                $penyelenggara, 
                $deskripsi, 
                $sertifikat, 
                $id
            ], 'iisssssssi');
            
            $success_message = 'Prestasi berhasil diupdate!';
        } else {
            // INSERT
            $result = query("
                INSERT INTO prestasis (
                    ekstrakurikuler_id, anggota_id, nama_prestasi, tingkat, 
                    peringkat, tanggal, penyelenggara, deskripsi, sertifikat
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $ekstrakurikuler_id, 
                $anggota_id, 
                $nama_prestasi, 
                $tingkat, 
                $peringkat, 
                $tanggal, 
                $penyelenggara, 
                $deskripsi, 
                $sertifikat
            ], 'iisssssss');
            
            $success_message = 'Prestasi berhasil ditambahkan!';
        }
        
        // Query berhasil jika tidak throw exception
        setFlash('success', $success_message);
        redirect('pembina/prestasi/index.php');
        
    } catch (Exception $e) {
        setFlash('danger', 'Gagal menyimpan data prestasi: ' . $e->getMessage());
        redirect('pembina/prestasi/tambah.php' . ($edit_mode ? '?edit=' . $id : ''));
    }
}
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="mb-4">
                <a href="<?php echo BASE_URL; ?>pembina/prestasi/index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <h2 class="mb-4">
                <i class="bi bi-<?php echo $edit_mode ? 'pencil-square' : 'plus-circle'; ?> text-warning"></i> 
                <?php echo $page_title; ?>
            </h2>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" class="form-select" id="eskulSelect" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php 
                                $eskul_list_result->data_seek(0);
                                while ($eskul = $eskul_list_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>" 
                                    <?php echo ($edit_mode && $data['ekstrakurikuler_id'] == $eskul['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Hanya menampilkan ekstrakurikuler yang Anda ampu</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Anggota (Opsional)</label>
                            <select name="anggota_id" class="form-select" id="anggotaSelect">
                                <option value="">-- Pilih Anggota (Opsional) --</option>
                                <?php if ($edit_mode && $data['anggota_id']): ?>
                                    <option value="<?php echo $data['anggota_id']; ?>" selected>Memuat anggota...</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Kosongkan jika prestasi untuk tim/kelompok</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Prestasi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_prestasi" class="form-control" 
                                value="<?php echo $edit_mode ? htmlspecialchars($data['nama_prestasi']) : ''; ?>" 
                                placeholder="Contoh: Juara 1 Lomba Futsal Antar SMA" required>
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
                                        placeholder="Contoh: Dinas Pendidikan Kab. Bogor">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" 
                                placeholder="Deskripsi singkat tentang prestasi ini..."><?php echo $edit_mode ? htmlspecialchars($data['deskripsi']) : ''; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sertifikat/Bukti (PDF/Gambar)</label>
                            <input type="file" name="sertifikat" class="form-control" accept="image/*,application/pdf">
                            <small class="text-muted">Max 5MB (JPG, PNG, PDF). Kosongkan jika tidak ingin mengubah.</small>
                            <?php if ($edit_mode && $data['sertifikat']): ?>
                            <div class="mt-2">
                                <a href="<?php echo BASE_URL . UPLOAD_URL . $data['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> Lihat File Saat Ini
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo BASE_URL; ?>pembina/prestasi/index.php" class="btn btn-secondary">
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

<script>
// Load anggota berdasarkan eskul yang dipilih
function loadAnggota(eskulId, selectedAnggotaId) {
    const anggotaSelect = document.getElementById('anggotaSelect');
    anggotaSelect.innerHTML = '<option value="">-- Memuat anggota... --</option>';

    if (eskulId) {
        fetch('<?php echo BASE_URL; ?>pembina/api/get_anggota.php?eskul_id=' + eskulId)
            .then(response => response.json())
            .then(data => {
                anggotaSelect.innerHTML = '<option value="">-- Pilih Anggota (Opsional) --</option>';
                
                if (data.length === 0) {
                    anggotaSelect.innerHTML += '<option value="" disabled>Belum ada anggota</option>';
                } else {
                    data.forEach(anggota => {
                        const isSelected = anggota.id == selectedAnggotaId ? 'selected' : '';
                        anggotaSelect.innerHTML += `<option value="${anggota.id}" ${isSelected}>${anggota.name}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading anggota:', error);
                anggotaSelect.innerHTML = '<option value="">-- Error memuat anggota --</option>';
            });
    } else {
        anggotaSelect.innerHTML = '<option value="">-- Pilih Anggota (Opsional) --</option>';
    }
}

document.getElementById('eskulSelect').addEventListener('change', function() {
    const eskulId = this.value;
    loadAnggota(eskulId, null);
});

<?php if ($edit_mode && $data['ekstrakurikuler_id']): ?>
// Muat anggota saat halaman dimuat dalam edit mode
document.addEventListener('DOMContentLoaded', function() {
    const initialEskulId = '<?php echo $data['ekstrakurikuler_id']; ?>';
    const initialAnggotaId = '<?php echo $data['anggota_id'] ?: ''; ?>';
    if (initialEskulId) {
        loadAnggota(initialEskulId, initialAnggotaId);
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>