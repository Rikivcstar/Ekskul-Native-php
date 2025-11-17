<?php
// siswa/berita.php - All-in-one (List + Detail)
require_once '../config/database.php';
requireRole(['siswa']);

$current_user = getCurrentUser();

// Cek apakah ada parameter id (detail berita)
if (isset($_GET['id'])) {
    // ========================================
    // MODE: DETAIL BERITA
    // ========================================
    $berita_id = $_GET['id'];
    
    // Query detail berita - FIXED: remove created_by
    $detail = query("
        SELECT b.*, e.nama_ekskul, e.id as eskul_id
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        WHERE b.id = ? AND b.is_published = 1
    ", [$berita_id], 'i');
    
    if (!$detail || $detail->num_rows == 0) {
        setFlash('Berita tidak ditemukan!', 'danger');
        redirect('siswa/berita.php');
    }
    
    $berita_detail = $detail->fetch_assoc();
    
    // Update views
    execute("UPDATE berita SET views = views + 1 WHERE id = ?", [$berita_id], 'i');
    
    // Berita terkait (dari eskul yang sama)
    $related = query("
        SELECT b.* 
        FROM berita b
        WHERE b.ekstrakurikuler_id = ? 
          AND b.id != ? 
          AND b.is_published = 1
        ORDER BY b.tanggal_post DESC 
        LIMIT 3
    ", [$berita_detail['eskul_id'], $berita_id], 'ii');
    
    // Berita populer
    $populer = query("
        SELECT b.id, b.judul, b.views, b.tanggal_post
        FROM berita b
        WHERE b.is_published = 1
        ORDER BY b.views DESC
        LIMIT 5
    ");
    
    $page_title = $berita_detail['judul'];
    
} else {
    // ========================================
    // MODE: LIST BERITA
    // ========================================
    $page_title = 'Berita & Kegiatan';
    
    // Pagination
    $limit = 9;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Filter
    $eskul_filter = isset($_GET['eskul']) ? $_GET['eskul'] : '';
    
    // Build query
    $where_clause = "b.is_published = 1 AND ae.user_id = ? AND ae.status = 'diterima'";
    $params = [$current_user['id']];
    $types = 'i';
    
    if ($eskul_filter) {
        $where_clause .= " AND e.id = ?";
        $params[] = $eskul_filter;
        $types .= 'i';
    }
    
    // Count total
    $total_result = query("
        SELECT COUNT(DISTINCT b.id) as total
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE $where_clause
    ", $params, $types);
    $total = $total_result->fetch_assoc()['total'];
    $total_pages = ceil($total / $limit);
    
    // Get berita - FIXED: remove created_by
    $berita = query("
        SELECT DISTINCT
            b.*,
            e.nama_ekskul
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE $where_clause
        ORDER BY b.tanggal_post DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]), $types . 'ii');
    
    // List eskul untuk filter
    $eskul_list = query("
        SELECT DISTINCT e.id, e.nama_ekskul
        FROM ekstrakurikulers e
        JOIN anggota_ekskul ae ON e.id = ae.ekstrakurikuler_id
        WHERE ae.user_id = ? AND ae.status = 'diterima'
        ORDER BY e.nama_ekskul
    ", [$current_user['id']], 'i');
}
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
        .berita-card {
            transition: all 0.3s;
            height: 100%;
        }
        .berita-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .berita-img {
            height: 200px;
            object-fit: cover;
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
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>siswa/berita.php">
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
                        <h2><i class="bi bi-newspaper text-primary"></i> Berita & Kegiatan</h2>
                        <p class="text-muted">Informasi terbaru dari ekstrakurikuler yang diikuti</p>
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

                <!-- Berita Grid -->
                <?php if ($berita && $berita->num_rows > 0): ?>
                <div class="row">
                    <?php while ($b = $berita->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card berita-card border-0 shadow-sm">
                            <?php if ($b['gambar']): ?>
                            <img src="<?php echo BASE_URL . $b['gambar']; ?>" class="card-img-top berita-img" alt="<?php echo htmlspecialchars($b['judul']); ?>">
                            <?php else: ?>
                            <div class="berita-img bg-primary d-flex align-items-center justify-content-center">
                                <i class="bi bi-newspaper text-white" style="font-size: 4rem;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo $b['nama_ekskul']; ?></span>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($b['judul']); ?></h5>
                                <p class="card-text text-muted small">
                                    <?php 
                                    $konten = strip_tags($b['konten']);
                                    echo strlen($konten) > 150 ? substr($konten, 0, 150) . '...' : $konten;
                                    ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> <?php echo formatTanggal($b['tanggal_post']); ?>
                                    </small>
                                    <a href="<?php echo BASE_URL; ?>post_berita.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        Baca Selengkapnya
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $eskul_filter ? '&eskul='.$eskul_filter : ''; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-newspaper text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <h4 class="mt-4 text-muted">Belum Ada Berita</h4>
                        <p class="text-muted">Belum ada berita untuk ekstrakurikuler yang kamu ikuti.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>