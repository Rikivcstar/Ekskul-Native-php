<?php
// update_kegiatan.php
$page_title = 'Berita & Kegiatan';
require_once 'includes/header.php';

// Pagination
$limit = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter eskul
$filter_eskul = isset($_GET['eskul']) ? $_GET['eskul'] : '';

// Query berita
if ($filter_eskul) {
    $berita = query("
        SELECT b.*, e.nama_ekskul
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        WHERE b.ekstrakurikuler_id = ? AND b.is_published = 1
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ", [$filter_eskul, $limit, $offset], 'iii');
    
    $total = query("SELECT COUNT(*) as total FROM berita WHERE ekstrakurikuler_id = ? AND is_published = 1", [$filter_eskul], 'i')->fetch_assoc()['total'];
} else {
    $berita = query("
        SELECT b.*, e.nama_ekskul
        FROM berita b
        JOIN ekstrakurikulers e ON b.ekstrakurikuler_id = e.id
        WHERE b.is_published = 1
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ", [$limit, $offset], 'ii');
    
    $total = query("SELECT COUNT(*) as total FROM berita WHERE is_published = 1")->fetch_assoc()['total'];
}

$total_pages = ceil($total / $limit);

// Daftar eskul untuk filter
$eskul_list = query("SELECT id, nama_ekskul FROM ekstrakurikulers WHERE status = 'aktif' ORDER BY nama_ekskul");
?>

<div class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Berita & Kegiatan Ekstrakurikuler</h2>
            <p class="text-muted">Update terbaru dari berbagai kegiatan ekstrakurikuler</p>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-md-6 offset-md-3">
                <form method="GET" action="">
                    <div class="input-group">
                        <select name="eskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php while ($eskul = $eskul_list->fetch_assoc()): ?>
                            <option value="<?php echo $eskul['id']; ?>" <?php echo $filter_eskul == $eskul['id'] ? 'selected' : ''; ?>>
                                <?php echo $eskul['nama_ekskul']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <?php if ($filter_eskul): ?>
                        <a href="<?php echo BASE_URL; ?>update_kegiatan.php" class="btn btn-secondary">
                            <i class="bi bi-x"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Berita List -->
        <div class="row g-4">
            <?php 
            if ($berita && $berita->num_rows > 0):
                while ($row = $berita->fetch_assoc()):
            ?>
            <div class="col-md-4">
                <div class="card news-card h-100 border-0 shadow-sm">
                    <?php if ($row['gambar']): ?>
                    <img src="<?php echo UPLOAD_URL . $row['gambar']; ?>" class="card-img-top" alt="<?php echo $row['judul']; ?>">
                    <?php else: ?>
                    <img src="https://via.placeholder.com/400x250/20c997/ffffff?text=Berita" class="card-img-top">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <span class="badge bg-success mb-2"><?php echo $row['nama_ekskul']; ?></span>
                        <h5 class="card-title"><?php echo $row['judul']; ?></h5>
                        <p class="card-text text-muted">
                            <?php echo substr(strip_tags($row['konten']), 0, 150); ?>...
                        </p>
                        <div class="news-meta mb-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?php echo formatTanggal($row['tanggal_post']); ?>
                            </small>
                            <small class="text-muted ms-3">
                                <i class="bi bi-eye"></i> <?php echo $row['views']; ?> views
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="<?php echo BASE_URL; ?>post_berita.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-success btn-sm w-100">
                            Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle fs-1"></i>
                    <p class="mt-3 mb-0">Belum ada berita untuk ditampilkan.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="row mt-5">
            <div class="col-12">
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $filter_eskul ? '&eskul=' . $filter_eskul : ''; ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filter_eskul ? '&eskul=' . $filter_eskul : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $filter_eskul ? '&eskul=' . $filter_eskul : ''; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>