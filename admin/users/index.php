<?php
// admin/users/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Kelola Users';
$current_user = getCurrentUser();

// Hapus user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $current_user['id']) {
        query("DELETE FROM users WHERE id = ?", [$id], 'i');
        setFlash('success', 'User berhasil dihapus!');
    } else {
        setFlash('danger', 'Tidak dapat menghapus akun sendiri!');
    }
    redirect('admin/users/index.php' . ($_GET['role'] ? '?role='.$_GET['role'] : ''));
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    query("UPDATE users SET is_active = NOT is_active WHERE id = ?", [$id], 'i');
    setFlash('success', 'Status user berhasil diupdate!');
    $redirect_params = [];
    if(isset($_GET['role'])) $redirect_params[] = 'role='.$_GET['role'];
    if(isset($_GET['page'])) $redirect_params[] = 'page='.$_GET['page'];
    if(isset($_GET['search'])) $redirect_params[] = 'search='.$_GET['search'];
    redirect('admin/users/index.php' . (count($redirect_params) > 0 ? '?'.implode('&', $redirect_params) : ''));
}

// Filter dan Search
$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = "";

if ($role_filter && $role_filter != 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR nis LIKE ? OR kelas LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$where = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Hitung total data
$count_query = "SELECT COUNT(*) as total FROM users $where";
$total_data = query($count_query, $params, $types)->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Query dengan limit dan offset
$sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$users = query($sql, $params, $types);

// Hitung jumlah per role untuk badge di tab
$count_admin = query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch_assoc()['total'];
$count_pembina = query("SELECT COUNT(*) as total FROM users WHERE role = 'pembina'")->fetch_assoc()['total'];
$count_siswa = query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'")->fetch_assoc()['total'];
$count_all = query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

// Statistik Penilaian untuk badge
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND nilai = ''")->fetch_assoc()['total'];
?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>
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
        <div>
            <h2><i class="bi bi-person-gear"></i> Kelola Users</h2>
            <p class="text-muted mb-0">
                Total: <?php echo $total_data; ?> users
                <?php if ($total_pages > 0): ?>
                | Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/users/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="" id="searchForm">
                <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                <div class="row align-items-center">
                    <div class="col-md-10">
                        <div class="position-relative">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama, email, NIS, atau kelas..." value="<?php echo htmlspecialchars($search); ?>">
                            <i class="bi bi-search position-absolute" style="right:12px;top:50%;transform:translateY(-50%);color:#6c757d;"></i>
                        </div>
                    </div>
                    <div class="col-md-2 mt-2 mt-md-0">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </div>
                <?php if ($search): ?>
                <div class="mt-2">
                    <a href="?role=<?php echo $role_filter; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Hapus Pencarian
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $role_filter == 'all' ? 'active' : ''; ?>" href="?role=all<?php echo $search ? '&search='.$search : ''; ?>">
                <i class="bi bi-people-fill"></i> Semua <span class="badge bg-secondary"><?php echo $count_all; ?></span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $role_filter == 'admin' ? 'active' : ''; ?>" href="?role=admin<?php echo $search ? '&search='.$search : ''; ?>">
                <i class="bi bi-shield-fill-check"></i> Admin <span class="badge bg-danger"><?php echo $count_admin; ?></span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $role_filter == 'pembina' ? 'active' : ''; ?>" href="?role=pembina<?php echo $search ? '&search='.$search : ''; ?>">
                <i class="bi bi-person-badge"></i> Pembina <span class="badge bg-primary"><?php echo $count_pembina; ?></span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?php echo $role_filter == 'siswa' ? 'active' : ''; ?>" href="?role=siswa<?php echo $search ? '&search='.$search : ''; ?>">
                <i class="bi bi-mortarboard-fill"></i> Siswa <span class="badge bg-info"><?php echo $count_siswa; ?></span>
            </a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-success">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nama</th>
                            <th width="20%">Email/NIS</th>
                            <th width="10%">Role</th>
                            <th width="10%">Kelas</th>
                            <th width="10%">Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($users && $users->num_rows > 0):
                            $no = $offset + 1;
                            while ($row = $users->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($row['foto']): ?>
                                    <img src="<?php echo BASE_URL . $row['foto']; ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;" alt="Foto">
                                    <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                        <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($row['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email'] ?? $row['nis']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['role'] == 'admin' ? 'danger' : ($row['role'] == 'pembina' ? 'primary' : 'info'); ?>"><?php echo ucfirst($row['role']); ?></span>
                            </td>
                            <td><?php echo $row['kelas'] ?? '-'; ?></td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="?toggle=<?php echo $row['id']; ?>&page=<?php echo $page; ?>&role=<?php echo $role_filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-<?php echo $row['is_active'] ? 'warning' : 'success'; ?>" title="Toggle Status">
                                        <i class="bi bi-<?php echo $row['is_active'] ? 'pause' : 'play'; ?>"></i>
                                    </a>
                                    <?php if ($row['id'] != $current_user['id']): ?>
                                    <a href="?delete=<?php echo $row['id']; ?>&page=<?php echo $page; ?>&role=<?php echo $role_filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p class="text-muted mt-2 mb-0">
                                    <?php if ($search): ?>Tidak ada hasil untuk pencarian "<?php echo htmlspecialchars($search); ?>"<?php else: ?>Tidak ada data user<?php endif; ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $role_filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    if ($page <= 3) { $end = min($total_pages, 5); }
                    if ($page > $total_pages - 3) { $start = max(1, $total_pages - 4); }
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1&role='.$role_filter.($search ? '&search='.urlencode($search) : '').'">1</a></li>';
                        if ($start > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                    }
                    for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor;
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                        echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&role='.$role_filter.($search ? '&search='.urlencode($search) : '').'">'.$total_pages.'</a></li>';
                    } ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $role_filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal konfirmasi dihapus (Pengganti alert/confirm) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin menghapus User ini? Aksi ini tidak dapat dibatalkan.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a id="deleteButton" href="#" class="btn btn-danger">Hapus</a>
      </div>
    </div>
  </div>
</div>

<script>
    // Menambahkan fungsi modal untuk konfirmasi hapus (menggantikan window.confirm)
    document.addEventListener('DOMContentLoaded', function() {
        var deleteLinks = document.querySelectorAll('a[href*="?delete="]');
        deleteLinks.forEach(function(link) {
            link.removeAttribute('onclick'); // Hapus onclick lama
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var deleteUrl = this.getAttribute('href');
                
                // Set URL di tombol Hapus Modal
                var deleteButton = document.getElementById('deleteButton');
                deleteButton.setAttribute('href', deleteUrl);
                
                // Tampilkan Modal
                var confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                confirmModal.show();
            });
        });
    });
</script>
<script>
  document.querySelector('input[name="search"]').addEventListener('keypress', function(e){
    if(e.key==='Enter'){ e.preventDefault(); document.getElementById('searchForm').submit(); }
  });
</script>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>