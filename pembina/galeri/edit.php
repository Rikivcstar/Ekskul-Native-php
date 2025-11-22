<?php
// pembina/galeri/edit.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$page_title = 'Edit Foto Galeri';
$current_user = getCurrentUser();

$galeri_id = $_GET['id'] ?? 0;

// Ambil data galeri
$galeri = query("
    SELECT g.*, e.nama_ekskul
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    WHERE g.id = ? AND e.pembina_id = ?
", [$galeri_id, $current_user['id']], 'ii')->fetch_assoc();

if (!$galeri) {
    setFlash('danger', 'Foto tidak ditemukan atau Anda tidak memiliki akses!');
    redirect('pembina/galeri/index.php');
}

// Ambil daftar eskul yang diampu
$eskul_list = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ? AND status = 'aktif'
    ORDER BY nama_ekskul
", [$current_user['id']], 'i');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $eskul_id = $_POST['ekstrakurikuler_id'] ?? 0;
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validasi eskul_id milik pembina
    $check_eskul = query("
        SELECT id 
        FROM ekstrakurikulers 
        WHERE id = ? AND pembina_id = ?
    ", [$eskul_id, $current_user['id']], 'ii')->fetch_assoc();
    
    if (!$check_eskul) {
        setFlash('danger', 'Ekstrakurikuler tidak valid!');
    } elseif (empty($judul)) {
        setFlash('danger', 'Judul foto wajib diisi!');
    } else {
        $gambar_baru = $galeri['gambar'];
        
        // Cek jika ada upload gambar baru
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            // Upload file baru
            $upload = uploadFile($_FILES['gambar'], 'galeri');
            
            if ($upload['success']) {
                // Hapus gambar lama
                if ($galeri['gambar']) {
                    deleteFile($galeri['gambar']);
                }
                $gambar_baru = $upload['filename'];
            } else {
                setFlash('danger', $upload['message']);
                $gambar_baru = null;
            }
        }
        
        if ($gambar_baru !== null) {
            // Update database
            try {
                query("
                    UPDATE galeris 
                    SET ekstrakurikuler_id = ?, judul = ?, deskripsi = ?, gambar = ?, is_active = ?
                    WHERE id = ?
                ", [$eskul_id, $judul, $deskripsi, $gambar_baru, $is_active, $galeri_id], 'isssii');
                
                setFlash('success', 'Foto berhasil diupdate!');
                redirect('pembina/galeri/index.php');
            } catch (Exception $e) {
                setFlash('danger', 'Gagal update data: ' . $e->getMessage());
            }
        }
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

    <div class="mb-4">
        <h2><i class="bi bi-pencil-square text-warning"></i> Edit Foto Galeri</h2>
        <p class="text-muted">Update informasi foto galeri</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php while ($eskul = $eskul_list->fetch_assoc()): ?>
                                <option value="<?php echo $eskul['id']; ?>" 
                                        <?php echo $eskul['id'] == $galeri['ekstrakurikuler_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Judul Foto <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" 
                                   value="<?php echo htmlspecialchars($galeri['judul']); ?>" 
                                   placeholder="Contoh: Latihan Rutin Minggu ke-3" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" 
                                      placeholder="Deskripsi singkat tentang foto ini..."><?php echo htmlspecialchars($galeri['deskripsi']); ?></textarea>
                            <small class="text-muted">Opsional - Jelaskan aktivitas dalam foto</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gambar Saat Ini</label>
                            <div class="border rounded p-3 mb-2">
                                <img src="<?php echo BASE_URL . UPLOAD_URL . $galeri['gambar']; ?>" 
                                     alt="Current" 
                                     style="max-width: 100%; max-height: 300px; border-radius: 8px;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ganti Gambar (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif" 
                                   onchange="previewImage(event)">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar. Format: JPG, PNG, GIF. Max 5MB</small>
                        </div>

                        <div class="mb-3">
                            <div id="imagePreview" class="mt-3" style="display:none;">
                                <label class="form-label">Preview Gambar Baru:</label>
                                <div class="border rounded p-2">
                                    <img id="preview" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="is_active" <?php echo $galeri['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Tampilkan di galeri publik
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Update Foto
                            </button>
                            <a href="<?php echo BASE_URL; ?>pembina/galeri/index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle text-primary"></i> Informasi</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Diupload:</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($galeri['tanggal_upload'])); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status:</td>
                            <td>
                                <span class="badge bg-<?php echo $galeri['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $galeri['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Eskul:</td>
                            <td><?php echo htmlspecialchars($galeri['nama_ekskul']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-lightbulb text-warning"></i> Tips</h6>
                    <ul class="small mb-0">
                        <li class="mb-2">Jika tidak ingin mengganti gambar, biarkan field "Ganti Gambar" kosong</li>
                        <li class="mb-2">Update judul dan deskripsi untuk informasi yang lebih jelas</li>
                        <li class="mb-2">Nonaktifkan foto jika tidak ingin ditampilkan sementara</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>