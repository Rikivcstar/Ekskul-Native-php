<?php
// siswa/dashboard.php
require_once '../config/database.php';
require_once '../config/middleware.php';
only('siswa');
requireRole(['siswa']);

$page_title = 'Dashboard Siswa';
$current_user = getCurrentUser();

// Data utama
$eskul_saya = query("SELECT e.*, ae.tanggal_daftar, ae.status, ae.id as anggota_id
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.user_id = ?
    ORDER BY ae.created_at DESC
", [$current_user['id']], 'i');

$total_eskul = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'", [$current_user['id']], 'i')->fetch_assoc()['total'];
$total_pending = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'pending'", [$current_user['id']], 'i')->fetch_assoc()['total'];
$total_prestasi = query("
    SELECT COUNT(*) as total FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
", [$current_user['id']], 'i')->fetch_assoc()['total'];
$presensi_bulan_ini = query("SELECT COUNT(*) as total FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ? AND MONTH(p.tanggal) = MONTH(CURDATE()) AND YEAR(p.tanggal) = YEAR(CURDATE())
", [$current_user['id']], 'i')->fetch_assoc()['total'];

$berita = query("SELECT b.id, b.judul, b.tanggal_post, e.nama_ekskul
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    WHERE b.is_published = 1
      AND b.ekstrakurikuler_id IN (
        SELECT ekstrakurikuler_id FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'
      )
    ORDER BY b.tanggal_post DESC
    LIMIT 3
", [$current_user['id']], 'i');

$pengumuman = query("SELECT p.judul, p.created_at, p.prioritas, e.nama_ekskul
    FROM pengumuman p
    LEFT JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    WHERE 1
      AND (
        p.ekstrakurikuler_id IS NULL OR p.ekstrakurikuler_id IN (
            SELECT ekstrakurikuler_id FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'
        )
      )
    ORDER BY p.prioritas DESC, p.created_at DESC
    LIMIT 5
", [$current_user['id']], 'i');

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.stat-tile {
  border-radius: 20px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 18px 45px rgba(15,23,42,0.08);
  transition: transform .2s ease, box-shadow .2s ease;
}
.stat-tile:hover {
  transform: translateY(-4px);
  box-shadow: 0 25px 60px rgba(15,23,42,0.12);
}
.quick-link {
  border-radius: 20px;
  min-height: 160px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #0f172a;
  text-decoration: none;
  transition: transform .2s, box-shadow .2s;
}
.quick-link:hover {
  transform: translateY(-4px);
  color: #0f172a;
}
.eskul-card {
  border-radius: 18px;
  border: 1px solid rgba(226,232,240,.7);
}
</style>

<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
  <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4 mb-4 align-items-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:20px;">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
          <span class="badge bg-light text-success mb-2">Selamat datang kembali</span>
          <h3 class="fw-bold text-dark mb-2">Halo, <?php echo htmlspecialchars($current_user['name']); ?> 👋</h3>
          <p class="text-muted mb-0">
            <i class="bi bi-person-badge"></i> NIS: <?php echo htmlspecialchars($current_user['nis']); ?> &nbsp; • &nbsp;
            <i class="bi bi-book"></i> Kelas: <?php echo htmlspecialchars($current_user['kelas']); ?>
          </p>
        </div>
        <div class="text-end">
          <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-success btn-lg rounded-pill">
            <i class="bi bi-pencil-square me-2"></i>Daftar Eskul Baru
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100" style="border-radius:20px;">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h6 class="text-muted mb-0">Ringkasan Aktivitas</h6>
          <span class="badge bg-primary-subtle text-primary">Bulan ini</span>
        </div>
        <div class="d-flex gap-3">
          <div>
            <h3 class="mb-0"><?php echo $presensi_bulan_ini; ?>x</h3>
            <small class="text-muted">Kehadiran</small>
          </div>
          <div>
            <h3 class="mb-0"><?php echo $total_prestasi; ?></h3>
            <small class="text-muted">Prestasi</small>
          </div>
          <div>
            <h3 class="mb-0"><?php echo $total_eskul; ?></h3>
            <small class="text-muted">Eskul Aktif</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-lg-4">
    <div class="card stat-tile border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <p class="text-muted mb-1">Eskul Diikuti</p>
            <h2 class="mb-0 counter" data-target="<?php echo $total_eskul; ?>">0</h2>
          </div>
          <span class="avatar bg-success text-white rounded-circle fs-1" style="width: 50px; height: 50px;">
            <i class="ti ti-grid-dots fs-1 ms-2"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="card stat-tile border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <p class="text-muted mb-1">Prestasi Saya</p>
            <h2 class="mb-0 counter" data-target="<?php echo $total_prestasi; ?>">0</h2>
          </div>
          <span class="avatar bg-info rounded-circle fs-1 text-white" style="width: 50px; height: 50px;">
            <i class="ti ti-medal fs-1 ms-2"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="card stat-tile border-0">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <p class="text-muted mb-1">Presensi Bulan Ini</p>
            <h2 class="mb-0 counter" data-target="<?php echo $presensi_bulan_ini; ?>">0</h2>
          </div>
          <span class="avatar bg-primary text-white rounded-circle fs-1" style="width: 50px; height: 50px;">
            <i class="ti ti-clipboard-check fs-1 ms-2"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <?php
  $quick_links = [
    ['title' => 'Jadwal Kegiatan', 'icon' => 'bi bi-calendar-week', 'url' => BASE_URL . 'siswa/jadwal.php', 'bg' => 'linear-gradient(135deg,#0ea5e9,#2563eb)', 'desc' => 'Lihat jadwal eskul'],
    ['title' => 'Presensi', 'icon' => 'bi bi-clipboard-check', 'url' => BASE_URL . 'siswa/presensi.php', 'bg' => 'linear-gradient(135deg,#14b8a6,#0d9488)', 'desc' => 'Riwayat presensi'],
    ['title' => 'Sertifikat', 'icon' => 'bi bi-award', 'url' => BASE_URL . 'siswa/sertifikat.php', 'bg' => 'linear-gradient(135deg,#a855f7,#7c3aed)', 'desc' => 'Cetak sertifikat'],
    ['title' => 'Galeri', 'icon' => 'bi bi-images', 'url' => BASE_URL . 'siswa/galeri.php', 'bg' => 'linear-gradient(135deg,#f97316,#ea580c)', 'desc' => 'Dokumentasi kegiatan'],
  ];
  foreach ($quick_links as $link): ?>
    <div class="col-sm-6 col-lg-3">
      <a class="quick-link card border-0 text-center shadow-sm" style="background: <?php echo $link['bg']; ?>;" href="<?php echo $link['url']; ?>">
        <i class="<?php echo $link['icon']; ?> text-white mb-3" style="font-size:2.5rem;"></i>
        <h5 class="fw-bold text-white mb-1"><?php echo $link['title']; ?></h5>
        <small class="text-white-50"><?php echo $link['desc']; ?></small>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-grid-fill text-primary"></i> Ekstrakurikuler Saya</h5>
        <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-plus-circle me-1"></i>Tambah</a>
      </div>
      <div class="card-body">
        <?php if ($eskul_saya && $eskul_saya->num_rows > 0): ?>
          <?php 
          $badge_class = [
            'pending' => 'warning',
            'diterima' => 'success',
            'ditolak' => 'danger',
            'keluar' => 'secondary'
          ];
          while ($eskul = $eskul_saya->fetch_assoc()): ?>
            <div class="ekskul-card p-3 mb-3">
              <div class="row align-items-center g-3">
                <div class="col-md-6">
                  <h6 class="text-primary fw-bold mb-1"><?php echo htmlspecialchars($eskul['nama_ekskul']); ?></h6>
                  <small class="text-muted"><i class="bi bi-calendar3"></i> Bergabung: <?php echo formatTanggal($eskul['tanggal_daftar']); ?></small>
                </div>
                <div class="col-md-3 text-md-center">
                  <span class="badge bg-<?php echo $badge_class[$eskul['status']]; ?> px-3 py-2"><?php echo ucfirst($eskul['status']); ?></span>
                </div>
                <div class="col-md-3 text-md-end">
                  <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $eskul['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                    <i class="bi bi-eye"></i> Detail
                  </a>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size:4rem;opacity:.2;"></i>
            <h5 class="mt-3 text-muted">Belum ada ekstrakurikuler aktif</h5>
            <p class="text-muted">Mulai perjalananmu dengan bergabung ke ekstrakurikuler favorit.</p>
            <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-success rounded-pill px-4">
              <i class="bi bi-plus-circle"></i> Daftar Sekarang
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-primary text-white border-0">
        <h6 class="mb-0 text-white"><i class="bi bi-graph-up"></i> Ringkasan Aktivitas</h6>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
          <span><i class="bi bi-check-circle text-success"></i> Total Kehadiran</span>
          <strong><?php echo $presensi_bulan_ini; ?>x</strong>
        </div>
        <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
          <span><i class="bi bi-trophy text-warning"></i> Total Prestasi</span>
          <strong><?php echo $total_prestasi; ?></strong>
        </div>
        <div class="d-flex justify-content-between">
          <span><i class="bi bi-grid text-primary"></i> Eskul Aktif</span>
          <strong><?php echo $total_eskul; ?></strong>
        </div>
      </div>
    </div>

    <?php if ($pengumuman && $pengumuman->num_rows > 0): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-megaphone"></i> Pengumuman</h6>
        <span class="badge bg-danger-subtle text-danger">Terbaru</span>
      </div>
      <div class="card-body">
        <?php while ($p = $pengumuman->fetch_assoc()): ?>
        <div class="mb-3 pb-3 border-bottom">
          <h6 class="mb-1"><?php echo htmlspecialchars($p['judul']); ?></h6>
          <small class="text-muted"><?php echo formatTanggal($p['created_at']); ?> • <?php echo $p['nama_ekskul'] ?? 'Umum'; ?></small>
          <div>
            <span class="badge bg-<?php echo $p['prioritas']=='tinggi'?'danger':($p['prioritas']=='sedang'?'warning text-dark':'secondary'); ?>">
              <?php echo ucfirst($p['prioritas']); ?>
            </span>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
