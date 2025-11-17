<?php
// siswa/galeri.php - FIXED untuk tabel galeris
require_once '../config/database.php';
requireRole(['siswa']);

$page_title = 'Galeri';
$current_user = getCurrentUser();

// Filter
$eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';

// Build query - FIXED: tabel galeris, kolom gambar
$where_clause = "ae.user_id = ? AND ae.status = 'diterima' AND g.is_active = 1";
$params = [$current_user['id']];
$types = 'i';

if ($eskul_filter) {
    $where_clause .= " AND e.id = ?";
    $params[] = $eskul_filter;
    $types .= 'i';
}

// Get galeri - FIXED query
$galeri = query("
    SELECT 
        g.*,
        e.nama_ekskul
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE $where_clause
    ORDER BY g.tanggal_upload DESC, g.urutan ASC
", $params, $types);

// List eskul
$eskul_list = query("
    SELECT DISTINCT e.id, e.nama_ekskul
    FROM ekstrakurikulers e
    JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
    WHERE ae.user_id = ? AND ae.status = 'diterima'
    ORDER BY e.nama_ekskul
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
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
            padding: 15px;
            transform: translateY(100%);
            transition: transform 0.3s;
        }
        .gallery-item:hover .gallery-overlay {
            transform: translateY(0);
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
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/prestasi.php">
                            <i class="bi bi-trophy-fill"></i> Prestasi Saya
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>siswa/berita.php">
                            <i class="bi bi-newspaper"></i> Berita & Kegiatan
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/galeri.php">
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
                        <h2><i class="bi bi-images text-primary"></i> Galeri</h2>
                        <p class="text-muted">Dokumentasi kegiatan ekstrakurikuler</p>
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

                <!-- Gallery Grid -->
                <?php if ($galeri && $galeri->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($g = $galeri->fetch_assoc()): ?>
                    <?php
                    // Fix path gambar untuk struktur: assets/img/uploads/galeri/
                    $img_path = $g['gambar'];
                    
                    // Hapus 'uploads/' jika ada, karena akan ditambahkan manual
                    $img_path = str_replace('uploads/', '', $img_path);
                    
                    // Build full path dengan struktur: assets/img/uploads/
                    $full_path = BASE_URL . 'assets/img/uploads/' . $img_path;
                    ?>
                    <div class="col-md-4">
                        <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#modal<?php echo $g['id']; ?>">
                            <img src="<?php echo $full_path; ?>" 
                                 alt="<?php echo htmlspecialchars($g['judul']); ?>"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/198754/ffffff?text=No+Image';">
                            <div class="gallery-overlay">
                                <h6 class="mb-1"><?php echo htmlspecialchars($g['judul']); ?></h6>
                                <small>
                                    <span class="badge bg-primary"><?php echo $g['nama_ekskul']; ?></span>
                                    <span class="ms-2"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', strtotime($g['tanggal_upload'])); ?></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Detail -->
                    <div class="modal fade" id="modal<?php echo $g['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo htmlspecialchars($g['judul']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="<?php echo $full_path; ?>" 
                                         class="img-fluid rounded mb-3" 
                                         alt="<?php echo htmlspecialchars($g['judul']); ?>"
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/800x600/198754/ffffff?text=No+Image';">
                                    <div class="mb-3">
                                        <span class="badge bg-primary me-2"><?php echo $g['nama_ekskul']; ?></span>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i> <?php echo date('d F Y', strtotime($g['tanggal_upload'])); ?>
                                        </small>
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
                        <i class="bi bi-images text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Belum Ada Galeri</h4>
                        <p class="text-muted">Belum ada foto dokumentasi untuk ekstrakurikuler yang kamu ikuti.</p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>Info:</strong> Galeri berisi dokumentasi kegiatan dari ekstrakurikuler yang kamu ikuti. Klik foto untuk melihat detail.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>