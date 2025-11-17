<?php
// siswa/sertifikat.php (Updated)
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Sertifikat';
$current_user = getCurrentUser();

// Get eskul yang diikuti (status diterima) dengan nilai
$eskul_saya = query("
    SELECT 
        e.*,
        ae.tanggal_daftar,
        ae.id as anggota_id,
        ae.nilai,
        ae.tanggal_penilaian,
        ae.catatan_pembina,
        u.name as pembina,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id AND p.status = 'hadir') as total_hadir,
        (SELECT COUNT(*) FROM presensis p WHERE p.anggota_id = ae.id) as total_pertemuan
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON e.pembina_id = u.id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY ae.tanggal_daftar DESC
", [$current_user['id']], 'i');

// Get prestasi untuk sertifikat
$prestasi = query("
    SELECT 
        p.*,
        e.nama_ekskul
    FROM prestasis p
    JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.user_id = ?
    ORDER BY p.tanggal DESC
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
        .sertifikat-card {
            transition: all 0.3s;
            border: 2px solid #e0e0e0;
        }
        .sertifikat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-color: #0d6efd;
        }
        .sertifikat-preview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }
        .nilai-badge-big {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 10px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .nilai-A { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white; 
        }
        .nilai-B { 
            background: linear-gradient(135deg, #ffc107, #fd7e14); 
            color: white; 
        }
        .nilai-C { 
            background: linear-gradient(135deg, #dc3545, #e83e8c); 
            color: white; 
        }
        @media print {
            .no-print { display: none !important; }
            .print-area { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
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
            <div class="col-md-2 bg-light p-0 no-print" style="min-height: calc(100vh - 56px);">
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
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/prestasi.php">
                            <i class="bi bi-trophy-fill"></i> Prestasi Saya
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/berita.php">
                            <i class="bi bi-newspaper"></i> Berita & Kegiatan
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/galeri.php">
                            <i class="bi bi-images"></i> Galeri
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/sertifikat.php">
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
                        <h2><i class="bi bi-award-fill text-success"></i> Sertifikat</h2>
                        <p class="text-muted">Cetak dan download sertifikat keikutsertaan</p>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>Informasi:</strong> Sertifikat dapat dicetak untuk ekstrakurikuler yang sudah Anda ikuti dengan minimal kehadiran 75%. Nilai yang tertera adalah penilaian dari pembina ekstrakurikuler.
                </div>

                <!-- Sertifikat Keikutsertaan -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-award"></i> Sertifikat Keikutsertaan Ekstrakurikuler</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($eskul_saya && $eskul_saya->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($e = $eskul_saya->fetch_assoc()): 
                                $persentase_hadir = $e['total_pertemuan'] > 0 ? round(($e['total_hadir'] / $e['total_pertemuan']) * 100) : 0;
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card sertifikat-card h-100">
                                    <div class="card-body">
                                        <div class="sertifikat-preview mb-3">
                                            <i class="bi bi-award-fill" style="font-size: 4rem;"></i>
                                            <h5 class="mt-3 mb-0">SERTIFIKAT</h5>
                                            <small>Keikutsertaan Ekstrakurikuler</small>
                                        </div>
                                        <h5 class="text-primary mb-3"><?php echo $e['nama_ekskul']; ?></h5>
                                        
                                        <!-- Nilai -->
                                        <?php if ($e['nilai']): ?>
                                        <div class="text-center mb-3">
                                            <span class="nilai-badge-big nilai-<?php echo $e['nilai']; ?>">
                                                ⭐ NILAI <?php echo $e['nilai']; ?> ⭐
                                            </span>
                                            <?php if ($e['tanggal_penilaian']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Dinilai: <?php echo formatTanggal($e['tanggal_penilaian']); ?>
                                                </small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-warning text-center mb-3">
                                            <small>⏳ Belum dinilai oleh pembina</small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Catatan Pembina -->
                                        <?php if ($e['catatan_pembina']): ?>
                                        <div class="alert alert-info mb-3">
                                            <small>
                                                <strong>📝 Catatan Pembina:</strong><br>
                                                <em>"<?php echo $e['catatan_pembina']; ?>"</em>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> Pembina: <?php echo $e['pembina'] ?: '-'; ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3"></i> Bergabung: <?php echo formatTanggal($e['tanggal_daftar']); ?>
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-clipboard-check"></i> Kehadiran: <?php echo $e['total_hadir']; ?>/<?php echo $e['total_pertemuan']; ?> (<?php echo $persentase_hadir; ?>%)
                                            </small>
                                        </div>
                                        <div class="progress mb-3" style="height: 5px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $persentase_hadir; ?>%"></div>
                                        </div>
                                        <?php if ($persentase_hadir >= 75): ?>
                                        <a href="<?php echo BASE_URL; ?>cetak_sertifikat.php?id=<?php echo $e['anggota_id']; ?>&print=1" target="_blank" class="btn btn-success w-100">
                                            <i class="bi bi-printer"></i> Cetak Sertifikat
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            <i class="bi bi-x-circle"></i> Kehadiran Minimal 75%
                                        </button>
                                        <small class="text-danger d-block mt-2">
                                            <i class="bi bi-exclamation-triangle"></i> Tingkatkan kehadiran Anda untuk mencetak sertifikat
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                            <h5 class="mt-3 text-muted">Belum Terdaftar di Ekstrakurikuler</h5>
                            <p class="text-muted">Daftar ekstrakurikuler terlebih dahulu untuk mendapatkan sertifikat</p>
                            <a href="<?php echo BASE_URL; ?>daftar_eskul.php" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Daftar Sekarang
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sertifikat Prestasi -->
                <?php if ($prestasi && $prestasi->num_rows > 0): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-trophy-fill"></i> Sertifikat Prestasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Prestasi</th>
                                        <th>Peringkat</th>
                                        <th>Tingkat</th>
                                        <th>Tanggal</th>
                                        <th>Sertifikat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($p = $prestasi->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['nama_prestasi'] ?? '-'); ?></strong><br>
                                            <small class="text-muted"><?php echo $p['nama_ekskul']; ?></small>
                                        </td>
                                        <td><span class="badge bg-success"><?php echo htmlspecialchars($p['peringkat'] ?? '-'); ?></span></td>
                                        <td><span class="badge bg-primary"><?php echo ucfirst($p['tingkat']); ?></span></td>
                                        <td><?php echo formatTanggal($p['tanggal']); ?></td>
                                        <td>
                                            <?php if ($p['sertifikat']): ?>
                                            <a href="<?php echo BASE_URL . $p['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-success">
                                                <i class="bi bi-file-earmark-pdf"></i> Lihat
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Syarat & Ketentuan -->
                <div class="alert alert-warning mt-4">
                    <h6><i class="bi bi-exclamation-triangle"></i> Syarat & Ketentuan:</h6>
                    <ul class="mb-0">
                        <li>Sertifikat keikutsertaan hanya dapat dicetak dengan minimal kehadiran 75%</li>
                        <li>Nilai (A, B, C) diberikan oleh pembina berdasarkan performa dan dedikasi Anda</li>
                        <li>Nilai dan catatan pembina akan tertera di sertifikat yang dicetak</li>
                        <li>Sertifikat prestasi tersedia jika file telah diupload oleh pembina</li>
                        <li>Pastikan data diri Anda sudah lengkap sebelum mencetak sertifikat</li>
                        <li>Sertifikat dapat dicetak sewaktu-waktu dan tidak memiliki masa berlaku</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>