<?php
// siswa/galeri.php - FIXED untuk tabel galeris
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Galeri';
$current_user = getCurrentUser();

$eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';

$where_clause = "ae.user_id = ? AND ae.status = 'diterima' AND g.is_active = 1";
$params = [$current_user['id']];
$types = 'i';

if ($eskul_filter) {
    $where_clause .= " AND e.id = ?";
    $params[] = $eskul_filter;
    $types .= 'i';
}

$galeri = query("
    SELECT g.*, e.nama_ekskul
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE $where_clause
    ORDER BY g.tanggal_upload DESC, g.urutan ASC
", $params, $types);

$eskul_list = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
", [$current_user['id']], 'i');

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.gallery-item {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 8px 20px rgba(15,23,42,0.15);
}
.gallery-item img {
  width: 100%;
  height: 240px;
  object-fit: cover;
}
.gallery-item:hover {
  transform: translateY(-4px);
}
.gallery-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(0deg, rgba(15,23,42,.9) 0%, rgba(15,23,42,0) 100%);
  color: #fff;
  padding: 16px;
}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-primary mb-2"><i class="bi bi-images"></i> Dokumentasi</span>
    <h3 class="fw-bold mb-1">Galeri Ekstrakurikuler</h3>
    <p class="text-muted mb-0">Kumpulan momen terbaik dari kegiatan eskul yang Anda ikuti.</p>
  </div>
</div>

                <!-- Filter -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Filter Ekstrakurikuler</label>
                                <select name="eskul" class="form-select">
                                    <option value="">Semua Ekstrakurikuler</option>
                                    <?php while ($e = $eskul_list->fetch_assoc()): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo $eskul_filter == $e['id'] ? 'selected' : ''; ?>>
                                        <?php echo $e['nama_ekskul']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

<?php if ($galeri && $galeri->num_rows > 0): ?>
  <div class="row g-4">
    <?php while ($g = $galeri->fetch_assoc()): ?>
      <?php
        $img_path = str_replace('uploads/', '', $g['gambar']);
        $full_path = BASE_URL . 'assets/img/uploads/' . $img_path;
      ?>
      <div class="col-md-4">
        <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modal<?php echo $g['id']; ?>">
          <img src="<?php echo $full_path; ?>" alt="<?php echo htmlspecialchars($g['judul']); ?>"
               onerror="this.onerror=null;this.src='https://via.placeholder.com/400x300/198754/ffffff?text=No+Image';">
          <div class="gallery-overlay">
            <h6 class="mb-1"><?php echo htmlspecialchars($g['judul']); ?></h6>
            <small>
              <span class="badge bg-primary"><?php echo htmlspecialchars($g['nama_ekskul']); ?></span>
              <span class="ms-2"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', strtotime($g['tanggal_upload'])); ?></span>
            </small>
          </div>
        </div>
      </div>

      <div class="modal fade" id="modal<?php echo $g['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?php echo htmlspecialchars($g['judul']); ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
              <img src="<?php echo $full_path; ?>" class="img-fluid rounded mb-3"
                   alt="<?php echo htmlspecialchars($g['judul']); ?>"
                   onerror="this.onerror=null;this.src='https://via.placeholder.com/800x600/198754/ffffff?text=No+Image';">
              <div class="mb-3">
                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($g['nama_ekskul']); ?></span>
                <small class="text-muted"><i class="bi bi-calendar3"></i> <?php echo date('d F Y', strtotime($g['tanggal_upload'])); ?></small>
              </div>
              <?php if ($g['deskripsi']): ?>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($g['deskripsi'])); ?></p>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <a href="<?php echo $full_path; ?>" download class="btn btn-primary">
                <i class="bi bi-download"></i> Download
              </a>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-images text-muted" style="font-size:4rem;opacity:.2;"></i>
      <h5 class="mt-3 text-muted">Belum ada galeri</h5>
      <p class="text-muted mb-0">Belum ada dokumentasi untuk ekstrakurikuler yang Anda ikuti.</p>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
  <i class="bi bi-info-circle"></i> Klik foto untuk melihat detail dan mengunduh dokumentasi kegiatan.
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>