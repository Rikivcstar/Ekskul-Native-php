<?php
// admin/prestasi/index.php
require_once '../../config/database.php';
require_once __DIR__ . '/../../config/middleware.php';

// Memastikan hanya Admin atau Pembina yang bisa mengakses
only('pembina');
requireRole(['pembina']);

$page_title = 'Kelola Prestasi';
$current_user = getCurrentUser();

// Fungsi konfirmasi hapus (akan digunakan di frontend)
function confirmDelete() {
    return "return confirm('Apakah Anda yakin ingin menghapus prestasi ini? Aksi ini tidak dapat dibatalkan.');";
}

// Hapus prestasi
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Ambil data prestasi untuk verifikasi dan menghapus file sertifikat
    $prestasi_data = query("
        SELECT p.sertifikat, e.pembina_id, ? as user_role
        FROM prestasis p
        JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
        WHERE p.id = ?
    ", [$current_user['role'], $delete_id], 'si')->fetch_assoc();

    if ($prestasi_data) {
        // Logika otorisasi penghapusan:
        // Admin bisa menghapus semua, Pembina hanya bisa menghapus prestasi dari ekskul yang dibinanya.
        $is_authorized = false;
        if($current_user['role'] == 'pembina' && $prestasi_data['pembina_id'] == $current_user['id']) {
            $is_authorized = true;
        }
        
        if ($is_authorized) {
            if ($prestasi_data['sertifikat']) {
                deleteFile($prestasi_data['sertifikat']);
            }
            query("DELETE FROM prestasis WHERE id = ?", [$delete_id], 'i');
            setFlash('success', 'Prestasi berhasil dihapus!');
        } else {
            setFlash('error', 'Anda tidak memiliki izin untuk menghapus prestasi ini.');
        }
    } else {
        setFlash('error', 'Prestasi tidak ditemukan.');
    }
    
    redirect('pembina/prestasi/index.php');
}

// Filter untuk Pembina: hanya menampilkan prestasi ekskul yang dibinanya
$where_clause = "";
$params = [];
$types = "";

if ($current_user['role'] == 'pembina') {
    // Bergabung dengan ekstrakurikulers untuk mendapatkan pembina_id
    $where_clause = "WHERE e.pembina_id = ?";
    $params = [$current_user['id']];
    $types = "i";
}

// Ambil prestasi
$prestasi = query("
    SELECT p.*, e.nama_ekskul, u.name as nama_siswa, u.kelas, e.pembina_id
    FROM prestasis p
    JOIN ekstrakurikulers e ON p.ekstrakurikuler_id = e.id
    LEFT JOIN anggota_ekskul ae ON p.anggota_id = ae.id
    LEFT JOIN users u ON ae.user_id = u.id
    $where_clause
    ORDER BY p.tanggal DESC
", $params, $types);

// Statistik Penilaian dihilangkan karena tidak relevan di halaman ini

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
        <h2><i class="bi bi-trophy-fill"></i> Prestasi Ekstrakurikuler</h2>
        <a href="<?php echo BASE_URL; ?>pembina/prestasi/tambah.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Tambah Prestasi
        </a>
    </div>

    <div class="row">
        <?php 
        if ($prestasi && $prestasi->num_rows > 0):
            while ($row = $prestasi->fetch_assoc()):
                // Tentukan apakah user saat ini Pembina dan apakah dia Pembina yang membina ekskul ini
                $can_edit_delete = $current_user['role'] == 'admin' || 
                                   ($current_user['role'] == 'pembina' && $row['pembina_id'] == $current_user['id']);
                
                $badge_color = [
                    'internasional' => 'danger',
                    'nasional' => 'primary',
                    'provinsi' => 'success',
                    'kabupaten' => 'info',
                    'kecamatan' => 'warning',
                    'sekolah' => 'secondary'
                ];
        ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-<?php echo $badge_color[$row['tingkat']] ?? 'secondary'; ?>">
                            <?php echo ucfirst($row['tingkat']); ?>
                        </span>
                        <span class="badge bg-success"><?php echo $row['nama_ekskul']; ?></span>
                    </div>
                    
                    <h5 class="card-title"><?php echo $row['nama_prestasi']; ?></h5>
                    
                    <div class="mb-2">
                        <strong class="text-warning">
                            <i class="bi bi-award-fill"></i> <?php echo $row['peringkat'] ?? 'Peserta'; ?>
                        </strong>
                    </div>

                    <?php if ($row['nama_siswa']): ?>
                    <p class="mb-1">
                        <i class="bi bi-person"></i> <strong><?php echo $row['nama_siswa']; ?></strong> 
                        (<?php echo $row['kelas']; ?>)
                    </p>
                    <?php endif; ?>

                    <p class="mb-2">
                        <i class="bi bi-calendar"></i> <?php echo formatTanggal($row['tanggal']); ?>
                    </p>

                    <?php if ($row['penyelenggara']): ?>
                    <p class="mb-2">
                        <i class="bi bi-building"></i> <?php echo $row['penyelenggara']; ?>
                    </p>
                    <?php endif; ?>

                    <?php if ($row['deskripsi']): ?>
                    <p class="text-muted small">
                        <?php echo substr($row['deskripsi'], 0, 100); ?>...
                    </p>
                    <?php endif; ?>

                    <?php if ($row['sertifikat']): ?>
                    <a href="<?php echo UPLOAD_URL . $row['sertifikat']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-pdf"></i> Lihat Sertifikat
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($can_edit_delete): ?>
                <div class="card-footer bg-white">
                    <div class="btn-group w-100">
                        <a href="<?php echo BASE_URL; ?>pembina/prestasi/tambah.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="<?php echo confirmDelete(); ?>">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="bi bi-trophy fs-1"></i>
                <p class="mt-3 mb-0">Belum ada prestasi yang tercatat untuk ekskul yang Anda bina.</p>
            </div>
        </div>
        <?php endif; ?>
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
        Apakah Anda yakin ingin menghapus prestasi ini? Aksi ini tidak dapat dibatalkan.
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