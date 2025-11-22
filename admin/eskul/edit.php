<?php
// admin/eskul/edit.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Edit Ekstrakurikuler';
$current_user = getCurrentUser();
$id = $_GET['id'] ?? 0;

// Ambil data eskul
$eskul = query("SELECT * FROM ekstrakurikulers WHERE id = ?", [$id], 'i');
if (!$eskul || $eskul->num_rows == 0) {
    setFlash('danger', 'Data tidak ditemukan!');
    redirect('admin/eskul/index.php');
}
$data = $eskul->fetch_assoc();

// Cek akses (pembina hanya bisa edit eskul sendiri)
if ($current_user['role'] == 'pembina' && $data['pembina_id'] != $current_user['id']) {
    setFlash('danger', 'Anda tidak memiliki akses untuk mengedit ekstrakurikuler ini!');
    redirect('admin/eskul/index.php');
}

// Ambil daftar pembina
$pembina_list = query("SELECT id, name FROM users WHERE role = 'pembina' AND is_active = 1 ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ekskul = $_POST['nama_ekskul'];
    $deskripsi = $_POST['deskripsi'];
    $pembina_id = $_POST['pembina_id'] ?: NULL;
    $kuota = $_POST['kuota'];
    $status = $_POST['status'];
    
    $gambar = $data['gambar'];
    
    // Upload gambar baru jika ada
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        if ($data['gambar']) {
            deleteFile($data['gambar']);
        }
        
        $upload = uploadFile($_FILES['gambar'], 'eskul');
        if ($upload['success']) {
            $gambar = $upload['filename'];
        }
    }
    
    $sql = "UPDATE ekstrakurikulers SET 
            nama_ekskul = ?, deskripsi = ?, pembina_id = ?, gambar = ?, kuota = ?, status = ?
            WHERE id = ?";
    
    $result = query($sql, [$nama_ekskul, $deskripsi, $pembina_id, $gambar, $kuota, $status, $id], 'ssisssi');
    
    if ($result['success']) {
        setFlash('success', 'Ekstrakurikuler berhasil diupdate!');
        redirect('admin/eskul/index.php');
    } else {
        setFlash('danger', 'Gagal mengupdate ekstrakurikuler!');
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

    <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Ekstrakurikuler</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Ekstrakurikuler <span class="text-danger">*</span></label>
                            <input type="text" name="nama_ekskul" class="form-control" value="<?php echo htmlspecialchars($data['nama_ekskul']); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Pembina</label>
                            <select name="pembina_id" class="form-select" <?php echo $current_user['role'] == 'pembina' ? 'disabled' : ''; ?>>
                                <option value="">-- Pilih Pembina --</option>
                                <?php while ($pembina = $pembina_list->fetch_assoc()): ?>
                                <option value="<?php echo $pembina['id']; ?>" <?php echo $data['pembina_id'] == $pembina['id'] ? 'selected' : ''; ?>>
                                    <?php echo $pembina['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4"><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Kuota Peserta <span class="text-danger">*</span></label>
                            <input type="number" name="kuota" class="form-control" value="<?php echo $data['kuota']; ?>" min="1" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" <?php echo $data['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="nonaktif" <?php echo $data['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this, 'preview')">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <?php if ($data['gambar']): ?>
                            <img src="<?php echo UPLOAD_URL . $data['gambar']; ?>" class="img-thumbnail" style="max-width: 200px;">
                            <?php endif; ?>
                            <img id="preview" src="" class="img-thumbnail ms-2" style="max-width: 200px; display: none;">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-end">
                    <a href="<?php echo BASE_URL; ?>admin/eskul/index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>