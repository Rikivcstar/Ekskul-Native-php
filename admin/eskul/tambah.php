<?php
// admin/eskul/tambah.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Tambah Ekstrakurikuler';
$current_user = getCurrentUser();

// Ambil daftar pembina
$pembina_list = query("SELECT id, name FROM users WHERE role = 'pembina' AND is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ekskul = $_POST['nama_ekskul'];
    $deskripsi = $_POST['deskripsi'];
    $pembina_id = $_POST['pembina_id'] ?: NULL;
    $kuota = $_POST['kuota'];
    $status = $_POST['status'];
    
    $gambar = '';
    
    // Upload gambar jika ada
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $upload = uploadFile($_FILES['gambar'], 'eskul');
        if ($upload['success']) {
            $gambar = $upload['filename'];
        } else {
            setFlash('danger', $upload['message']);
            redirect('admin/eskul/tambah.php');
        }
    }
    
    $sql = "INSERT INTO ekstrakurikulers (nama_ekskul, deskripsi, pembina_id, gambar, kuota, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $result = query($sql, [$nama_ekskul, $deskripsi, $pembina_id, $gambar, $kuota, $status], 'ssisss');
    
    if ($result['success']) {
        setFlash('success', 'Ekstrakurikuler berhasil ditambahkan!');
        redirect('admin/eskul/index.php');
    } else {
        setFlash('danger', 'Gagal menambahkan ekstrakurikuler!');
    }
}
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
<div class="p-4">
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>admin/eskul/index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Tambah Ekstrakurikuler</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Ekstrakurikuler <span class="text-danger">*</span></label>
                            <input type="text" name="nama_ekskul" class="form-control" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Pembina</label>
                            <select name="pembina_id" class="form-select">
                                <option value="">-- Pilih Pembina --</option>
                                <?php while ($pembina = $pembina_list->fetch_assoc()): ?>
                                <option value="<?php echo $pembina['id']; ?>">
                                    <?php echo $pembina['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Opsional - bisa diisi nanti</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan tentang ekstrakurikuler ini..."></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Kuota Peserta <span class="text-danger">*</span></label>
                            <input type="number" name="kuota" class="form-control" value="30" min="1" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this, 'preview')">
                            <small class="text-muted">Max 5MB (JPG, PNG, GIF)</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <img id="preview" src="" class="img-thumbnail" style="max-width: 200px; display: none;">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-end">
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>