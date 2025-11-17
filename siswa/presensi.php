<?php
// siswa/presensi.php
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Presensi Saya';
$current_user = getCurrentUser();

// Filter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';

// Query presensi dengan filter
$where_clause = "ae.user_id = ?";
$params = [$current_user['id']];
$types = 'i';

if ($eskul_filter) {
    $where_clause .= " AND e.id = ?";
    $params[] = $eskul_filter;
    $types .= 'i';
}

if ($bulan && $tahun) {
    $where_clause .= " AND MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
    $types .= 'ii';
}

$presensi = query("
    SELECT 
        p.id,
        p.tanggal,
        p.status,
        p.keterangan,
        e.nama_ekskul,
        e.id as eskul_id
    FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE $where_clause
    ORDER BY p.tanggal DESC
", $params, $types);

// Statistik presensi
$stats = query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN p.status = 'alpa' THEN 1 ELSE 0 END) as alpa
    FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
    " . ($bulan && $tahun ? "AND MONTH(p.tanggal) = ? AND YEAR(p.tanggal) = ?" : ""),
    $bulan && $tahun ? [$current_user['id'], $bulan, $tahun] : [$current_user['id']],
    $bulan && $tahun ? 'iii' : 'i'
)->fetch_assoc();

// List eskul untuk filter
$eskul_list = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
", [$current_user['id']], 'i');

// Hitung persentase
$persentase_hadir = $stats['total'] > 0 ? round(($stats['hadir'] / $stats['total']) * 100) : 0;
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
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
            <!-- Sidebar -->
            <div class="col-md-2 bg-light p-0" style="min-height: calc(100vh - 56px);">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/jadwal.php">
                            <i class="bi bi-calendar-week"></i> Jadwal Kegiatan
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/presensi.php">
                            <i class="bi bi-clipboard-check"></i> Presensi Saya
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/prestasi.php">
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

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-clipboard-check text-primary"></i> Presensi Saya</h2>
                        <p class="text-muted">Riwayat kehadiran dalam kegiatan ekstrakurikuler</p>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Bulan</label>
                                <select name="bulan" class="form-select">
                                    <?php
                                    $nama_bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                    for ($i = 1; $i <= 12; $i++):
                                    ?>
                                    <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo $bulan == sprintf('%02d', $i) ? 'selected' : ''; ?>>
                                        <?php echo $nama_bulan[$i]; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tahun</label>
                                <select name="tahun" class="form-select">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ekstrakurikuler</label>
                                <select name="eskul" class="form-select">
                                    <option value="">Semua Eskul</option>
                                    <?php while ($e = $eskul_list->fetch_assoc()): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo $eskul_filter == $e['id'] ? 'selected' : ''; ?>>
                                        <?php echo $e['nama_ekskul']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Pertemuan</h6>
                                        <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                                    </div>
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="bi bi-calendar3 fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Hadir</h6>
                                        <h2 class="mb-0"><?php echo $stats['hadir']; ?></h2>
                                        <small class="text-success"><?php echo $persentase_hadir; ?>%</small>
                                    </div>
                                    <div class="bg-success text-white rounded-circle p-3">
                                        <i class="bi bi-check-circle fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Izin</h6>
                                        <h2 class="mb-0"><?php echo $stats['izin']; ?></h2>
                                        <small class="text-muted">+ Sakit: <?php echo $stats['sakit']; ?></small>
                                    </div>
                                    <div class="bg-warning text-white rounded-circle p-3">
                                        <i class="bi bi-file-earmark-medical fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Alpa</h6>
                                        <h2 class="mb-0"><?php echo $stats['alpa']; ?></h2>
                                    </div>
                                    <div class="bg-danger text-white rounded-circle p-3">
                                        <i class="bi bi-x-circle fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">Persentase Kehadiran</h6>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $persentase_hadir; ?>%;" 
                                 aria-valuenow="<?php echo $persentase_hadir; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo $persentase_hadir; ?>% Hadir
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <?php if ($persentase_hadir >= 80): ?>
                            <i class="bi bi-emoji-smile text-success"></i> Kehadiran Anda sangat baik! Pertahankan!
                            <?php elseif ($persentase_hadir >= 60): ?>
                            <i class="bi bi-emoji-neutral text-warning"></i> Kehadiran cukup, tingkatkan lagi ya!
                            <?php else: ?>
                            <i class="bi bi-emoji-frown text-danger"></i> Kehadiran kurang, ayo lebih rajin hadir!
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <!-- Tabel Presensi -->
                <?php if ($presensi && $presensi->num_rows > 0): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-table"></i> Riwayat Presensi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Ekstrakurikuler</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $badge_status = [
                                        'hadir' => 'success',
                                        'izin' => 'warning',
                                        'sakit' => 'info',
                                        'alpa' => 'danger'
                                    ];
                                    $icon_status = [
                                        'hadir' => 'check-circle',
                                        'izin' => 'file-earmark-text',
                                        'sakit' => 'file-earmark-medical',
                                        'alpa' => 'x-circle'
                                    ];
                                    while ($p = $presensi->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <i class="bi bi-calendar3"></i> 
                                            <?php echo formatTanggal($p['tanggal']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $p['nama_ekskul']; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge_status[$p['status']]; ?>">
                                                <i class="bi bi-<?php echo $icon_status[$p['status']]; ?>"></i>
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($p['keterangan']): ?>
                                            <small class="text-muted"><?php echo $p['keterangan']; ?></small>
                                            <?php else: ?>
                                            <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clipboard-x text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Belum Ada Data Presensi</h4>
                        <p class="text-muted">Belum ada catatan kehadiran untuk periode yang dipilih.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Info -->
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>Catatan:</strong> Presensi dicatat oleh pembina pada setiap pertemuan. Pastikan Anda selalu hadir tepat waktu.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>