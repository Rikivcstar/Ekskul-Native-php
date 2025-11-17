<?php
// siswa/jadwal.php
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Jadwal Kegiatan';
$current_user = getCurrentUser();

// Get hari ini
$hari_ini = date('l');
$hari_indonesia = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_sekarang = $hari_indonesia[$hari_ini];

// Query jadwal
$jadwal_result = query("
    SELECT 
        j.id,
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
        j.lokasi,
        j.keterangan,
        e.nama_ekskul,
        e.id as eskul_id,
        u.name as pembina
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE ae.user_id = ? 
    AND ae.status = 'diterima'
    AND j.is_active = 1
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'),
        j.jam_mulai
", [$current_user['id']], 'i');

// Convert to array
$jadwal = [];
if ($jadwal_result) {
    while ($row = $jadwal_result->fetch_assoc()) {
        $jadwal[] = $row;
    }
}

// Group by hari
$jadwal_per_hari = [];
foreach ($jadwal as $j) {
    $jadwal_per_hari[$j['hari']][] = $j;
}

$jadwal_hari_ini = isset($jadwal_per_hari[$hari_sekarang]) ? $jadwal_per_hari[$hari_sekarang] : [];
$urutan_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
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
        .schedule-table td {
            width: 14.28%;
            min-width: 150px;
            vertical-align: top;
            padding: 10px;
        }
        .schedule-card {
            transition: transform 0.2s;
        }
        .schedule-card:hover {
            transform: translateY(-2px);
        }
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary sticky-top shadow no-print">
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
            <div class="col-md-2 bg-light p-0 no-print" style="min-height: calc(100vh - 56px);">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/jadwal.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-calendar-week text-primary"></i> Jadwal Kegiatan</h2>
                        <p class="text-muted">Jadwal latihan ekstrakurikuler minggu ini</p>
                    </div>
                    <button onclick="window.print()" class="btn btn-outline-primary no-print">
                        <i class="bi bi-printer"></i> Print Jadwal
                    </button>
                </div>

                <!-- Alert Kegiatan Hari Ini -->
                <?php if (count($jadwal_hari_ini) > 0): ?>
                <div class="alert alert-primary alert-dismissible fade show">
                    <h5><i class="bi bi-bell-fill"></i> Kegiatan Hari Ini - <?php echo $hari_sekarang; ?>, <?php echo date('d M Y'); ?></h5>
                    <hr>
                    <?php foreach ($jadwal_hari_ini as $j): ?>
                    <div class="mb-2">
                        <strong><?php echo $j['nama_ekskul']; ?></strong><br>
                        <i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'], 0, 5); ?> - <?php echo substr($j['jam_selesai'], 0, 5); ?> WIB
                        <span class="ms-2"><i class="bi bi-geo-alt"></i> <?php echo $j['lokasi']; ?></span>
                    </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Jadwal</h6>
                                        <h2 class="mb-0"><?php echo count($jadwal); ?></h2>
                                    </div>
                                    <div class="bg-primary text-white rounded-circle p-3">
                                        <i class="bi bi-calendar3 fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Kegiatan Hari Ini</h6>
                                        <h2 class="mb-0"><?php echo count($jadwal_hari_ini); ?></h2>
                                    </div>
                                    <div class="bg-success text-white rounded-circle p-3">
                                        <i class="bi bi-calendar-check fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Hari Aktif</h6>
                                        <h2 class="mb-0"><?php echo count($jadwal_per_hari); ?></h2>
                                    </div>
                                    <div class="bg-warning text-white rounded-circle p-3">
                                        <i class="bi bi-grid-fill fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (count($jadwal) > 0): ?>
                <!-- Jadwal Mingguan -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Jadwal Mingguan</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0 schedule-table">
                                <thead class="table-light">
                                    <tr>
                                        <?php foreach ($urutan_hari as $hari): ?>
                                        <th class="text-center <?php echo $hari == $hari_sekarang ? 'table-primary' : ''; ?>">
                                            <?php echo $hari; ?>
                                            <?php if ($hari == $hari_sekarang): ?>
                                            <br><span class="badge bg-primary">Hari Ini</span>
                                            <?php endif; ?>
                                        </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <?php foreach ($urutan_hari as $hari): ?>
                                        <td class="<?php echo $hari == $hari_sekarang ? 'table-primary bg-opacity-10' : ''; ?>">
                                            <?php if (isset($jadwal_per_hari[$hari])): ?>
                                                <?php foreach ($jadwal_per_hari[$hari] as $j): ?>
                                                <div class="card schedule-card mb-2 border-start border-4 border-primary">
                                                    <div class="card-body p-2">
                                                        <div class="fw-bold text-primary small"><?php echo $j['nama_ekskul']; ?></div>
                                                        <div class="small mb-1">
                                                            <i class="bi bi-clock"></i> <?php echo substr($j['jam_mulai'], 0, 5); ?> - <?php echo substr($j['jam_selesai'], 0, 5); ?>
                                                        </div>
                                                        <div class="small text-muted">
                                                            <i class="bi bi-geo-alt"></i> <?php echo $j['lokasi']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <small>Tidak ada kegiatan</small>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detail Jadwal Per Hari -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detail Jadwal</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($urutan_hari as $hari): ?>
                            <?php if (isset($jadwal_per_hari[$hari])): ?>
                            <div class="mb-4">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-calendar-day text-primary"></i> <?php echo $hari; ?>
                                    <?php if ($hari == $hari_sekarang): ?>
                                    <span class="badge bg-primary">Hari Ini</span>
                                    <?php endif; ?>
                                </h5>
                                <div class="row">
                                    <?php foreach ($jadwal_per_hari[$hari] as $j): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-0 shadow-sm h-100 schedule-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="text-primary mb-0">
                                                        <i class="bi bi-grid-fill"></i> <?php echo $j['nama_ekskul']; ?>
                                                    </h6>
                                                    <span class="badge bg-primary">
                                                        <?php 
                                                        $durasi = (strtotime($j['jam_selesai']) - strtotime($j['jam_mulai'])) / 3600;
                                                        echo $durasi . ' jam';
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="mb-2">
                                                    <i class="bi bi-clock text-primary"></i>
                                                    <strong><?php echo substr($j['jam_mulai'], 0, 5); ?> - <?php echo substr($j['jam_selesai'], 0, 5); ?> WIB</strong>
                                                </div>
                                                <div class="mb-2">
                                                    <i class="bi bi-geo-alt-fill text-danger"></i> <?php echo $j['lokasi']; ?>
                                                </div>
                                                <?php if ($j['pembina']): ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-person-fill text-success"></i> <?php echo $j['pembina']; ?>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($j['keterangan']): ?>
                                                <div class="mt-2 pt-2 border-top">
                                                    <small class="text-muted">
                                                        <i class="bi bi-info-circle"></i> <?php echo $j['keterangan']; ?>
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                                <div class="mt-3">
                                                    <a href="<?php echo BASE_URL; ?>profile_eskul.php?id=<?php echo $j['eskul_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Detail Eskul
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- Empty State -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Belum Ada Jadwal</h4>
                        <p class="text-muted">Kamu belum memiliki jadwal kegiatan.<br>Daftar ekstrakurikuler terlebih dahulu untuk melihat jadwal.</p>
                        <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Daftar Ekstrakurikuler
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Info Box -->
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>Catatan:</strong> Jadwal dapat berubah sewaktu-waktu. Pastikan selalu cek informasi terbaru dari pembina atau pengumuman.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>