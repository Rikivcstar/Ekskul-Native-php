<?php
// admin/berita/manage.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Kelola Berita';
$current_user = getCurrentUser();

// Hapus berita
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $berita = query("SELECT gambar FROM berita WHERE id = ?", [$id], 'i')->fetch_assoc();
    if ($berita['gambar']) {
        deleteFile($berita['gambar']);
    }
    query("DELETE FROM berita WHERE id = ?", [$id], 'i');
    setFlash('success', 'Berita berhasil dihapus!');
    redirect('admin/berita/manage.php');
}

// Filter untuk pembina
$where_clause = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where_clause = "WHERE e.pembina_id = ?";
    $params = [$current_user['id']];
    $types = "i";
}

// Ambil semua berita
$berita = query("
    SELECT b.*, e.nama_ekskul, u.name as penulis
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON b.user_id = u.id
    $where_clause
    ORDER BY b.created_at DESC
", $params, $types);

// Statistik Penilaian untuk badge
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-newspaper"></i> Kelola Berita</h2>
        <a href="<?php echo BASE_URL; ?>admin/berita/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Berita
        </a>
    </div>

    <div class="row">
        <?php 
        if ($berita && $berita->num_rows > 0):
            while ($row = $berita->fetch_assoc()):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <?php if ($row['gambar']): ?>
                <img src="<?php echo UPLOAD_URL . $row['gambar']; ?>" class="card-img-top" alt="<?php echo $row['judul']; ?>" style="height: 200px; object-fit: cover;">
                <?php else: ?>
                <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="No Image">
                <?php endif; ?>
                <div class="card-body">
                    <span class="badge bg-success mb-2"><?php echo $row['nama_ekskul']; ?></span>
                    <h5 class="card-title"><?php echo $row['judul']; ?></h5>
                    <p class="card-text text-muted small">
                        <?php echo substr(strip_tags($row['konten']), 0, 100); ?>...
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> <?php echo formatTanggal($row['tanggal_post']); ?>
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-eye"></i> <?php echo $row['views']; ?>
                        </small>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-person"></i> <?php echo $row['penulis'] ?? 'Admin'; ?>
                        </small>
                    </div>
                    <div class="mt-2">
                        <?php if ($row['is_published']): ?>
                        <span class="badge bg-success">Published</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="btn-group w-100">
                        <a href="<?php echo BASE_URL; ?>admin/berita/tambah.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline-warning me-2">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-outline-danger" onclick="return confirmDelete()">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle fs-1"></i>
                <p class="mt-2">Belum ada berita. Silakan tambah berita baru.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
        Apakah Anda yakin ingin menghapus Berita ini? Aksi ini tidak dapat dibatalkan.
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