<?php
// pembina/galeri/upload.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$page_title = 'Upload Foto Galeri';
$current_user = getCurrentUser();

// Ambil daftar eskul yang diampu
$eskul_list = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ? AND status = 'aktif'
    ORDER BY nama_ekskul
", [$current_user['id']], 'i');

if ($eskul_list->num_rows == 0) {
    setFlash('warning', 'Anda belum mengampu ekstrakurikuler apapun!');
    redirect('pembina/galeri/index.php');
}

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
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] != 0) {
        setFlash('danger', 'Silakan pilih file gambar!');
    } else {
        // Upload file
        $upload = uploadFile($_FILES['gambar'], 'galeri');
        
        if ($upload['success']) {
            // Simpan ke database
            try {
                query("
                    INSERT INTO galeris (ekstrakurikuler_id, judul, deskripsi, gambar, is_active, tanggal_upload, urutan)
                    VALUES (?, ?, ?, ?, ?, NOW(), 0)
                ", [$eskul_id, $judul, $deskripsi, $upload['filename'], $is_active], 'isssi');
                
                setFlash('success', 'Foto berhasil diupload!');
                redirect('pembina/galeri/index.php');
            } catch (Exception $e) {
                // Hapus file jika gagal simpan ke database
                deleteFile($upload['filename']);
                setFlash('danger', 'Gagal menyimpan data: ' . $e->getMessage());
            }
        } else {
            setFlash('danger', $upload['message']);
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
        <h2><i class="bi bi-cloud-upload text-success"></i> Upload Foto Galeri</h2>
        <p class="text-muted">Upload foto kegiatan ekstrakurikuler Anda</p>
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
                                <?php 
                                $eskul_list->data_seek(0);
                                while ($eskul = $eskul_list->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $eskul['id']; ?>">
                                    <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Judul Foto <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" 
                                   placeholder="Contoh: Latihan Rutin Minggu ke-3" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" 
                                      placeholder="Deskripsi singkat tentang foto ini..."></textarea>
                            <small class="text-muted">Opsional - Jelaskan aktivitas dalam foto</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">File Gambar <span class="text-danger">*</span></label>
                            <input type="file" name="gambar" class="form-control" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif" 
                                   onchange="previewImage(event)" required>
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF. Maksimal 5MB</small>
                        </div>

                        <div class="mb-3">
                            <div id="imagePreview" class="mt-3" style="display:none;">
                                <label class="form-label">Preview:</label>
                                <div class="border rounded p-2">
                                    <img id="preview" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Tampilkan di galeri publik
                                </label>
                            </div>
                            <small class="text-muted">Foto akan langsung ditampilkan di halaman galeri</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-cloud-upload"></i> Upload Foto
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
                    <h6 class="card-title"><i class="bi bi-info-circle text-primary"></i> Panduan Upload</h6>
                    <ul class="small mb-0">
                        <li class="mb-2">Gunakan foto dengan resolusi minimal 800x600 piksel untuk hasil terbaik</li>
                        <li class="mb-2">Pastikan foto tidak blur dan pencahayaan cukup</li>
                        <li class="mb-2">Hindari foto yang mengandung konten tidak pantas</li>
                        <li class="mb-2">Berikan judul dan deskripsi yang jelas</li>
                        <li class="mb-2">Anda dapat menonaktifkan foto kapan saja dari halaman galeri</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-file-earmark-image text-success"></i> Format yang Didukung</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-success">JPG</span>
                        <span class="badge bg-success">JPEG</span>
                        <span class="badge bg-success">PNG</span>
                        <span class="badge bg-success">GIF</span>
                    </div>
                    <hr>
                    <p class="small mb-0"><strong>Ukuran Maksimal:</strong> 5 MB</p>
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