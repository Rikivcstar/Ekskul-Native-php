<?php
// admin/galeri/index.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Kelola Galeri';
$current_user = getCurrentUser();

// Hapus galeri
if (isset($_GET['delete'])) {
    $galeri = query("SELECT gambar FROM galeris WHERE id = ?", [$_GET['delete']], 'i')->fetch_assoc();
    if ($galeri['gambar']) {
        deleteFile($galeri['gambar']);
    }
    execute("DELETE FROM galeris WHERE id = ?", [$_GET['delete']], 'i');
    setFlash('success', 'Foto berhasil dihapus!');
    redirect('admin/galeri/index.php');
}

// Toggle status
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $status = $_GET['status'] == '1' ? 0 : 1;
    execute("UPDATE galeris SET is_active = ? WHERE id = ?", [$status, $_GET['toggle']], 'ii');
    setFlash('success', 'Status berhasil diupdate!');
    redirect('admin/galeri/index.php');
}

// Filter
$eskul_filter = $_GET['eskul'] ?? '';

$where_clause = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where_clause = "WHERE e.pembina_id = ?";
    $params = [$current_user['id']];
    $types = "i";
    
    if ($eskul_filter) {
        $where_clause .= " AND g.ekstrakurikuler_id = ?";
        $params[] = $eskul_filter;
        $types .= "i";
    }
} elseif ($eskul_filter) {
    $where_clause = "WHERE g.ekstrakurikuler_id = ?";
    $params = [$eskul_filter];
    $types = "i";
}

// Ambil galeri
$galeri = query("
    SELECT g.*, e.nama_ekskul
    FROM galeris g
    JOIN ekstrakurikulers e ON g.ekstrakurikuler_id = e.id
    $where_clause
    ORDER BY g.tanggal_upload DESC, g.urutan ASC
", $params, $types);

// Ambil daftar eskul untuk filter
$where_eskul = "";
$params_eskul = [];
$types_eskul = "";

if ($current_user['role'] == 'pembina') {
    $where_eskul = "WHERE pembina_id = ?";
    $params_eskul = [$current_user['id']];
    $types_eskul = "i";
}

$eskul_list = query("SELECT id, nama_ekskul FROM ekstrakurikulers $where_eskul ORDER BY nama_ekskul", $params_eskul, $types_eskul);

// Statistik Penilaian untuk badge
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
<style>
  .gallery-card{ border:0; overflow:hidden; border-radius:12px; }
  .gallery-thumb{ position:relative; width:100%; padding-top:66.6667%; background:#f6f7fb; }
  .gallery-img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
  .gallery-card:hover .gallery-img{ transform:scale(1.06); }
  .gallery-overlay{ position:absolute; left:0; right:0; bottom:0; padding:12px; color:#fff; background:linear-gradient(to top, rgba(0,0,0,.6), rgba(0,0,0,0)); }
  .gallery-title{ margin:6px 0 0; font-size:.95rem; font-weight:600; line-height:1.2; }
  .gallery-actions{ position:absolute; top:10px; right:10px; display:flex; flex-direction:column; gap:8px; opacity:0; transform:translateY(-6px); transition:all .25s ease; z-index:2; }
  .gallery-card:hover .gallery-actions{ opacity:1; transform:translateY(0); }
  .gallery-actions .btn{ box-shadow:0 6px 18px rgba(0,0,0,.12); border:0; }
</style>
<div class="p-4">
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
        <h2><i class="bi bi-images"></i> Kelola Galeri</h2>
        <a href="<?php echo BASE_URL; ?>admin/galeri/upload.php" class="btn btn-success">
            <i class="bi bi-cloud-upload"></i> Upload Foto
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-10">
                        <select name="eskul" class="form-select">
                            <option value="">Semua Ekstrakurikuler</option>
                            <?php while ($eskul = $eskul_list->fetch_assoc()): ?>
                            <option value="<?php echo $eskul['id']; ?>" <?php echo $eskul_filter == $eskul['id'] ? 'selected' : ''; ?>>
                                <?php echo $eskul['nama_ekskul']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Galeri -->
    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
        <?php 
        if ($galeri && $galeri->num_rows > 0):
            while ($row = $galeri->fetch_assoc()):
        ?>
        <div class="col">
            <div class="card gallery-card shadow-sm">
                <div class="gallery-thumb">
                    <img class="gallery-img" src="<?php echo UPLOAD_URL . $row['gambar']; ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>">
                    <div class="gallery-actions">
                        <a href="?toggle=<?php echo $row['id']; ?>&status=<?php echo $row['is_active']; ?>" 
                           class="btn btn-sm btn-<?php echo $row['is_active'] ? 'success' : 'secondary'; ?>" 
                           title="<?php echo $row['is_active'] ? 'Aktif' : 'Nonaktif'; ?>">
                            <i class="bi bi-<?php echo $row['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirmDelete()" 
                           title="Hapus">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                    <div class="gallery-overlay">
                        <span class="badge bg-success mb-1"><?php echo $row['nama_ekskul']; ?></span>
                        <div class="gallery-title"><?php echo htmlspecialchars($row['judul']); ?></div>
                        <?php if ($row['deskripsi']): ?>
                          <small class="opacity-75"><?php echo substr($row['deskripsi'], 0, 50); ?>...</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="bi bi-image fs-1"></i>
                <p class="mt-3 mb-0">Belum ada foto di galeri</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>