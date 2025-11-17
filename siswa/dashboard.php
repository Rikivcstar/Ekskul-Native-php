<?php
// siswa/dashboard.php
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Dashboard Siswa';
$current_user = getCurrentUser();

// Ambil data eskul yang diikuti
$eskul_saya = query("
    SELECT e.*, ae.tanggal_daftar, ae.status, ae.id as anggota_id
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.user_id = ?
    ORDER BY ae.created_at DESC
", [$current_user['id']], 'i');

// Statistik siswa
$total_eskul = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'", [$current_user['id']], 'i')->fetch_assoc()['total'];
$total_pending = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE user_id = ? AND status = 'pending'", [$current_user['id']], 'i')->fetch_assoc()['total'];
$total_prestasi = query("
    SELECT COUNT(*) as total FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ?
", [$current_user['id']], 'i')->fetch_assoc()['total'];

// Presensi bulan ini
$presensi_bulan_ini = query("
    SELECT COUNT(*) as total FROM presensis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    WHERE ae.user_id = ? AND MONTH(p.tanggal) = MONTH(CURDATE()) AND YEAR(p.tanggal) = YEAR(CURDATE())
", [$current_user['id']], 'i')->fetch_assoc()['total'];

// Pengumuman terbaru (3 terakhir)
$pengumuman = query("
    SELECT p.id, p.judul, p.prioritas, p.created_at, e.nama_ekskul
    FROM pengumuman p
    LEFT JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    WHERE p.is_active = 1
    AND CURDATE() BETWEEN p.tanggal_mulai AND p.tanggal_selesai
    AND (
        p.ekstrakurikuler_id IS NULL 
        OR p.ekstrakurikuler_id IN (
            SELECT ekstrakurikuler_id FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'
        )
    )
    ORDER BY FIELD(p.prioritas, 'tinggi', 'sedang', 'rendah'), p.created_at DESC
    LIMIT 3
", [$current_user['id']], 'i');

// Berita terbaru (3 terakhir)
$berita = query("
    SELECT b.id, b.judul, b.tanggal_post, e.nama_ekskul
    FROM berita b
    JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
    WHERE b.is_published = 1
    AND b.ekstrakurikuler_id IN (
        SELECT ekstrakurikuler_id FROM anggota_ekskul WHERE user_id = ? AND status = 'diterima'
    )
    ORDER BY b.tanggal_post DESC
    LIMIT 3
", [$current_user['id']], 'i');
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
        .menu-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-height: 180px;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
            background: white;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .ekstrakurikuler-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        .ekstrakurikuler-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                <i class="bi bi-grid-fill"></i> Dashboard Siswa
            </a>
            <div class="d-flex align-items-center text-white">
                <div class="dropdown me-2">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($total_pending > 0): ?>
                        <span class="badge bg-danger"><?php echo $total_pending; ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Notifikasi</h6></li>
                        <?php if ($total_pending > 0): ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                            <i class="bi bi-clock text-warning"></i> <?php echo $total_pending; ?> Pendaftaran Pending
                        </a></li>
                        <?php else: ?>
                        <li><span class="dropdown-item text-muted">Tidak ada notifikasi</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
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
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/jadwal.php">
                            <i class="bi bi-calendar-week"></i> Jadwal Kegiatan
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/presensi.php">
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
                        <h2>Selamat Datang, <?php echo $current_user['name']; ?>!</h2>
                        <p class="text-muted">
                            <i class="bi bi-person-badge"></i> NIS: <?php echo $current_user['nis']; ?> | 
                            <i class="bi bi-book"></i> Kelas: <?php echo $current_user['kelas']; ?>
                        </p>
                    </div>
                </div>

                <!-- Statistik Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Eskul Diikuti</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_eskul; ?>">0</h2>
                                    </div>
                                    <div class="bg-success text-white rounded-circle p-3">
                                        <i class="bi bi-grid-fill fs-4"></i>
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
                                        <h6 class="text-muted mb-1">Pending</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_pending; ?>">0</h2>
                                    </div>
                                    <div class="bg-warning text-white rounded-circle p-3">
                                        <i class="bi bi-clock-fill fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Prestasi</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $total_prestasi; ?>">0</h2>
                                    </div>
                                    <div class="bg-info text-white rounded-circle p-3">
                                        <i class="bi bi-trophy-fill fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="card stat-card border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Presensi Bulan Ini</h6>
                                        <h2 class="mb-0 counter" data-target="<?php echo $presensi_bulan_ini; ?>">0</h2>
                                    </div>
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="bi bi-clipboard-check fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu Cepat -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="text-decoration-none">
                            <div class="card menu-card bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-pencil-square text-white" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-0 text-dark">Daftar Eskul</h5>
                                    <small class="text-dark">Daftar ekstrakurikuler baru</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>siswa/jadwal.php" class="text-decoration-none">
                            <div class="card menu-card bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-calendar-week text-white" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-0 text-dark">Jadwal</h5>
                                    <small class="text-dark">Lihat jadwal kegiatan</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>siswa/sertifikat.php" class="text-decoration-none">
                            <div class="card menu-card bg-gradient" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-award-fill text-white" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-0 text-dark">Sertifikat</h5>
                                    <small class="text-dark">Cetak sertifikat</small>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>siswa/galeri.php" class="text-decoration-none">
                            <div class="card menu-card bg-gradient" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                <div class="card-body text-center py-4">
                                    <i class="bi bi-images text-white" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 mb-0 text-dark">Galeri</h5>
                                    <small class="text-dark">Dokumentasi kegiatan</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Ekstrakurikuler Saya -->
                    <div class="col-md-8 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-grid-fill text-primary"></i> Ekstrakurikuler Saya</h5>
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
                                    while ($eskul = $eskul_saya->fetch_assoc()): 
                                    ?>
                                    <div class="ekstrakurikuler-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h6 class="mb-1 fw-bold text-primary"><?php echo $eskul['nama_ekskul']; ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3"></i> Bergabung: <?php echo formatTanggal($eskul['tanggal_daftar']); ?>
                                                </small>
                                                <?php if ($eskul['status'] == 'pending'): ?>
                                                <br><small class="text-warning"><i class="bi bi-info-circle"></i> Menunggu persetujuan pembina</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <span class="badge bg-<?php echo $badge_class[$eskul['status']]; ?> px-3 py-2">
                                                    <?php echo ucfirst($eskul['status']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $eskul['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                                    <h5 class="text-muted mt-3 mb-3">Anda belum terdaftar di ekstrakurikuler manapun</h5>
                                    <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Daftar Sekarang
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Berita Terbaru -->
                        <?php if ($berita && $berita->num_rows > 0): ?>
                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-newspaper text-info"></i> Berita Terbaru</h5>
                                <a href="<?php echo BASE_URL; ?>siswa/berita.php" class="btn btn-sm btn-outline-primary">
                                    Lihat Semua
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php while ($b = $berita->fetch_assoc()): ?>
                                    <a href="<?php echo BASE_URL; ?>siswa/berita.php?id=<?php echo $b['id']; ?>" class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($b['judul']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3"></i> <?php echo formatTanggal($b['tanggal_post']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-primary ms-2"><?php echo $b['nama_ekskul']; ?></span>
                                        </div>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar Kanan -->
                    <div class="col-md-4">
                        <!-- Quick Stats -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Ringkasan Aktivitas</h6>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>