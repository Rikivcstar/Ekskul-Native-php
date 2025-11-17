<?php
// admin/jadwal/index.php
require_once '../../config/database.php';
requireRole(['admin', 'pembina']);

$page_title = 'Kelola Jadwal';
$current_user = getCurrentUser();

// Hapus jadwal
if (isset($_GET['delete'])) {
    execute("DELETE FROM jadwal_latihans WHERE id = ?", [$_GET['delete']], 'i');
    setFlash('success', 'Jadwal berhasil dihapus!');
    redirect('admin/jadwal/index.php');
}

// Filter untuk pembina
$where_clause = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where_clause = "WHERE e.pembina_id = ?";
    $params = [$current_user['id']];
    $types = "i";
}

// Ambil jadwal
$jadwal = query("
    SELECT j.*, e.nama_ekskul, e.pembina_id
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    $where_clause
    ORDER BY 
        CASE j.hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
            WHEN 'Minggu' THEN 7
        END,
        j.jam_mulai
", $params, $types);

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
        <h2><i class="bi bi-calendar-check"></i> Kelola Jadwal Latihan</h2>
        <a href="<?php echo BASE_URL; ?>admin/jadwal/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Jadwal
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>No</th>
                            <th>Ekstrakurikuler</th>
                            <th>Hari</th>
                            <th>Waktu</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($jadwal && $jadwal->num_rows > 0):
                            $no = 1;
                            while ($row = $jadwal->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $row['nama_ekskul']; ?></td>
                            <td><strong><?php echo $row['hari']; ?></strong></td>
                            <td><?php echo substr($row['jam_mulai'], 0, 5) . ' - ' . substr($row['jam_selesai'], 0, 5); ?></td>
                            <td><?php echo $row['lokasi']; ?></td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo BASE_URL; ?>admin/jadwal/tambah.php?edit=<?php echo $row['id']; ?>" class="btn btn-warning" title="Edit">
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
                                <i class="bi bi-calendar-x fs-1"></i>
                                <p class="mt-2">Belum ada jadwal latihan</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Jadwal Per Hari -->
    <div class="row mt-4">
        <div class="col-12">
            <h4 class="mb-3">Jadwal Mingguan</h4>
        </div>
        <?php
        $hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ($hari_list as $hari):
            $jadwal_hari = query("
                SELECT j.*, e.nama_ekskul
                FROM jadwal_latihans j
                JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
                WHERE j.hari = ? AND j.is_active = 1
                ORDER BY j.jam_mulai
            ", [$hari], 's');
        ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <strong><?php echo $hari; ?></strong>
                </div>
                <div class="card-body">
                    <?php if ($jadwal_hari && $jadwal_hari->num_rows > 0): ?>
                        <?php while ($jh = $jadwal_hari->fetch_assoc()): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?php echo $jh['nama_ekskul']; ?></strong><br>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?php echo substr($jh['jam_mulai'], 0, 5) . ' - ' . substr($jh['jam_selesai'], 0, 5); ?><br>
                                <i class="bi bi-geo-alt"></i> <?php echo $jh['lokasi']; ?>
                            </small>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Tidak ada jadwal</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>