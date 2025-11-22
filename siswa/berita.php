<?php
// siswa/berita.php - All-in-one (List + Detail)
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$current_user = getCurrentUser();

// MODE: DETAIL
if (isset($_GET['id'])) {
    $berita_id = (int)$_GET['id'];
    $detail = query("
        SELECT b.*, e.nama_ekskul, e.id as eskul_id
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        WHERE b.id = ? AND b.is_published = 1
    ", [$berita_id], 'i');

    if (!$detail || $detail->num_rows === 0) {
        setFlash('danger', 'Berita tidak ditemukan!');
        redirect('siswa/berita.php');
    }

    $berita_detail = $detail->fetch_assoc();
    query("UPDATE berita SET views = views + 1 WHERE id = ?", [$berita_id], 'i');

    $related = query("
        SELECT b.id, b.judul, b.tanggal_post
        FROM berita b
        WHERE b.ekstrakurikuler_id = ? AND b.id != ? AND b.is_published = 1
        ORDER BY b.tanggal_post DESC
        LIMIT 3
    ", [$berita_detail['eskul_id'], $berita_id], 'ii');

    $populer = query("
        SELECT b.id, b.judul, b.views, b.tanggal_post
        FROM berita b
        WHERE b.is_published = 1
        ORDER BY b.views DESC
        LIMIT 5
    ");

    $page_title = $berita_detail['judul'];
} else {
    // MODE: LIST
    $page_title = 'Berita & Kegiatan';
    $limit = 9;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    $eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';

    $where_clause = "b.is_published = 1 AND ae.user_id = ? AND ae.status = 'diterima'";
    $params = [$current_user['id']];
    $types = 'i';

    if ($eskul_filter) {
        $where_clause .= " AND e.id = ?";
        $params[] = $eskul_filter;
        $types .= 'i';
    }

    $total_result = query("
        SELECT COUNT(DISTINCT b.id) as total
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE $where_clause
    ", $params, $types);
    $total = $total_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total / $limit));

    $berita = query("
        SELECT DISTINCT b.*, e.nama_ekskul
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE $where_clause
        ORDER BY b.tanggal_post DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]), $types . 'ii');

    $eskul_list = query("
        SELECT DISTINCT e.id, e.nama_ekskul
        FROM ekstrakurikulers e
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE ae.user_id = ? AND ae.status = 'diterima'
        ORDER BY e.nama_ekskul
    ", [$current_user['id']], 'i');
}

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.news-card {
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 15px 40px rgba(15,23,42,0.08);
  height: 100%;
}
.news-card img {
  height: 190px;
  object-fit: cover;
}
.news-card:hover {
  transform: translateY(-4px);
  transition: transform .2s ease;
}
.news-detail-cover {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  min-height: 260px;
}
.news-detail-cover::after {
  content:'';
  position:absolute;
  inset:0;
  background: linear-gradient(180deg,rgba(15,23,42,0) 0%,rgba(15,23,42,0.85) 100%);
}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-primary mb-2"><i class="bi bi-newspaper"></i> Informasi Ekstrakurikuler</span>
    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($page_title); ?></h3>
    <p class="text-muted mb-0">Dapatkan berita terbaru dan pengumuman penting untuk ekstrakurikuler yang Anda ikuti.</p>
  </div>
  <div>
    <a href="<?php echo BASE_URL; ?>siswa/dashboard.php" class="btn btn-outline-secondary rounded-pill">
      <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
    </a>
  </div>
</div>

<?php if (isset($_GET['id'])): ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card border-0 shadow-sm" style="border-radius:24px;">
        <?php if (!empty($berita_detail['gambar'])): ?>
          <div class="news-detail-cover mb-3">
            <img src="<?php echo UPLOAD_URL . $berita_detail['gambar']; ?>" class="w-100 h-100" alt="<?php echo htmlspecialchars($berita_detail['judul']); ?>">
          </div>
        <?php endif; ?>
        <div class="card-body p-4">
          <div class="d-flex gap-2 mb-3">
            <span class="badge bg-primary"><?php echo htmlspecialchars($berita_detail['nama_ekskul']); ?></span>
            <span class="badge bg-light text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo formatTanggal($berita_detail['tanggal_post']); ?></span>
            <span class="badge bg-light text-muted"><i class="bi bi-eye me-1"></i><?php echo $berita_detail['views']; ?> views</span>
          </div>
          <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($berita_detail['judul']); ?></h2>
          <div class="content text-muted" style="line-height:1.8;">
            <?php echo $berita_detail['konten']; ?>
          </div>
        </div>
      </div>

      <?php if ($related && $related->num_rows > 0): ?>
      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0">
          <h5 class="mb-0">Berita Terkait</h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php while ($rel = $related->fetch_assoc()): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <a href="?id=<?php echo $rel['id']; ?>" class="fw-semibold text-decoration-none"><?php echo htmlspecialchars($rel['judul']); ?></a>
              <div class="small text-muted"><i class="bi bi-calendar-event"></i> <?php echo formatTanggal($rel['tanggal_post']); ?></div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
          </li>
          <?php endwhile; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <div class="col-xl-4">
      <?php if ($populer && $populer->num_rows > 0): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between">
          <h6 class="mb-0">Berita Populer</h6>
          <span class="badge bg-warning text-dark">Terbaca</span>
        </div>
        <div class="list-group list-group-flush">
          <?php while ($pop = $populer->fetch_assoc()): ?>
          <a href="?id=<?php echo $pop['id']; ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between">
              <div>
                <div class="fw-semibold"><?php echo htmlspecialchars($pop['judul']); ?></div>
                <small class="text-muted"><?php echo formatTanggal($pop['tanggal_post']); ?></small>
              </div>
              <span class="text-primary"><i class="bi bi-eye me-1"></i><?php echo $pop['views']; ?></span>
            </div>
          </a>
          <?php endwhile; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3">
        <div class="col-lg-9">
          <label class="form-label">Filter Ekstrakurikuler</label>
          <select name="eskul" class="form-select">
            <option value="">Semua Ekstrakurikuler</option>
            <?php while ($e = $eskul_list->fetch_assoc()): ?>
              <option value="<?php echo $e['id']; ?>" <?php echo $eskul_filter == $e['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($e['nama_ekskul']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-lg-3 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100 rounded-pill">
            <i class="bi bi-funnel"></i> Terapkan
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($berita && $berita->num_rows > 0): ?>
    <div class="row g-4">
      <?php while ($b = $berita->fetch_assoc()): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card news-card border-0 h-100">
            <?php if (!empty($b['gambar'])): ?>
              <img src="<?php echo UPLOAD_URL . $b['gambar']; ?>" alt="<?php echo htmlspecialchars($b['judul']); ?>">
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center bg-primary text-white" style="height:190px;">
                <i class="bi bi-newspaper" style="font-size:3rem;"></i>
              </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
              <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($b['nama_ekskul']); ?></span>
              <h5 class="fw-semibold"><?php echo htmlspecialchars($b['judul']); ?></h5>
              <p class="text-muted small flex-grow-1">
                <?php 
                $konten = strip_tags($b['konten']);
                echo strlen($konten) > 140 ? substr($konten, 0, 140) . '...' : $konten;
                ?>
              </p>
              <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo formatTanggal($b['tanggal_post']); ?></small>
                <a href="?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">Baca</a>
              </div>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <nav class="mt-4">
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $i; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-5">
        <i class="bi bi-newspaper text-muted" style="font-size:4rem;opacity:.2;"></i>
        <h5 class="mt-3 text-muted">Belum ada berita untuk ekstrakurikuler yang Anda ikuti.</h5>
        <p class="text-muted mb-0">Coba pilih ekstrakurikuler lain atau kembali beberapa saat lagi.</p>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>