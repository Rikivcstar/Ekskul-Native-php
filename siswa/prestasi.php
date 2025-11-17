<?php
// siswa/prestasi.php
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Prestasi Saya';
$current_user = getCurrentUser();

// Filter
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$tingkat = isset($_GET['tingkat']) ? $_GET['tingkat'] : '';

// Query prestasi
$where_clause = "ae.user_id = ?";
$params = [$current_user['id']];
$types = 'i';

if ($tahun) {
    $where_clause .= " AND YEAR(p.tanggal) = ?";
    $params[] = $tahun;
    $types .= 'i';
}

if ($tingkat) {
    $where_clause .= " AND p.tingkat = ?";
    $params[] = $tingkat;
    $types .= 's';
}

$prestasi = query("
    SELECT 
        p.*,
        e.nama_ekskul,
        e.id as eskul_id
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE $where_clause
    ORDER BY p.tanggal DESC
", $params, $types);

// Statistik prestasi
$stats = query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN p.tingkat = 'sekolah' THEN 1 ELSE 0 END) as sekolah,
        SUM(CASE WHEN p.tingkat = 'kecamatan' THEN 1 ELSE 0 END) as kecamatan,
        SUM(CASE WHEN p.tingkat = 'kabupaten' THEN 1 ELSE 0 END) as kabupaten,
        SUM(CASE WHEN p.tingkat = 'provinsi' THEN 1 ELSE 0 END) as provinsi,
        SUM(CASE WHEN p.tingkat = 'nasional' THEN 1 ELSE 0 END) as nasional,
        SUM(CASE WHEN p.tingkat = 'internasional' THEN 1 ELSE 0 END) as internasional
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
", [$current_user['id']], 'i')->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - MTsN 1 Lebak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .prestasi-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .prestasi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                <i class="bi bi-arrow-left"></i> Dashboard Siswa
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="badge bg-light text-primary me-2">Siswa</span>
                <span class="me-3">
                    <i class="bi bi-person-circle"></i> <?php echo $current_user['name']; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>siswa/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-light p-0" style="min-height: calc(100vh - 56px);">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/jadwal.php">
                            <i class="bi bi-calendar-week"></i> Jadwal Kegiatan
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/presensi.php">
                            <i class="bi bi-clipboard-check"></i> Presensi Saya
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/prestasi.php">
                            <i class="bi bi-trophy-fill"></i> Prestasi Saya
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/berita.php">
                            <i class="bi bi-newspaper"></i> Berita & Kegiatan
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/galeri.php">
                            <i class="bi bi-images"></i> Galeri
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/sertifikat.php">
                            <i class="bi bi-award-fill"></i> Sertifikat
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/profil.php">
                            <i class="bi bi-person-circle"></i> Profil Saya
                        </a>
                        <hr class="my-2">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">
                            <i class="bi bi-house-fill"></i> Kembali ke Beranda
                        </a>
                    </nav>
                </div>
            </div>

            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-trophy-fill text-warning"></i> Prestasi Saya</h2>
                        <p class="text-muted">Pencapaian dan penghargaan yang telah diraih</p>
                    </div>
                </div>

                <!-- Filter -->
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
                                    <option value="sekolah" <?php echo $tingkat == 'sekolah' ? 'selected' : ''; ?>>Sekolah</option>
                                    <option value="kecamatan" <?php echo $tingkat == 'kecamatan' ? 'selected' : ''; ?>>Kecamatan</option>
                                    <option value="kabupaten" <?php echo $tingkat == 'kabupaten' ? 'selected' : ''; ?>>Kabupaten</option>
                                    <option value="provinsi" <?php echo $tingkat == 'provinsi' ? 'selected' : ''; ?>>Provinsi</option>
                                    <option value="nasional" <?php echo $tingkat == 'nasional' ? 'selected' : ''; ?>>Nasional</option>
                                    <option value="internasional" <?php echo $tingkat == 'internasional' ? 'selected' : ''; ?>>Internasional</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                                <h2 class="mt-2 mb-0"><?php echo $stats['total']; ?></h2>
                                <small class="text-muted">Total Prestasi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-star-fill text-success" style="font-size: 3rem;"></i>
                                <h2 class="mt-2 mb-0"><?php echo $stats['nasional']; ?></h2>
                                <small class="text-muted">Tingkat Nasional</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <i class="bi bi-globe text-info" style="font-size: 3rem;"></i>
                                <h2 class="mt-2 mb-0"><?php echo $stats['internasional']; ?></h2>
                                <small class="text-muted">Internasional</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Stats -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Prestasi Berdasarkan Tingkat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $tingkat_list = ['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional'];
                            $tingkat_label = ['Sekolah', 'Kecamatan', 'Kabupaten', 'Provinsi', 'Nasional', 'Internasional'];
                            $colors = ['primary', 'info', 'success', 'warning', 'danger', 'dark'];
                            foreach ($tingkat_list as $idx => $t):
                            ?>
                            <div class="col-md-2 mb-3">
                                <div class="text-center">
                                    <div class="progress" style="height: 100px;">
                                        <div class="progress-bar bg-<?php echo $colors[$idx]; ?>" 
                                             role="progressbar" 
                                             style="width: 100%; height: <?php echo $stats['total'] > 0 ? ($stats[$t] / $stats['total'] * 100) : 0; ?>%"
                                             aria-valuenow="<?php echo $stats[$t]; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $stats['total']; ?>">
                                        </div>
                                    </div>
                                    <h4 class="mt-2"><?php echo $stats[$t]; ?></h4>
                                    <small class="text-muted"><?php echo $tingkat_label[$idx]; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- List Prestasi -->
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
                                    <span class="badge bg-<?php echo $badge_tingkat[$p['tingkat']]; ?> fs-6">
                                        <?php echo ucfirst($p['tingkat']); ?>
                                    </span>
                                    <i class="bi bi-trophy-fill text-warning" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title text-primary"><?php echo htmlspecialchars($p['nama_prestasi'] ?? ''); ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-success mb-2">
                                        <i class="bi bi-award"></i> <?php echo htmlspecialchars($p['peringkat'] ?? ''); ?>
                                    </span>
                                </p>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> <?php echo formatTanggal($p['tanggal']); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($p['penyelenggara'] ?? ''); ?>
                                    </small>
                                </div>
                                <div class="mb-3">
                                    <small>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-grid-fill"></i> <?php echo $p['nama_ekskul']; ?>
                                        </span>
                                    </small>
                                </div>
                                <?php if ($p['deskripsi']): ?>
                                <p class="small text-muted mb-3"><?php echo htmlspecialchars($p['deskripsi']); ?></p>
                                <?php endif; ?>
                                <?php if ($p['sertifikat']): ?>
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
                        <i class="bi bi-trophy text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Belum Ada Prestasi</h4>
                        <p class="text-muted">Terus berlatih dan raih prestasi terbaikmu!</p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="alert alert-success mt-4">
                    <i class="bi bi-lightbulb"></i>
                    <strong>Tips:</strong> Simpan semua sertifikat dan dokumentasi prestasi Anda dengan baik. Prestasi ini akan menjadi portofolio berharga untuk masa depan Anda.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>