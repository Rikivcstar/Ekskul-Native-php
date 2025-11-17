<?php
// admin/pengumuman/index.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Kelola Pengumuman';
$current_user = getCurrentUser();

// Hapus pengumuman
if (isset($_GET['delete'])) {
    execute("DELETE FROM pengumuman WHERE id = ?", [$_GET['delete']], 'i');
    setFlash('success', 'Pengumuman berhasil dihapus!');
    redirect('admin/pengumuman/index.php');
}

// Toggle status
if (isset($_GET['toggle'])) {
    execute("UPDATE pengumuman SET is_active = NOT is_active WHERE id = ?", [$_GET['toggle']], 'i');
    setFlash('success', 'Status pengumuman berhasil diupdate!');
    redirect('admin/pengumuman/index.php');
}

// Filter
$where = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where = "WHERE (p.user_id = ? OR e.pembina_id = ?)";
    $params = [$current_user['id'], $current_user['id']];
    $types = "ii";
}

// Ambil pengumuman
$pengumuman = query("
    SELECT p.*, e.nama_ekskul, u.name as pembuat
    FROM pengumuman p
    LEFT JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    LEFT JOIN users u ON p.user_id = u.id
    $where
    ORDER BY p.prioritas DESC, p.created_at DESC
", $params, $types);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-success sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="badge bg-light text-success me-2"><?php echo ucfirst($current_user['role']); ?></span>
                <span class="me-3">
                    <i class="bi bi-person-circle"></i> <?php echo $current_user['name']; ?>
                </span>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 bg-light p-0">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/eskul/index.php">
                            <i class="bi bi-grid-fill"></i> Ekstrakurikuler
                        </a>
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/berita/manage.php">
                            <i class="bi bi-newspaper"></i> Berita
                        </a>
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>admin/pengumuman/index.php">
                            <i class="bi bi-megaphone"></i> Pengumuman
                        </a>
                    </nav>
                </div>
            </div>

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
                    <h2><i class="bi bi-megaphone"></i> Kelola Pengumuman</h2>
                    <a href="<?php echo BASE_URL; ?>admin/pengumuman/tambah.php" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Tambah Pengumuman
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>No</th>
                                        <th>Judul</th>
                                        <th>Ekstrakurikuler</th>
                                        <th>Periode</th>
                                        <th>Prioritas</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($pengumuman && $pengumuman->num_rows > 0):
                                        $no = 1;
                                        $badge_priority = [
                                            'tinggi' => 'danger',
                                            'sedang' => 'warning',
                                            'rendah' => 'info'
                                        ];
                                        while ($row = $pengumuman->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo $row['judul']; ?></strong><br>
                                            <small class="text-muted"><?php echo substr($row['isi'], 0, 50); ?>...</small>
                                        </td>
                                        <td><?php echo $row['nama_ekskul'] ?? '<span class="badge bg-secondary">Umum</span>'; ?></td>
                                        <td>
                                            <small>
                                                <?php echo $row['tanggal_mulai'] ? date('d/m/Y', strtotime($row['tanggal_mulai'])) : '-'; ?><br>
                                                s/d <?php echo $row['tanggal_selesai'] ? date('d/m/Y', strtotime($row['tanggal_selesai'])) : '-'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge_priority[$row['prioritas']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($row['prioritas']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-<?php echo $row['is_active'] ? 'warning' : 'success'; ?>" title="Toggle Status">
                                                    <i class="bi bi-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>admin/pengumuman/tambah.php?edit=<?php echo $row['id']; ?>" class="btn btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirmDelete()" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-1"></i>
                                            <p class="mt-2">Belum ada pengumuman</p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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