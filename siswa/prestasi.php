<?php
require_once '../config/database.php';
require_once '../config/middleware.php';

only('siswa');
requireRole(['siswa']);

$page_title = 'Prestasi Saya';
$current_user = getCurrentUser();

// =========================================================
// === PROSES TAMBAH PRESTASI (FIXED) ===
// =========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_prestasi'])) {
    $ekstrakurikuler_id = $_POST['ekstrakurikuler_id'];
    $nama_prestasi = trim($_POST['nama_prestasi']);
    $tingkat = $_POST['tingkat'];
    $peringkat = trim($_POST['peringkat']);
    $penyelenggara = trim($_POST['penyelenggara']);
    $tanggal = $_POST['tanggal'];
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi: Cek apakah siswa terdaftar di eskul tersebut
    $cek_anggota = query("
        SELECT id FROM anggota_ekskul 
        WHERE user_id = ? AND ekstrakurikuler_id = ? AND status = 'diterima'
    ", [$current_user['id'], $ekstrakurikuler_id], 'ii');
    
    if (!$cek_anggota || $cek_anggota->num_rows == 0) {
        setFlash('danger', 'Anda tidak terdaftar di ekstrakurikuler ini!');
        redirect('siswa/prestasi.php');
    }
    
    $anggota_id = $cek_anggota->fetch_assoc()['id'];
    
    // Upload sertifikat (opsional)
    $sertifikat_path = null;
    if (isset($_FILES['sertifikat']) && $_FILES['sertifikat']['error'] == 0) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['sertifikat']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            setFlash('danger', 'Format file harus PDF, JPG, JPEG, atau PNG');
            redirect('siswa/prestasi.php');
        }
        
        // Validasi ukuran (max 5MB)
        if ($_FILES['sertifikat']['size'] > 5000000) {
            setFlash('danger', 'Ukuran file maksimal 5MB');
            redirect('siswa/prestasi.php');
        }
        
        $newname = 'sertifikat_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = '../assets/img/uploads/sertifikat/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_path = $upload_dir . $newname;
        
        if (move_uploaded_file($_FILES['sertifikat']['tmp_name'], $upload_path)) {
            $sertifikat_path = 'assets/img/uploads/sertifikat/' . $newname;
        } else {
            setFlash('danger', 'Gagal mengupload file sertifikat.');
            redirect('siswa/prestasi.php');
        }
    }
    
    // Baris 73
    $check_columns = query("SHOW COLUMNS FROM prestasis");

    // Baris 74-76: Cek apakah $check_columns adalah objek yang valid dan memiliki baris
    $has_ekstrakurikuler_id = false;

    // Perbaikan dilakukan di sini
    if ($check_columns instanceof mysqli_result && $check_columns->num_rows > 0) {
        while ($col = $check_columns->fetch_assoc()) {
            if ($col['Field'] == 'ekstrakurikuler_id') {
                $has_ekstrakurikuler_id = true;
                break;
            }
        }
    }
    
    if ($has_ekstrakurikuler_id) {
        // Jika ada kolom ekstrakurikuler_id, insert dengan kolom tersebut
        $result = query("
            INSERT INTO prestasis (anggota_id, ekstrakurikuler_id, nama_prestasi, tingkat, peringkat, penyelenggara, tanggal, deskripsi, sertifikat) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [$anggota_id, $ekstrakurikuler_id, $nama_prestasi, $tingkat, $peringkat, $penyelenggara, $tanggal, $deskripsi, $sertifikat_path], 
           'iisssssss');
    } else {
        // Jika tidak ada, insert tanpa ekstrakurikuler_id
        $result = query("
            INSERT INTO prestasis (anggota_id, nama_prestasi, tingkat, peringkat, penyelenggara, tanggal, deskripsi, sertifikat) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [$anggota_id, $nama_prestasi, $tingkat, $peringkat, $penyelenggara, $tanggal, $deskripsi, $sertifikat_path], 
           'isssssss');
    }
    
    if ($result['success']) {
        setFlash('success', 'Prestasi berhasil ditambahkan!');
    } else {
        setFlash('danger', 'Gagal menambahkan prestasi: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    redirect('siswa/prestasi.php');
}

// =========================================================
// === HAPUS PRESTASI ===
// =========================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Cek apakah prestasi ini milik user yang login
    $cek = query("
        SELECT p.sertifikat 
        FROM prestasis p
        JOIN anggota_ekskul ae ON p.anggota_id = ae.id
        WHERE p.id = ? AND ae.user_id = ?
    ", [$id, $current_user['id']], 'ii');
    
    if ($cek && $cek->num_rows > 0) {
        $data = $cek->fetch_assoc();
        
        // Hapus file sertifikat jika ada
        if ($data['sertifikat'] && file_exists('../' . $data['sertifikat'])) {
            unlink('../' . $data['sertifikat']);
        }
        
        // Hapus dari database
        $result = query("DELETE FROM prestasis WHERE id = ?", [$id], 'i');
        
        if ($result['success']) {
            setFlash('success', 'Prestasi berhasil dihapus.');
        } else {
            setFlash('danger', 'Gagal menghapus prestasi.');
        }
    } else {
        setFlash('danger', 'Anda tidak memiliki izin untuk menghapus prestasi ini.');
    }
    
    redirect('siswa/prestasi.php');
}

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
", [$current_user['id']], 'i');

$stats = $stats && $stats->num_rows > 0 ? $stats->fetch_assoc() : [
    'total' => 0,
    'sekolah' => 0,
    'kecamatan' => 0,
    'kabupaten' => 0,
    'provinsi' => 0,
    'nasional' => 0,
    'internasional' => 0
];

// List eskul untuk form tambah prestasi
$eskul_for_prestasi = query("
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
.prestasi-card {
  border-radius: 20px;
  transition: transform .2s ease, box-shadow .2s ease;
}
.prestasi-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 40px rgba(15,23,42,0.12);
}
</style>

<?php
$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
    <?php echo $flash['message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <span class="badge bg-light text-warning mb-2"><i class="bi bi-trophy"></i> Prestasi</span>
    <h3 class="fw-bold mb-1">Prestasi Saya</h3>
    <p class="text-muted mb-0">Catatan penghargaan dan pencapaian terbaik yang pernah diraih.</p>
  </div>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahPrestasiModal">
    <i class="bi bi-plus-circle"></i> Tambah Prestasi
  </button>
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
              <div>
                <span class="badge bg-light text-dark me-1"><?php echo formatTanggal($p['tanggal']); ?></span>
                <a href="?action=delete&id=<?php echo $p['id']; ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Yakin ingin menghapus prestasi ini?')"
                   title="Hapus Prestasi">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
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
      <p class="text-muted mb-3">Terus berlatih dan raih prestasi terbaikmu!</p>
      <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#tambahPrestasiModal">
        <i class="bi bi-plus-circle"></i> Tambah Prestasi Pertama
      </button>
    </div>
  </div>
<?php endif; ?>

<div class="alert alert-success mt-4">
  <i class="bi bi-lightbulb"></i> Simpan sertifikat dan bukti prestasi Anda dengan baik—akan sangat berguna untuk portofolio akademik.
</div>

<!-- Modal Tambah Prestasi -->
<div class="modal fade" id="tambahPrestasiModal" tabindex="-1" aria-labelledby="tambahPrestasiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="tambahPrestasiModalLabel">
                        <i class="bi bi-trophy"></i> Tambah Prestasi Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tambah_prestasi" value="1">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Info:</strong> Tambahkan prestasi yang Anda raih dalam kegiatan ekstrakurikuler.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="ekstrakurikuler_id" class="form-label">Ekstrakurikuler <span class="text-danger">*</span></label>
                            <select name="ekstrakurikuler_id" id="ekstrakurikuler_id" class="form-select" required>
                                <option value="">-- Pilih Ekstrakurikuler --</option>
                                <?php while ($e = $eskul_for_prestasi->fetch_assoc()): ?>
                                <option value="<?php echo $e['id']; ?>">
                                    <?php echo htmlspecialchars($e['nama_ekskul']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="nama_prestasi" class="form-label">Nama Prestasi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_prestasi" id="nama_prestasi" class="form-control" 
                                   placeholder="Contoh: Juara 1 Lomba Basket Antar Sekolah" required maxlength="200">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tingkat" class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select name="tingkat" id="tingkat" class="form-select" required>
                                <option value="">-- Pilih Tingkat --</option>
                                <option value="sekolah">Sekolah</option>
                                <option value="kecamatan">Kecamatan</option>
                                <option value="kabupaten">Kabupaten</option>
                                <option value="provinsi">Provinsi</option>
                                <option value="nasional">Nasional</option>
                                <option value="internasional">Internasional</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="peringkat" class="form-label">Peringkat <span class="text-danger">*</span></label>
                            <input type="text" name="peringkat" id="peringkat" class="form-control" 
                                   placeholder="Contoh: Juara 1, Juara 2, Peserta" required maxlength="50">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="penyelenggara" class="form-label">Penyelenggara <span class="text-danger">*</span></label>
                            <input type="text" name="penyelenggara" id="penyelenggara" class="form-control" 
                                   placeholder="Contoh: Dinas Pendidikan Kab. Lebak" required maxlength="100">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3" 
                                      placeholder="Tambahkan deskripsi singkat tentang prestasi (opsional)"></textarea>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="sertifikat" class="form-label">Upload Sertifikat (Opsional)</label>
                            <input type="file" name="sertifikat" id="sertifikat" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Format: PDF, JPG, JPEG, PNG. Maksimal 5MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Prestasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/berry_siswa_shell_close.php'; ?>