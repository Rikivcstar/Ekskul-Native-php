<?php
// admin/anggota/manage.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';
only('admin');
requireRole(['admin']);

$page_title = 'Kelola Anggota';
$current_user = getCurrentUser();

// =========================================================
// === PROSES CRUD: APPROVE / REJECT / DELETE / UPDATE ===
// =========================================================

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if ($action == 'approve') {
        $result = query("UPDATE anggota_ekskul SET status = 'diterima', tanggal_diterima = CURDATE() WHERE id = ?", [$id], 'i');
        if ($result['success']) {
            setFlash('success', 'Pendaftaran berhasil disetujui!');
        }
    } elseif ($action == 'reject') {
        $result = query("UPDATE anggota_ekskul SET status = 'ditolak' WHERE id = ?", [$id], 'i');
        if ($result['success']) {
            setFlash('success', 'Pendaftaran berhasil ditolak!');
        }
    } elseif ($action == 'delete') {
        // Hapus Anggota (Delete)
        $result = query("DELETE FROM anggota_ekskul WHERE id = ?", [$id], 'i');
        if ($result['success']) {
            setFlash('success', 'Anggota berhasil dihapus dari ekstrakurikuler.');
        } else {
            setFlash('danger', 'Gagal menghapus anggota.');
        }
    }
    redirect('admin/anggota/manage.php');
}

// Proses POST untuk UPDATE NILAI atau Status/Eskul (Opsional)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_anggota'])) {
    $anggota_id = $_POST['anggota_id'];
    $nilai = $_POST['nilai'] ?? null;
    $status = $_POST['status'] ?? null;

    // Tambahkan logika update sesuai kebutuhan Anda (misal: hanya update nilai)
    if ($nilai !== null) {
        $result = query("UPDATE anggota_ekskul SET nilai = ? WHERE id = ?", [$nilai, $anggota_id], 'si');
        if ($result['success']) {
            setFlash('success', 'Nilai anggota berhasil diperbarui.');
        } else {
            setFlash('danger', 'Gagal memperbarui nilai anggota.');
        }
    }
    // Jika Anda ingin mengizinkan perubahan status atau eskul via POST/Modal, tambahkan di sini.

    redirect('admin/anggota/manage.php');
}

// Filter untuk pembina (hanya lihat eskul sendiri)
$where_clause = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    $where_clause = "WHERE e.pembina_id = ?";
    $params = [$current_user['id']];
    $types = "i";
}

// Ambil data anggota
// Tambahkan kolom 'nilai' untuk ditampilkan/diedit
$anggota = query("
    SELECT ae.*, u.name, u.nis, u.kelas, u.jenis_kelamin, u.no_hp, e.nama_ekskul, e.pembina_id
    FROM anggota_ekskul ae
    JOIN users u ON ae.user_id = u.id
    JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id
    $where_clause
    ORDER BY ae.created_at DESC
", $params, $types);

// Statistik Penilaian untuk badge (Tetap)
$belum_dinilai = query("SELECT COUNT(*) as total FROM anggota_ekskul WHERE status = 'diterima' AND (nilai IS NULL OR nilai = '')")->fetch_assoc()['total']; 
// ... Kode HTML ...
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

    <h2 class="mb-4"><i class="bi bi-people-fill"></i> Kelola Anggota</h2>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                <i class="bi bi-clock"></i> Pending
                <?php 
                $count_sql = "SELECT COUNT(*) as total FROM anggota_ekskul ae JOIN ekstrakurikulers e ON ae.ekstrakurikuler_id = e.id WHERE ae.status = 'pending'";
                if ($current_user['role'] == 'pembina') {
                    $count_sql .= " AND e.pembina_id = " . $current_user['id'];
                }
                $count_pending = query($count_sql)->fetch_assoc()['total'];
                if ($count_pending > 0) echo "<span class='badge bg-warning ms-1'>$count_pending</span>";
                ?>
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
                                    if ($row['status'] == 'pending'):
                                        $found = true;
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['kelas']; ?></td>
                                    <td><?php echo $row['nama_ekskul']; ?></td>
                                    <td><?php echo formatTanggal($row['tanggal_daftar']); ?></td>
                                    <td>
                                        <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Setujui">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                        <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak pendaftaran ini?')" title="Tolak">
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
                                    <th>Nilai</th> <th>Status</th>
                                    <th>Aksi</th> </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $anggota->data_seek(0);
                                $no_diterima = 1; // Ubah $no menjadi $no_diterima
                                $found = false;
                                while ($row = $anggota->fetch_assoc()):
                                    if ($row['status'] == 'diterima'):
                                        $found = true;
                                ?>
                                <tr>
                                    <td><?php echo $no_diterima++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['kelas']; ?></td>
                                    <td><?php echo $row['nama_ekskul']; ?></td>
                                    <td>
                                        <?php 
                                        // Tampilkan nilai atau tombol edit jika nilai kosong
                                        if (empty($row['nilai'])) {
                                            echo '<span class="badge bg-danger">Belum Dinilai</span>';
                                        } else {
                                            echo '<span class="fw-bold text-success">' . htmlspecialchars($row['nilai']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="badge bg-success">Diterima</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                            data-bs-target="#editAnggotaModal" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                            data-eskul="<?php echo htmlspecialchars($row['nama_ekskul']); ?>" 
                                            data-nilai="<?php echo htmlspecialchars($row['nilai']); ?>"
                                            title="Edit Nilai/Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Yakin ingin menghapus keanggotaan <?php echo htmlspecialchars($row['name']); ?> dari <?php echo htmlspecialchars($row['nama_ekskul']); ?>?')" 
                                            title="Hapus Keanggotaan">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endif;
                                endwhile;
                                if (!$found):
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
                                $found = false;
                                while ($row = $anggota->fetch_assoc()):
                                    if ($row['status'] == 'ditolak'):
                                        $found = true;
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['kelas']; ?></td>
                                    <td><?php echo $row['nama_ekskul']; ?></td>
                                    <td><?php echo formatTanggal($row['tanggal_daftar']); ?></td>
                                    <td><span class="badge bg-danger">Ditolak</span></td>
                                </tr>
                                <?php 
                                    endif;
                                endwhile;
                                if (!$found):
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

<div class="modal fade" id="editAnggotaModal" tabindex="-1" aria-labelledby="editAnggotaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="editAnggotaModalLabel"><i class="bi bi-pencil-square"></i> Edit Anggota</h5>
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
            // Button that triggered the modal
            var button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var eskul = button.getAttribute('data-eskul');
            var nilai = button.getAttribute('data-nilai');
            
            // Update the modal's content.
            var modalIdInput = editAnggotaModal.querySelector('#anggotaIdInput');
            var modalName = editAnggotaModal.querySelector('#anggotaName');
            var modalEskul = editAnggotaModal.querySelector('#anggotaEskul');
            var modalNilai = editAnggotaModal.querySelector('#inputNilai');
            
            modalIdInput.value = id;
            modalName.value = name;
            modalEskul.value = eskul;
            modalNilai.value = nilai;
        });
    }
});
</script>
<?php include __DIR__ . '/../../includes/berry_shell_close.php'; ?>