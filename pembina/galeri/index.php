<?php
// pembina/galeri/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina');

$page_title = 'Kelola Galeri';
$current_user = getCurrentUser();

// Hapus galeri
if (isset($_GET['delete'])) {
    // Cek apakah galeri milik eskul yang diampu pembina
    $check = query("
        SELECT g.id, g.gambar 
        FROM galeris g
        JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
        WHERE g.id = ? AND e.pembina_id = ?
    ", [$_GET['delete'], $current_user['id']], 'ii')->fetch_assoc();
    
    if ($check) {
        if ($check['gambar']) {
            deleteFile($check['gambar']);
        }
        query("DELETE FROM galeris WHERE id = ?", [$_GET['delete']], 'i');
        setFlash('success', 'Foto berhasil dihapus!');
    } else {
        setFlash('danger', 'Anda tidak memiliki akses untuk menghapus foto ini!');
    }
    redirect('pembina/galeri/index.php');
}

// Toggle status
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    // Cek apakah galeri milik eskul yang diampu pembina
    $check = query("
        SELECT g.id 
        FROM galeris g
        JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
        WHERE g.id = ? AND e.pembina_id = ?
    ", [$_GET['toggle'], $current_user['id']], 'ii')->fetch_assoc();
    
    if ($check) {
        $status = $_GET['status'] == '1' ? 0 : 1;
        query("UPDATE galeris SET is_active = ? WHERE id = ?", [$status, $_GET['toggle']], 'ii');
        setFlash('success', 'Status berhasil diupdate!');
    } else {
        setFlash('danger', 'Anda tidak memiliki akses untuk mengubah foto ini!');
    }
    redirect('pembina/galeri/index.php');
}

// Filter
$eskul_filter = $_GET['eskul'] ?? '';

// Query galeri - HANYA untuk eskul yang diampu pembina
$where_clause = "WHERE e.pembina_id = ?";
$params = [$current_user['id']];
$types = "i";

if ($eskul_filter) {
    $where_clause .= " AND g.ekstrakurikuler_id = ?";
    $params[] = $eskul_filter;
    $types .= "i";
}

// Ambil galeri
$galeri = query("
    SELECT g.*, e.nama_ekskul
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    $where_clause
    ORDER BY g.tanggal_upload DESC, g.urutan ASC
", $params, $types);

// Ambil daftar eskul yang diampu untuk filter
$eskul_list = query("
    SELECT id, nama_ekskul 
    FROM ekstrakurikulers 
    WHERE pembina_id = ? AND status = 'aktif'
    ORDER BY nama_ekskul
", [$current_user['id']], 'i');

$total_eskul = $eskul_list->num_rows;
$total_foto = $galeri->num_rows;
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
<style>
  .gallery-card{ border:0; overflow:hidden; border-radius:12px; transition:transform .2s ease; }
  .gallery-card:hover{ transform:translateY(-4px); }
  .gallery-thumb{ position:relative; width:100%; padding-top:66.6667%; background:#f6f7fb; }
  .gallery-img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
  .gallery-card:hover .gallery-img{ transform:scale(1.06); }
  .gallery-overlay{ position:absolute; left:0; right:0; bottom:0; padding:12px; color:#fff; background:linear-gradient(to top, rgba(0,0,0,.7), rgba(0,0,0,0)); }
  .gallery-title{ margin:6px 0 0; font-size:.95rem; font-weight:600; line-height:1.2; }
  .gallery-actions{ position:absolute; top:10px; right:10px; display:flex; flex-direction:column; gap:8px; opacity:0; transform:translateY(-6px); transition:all .25s ease; z-index:2; }
  .gallery-card:hover .gallery-actions{ opacity:1; transform:translateY(0); }
  .gallery-actions .btn{ box-shadow:0 6px 18px rgba(0,0,0,.15); border:0; }
  .stat-badge{ background:#fff; border-radius:8px; padding:12px 16px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .stat-number{ font-size:1.5rem; font-weight:700; color:#2c3e50; margin:0; }
  .stat-label{ font-size:.8rem; color:#6c757d; margin:0; }
</style>
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
            <h2><i class="bi bi-images text-primary"></i> Kelola Galeri</h2>
            <p class="text-muted mb-0">Kelola foto-foto kegiatan ekstrakurikuler Anda</p>
        </div>
        <?php if ($total_eskul > 0): ?>
        <a href="<?php echo BASE_URL; ?>pembina/galeri/upload.php" class="btn btn-success">
            <i class="bi bi-cloud-upload"></i> Upload Foto
        </a>
        <?php endif; ?>
    </div>

    <?php if ($total_eskul == 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Anda belum mengampu ekstrakurikuler apapun. 
            Silakan hubungi admin untuk penugasan.
        </div>
    <?php else: ?>

    <!-- Statistik Singkat -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="stat-badge d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label">Total Foto Galeri</p>
                    <h3 class="stat-number text-primary"><?php echo $total_foto; ?></h3>
                </div>
                <i class="bi bi-images fs-1 text-primary opacity-25"></i>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-badge d-flex justify-content-between align-items-center">
                <div>
                    <p class="stat-label">Ekstrakurikuler Diampu</p>
                    <h3 class="stat-number text-success"><?php echo $total_eskul; ?></h3>
                </div>
                <i class="bi bi-grid fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row align-items-end">
                    <div class="col-md-10">
                        <label class="form-label small mb-1">Filter berdasarkan Ekstrakurikuler</label>
                        <select name="eskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php 
                            $eskul_list->data_seek(0);
                            while ($eskul = $eskul_list->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $eskul['id']; ?>" <?php echo $eskul_filter == $eskul['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($eskul['nama_ekskul']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Galeri -->
    <?php if ($total_foto > 0): ?>
    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
        <?php 
        $galeri->data_seek(0);
        while ($row = $galeri->fetch_assoc()):
        ?>
        <div class="col">
            <div class="card gallery-card shadow-sm">
                <div class="gallery-thumb">
                    <img class="gallery-img" 
                         src="<?php echo  UPLOAD_URL . $row['gambar']; ?>" 
                         alt="<?php echo htmlspecialchars($row['judul']); ?>"
                         loading="lazy">
                    <div class="gallery-actions">
                        <a href="?toggle=<?php echo $row['id']; ?>&status=<?php echo $row['is_active']; ?>" 
                           class="btn btn-sm btn-<?php echo $row['is_active'] ? 'success' : 'secondary'; ?>" 
                           title="<?php echo $row['is_active'] ? 'Aktif - Klik untuk Nonaktifkan' : 'Nonaktif - Klik untuk Aktifkan'; ?>">
                            <i class="bi bi-<?php echo $row['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                        </a>
                        <a href="edit.php?id=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-warning" 
                           title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Yakin ingin menghapus foto ini?')" 
                           title="Hapus">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                    <div class="gallery-overlay">
                        <span class="badge bg-success mb-1"><?php echo htmlspecialchars($row['nama_ekskul']); ?></span>
                        <div class="gallery-title"><?php echo htmlspecialchars($row['judul']); ?></div>
                        <?php if ($row['deskripsi']): ?>
                          <small class="opacity-75"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 50)); ?>...</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-image fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Belum Ada Foto di Galeri</h5>
            <p class="text-muted mb-3">Upload foto kegiatan ekstrakurikuler Anda untuk ditampilkan di galeri</p>
            <a href="<?php echo BASE_URL; ?>pembina/galeri/upload.php" class="btn btn-success">
                <i class="bi bi-cloud-upload"></i> Upload Foto Sekarang
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Modal konfirmasi dihapus (Pengganti alert/confirm) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin menghapus Galeri ini? Aksi ini tidak dapat dibatalkan.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a id="deleteButton" href="#" class="btn btn-danger">Hapus</a>
      </div>
    </div>
  </div>
</div>

<script>
    // Menambahkan fungsi modal untuk konfirmasi hapus (menggantikan window.confirm)
    document.addEventListener('DOMContentLoaded', function() {
        var deleteLinks = document.querySelectorAll('a[href*="?delete="]');
        deleteLinks.forEach(function(link) {
            link.removeAttribute('onclick'); // Hapus onclick lama
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var deleteUrl = this.getAttribute('href');
                
                // Set URL di tombol Hapus Modal
                var deleteButton = document.getElementById('deleteButton');
                deleteButton.setAttribute('href', deleteUrl);
                
                // Tampilkan Modal
                var confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                confirmModal.show();
            });
        });
    });
</script>


<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>