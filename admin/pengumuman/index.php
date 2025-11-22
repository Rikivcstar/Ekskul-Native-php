<?php
// admin/pengumuman/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Kelola Pengumuman';
$current_user = getCurrentUser();

// Hapus pengumuman
if (isset($_GET['delete'])) {
    query("DELETE FROM pengumuman WHERE id = ?", [$_GET['delete']], 'i');
    setFlash('success', 'Pengumuman berhasil dihapus!');
    redirect('admin/pengumuman/index.php');
}

// Toggle status aktif
if (isset($_GET['toggle'])) {
    query("UPDATE pengumuman SET is_active = NOT is_active WHERE id = ?", [$_GET['toggle']], 'i');
    setFlash('success', 'Status pengumuman berhasil diupdate!');
    redirect('admin/pengumuman/index.php');
}

// Filter data
$where = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where = "WHERE (p.user_id = ? OR e.pembina_id = ?)";
    $params = [$current_user['id'], $current_user['id']];
    $types = "ii";
}

// Ambil daftar pengumuman
$pengumuman = query("
    SELECT p.*, e.nama_ekskul, u.name AS pembuat
    FROM pengumuman p
    LEFT JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON p.user_id = u.id
    $where
    ORDER BY p.prioritas DESC, p.created_at DESC
", $params, $types);


// PRIORITY BADGE
$badge_priority = [
    'tinggi' => 'danger',
    'sedang' => 'warning',
    'rendah' => 'info'
];

?>

<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>

<div class="p-4">

    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type']; ?> alert-dismissible fade show">
        <?= $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-megaphone"></i> Kelola Pengumuman</h2>
        <a href="<?= BASE_URL; ?>admin/pengumuman/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Pengumuman
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-success">
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Ekskul</th>
                            <th>Periode</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ($pengumuman && $pengumuman->num_rows > 0): ?>
                        <?php $no = 1; while ($row = $pengumuman->fetch_assoc()): ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <strong><?= $row['judul']; ?></strong><br>
                                <small class="text-muted"><?= substr($row['isi'], 0, 50); ?>...</small>
                            </td>

                            <td>
                                <?= $row['nama_ekskul'] ?: '<span class="badge bg-secondary">Umum</span>'; ?>
                            </td>

                            <td>
                                <small>
                                    <?= $row['tanggal_mulai'] ? date('d/m/Y', strtotime($row['tanggal_mulai'])) : '-'; ?> <br>
                                    s/d <?= $row['tanggal_selesai'] ? date('d/m/Y', strtotime($row['tanggal_selesai'])) : '-'; ?>
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-<?= $badge_priority[$row['prioritas']] ?? 'secondary'; ?>">
                                    <?= ucfirst($row['prioritas']); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($row['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm">

                                    <a href="?toggle=<?= $row['id']; ?>" 
                                       class="btn btn-<?= $row['is_active'] ? 'warning' : 'success'; ?>" 
                                       title="Toggle Status">
                                        <i class="bi bi-<?= $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                                    </a>

                                    <a href="<?= BASE_URL; ?>admin/pengumuman/tambah.php?edit=<?= $row['id']; ?>" 
                                       class="btn btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a href="?delete=<?= $row['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirmDelete()" 
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                </div>
                            </td>
                        </tr>

                        <?php endwhile; ?>

                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2">Belum ada pengumuman</p>
                            </td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
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
        Apakah Anda yakin ingin menghapus Pengumuman ini? Aksi ini tidak dapat dibatalkan.
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
