<?php
require_once '../config/database.php';
require_once '../config/middleware.php';

only('siswa');
requireRole(['siswa']);

$page_title = 'Prestasi Saya';
$current_user = getCurrentUser();

$tahun = $_GET['tahun'] ?? date('Y');
$tingkat = $_GET['tingkat'] ?? '';

$where_clause = "ae.user_id = ?";
$params = [$current_user['id']];
$types = 'i';

if (!empty($tahun)) {
    $where_clause .= " AND YEAR(p.tanggal) = ?";
    $params[] = $tahun;
    $types .= 'i';
}

if (!empty($tingkat)) {
    $where_clause .= " AND p.tingkat = ?";
    $params[] = $tingkat;
    $types .= 's';
}

$prestasi = query("
    SELECT p.*, e.nama_ekskul, e.id as eskul_id
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE $where_clause
    ORDER BY p.tanggal DESC
", $params, $types);

$stats = query("
    SELECT 
        COUNT(*) as total,
        SUM(p.tingkat = 'sekolah') as sekolah,
        SUM(p.tingkat = 'kecamatan') as kecamatan,
        SUM(p.tingkat = 'kabupaten') as kabupaten,
        SUM(p.tingkat = 'provinsi') as provinsi,
        SUM(p.tingkat = 'nasional') as nasional,
        SUM(p.tingkat = 'internasional') as internasional
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
", [$current_user['id']], 'i')->fetch_assoc();

require_once '../includes/berry_siswa_head.php';
require_once '../includes/berry_siswa_shell_open.php';
?>

<style>
.prestasi-card {
  border-radius: 20px;
  transition: transform .2s ease, box-shadow .2s ease;
}
.prestasi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 40px rgba(15,23,42,0.12);
}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-warning mb-2"><i class="bi bi-trophy"></i> Prestasi</span>
    <h3 class="fw-bold mb-1">Prestasi Saya</h3>
    <p class="text-muted mb-0">Catatan penghargaan dan pencapaian terbaik yang pernah diraih.</p>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Tahun</label>
        <select name="tahun" class="form-select">
          <option value="">Semua Tahun</option>
          <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
              <?php echo $y; ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tingkat</label>
        <select name="tingkat" class="form-select">
          <option value="">Semua Tingkat</option>
          <?php
          $tingkat_options = ['sekolah','kecamatan','kabupaten','provinsi','nasional','internasional'];
          foreach ($tingkat_options as $opt):
          ?>
            <option value="<?php echo $opt; ?>" <?php echo $tingkat == $opt ? 'selected' : ''; ?>>
              <?php echo ucfirst($opt); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Terapkan Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-trophy-fill text-warning" style="font-size:3rem;"></i>
        <h2 class="mt-3 mb-0"><?php echo $stats['total']; ?></h2>
        <small class="text-muted">Total Prestasi</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-star-fill text-danger" style="font-size:3rem;"></i>
        <h2 class="mt-3 mb-0"><?php echo $stats['nasional']; ?></h2>
        <small class="text-muted">Tingkat Nasional</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-globe-americas text-info" style="font-size:3rem;"></i>
        <h2 class="mt-3 mb-0"><?php echo $stats['internasional']; ?></h2>
        <small class="text-muted">Internasional</small>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white">
    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Prestasi Berdasarkan Tingkat</h5>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <?php
      $colors = ['primary','info','success','warning','danger','dark'];
      foreach ($tingkat_options as $idx => $opt):
        $value = (int)$stats[$opt];
        $percent = $stats['total'] > 0 ? ($value / $stats['total']) * 100 : 0;
      ?>
        <div class="col-md-2 text-center">
          <div class="progress" style="height:100px;">
            <div class="progress-bar bg-<?php echo $colors[$idx]; ?>" style="width:100%;height:<?php echo $percent; ?>%"></div>
          </div>
          <h4 class="mt-2 mb-0"><?php echo $value; ?></h4>
          <small class="text-muted"><?php echo ucfirst($opt); ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($prestasi && $prestasi->num_rows > 0): ?>
  <div class="row">
    <?php
    $badge_tingkat = [
        'sekolah' => 'primary',
        'kecamatan' => 'info',
        'kabupaten' => 'success',
        'provinsi' => 'warning',
        'nasional' => 'danger',
        'internasional' => 'dark'
    ];
    while ($p = $prestasi->fetch_assoc()):
    ?>
      <div class="col-md-6 mb-4">
        <div class="card prestasi-card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="badge bg-<?php echo $badge_tingkat[$p['tingkat']]; ?> px-3 py-2 text-uppercase">
                <?php echo htmlspecialchars($p['tingkat']); ?>
              </span>
              <span class="badge bg-light text-dark"><?php echo formatTanggal($p['tanggal']); ?></span>
            </div>
            <h5 class="text-primary mb-2"><?php echo htmlspecialchars($p['nama_prestasi'] ?? ''); ?></h5>
            <p class="mb-2">
              <span class="badge bg-success"><i class="bi bi-award"></i> <?php echo htmlspecialchars($p['peringkat'] ?? ''); ?></span>
              <span class="badge bg-secondary ms-2"><i class="bi bi-grid"></i> <?php echo htmlspecialchars($p['nama_ekskul']); ?></span>
            </p>
            <?php if (!empty($p['penyelenggara'])): ?>
              <p class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($p['penyelenggara']); ?></p>
            <?php endif; ?>
            <?php if (!empty($p['deskripsi'])): ?>
              <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($p['deskripsi'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($p['sertifikat'])): ?>
              <a href="<?php echo BASE_URL . $p['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-pdf"></i> Lihat Sertifikat
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <i class="bi bi-trophy text-muted" style="font-size:4rem;opacity:.2;"></i>
      <h5 class="mt-3 text-muted">Belum ada prestasi tercatat</h5>
      <p class="text-muted mb-0">Terus berlatih dan raih prestasi terbaikmu!</p>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-success mt-4">
  <i class="bi bi-lightbulb"></i> Simpan sertifikat dan bukti prestasi Anda dengan baik—akan sangat berguna untuk portofolio akademik.
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>



