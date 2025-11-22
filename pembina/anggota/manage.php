<?php
// pembina/anggota/manage.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';

// Hanya pembina yang boleh mengakses halaman ini
only('pembina');
requireRole(['pembina']);

$page_title = 'Kelola Anggota';
$current_user = getCurrentUser();

// Ambil pembina_id dari session sesuai konfirmasi user
$pembina_id = isset($_SESSION['pembina_id']) ? intval($_SESSION['pembina_id']) : intval($current_user['id'] ?? 0);

// =========================================================
// === PROSES CRUD: APPROVE / REJECT / DELETE ===
// =========================================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    // Validasi: pastikan anggota tersebut terkait ekskul pembina ini
    $cek = query("
        SELECT ae.id
        FROM anggota_ekskul ae
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        WHERE ae.id = ? AND e.pembina_id = ?
    ", [$id, $pembina_id], "ii");

    if (!$cek || $cek->num_rows == 0) {
        setFlash('danger', 'Akses ditolak: anggota tidak ditemukan di ekskul Anda.');
        redirect('pembina/anggota/manage.php');
    }

    if ($action === 'approve') {
        query("UPDATE anggota_ekskul SET status = 'diterima', tanggal_diterima = CURDATE() WHERE id = ?", [$id], 'i');
        setFlash('success', 'Pendaftaran berhasil disetujui!');
    } elseif ($action === 'reject') {
        query("UPDATE anggota_ekskul SET status = 'ditolak' WHERE id = ?", [$id], 'i');
        setFlash('success', 'Pendaftaran berhasil ditolak!');
    } elseif ($action === 'delete') {
        query("DELETE FROM anggota_ekskul WHERE id = ?", [$id], 'i');
        setFlash('success', 'Anggota berhasil dihapus dari ekstrakurikuler.');
    }

    redirect('pembina/anggota/manage.php');
}

// =========================================================
// === PROSES POST: UPDATE NILAI (DARI MODAL) ===
// =========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_anggota'])) {
    $anggota_id = intval($_POST['anggota_id']);
    $nilai = trim($_POST['nilai'] ?? '');
    $catatan = trim($_POST['catatan_pembina'] ?? '');

    // Validasi lagi: pastikan anggota milik pembina
    $cek = query("
        SELECT ae.id
        FROM anggota_ekskul ae
        JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
        WHERE ae.id = ? AND e.pembina_id = ?
    ", [$anggota_id, $pembina_id], "ii");

    if (!$cek || $cek->num_rows == 0) {
        setFlash('danger', 'Akses ditolak: tidak bisa mengubah anggota ini.');
        redirect('pembina/anggota/manage.php');
    }

    // Update nilai dan catatan pembina
    query("UPDATE anggota_ekskul SET nilai = ?, catatan_pembina = ?, tanggal_penilaian = CURDATE() WHERE id = ?", [$nilai, $catatan, $anggota_id], "ssi");
    setFlash('success', 'Nilai / catatan anggota berhasil diperbarui.');
    redirect('pembina/anggota/manage.php');
}

// =========================================================
// === FILTER: hanya anggota pada ekskul yang dibina ===
// =========================================================
$where_clause = "WHERE e.pembina_id = ?";
$params = [$pembina_id];
$types = "i";

// Ambil data anggota (semua status) untuk pembina
$anggota = query("
    SELECT ae.*, u.name, u.nis, u.kelas, u.jenis_kelamin, u.no_hp, e.nama_ekskul, e.pembina_id
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    $where_clause
    ORDER BY ae.created_at DESC
", $params, $types);

// Hitung pending untuk badge
$count_pending_q = query("
    SELECT COUNT(*) AS total
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.status = 'pending' AND e.pembina_id = ?
", [$pembina_id], "i");
$count_pending = intval($count_pending_q->fetch_assoc()['total'] ?? 0);

// Hitung belum dinilai untuk badge (hanya anggota diterima tanpa nilai)
$belum_dinilai_q = query("
    SELECT COUNT(*) AS total
    FROM anggota_ekskul ae
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    WHERE ae.status = 'diterima' AND (ae.nilai IS NULL OR ae.nilai = '') AND e.pembina_id = ?
", [$pembina_id], "i");
$belum_dinilai = intval($belum_dinilai_q->fetch_assoc()['total'] ?? 0);

?>
<?php include __DIR__ . '/../../includes/berry_head.php'; ?>
<?php include __DIR__ . '/../../includes/berry_shell_open.php'; ?>

<div class="p-4">

    <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <h2 class="mb-4"><i class="bi bi-people-fill"></i> Kelola Anggota (Pembina)</h2>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                <i class="bi bi-clock"></i> Pending
                <?php if ($count_pending > 0): ?>
                    <span class="badge bg-warning ms-1"><?= $count_pending; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#diterima">
                <i class="bi bi-check-circle"></i> Diterima
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#ditolak">
                <i class="bi bi-x-circle"></i> Ditolak
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Pending -->
        <div class="tab-pane fade show active" id="pending">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-warning">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Eskul</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $anggota->data_seek(0);
                                $no = 1;
                                $found = false;
                                while ($row = $anggota->fetch_assoc()):
                                    if ($row['status'] === 'pending'):
                                        $found = true;
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nis']); ?></td>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= htmlspecialchars($row['kelas']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_ekskul']); ?></td>
                                    <td><?= htmlspecialchars(formatTanggal($row['tanggal_daftar'])); ?></td>
                                    <td>
                                        <a href="?action=approve&id=<?= $row['id']; ?>" class="btn btn-sm btn-success" title="Setujui" onclick="return confirm('Setujui pendaftaran ini?')">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        <a href="?action=reject&id=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pendaftaran ini?')" title="Tolak">
                                            <i class="bi bi-x-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                    endif;
                                endwhile;
                                if (!$found):
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada pendaftaran pending</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diterima -->
        <div class="tab-pane fade" id="diterima">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Eskul</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $anggota->data_seek(0);
                                $no_diterima = 1;
                                $found2 = false;
                                while ($row = $anggota->fetch_assoc()):
                                    if ($row['status'] === 'diterima'):
                                        $found2 = true;
                                ?>
                                <tr>
                                    <td><?= $no_diterima++; ?></td>
                                    <td><?= htmlspecialchars($row['nis']); ?></td>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= htmlspecialchars($row['kelas']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_ekskul']); ?></td>
                                    <td>
                                        <?php if (empty($row['nilai'])): ?>
                                            <span class="badge bg-danger">Belum Dinilai</span>
                                        <?php else: ?>
                                            <span class="fw-bold text-success"><?= htmlspecialchars($row['nilai']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success">Diterima</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                            data-bs-target="#editAnggotaModal"
                                            data-id="<?= $row['id']; ?>"
                                            data-name="<?= htmlspecialchars($row['name']); ?>"
                                            data-eskul="<?= htmlspecialchars($row['nama_ekskul']); ?>"
                                            data-nilai="<?= htmlspecialchars($row['nilai']); ?>"
                                            data-catatan="<?= htmlspecialchars($row['catatan_pembina'] ?? '') ?>"
                                            title="Edit Nilai/Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <a href="?action=delete&id=<?= $row['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin ingin menghapus keanggotaan <?= htmlspecialchars($row['name']); ?> dari <?= htmlspecialchars($row['nama_ekskul']); ?>?')"
                                            title="Hapus Keanggotaan">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                    endif;
                                endwhile;
                                if (!$found2):
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada anggota diterima</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ditolak -->
        <div class="tab-pane fade" id="ditolak">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-danger">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Eskul</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $anggota->data_seek(0);
                                $no = 1;
                                $found3 = false;
                                while ($row = $anggota->fetch_assoc()):
                                    if ($row['status'] === 'ditolak'):
                                        $found3 = true;
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nis']); ?></td>
                                    <td><?= htmlspecialchars($row['name']); ?></td>
                                    <td><?= htmlspecialchars($row['kelas']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_ekskul']); ?></td>
                                    <td><?= htmlspecialchars(formatTanggal($row['tanggal_daftar'])); ?></td>
                                    <td><span class="badge bg-danger">Ditolak</span></td>
                                </tr>
                                <?php
                                    endif;
                                endwhile;
                                if (!$found3):
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada pendaftaran ditolak</td>
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

<!-- Modal Edit Nilai / Catatan -->
<div class="modal fade" id="editAnggotaModal" tabindex="-1" aria-labelledby="editAnggotaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="editAnggotaModalLabel"><i class="bi bi-pencil-square"></i> Edit Nilai & Catatan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="anggota_id" id="anggotaIdInput">
          <input type="hidden" name="update_anggota" value="1">
          
          <div class="mb-3">
            <label class="form-label">Nama Anggota</label>
            <input type="text" class="form-control" id="anggotaName" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Ekstrakurikuler</label>
            <input type="text" class="form-control" id="anggotaEskul" readonly>
          </div>
          
          <div class="mb-3">
            <label for="inputNilai" class="form-label">Nilai Akhir / Keterangan</label>
            <input type="text" name="nilai" class="form-control" id="inputNilai" placeholder="Masukkan nilai (misal: A, B, C atau Baik, Cukup)">
            <small class="text-muted">Isi dengan nilai/keterangan prestasi anggota (misal: Sangat Baik, Cukup, dll.)</small>
          </div>

          <div class="mb-3">
            <label for="catatanPembina" class="form-label">Catatan Pembina</label>
            <textarea name="catatan_pembina" id="catatanPembina" class="form-control" rows="3" placeholder="Catatan untuk anggota (opsional)"></textarea>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-info">Simpan Perubahan</button>
        </div>
      </form>
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
        Apakah Anda yakin ingin menghapus Anggota ini? Aksi ini tidak dapat dibatalkan.
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
        let deleteLinks = document.querySelectorAll('a[href*="action=delete"]');
        deleteLinks.forEach(function(link) {
            link.removeAttribute('onclick'); // Hapus onclick lama
            link.addEventListener('click', function(e) {
                e.preventDefault();
                let deleteUrl = this.getAttribute('href');
                
                // Set URL di tombol Hapus Modal
                let deleteButton = document.getElementById('deleteButton');
                deleteButton.setAttribute('href', deleteUrl);
                
                // Tampilkan Modal
                let confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                confirmModal.show();
            });
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var editAnggotaModal = document.getElementById('editAnggotaModal');
    if (editAnggotaModal) {
        editAnggotaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var eskul = button.getAttribute('data-eskul');
            var nilai = button.getAttribute('data-nilai');
            var catatan = button.getAttribute('data-catatan');

            editAnggotaModal.querySelector('#anggotaIdInput').value = id;
            editAnggotaModal.querySelector('#anggotaName').value = name;
            editAnggotaModal.querySelector('#anggotaEskul').value = eskul;
            editAnggotaModal.querySelector('#inputNilai').value = nilai;
            editAnggotaModal.querySelector('#catatanPembina').value = catatan;
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>
