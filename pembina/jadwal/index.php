<?php
// pembina/jadwal/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('pembina'); // middleware pembina seharusnya sudah session_start()

// Ambil session pembina
$pembina_id = $_SESSION['user_id'] ?? null;

if (!$pembina_id) {
    setFlash('danger', 'Session pembina tidak ditemukan.');
    redirect('admin/login_admin.php');
    exit;
}

/* ============================
   Hapus Jadwal
============================ */
if (isset($_GET['delete'])) {
    $hapus_id = intval($_GET['delete']);

    // Cek apakah ini jadwal milik pembina
    $cek = query("
        SELECT j.id 
        FROM jadwal_latihans j
        JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
        WHERE j.id = ? AND e.pembina_id = ?
    ", [$hapus_id, $pembina_id], "ii");

    if ($cek && $cek->num_rows > 0) {
            query("DELETE FROM jadwal_latihans WHERE id = ?", [$hapus_id], 'i');
        setFlash('success', 'Jadwal berhasil dihapus!');
    } else {
        setFlash('danger', 'Anda tidak memiliki akses untuk menghapus jadwal ini.');
    }

    redirect('pembina/jadwal/index.php');
    exit;
}

/* ============================
   Ambil semua jadwal pembina
============================ */
$jadwal = query("
    SELECT j.*, e.nama_ekskul
    FROM jadwal_latihans j
    JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
    WHERE e.pembina_id = ?
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
", [$pembina_id], "i");

?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>

<div class="p-4">

    <!-- Flash Message -->
    <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-check"></i> Jadwal Latihan Ekskul Saya</h2>
        <a href="<?php echo BASE_URL; ?>pembina/jadwal/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Jadwal
        </a>
    </div>

    <!-- Tabel Jadwal -->
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
                        <?php if ($jadwal && $jadwal->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $jadwal->fetch_assoc()): ?>
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
                                            <a href="<?php echo BASE_URL; ?>pembina/jadwal/tambah.php?edit=<?php echo $row['id']; ?>" class="btn btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Hapus jadwal ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-calendar-x fs-1"></i>
                                    <p class="mt-2">Belum ada jadwal latihan untuk ekskul Anda.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>


    <!-- Jadwal Mingguan -->
    <div class="row mt-4">
        <h4 class="mb-3">Jadwal Mingguan</h4>

        <?php
        $hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

        foreach ($hari_list as $hari):

            $jadwal_hari = query("
                SELECT j.*, e.nama_ekskul
                FROM jadwal_latihans j
                JOIN ekstrakurikulers e ON j.ekstrakurikuler_id = e.id
                WHERE j.hari = ? 
                AND j.is_active = 1 
                AND e.pembina_id = ?
                ORDER BY j.jam_mulai
            ", [$hari, $pembina_id], "si");
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
                                    <i class="bi bi-clock"></i>
                                    <?php echo substr($jh['jam_mulai'], 0, 5) . " - " . substr($jh['jam_selesai'], 0, 5); ?><br>
                                    <i class="bi bi-geo-alt"></i> <?php echo $jh['lokasi']; ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Tidak ada jadwal.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
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
        Apakah Anda yakin ingin menghapus Jadwal ini? Aksi ini tidak dapat dibatalkan.
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

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
